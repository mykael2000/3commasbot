<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user = current_user();

$symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'ADAUSDT', 'XRPUSDT'];
$selected = strtoupper(trim($_GET['symbol'] ?? 'BTCUSDT'));
if (!in_array($selected, $symbols, true)) {
    $selected = 'BTCUSDT';
}

$prices = [];
foreach ($symbols as $sym) {
    $prices[$sym] = price_for_symbol($sym);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Markets – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">Markets</span>
    <a href="../logout.php" class="text-slate-600 hover:text-red-500 transition text-xs">Logout</a>
  </header>

  <main class="max-w-2xl mx-auto py-6 space-y-6">

    <!-- Symbol Selector -->
    <div class="flex gap-2 flex-wrap">
      <?php foreach ($symbols as $sym): ?>
      <a href="markets.php?symbol=<?= urlencode($sym) ?>"
         class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                <?= $sym === $selected ? 'bg-emerald-500 text-slate-900' : 'bg-white text-slate-700 hover:bg-slate-100' ?>">
        <?= $sym ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- TradingView Chart Widget -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden" style="height:420px;">
      <div class="tradingview-widget-container h-full">
        <div class="tradingview-widget-container__widget h-full"></div>
        <script type="text/javascript"
          src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
        {
          "autosize": true,
          "symbol": "BINANCE:<?= sanitize($selected) ?>",
          "interval": "60",
          "timezone": "Etc/UTC",
          "theme": "light",
          "style": "1",
          "locale": "en",
          "enable_publishing": false,
          "hide_top_toolbar": false,
          "hide_legend": false,
          "save_image": false,
          "backgroundColor": "rgba(255, 255, 255, 1)",
          "gridColor": "rgba(226, 232, 240, 1)"
        }
        </script>
      </div>
    </div>

    <!-- Market Overview Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900">Market Overview</h2>
      </div>
      <table class="w-full text-sm">
        <thead class="bg-slate-100/80">
          <tr>
            <th class="text-left text-slate-600 font-medium px-5 py-3">Asset</th>
            <th class="text-right text-slate-600 font-medium px-5 py-3">Price (USDT)</th>
            <th class="text-right text-slate-600 font-medium px-5 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($symbols as $sym): ?>
          <?php $base = str_replace('USDT', '', $sym); ?>
          <tr class="border-t border-slate-200 hover:bg-slate-50 transition">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-xs font-bold"><?= $base[0] ?></div>
                <div>
                  <p class="font-semibold text-slate-900"><?= $base ?></p>
                  <p class="text-slate-600 text-xs"><?= $sym ?></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3 text-right font-bold text-slate-900">
              $<?= number_format($prices[$sym], 2) ?>
            </td>
            <td class="px-5 py-3 text-right">
              <a href="trading.php?symbol=<?= urlencode($sym) ?>"
                 class="bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/30 px-3 py-1 rounded-lg text-xs font-medium transition">
                Trade
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-white/95 border-t border-slate-200 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="markets.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      Markets
    </a>
    <a href="trading.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      Trade
    </a>
    <a href="wallet.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="profile.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

</body>
</html>


