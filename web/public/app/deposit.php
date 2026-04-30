<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user    = current_user();
$error   = get_flash('error');
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deposit') {
    csrf_verify();

    $asset  = strtoupper(trim($_POST['asset_ticker'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    $txid   = trim($_POST['txid']   ?? '');
    $addr   = trim($_POST['address'] ?? '');

    if ($asset === '' || $amount <= 0) {
        flash('error', 'Asset and a positive amount are required.');
        redirect('deposit.php');
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO deposit_requests (user_id, asset_ticker, amount, txid, address, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $asset, $amount, $txid ?: null, $addr ?: null, 'pending']);
        flash('success', 'Deposit request submitted! It will be reviewed within 24 hours.');
    } catch (Throwable) {
        flash('error', 'Failed to submit deposit request.');
    }
    redirect('deposit.php');
}

$depositAddresses = [];
try {
    $depositAddresses = db()->query('SELECT * FROM deposit_addresses WHERE active = 1 ORDER BY asset_ticker')->fetchAll();
} catch (Throwable) {}

$depositHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
    );
    $st->execute([$user['id']]);
    $depositHistory = $st->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deposit – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center gap-3">
    <a href="wallet.php" class="text-slate-400 hover:text-white transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span class="text-xl font-extrabold text-emerald-400 flex-1">Deposit</span>
    <a href="../logout.php" class="text-slate-400 hover:text-red-400 transition text-xs">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Deposit Addresses -->
    <?php if (!empty($depositAddresses)): ?>
    <div class="bg-slate-800 rounded-2xl p-5 space-y-3">
      <h2 class="font-bold text-white text-lg">Network Addresses</h2>
      <p class="text-slate-400 text-sm">Send crypto to one of the addresses below, then submit your deposit request.</p>
      <?php foreach ($depositAddresses as $da): ?>
      <div class="bg-slate-700 rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="font-semibold text-emerald-400"><?= sanitize($da['asset_ticker']) ?></span>
          <span class="text-xs text-slate-400 bg-slate-600 px-2 py-1 rounded"><?= sanitize($da['network']) ?></span>
        </div>
        <p class="text-white text-sm font-mono break-all select-all"><?= sanitize($da['address']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Deposit Form -->
    <div class="bg-slate-800 rounded-2xl p-5 space-y-4">
      <h2 class="font-bold text-white text-lg">Submit Deposit Request</h2>
      <form method="POST" action="deposit.php" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="deposit">

        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Asset</label>
          <select name="asset_ticker" class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="BTC">BTC – Bitcoin</option>
            <option value="ETH">ETH – Ethereum</option>
            <option value="USDT" selected>USDT – Tether</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Amount</label>
          <input type="number" name="amount" min="0.00000001" step="0.00000001" required
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Transaction ID (TXID)</label>
          <input type="text" name="txid"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Paste your transaction hash">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">From Address (optional)</label>
          <input type="text" name="address"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Your sending wallet address">
        </div>
        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition">
          Submit Deposit Request
        </button>
      </form>
    </div>

    <!-- Deposit History -->
    <?php if (!empty($depositHistory)): ?>
    <div class="bg-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-700">
        <h2 class="font-bold text-white">Recent Deposits</h2>
      </div>
      <div class="divide-y divide-slate-700">
        <?php foreach ($depositHistory as $d):
          $statusColors = ['pending'=>'text-yellow-400 bg-yellow-500/10', 'approved'=>'text-emerald-400 bg-emerald-500/10', 'rejected'=>'text-red-400 bg-red-500/10'];
          $sc = $statusColors[$d['status']] ?? 'text-slate-400 bg-slate-700';
        ?>
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-white">Deposit – <?= sanitize($d['asset_ticker']) ?></p>
            <p class="text-xs text-slate-400"><?= sanitize($d['created_at']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold text-white"><?= format_currency((float)$d['amount']) ?></p>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($d['status']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-700 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="markets.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
      Markets
    </a>
    <a href="trading.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      Trade
    </a>
    <a href="wallet.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="profile.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

</body>
</html>
