<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/csrf.php';
require_once __DIR__ . '/../../../src/helpers.php';

require_login();
$user = current_user();

$symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'ADAUSDT', 'XRPUSDT'];
$error   = get_flash('error');
$success = get_flash('success');

// Handle Place Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'open') {
    csrf_verify();

    $symbol = strtoupper(trim($_POST['symbol'] ?? 'BTCUSDT'));
    $side   = in_array($_POST['side'] ?? '', ['buy', 'sell'], true) ? $_POST['side'] : 'buy';
    $qty    = (float)($_POST['qty'] ?? 0);

    if (!in_array($symbol, $symbols, true) || $qty <= 0) {
        flash('error', 'Invalid symbol or quantity.');
        redirect('/web/public/app/trading.php');
    }

    $priceOpen = price_for_symbol($symbol);

    try {
        $stmt = db()->prepare(
            'INSERT INTO demo_trades (user_id, symbol, side, qty, price_open, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $symbol, $side, $qty, $priceOpen, 'open']);
        flash('success', 'Demo order placed at $' . number_format($priceOpen, 2));
    } catch (Throwable) {
        flash('error', 'Failed to place order. Please try again.');
    }
    redirect('/web/public/app/trading.php');
}

// Handle Close Position
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close') {
    csrf_verify();

    $tradeId = (int)($_POST['trade_id'] ?? 0);

    try {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT * FROM demo_trades WHERE id = ? AND user_id = ? AND status = ? LIMIT 1');
        $stmt->execute([$tradeId, $user['id'], 'open']);
        $trade = $stmt->fetch();

        if (!$trade) {
            flash('error', 'Trade not found or already closed.');
            redirect('/web/public/app/trading.php');
        }

        $priceClose = price_for_symbol($trade['symbol']);
        $pnl = $trade['side'] === 'buy'
            ? ($priceClose - (float)$trade['price_open']) * (float)$trade['qty']
            : ((float)$trade['price_open'] - $priceClose) * (float)$trade['qty'];

        $upd = $pdo->prepare(
            'UPDATE demo_trades SET status = ?, price_close = ?, pnl = ?, closed_at = NOW() WHERE id = ?'
        );
        $upd->execute(['closed', $priceClose, $pnl, $tradeId]);

        flash('success', sprintf('Position closed. P&L: %s$%.2f', $pnl >= 0 ? '+' : '', $pnl));
    } catch (Throwable) {
        flash('error', 'Failed to close position.');
    }
    redirect('/web/public/app/trading.php');
}

$selectedSymbol = strtoupper(trim($_GET['symbol'] ?? 'BTCUSDT'));
if (!in_array($selectedSymbol, $symbols, true)) {
    $selectedSymbol = 'BTCUSDT';
}
$currentPrice = price_for_symbol($selectedSymbol);

