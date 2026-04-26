<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/csrf.php';
require_once __DIR__ . '/../../../src/helpers.php';

require_login();
$user = current_user();

// Fetch open demo trades
$openTrades = [];
try {
    $st = db()->prepare(
        'SELECT * FROM demo_trades WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT 10'
    );
    $st->execute([$user['id'], 'open']);
    $openTrades = $st->fetchAll();
} catch (Throwable) {}

// Fetch active user plan
$activePlan = null;
try {
    $st = db()->prepare(
        'SELECT up.*, ip.name AS plan_name, ip.roi_percent
         FROM user_plans up
         JOIN investment_plans ip ON ip.id = up.plan_id
         WHERE up.user_id = ? AND up.status = ?
         ORDER BY up.created_at DESC LIMIT 1'
    );
    $st->execute([$user['id'], 'active']);
    $activePlan = $st->fetch() ?: null;
} catch (Throwable) {}

// Today's PnL from closed trades
$todayPnl = 0.0;
try {
    $st = db()->prepare(
        "SELECT COALESCE(SUM(pnl),0) AS total_pnl
         FROM demo_trades
         WHERE user_id = ? AND status = 'closed' AND DATE(closed_at) = CURDATE()"
    );
    $st->execute([$user['id']]);
    $row = $st->fetch();
    $todayPnl = (float)($row['total_pnl'] ?? 0);
} catch (Throwable) {}

// Prices
$watchSymbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT'];
$prices = [];
foreach ($watchSymbols as $sym) {
    $prices[$sym] = price_for_symbol($sym);
}

$btcPrice  = $prices['BTCUSDT'];
$ethPrice  = $prices['ETHUSDT'];
$balance   = (float)$user['balance'];

// Unrealized PnL from open trades
$unrealizedPnl = 0.0;
$marginUsed    = 0.0;
foreach ($openTrades as $trade) {
    $cp = price_for_symbol($trade['symbol']);
    $pnl = $trade['side'] === 'buy'
        ? ($cp - (float)$trade['price_open']) * (float)$trade['qty']
        : ((float)$trade['price_open'] - $cp)  * (float)$trade['qty'];
    $unrealizedPnl += $pnl;
    $marginUsed    += (float)$trade['price_open'] * (float)$trade['qty'];
}

// Portfolio metrics
$equity     = $balance + $unrealizedPnl;
$freeMargin = max(0.0, $equity - $marginUsed);
$displayPnl = $todayPnl + $unrealizedPnl;

