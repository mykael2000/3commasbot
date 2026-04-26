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

// Prices for watchlist
$watchSymbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT'];
$prices = [];
foreach ($watchSymbols as $sym) {
    $prices[$sym] = price_for_symbol($sym);
}

$balance = number_format((float)$user['balance'], 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <!-- Top bar -->
  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">3Commas</span>
    <div class="flex items-center gap-3">
      <span class="text-slate-400 text-sm">Hi, <?= sanitize($user['name']) ?></span>
      <a href="../logout.php" class="text-slate-400 hover:text-red-400 transition text-xs">Logout</a>
    </div>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-emerald-600 to-slate-800 rounded-2xl p-6 shadow-xl">
      <p class="text-emerald-200 text-sm mb-1">Total Balance</p>
      <p class="text-4xl font-extrabold text-white mb-1">$<?= $balance ?></p>
      <p class="text-emerald-300 text-sm">USDT</p>
      <div class="mt-4 flex gap-3">
        <a href="wallet.php#deposit"
           class="flex-1 bg-white/10 hover:bg-white/20 text-white text-center py-2.5 rounded-xl text-sm font-semibold transition">
          ↓ Deposit
        </a>
        <a href="wallet.php#withdraw"
           class="flex-1 bg-white/10 hover:bg-white/20 text-white text-center py-2.5 rounded-xl text-sm font-semibold transition">
          ↑ Withdraw
        </a>
      </div>
    </div>

    <!-- Active Investment Plan -->
    <?php if ($activePlan): ?>
    <div class="bg-slate-800 border border-emerald-500/30 rounded-2xl p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-white">Active Plan</h2>
        <span class="bg-emerald-500/20 text-emerald-400 text-xs px-2 py-1 rounded-full">Active</span>
      </div>
      <p class="text-emerald-400 font-semibold"><?= sanitize($activePlan['plan_name']) ?></p>
      <div class="mt-3 grid grid-cols-3 gap-2 text-center text-sm">
        <div>
          <p class="text-slate-400">Amount</p>
          <p class="font-bold text-white">$<?= format_currency((float)$activePlan['amount']) ?></p>
        </div>
        <div>
          <p class="text-slate-400">ROI</p>
          <p class="font-bold text-emerald-400"><?= format_currency((float)$activePlan['roi_percent']) ?>%</p>
        </div>
        <div>
          <p class="text-slate-400">End Date</p>
          <p class="font-bold text-white"><?= sanitize($activePlan['end_date']) ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Market Watchlist -->
    <div class="bg-slate-800 rounded-2xl p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-white">Markets</h2>
        <a href="markets.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">See all →</a>
      </div>
      <div class="space-y-3">
        <?php foreach ($watchSymbols as $sym): ?>
        <?php $base = str_replace('USDT', '', $sym); ?>
        <div class="flex items-center justify-between py-2 border-b border-slate-700 last:border-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-slate-700 rounded-full flex items-center justify-center text-xs font-bold text-white"><?= $base[0] ?></div>
            <div>
              <p class="font-semibold text-white text-sm"><?= $base ?></p>
              <p class="text-slate-400 text-xs"><?= $sym ?></p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold text-white text-sm">$<?= number_format($prices[$sym], 2) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Open Demo Positions -->
    <?php if (!empty($openTrades)): ?>
    <div class="bg-slate-800 rounded-2xl p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-white">Open Positions</h2>
        <a href="trading.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">Trade →</a>
      </div>
      <div class="space-y-3">
        <?php foreach ($openTrades as $trade): ?>
        <?php
          $curPrice = price_for_symbol($trade['symbol']);
          $pnl = $trade['side'] === 'buy'
              ? ($curPrice - (float)$trade['price_open']) * (float)$trade['qty']
              : ((float)$trade['price_open'] - $curPrice) * (float)$trade['qty'];
          $pnlClass = $pnl >= 0 ? 'text-emerald-400' : 'text-red-400';
        ?>
        <div class="flex items-center justify-between py-2 border-b border-slate-700 last:border-0">
          <div>
            <span class="text-white font-semibold text-sm"><?= sanitize($trade['symbol']) ?></span>
            <span class="ml-2 text-xs px-2 py-0.5 rounded <?= $trade['side']==='buy' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
              <?= strtoupper($trade['side']) ?>
            </span>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold <?= $pnlClass ?>"><?= $pnl >= 0 ? '+' : '' ?><?= format_currency($pnl) ?></p>
            <p class="text-xs text-slate-400">Qty: <?= (float)$trade['qty'] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-700 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      Home
    </a>
    <a href="markets.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
      </svg>
      Markets
    </a>
    <a href="trading.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
      </svg>
      Trade
    </a>
    <a href="wallet.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
      </svg>
      Wallet
    </a>
    <a href="profile.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      Profile
    </a>
  </nav>

</body>
</html>