// Fetch open positions
$openTrades = [];
try {
    $st = db()->prepare(
        'SELECT * FROM demo_trades WHERE user_id = ? AND status = ? ORDER BY created_at DESC'
    );
    $st->execute([$user['id'], 'open']);
    $openTrades = $st->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demo Trading – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">Trading</span>
    <span class="bg-yellow-500/20 text-yellow-400 text-xs font-bold px-3 py-1 rounded-full">DEMO – Simulated</span>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Order Form -->
    <div class="bg-slate-800 rounded-2xl p-5">
      <h2 class="font-bold text-white mb-4">Place Demo Order</h2>

      <form method="POST" action="/web/public/app/trading.php" id="tradeForm" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="open">

        <!-- Symbol -->
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Symbol</label>
          <select name="symbol" id="symbolSelect"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <?php foreach ($symbols as $sym): ?>
            <option value="<?= $sym ?>" <?= $sym === $selectedSymbol ? 'selected' : '' ?>><?= $sym ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Current Price -->
        <div class="bg-slate-700 rounded-lg px-4 py-3 flex items-center justify-between">
          <span class="text-slate-400 text-sm">Current Price</span>
          <span class="text-emerald-400 font-bold text-lg" id="priceDisplay">
            $<?= number_format($currentPrice, 2) ?>
          </span>
        </div>

        <!-- Side toggle -->
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Side</label>
          <div class="grid grid-cols-2 gap-2">
            <label class="cursor-pointer">
              <input type="radio" name="side" value="buy" class="sr-only peer" checked>
              <div class="peer-checked:bg-emerald-500 peer-checked:border-emerald-500 bg-slate-700 border border-slate-600 text-center py-3 rounded-lg font-semibold text-sm transition">
                Buy / Long
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="side" value="sell" class="sr-only peer">
              <div class="peer-checked:bg-red-500 peer-checked:border-red-500 bg-slate-700 border border-slate-600 text-center py-3 rounded-lg font-semibold text-sm transition">
                Sell / Short
              </div>
            </label>
          </div>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Quantity</label>
          <input type="number" name="qty" min="0.00001" step="0.00001" value="0.001" required
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <button type="submit"
          class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition">
          Place Demo Order
        </button>
      </form>
    </div>

    <!-- Open Positions -->
    <?php if (!empty($openTrades)): ?>
    <div class="bg-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-700">
        <h2 class="font-bold text-white">Open Positions</h2>
      </div>
      <?php foreach ($openTrades as $trade): ?>
      <?php
        $cp  = price_for_symbol($trade['symbol']);
        $pnl = $trade['side'] === 'buy'
            ? ($cp - (float)$trade['price_open']) * (float)$trade['qty']
            : ((float)$trade['price_open'] - $cp) * (float)$trade['qty'];
        $pnlClass = $pnl >= 0 ? 'text-emerald-400' : 'text-red-400';
      ?>
      <div class="px-5 py-4 border-b border-slate-700 last:border-0">
        <div class="flex items-start justify-between">
          <div>
            <span class="font-semibold text-white"><?= sanitize($trade['symbol']) ?></span>
            <span class="ml-2 text-xs px-2 py-0.5 rounded <?= $trade['side']==='buy' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
              <?= strtoupper($trade['side']) ?>
            </span>
            <div class="text-slate-400 text-xs mt-1">
              Open: $<?= number_format((float)$trade['price_open'], 2) ?> &middot;
              Qty: <?= (float)$trade['qty'] ?> &middot;
              Now: $<?= number_format($cp, 2) ?>
            </div>
          </div>
          <div class="text-right">
            <p class="font-bold <?= $pnlClass ?>">
              <?= $pnl >= 0 ? '+' : '' ?>$<?= format_currency($pnl) ?>
            </p>
            <form method="POST" action="/web/public/app/trading.php" class="mt-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="close">
              <input type="hidden" name="trade_id" value="<?= (int)$trade['id'] ?>">
              <button type="submit"
                class="text-xs bg-red-500/20 hover:bg-red-500/40 text-red-400 px-3 py-1 rounded-lg transition">
                Close
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-slate-800 rounded-2xl p-8 text-center">
      <p class="text-slate-400">No open positions. Place a demo order above!</p>
    </div>
    <?php endif; ?>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-700 flex justify-around py-2 z-50">
    <a href="/web/public/app/index.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="/web/public/app/markets.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      Markets
    </a>
    <a href="/web/public/app/trading.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      Trade
    </a>
    <a href="/web/public/app/wallet.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="/web/public/app/profile.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

  <script>
    // Update price when symbol changes
    document.getElementById('symbolSelect').addEventListener('change', function() {
      const sym = this.value;
      fetch('https://api.binance.com/api/v3/ticker/price?symbol=' + sym)
        .then(r => r.json())
        .then(d => {
          if (d.price) {
            document.getElementById('priceDisplay').textContent =
              '$' + parseFloat(d.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
        })
        .catch(() => {});
    });
  </script>

</body>
</html>