// Crypto asset balances (split: assume 40% BTC, 20% ETH, 40% USDT of total)
$usdtBalance = round($balance * 0.40, 2);
$btcBalance  = round(($balance * 0.40) / max(1, $btcPrice), 8);
$ethBalance  = round(($balance * 0.20) / max(1, $ethPrice), 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
    .live-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
    .glass { background: rgba(255,255,255,.04); backdrop-filter: blur(12px); }
    .card-glow { box-shadow: 0 0 0 1px rgba(16,185,129,.15), 0 8px 32px rgba(0,0,0,.4); }
    .pnl-pos { color: #10b981; }
    .pnl-neg { color: #ef4444; }
    .ticker-scroll { animation: ticker 20s linear infinite; }
    @keyframes ticker { from{transform:translateX(0)} to{transform:translateX(-50%)} }
  </style>
</head>
<body class="bg-[#0b0e1a] text-white min-h-screen pb-24 font-sans antialiased">

  <!-- Top Bar -->
  <header class="sticky top-0 z-40 bg-[#0d1120]/95 backdrop-blur border-b border-white/5 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 bg-emerald-500 rounded-lg flex items-center justify-center">
        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
      </div>
      <span class="text-base font-extrabold tracking-tight text-white">3Commas</span>
    </div>
    <div class="flex items-center gap-3">
      <div class="flex items-center gap-1.5">
        <span class="live-dot w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
        <span class="text-emerald-400 text-xs font-medium">Live</span>
      </div>
      <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-emerald-500 flex items-center justify-center text-xs font-bold">
        <?= strtoupper(substr(sanitize($user['name']), 0, 1)) ?>
      </div>
    </div>
  </header>

  <!-- Ticker Bar -->
  <div class="bg-[#111827] border-b border-white/5 overflow-hidden py-1.5">
    <div class="flex gap-8 ticker-scroll whitespace-nowrap text-xs" id="tickerBar">
      <?php foreach ($prices as $sym => $p): ?>
      <?php $base = str_replace('USDT','', $sym); ?>
      <span class="text-slate-400"><span class="text-white font-semibold"><?= $base ?>/USDT</span> <span class="text-emerald-400" data-ticker="<?= $sym ?>">$<?= number_format($p, 2) ?></span></span>
      <?php endforeach; ?>
      <?php foreach ($prices as $sym => $p): ?>
      <?php $base = str_replace('USDT','', $sym); ?>
      <span class="text-slate-400"><span class="text-white font-semibold"><?= $base ?>/USDT</span> <span class="text-emerald-400">$<?= number_format($p, 2) ?></span></span>
      <?php endforeach; ?>
    </div>
  </div>

  <main class="max-w-lg mx-auto px-4 py-5 space-y-4">

    <!-- Hero Balance Card -->
    <div class="relative overflow-hidden rounded-2xl card-glow"
         style="background: linear-gradient(135deg,#0f2027 0%,#1a3a4a 50%,#0d2d3a 100%);">
      <div class="absolute inset-0 opacity-20"
           style="background: radial-gradient(ellipse at top right,#10b981 0%,transparent 60%);"></div>
      <div class="relative p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-slate-400 text-xs font-medium uppercase tracking-widest">Total Portfolio</p>
          <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full font-medium">USDT</span>
        </div>
        <p class="text-4xl font-black text-white tracking-tight mb-0.5" id="heroBalance">
          $<?= number_format($balance, 2) ?>
        </p>
        <p class="text-sm <?= $displayPnl >= 0 ? 'text-emerald-400' : 'text-red-400' ?> font-semibold mb-4">
          <?= $displayPnl >= 0 ? '▲' : '▼' ?> $<?= number_format(abs($displayPnl), 2) ?> today
        </p>

        <!-- Stats Row -->
        <div class="grid grid-cols-4 gap-2 mb-4">
          <div class="bg-white/5 rounded-xl p-2.5 text-center">
            <p class="text-slate-500 text-[10px] uppercase tracking-wide mb-0.5">PnL</p>
            <p class="text-xs font-bold <?= $displayPnl >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
              <?= $displayPnl >= 0 ? '+' : '' ?>$<?= number_format($displayPnl, 2) ?>
            </p>
          </div>
          <div class="bg-white/5 rounded-xl p-2.5 text-center">
            <p class="text-slate-500 text-[10px] uppercase tracking-wide mb-0.5">Equity</p>
            <p class="text-xs font-bold text-white">$<?= number_format($equity, 2) ?></p>
          </div>
          <div class="bg-white/5 rounded-xl p-2.5 text-center">
            <p class="text-slate-500 text-[10px] uppercase tracking-wide mb-0.5">Margin</p>
            <p class="text-xs font-bold text-yellow-400">$<?= number_format($marginUsed, 2) ?></p>
          </div>
          <div class="bg-white/5 rounded-xl p-2.5 text-center">
            <p class="text-slate-500 text-[10px] uppercase tracking-wide mb-0.5">Free</p>
            <p class="text-xs font-bold text-sky-400">$<?= number_format($freeMargin, 2) ?></p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2.5">
          <a href="wallet.php#deposit"
             class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-bold py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            Deposit
          </a>
          <a href="wallet.php#withdraw"
             class="flex-1 flex items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-bold py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            Withdraw
          </a>
          <a href="trading.php"
             class="flex-1 flex items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-bold py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Trade
          </a>
        </div>
      </div>
    </div>

    <!-- Asset Balances -->
    <div class="bg-[#111827] rounded-2xl overflow-hidden border border-white/5">
      <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="font-bold text-sm text-white">Asset Balances</h2>
        <a href="wallet.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">Manage →</a>
      </div>
      <div class="divide-y divide-white/5">
        <!-- BTC -->
        <div class="flex items-center justify-between px-4 py-3.5">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black"
                 style="background:linear-gradient(135deg,#f7931a,#ff6b00)">₿</div>
            <div>
              <p class="font-semibold text-sm text-white">Bitcoin</p>
              <p class="text-slate-500 text-xs">BTC · <span id="btcPrice">$<?= number_format($btcPrice, 2) ?></span></p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm text-white"><?= number_format($btcBalance, 6) ?> BTC</p>
            <p class="text-slate-400 text-xs">≈ $<?= number_format($btcBalance * $btcPrice, 2) ?></p>
          </div>
        </div>
        <!-- ETH -->
        <div class="flex items-center justify-between px-4 py-3.5">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black"
                 style="background:linear-gradient(135deg,#627eea,#3a5bd9)">Ξ</div>
            <div>
              <p class="font-semibold text-sm text-white">Ethereum</p>
              <p class="text-slate-500 text-xs">ETH · <span id="ethPrice">$<?= number_format($ethPrice, 2) ?></span></p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm text-white"><?= number_format($ethBalance, 5) ?> ETH</p>
            <p class="text-slate-400 text-xs">≈ $<?= number_format($ethBalance * $ethPrice, 2) ?></p>
          </div>
        </div>
        <!-- USDT -->
        <div class="flex items-center justify-between px-4 py-3.5">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-emerald-500/20 flex items-center justify-center text-xs font-black text-emerald-400">₮</div>
            <div>
              <p class="font-semibold text-sm text-white">Tether</p>
              <p class="text-slate-500 text-xs">USDT · $1.00</p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm text-white"><?= number_format($usdtBalance, 2) ?> USDT</p>
            <p class="text-slate-400 text-xs">≈ $<?= number_format($usdtBalance, 2) ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Swap Widget -->
    <div class="bg-[#111827] rounded-2xl border border-white/5">
      <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
          <h2 class="font-bold text-sm text-white">Quick Swap</h2>
        </div>
        <span class="text-xs text-slate-500">Real-time rates</span>
      </div>
      <div class="p-4 space-y-3">
        <!-- From -->
        <div class="bg-[#0d1120] rounded-xl p-3.5">
          <div class="flex items-center justify-between mb-2">
            <span class="text-slate-500 text-xs">From</span>
            <span class="text-slate-500 text-xs">Balance: <span id="swapFromBal">—</span></span>
          </div>
          <div class="flex items-center gap-3">
            <input id="swapAmountIn" type="number" placeholder="0.00" min="0" step="any"
              class="flex-1 bg-transparent text-white text-xl font-bold outline-none placeholder-slate-700 w-0">
            <select id="swapFrom"
              class="bg-slate-700 text-white text-sm font-semibold rounded-xl px-3 py-2 outline-none border border-white/10 cursor-pointer">
              <option value="USDT">USDT</option>
              <option value="BTC">BTC</option>
              <option value="ETH">ETH</option>
              <option value="BNB">BNB</option>
              <option value="SOL">SOL</option>
            </select>
          </div>
        </div>

        <!-- Swap Arrow -->
        <div class="flex justify-center">
          <button id="swapFlip" class="w-9 h-9 bg-indigo-500/20 hover:bg-indigo-500/40 rounded-full flex items-center justify-center transition" title="Flip">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
          </button>
        </div>

        <!-- To -->
        <div class="bg-[#0d1120] rounded-xl p-3.5">
          <div class="flex items-center justify-between mb-2">
            <span class="text-slate-500 text-xs">To</span>
            <span class="text-slate-500 text-xs" id="swapRate">Rate: —</span>
          </div>
          <div class="flex items-center gap-3">
            <input id="swapAmountOut" type="text" placeholder="0.00" readonly
              class="flex-1 bg-transparent text-emerald-400 text-xl font-bold outline-none placeholder-slate-700 w-0 cursor-default">
            <select id="swapTo"
              class="bg-slate-700 text-white text-sm font-semibold rounded-xl px-3 py-2 outline-none border border-white/10 cursor-pointer">
              <option value="BTC">BTC</option>
              <option value="ETH">ETH</option>
              <option value="USDT">USDT</option>
              <option value="BNB">BNB</option>
              <option value="SOL">SOL</option>
            </select>
          </div>
        </div>

        <div id="swapInfo" class="text-xs text-slate-500 text-center hidden">
          1 <span id="swapFromLabel">—</span> ≈ <span id="swapRateVal">—</span> <span id="swapToLabel">—</span>
        </div>

        <button id="swapBtn"
          class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold py-3 rounded-xl transition text-sm">
          Preview Swap
        </button>
        <p class="text-center text-slate-600 text-xs">Swap executes at market price via demo account.</p>
      </div>
    </div>

    <!-- Active Investment Plan -->
    <?php if ($activePlan): ?>
    <div class="bg-[#111827] border border-emerald-500/20 rounded-2xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-sm text-white">Active Plan</h2>
        <span class="bg-emerald-500/20 text-emerald-400 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
      </div>
      <p class="text-emerald-400 font-semibold text-sm"><?= sanitize($activePlan['plan_name']) ?></p>
      <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
        <div class="bg-white/5 rounded-xl p-2.5">
          <p class="text-slate-500 mb-0.5">Amount</p>
          <p class="font-bold text-white">$<?= format_currency((float)$activePlan['amount']) ?></p>
        </div>
        <div class="bg-white/5 rounded-xl p-2.5">
          <p class="text-slate-500 mb-0.5">ROI</p>
          <p class="font-bold text-emerald-400"><?= format_currency((float)$activePlan['roi_percent']) ?>%</p>
        </div>
        <div class="bg-white/5 rounded-xl p-2.5">
          <p class="text-slate-500 mb-0.5">Ends</p>
          <p class="font-bold text-white"><?= sanitize($activePlan['end_date']) ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Market Watchlist -->
    <div class="bg-[#111827] rounded-2xl border border-white/5">
      <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="font-bold text-sm text-white">Markets</h2>
        <a href="markets.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">All markets →</a>
      </div>
      <div class="divide-y divide-white/5">
        <?php foreach ($watchSymbols as $sym): ?>
        <?php $base = str_replace('USDT', '', $sym); ?>
        <div class="flex items-center justify-between px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-white"><?= $base[0] ?></div>
            <div>
              <p class="font-semibold text-sm text-white"><?= $base ?></p>
              <p class="text-slate-500 text-xs"><?= $sym ?></p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm text-white" data-market="<?= $sym ?>">$<?= number_format($prices[$sym], 2) ?></p>
            <p class="text-emerald-400 text-xs">↑ live</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Open Positions -->
    <?php if (!empty($openTrades)): ?>
    <div class="bg-[#111827] rounded-2xl border border-white/5">
      <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="font-bold text-sm text-white">Open Positions</h2>
        <a href="trading.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">Trade →</a>
      </div>
      <div class="divide-y divide-white/5">
        <?php foreach ($openTrades as $trade): ?>
        <?php
          $curPrice = price_for_symbol($trade['symbol']);
          $pnl = $trade['side'] === 'buy'
              ? ($curPrice - (float)$trade['price_open']) * (float)$trade['qty']
              : ((float)$trade['price_open'] - $curPrice) * (float)$trade['qty'];
          $pnlClass = $pnl >= 0 ? 'text-emerald-400' : 'text-red-400';
        ?>
        <div class="flex items-center justify-between px-4 py-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-white font-semibold text-sm"><?= sanitize($trade['symbol']) ?></span>
              <span class="text-xs px-1.5 py-0.5 rounded font-medium <?= $trade['side']==='buy' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                <?= strtoupper($trade['side']) ?>
              </span>
            </div>
            <p class="text-slate-500 text-xs mt-0.5">Entry: $<?= number_format((float)$trade['price_open'], 2) ?> · Qty: <?= (float)$trade['qty'] ?></p>
          </div>
          <div class="text-right">
            <p class="font-bold text-sm <?= $pnlClass ?>"><?= $pnl >= 0 ? '+' : '' ?>$<?= format_currency($pnl) ?></p>
            <p class="text-slate-500 text-xs">Now: $<?= number_format($curPrice, 2) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-[#0d1120]/95 backdrop-blur border-t border-white/5 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1 py-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="markets.php" class="flex flex-col items-center text-xs text-slate-500 hover:text-emerald-400 transition gap-1 py-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      Markets
    </a>
    <a href="trading.php" class="flex flex-col items-center text-xs text-slate-500 hover:text-emerald-400 transition gap-1 py-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      Trade
    </a>
    <a href="wallet.php" class="flex flex-col items-center text-xs text-slate-500 hover:text-emerald-400 transition gap-1 py-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="profile.php" class="flex flex-col items-center text-xs text-slate-500 hover:text-emerald-400 transition gap-1 py-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

  <script>
  (function () {
    // ── Live price store ──────────────────────────────────────────────
    const livePrices = {
      BTCUSDT: <?= $btcPrice ?>,
      ETHUSDT: <?= $ethPrice ?>,
      SOLUSDT: <?= $prices['SOLUSDT'] ?>,
      BNBUSDT: <?= $prices['BNBUSDT'] ?>
    };

    function fmt(n, d = 2) {
      return parseFloat(n).toLocaleString('en-US', {minimumFractionDigits: d, maximumFractionDigits: d});
    }

    // Fetch all prices in one request
    function refreshPrices() {
      const symbols = Object.keys(livePrices);
      Promise.all(
        symbols.map(s =>
          fetch('https://api.binance.com/api/v3/ticker/price?symbol=' + s)
            .then(r => r.json())
            .catch(() => null)
        )
      ).then(results => {
        results.forEach((d, i) => {
          if (d && d.price) {
            livePrices[symbols[i]] = parseFloat(d.price);
          }
        });
        updateUI();
      });
    }

    function updateUI() {
      // Market list
      document.querySelectorAll('[data-market]').forEach(el => {
        const sym = el.dataset.market;
        if (livePrices[sym]) el.textContent = '$' + fmt(livePrices[sym]);
      });

      // Ticker bar
      document.querySelectorAll('[data-ticker]').forEach(el => {
        const sym = el.dataset.ticker;
        if (livePrices[sym]) el.textContent = '$' + fmt(livePrices[sym]);
      });

      // Asset price labels
      if (livePrices.BTCUSDT) {
        document.getElementById('btcPrice').textContent = '$' + fmt(livePrices.BTCUSDT);
      }
      if (livePrices.ETHUSDT) {
        document.getElementById('ethPrice').textContent = '$' + fmt(livePrices.ETHUSDT);
      }

      // Re-calculate swap
      calcSwap();
    }

    // ── Swap Widget ───────────────────────────────────────────────────
    const swapFrom   = document.getElementById('swapFrom');
    const swapTo     = document.getElementById('swapTo');
    const amtIn      = document.getElementById('swapAmountIn');
    const amtOut     = document.getElementById('swapAmountOut');
    const swapRate   = document.getElementById('swapRate');
    const swapFromLbl = document.getElementById('swapFromLabel');
    const swapToLbl  = document.getElementById('swapToLabel');
    const swapRateVal = document.getElementById('swapRateVal');
    const swapInfo   = document.getElementById('swapInfo');
    const swapFromBal = document.getElementById('swapFromBal');
    const swapBtn    = document.getElementById('swapBtn');

    // Map tickers to USDT prices
    function getUsdPrice(ticker) {
      const map = {
        USDT: 1,
        BTC: livePrices.BTCUSDT,
        ETH: livePrices.ETHUSDT,
        SOL: livePrices.SOLUSDT,
        BNB: livePrices.BNBUSDT
      };
      return map[ticker] ?? 1;
    }

    function calcSwap() {
      const from = swapFrom.value;
      const to   = swapTo.value;
      const qty  = parseFloat(amtIn.value);

      const fromP = getUsdPrice(from);
      const toP   = getUsdPrice(to);

      if (toP > 0 && fromP > 0) {
        const rate = fromP / toP;
        swapRateVal.textContent = fmt(rate, 6);
        swapFromLbl.textContent = from;
        swapToLbl.textContent   = to;
        swapRate.textContent    = 'Rate: 1 ' + from + ' ≈ ' + fmt(rate, 6) + ' ' + to;
        swapInfo.classList.remove('hidden');

        if (!isNaN(qty) && qty > 0) {
          amtOut.value = fmt(qty * rate, 8);
        } else {
          amtOut.value = '';
        }
      }

      // Show balance hint
      const balMap = {
        USDT: <?= $usdtBalance ?>,
        BTC:  <?= $btcBalance ?>,
        ETH:  <?= $ethBalance ?>,
        SOL:  0,
        BNB:  0
      };
      swapFromBal.textContent = (balMap[from] ?? 0) + ' ' + from;
    }

    // Flip button
    document.getElementById('swapFlip').addEventListener('click', () => {
      const tmp = swapFrom.value;
      swapFrom.value = swapTo.value;
      swapTo.value   = tmp;
      calcSwap();
    });

    swapFrom.addEventListener('change', calcSwap);
    swapTo.addEventListener('change', calcSwap);
    amtIn.addEventListener('input', calcSwap);

    swapBtn.addEventListener('click', () => {
      const qty = parseFloat(amtIn.value);
      if (!qty || qty <= 0) { alert('Enter a valid amount to swap.'); return; }
      const from = swapFrom.value;
      const to   = swapTo.value;
      if (from === to) { alert('Select different assets to swap.'); return; }
      const out = amtOut.value;
      alert('Demo Swap: ' + qty + ' ' + from + ' → ' + out + ' ' + to + '\n\nThis is a simulated preview. No real funds are moved.');
    });

    // Init
    calcSwap();

    // Refresh prices every 10 seconds
    refreshPrices();
    setInterval(refreshPrices, 10000);
  })();
  </script>

</body>
</html>
