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

// Fetch recent transactions
$depositHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'
    );
    $st->execute([$user['id']]);
    $depositHistory = $st->fetchAll();
} catch (Throwable) {}

$withdrawHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM withdrawal_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'
    );
    $st->execute([$user['id']]);
    $withdrawHistory = $st->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wallet – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">Wallet</span>
    <a href="../logout.php" class="text-slate-400 hover:text-red-400 transition text-xs">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Balance -->
    <div class="bg-gradient-to-br from-emerald-600 to-slate-800 rounded-2xl p-6">
      <p class="text-emerald-200 text-sm">Available Balance</p>
      <p class="text-4xl font-extrabold text-white mt-1">$<?= number_format((float)$user['balance'], 2) ?></p>
      <p class="text-emerald-300 text-sm mt-1">USDT</p>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4">
      <a href="deposit.php"
        class="flex flex-col items-center justify-center bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-5 rounded-2xl transition gap-2 shadow-lg shadow-emerald-900/30">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        Deposit
      </a>
      <a href="withdraw.php"
        class="flex flex-col items-center justify-center bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-5 rounded-2xl transition gap-2 shadow-lg shadow-yellow-900/30">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        Withdraw
      </a>
    </div>

    <!-- Transaction History -->
    <div class="bg-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-700">
        <h2 class="font-bold text-white">Transaction History</h2>
      </div>

      <?php
        $allTx = [];
        foreach ($depositHistory as $d) {
            $allTx[] = ['type'=>'Deposit', 'asset'=>$d['asset_ticker'], 'amount'=>$d['amount'], 'status'=>$d['status'], 'date'=>$d['created_at']];
        }
        foreach ($withdrawHistory as $w) {
            $allTx[] = ['type'=>'Withdrawal', 'asset'=>$w['asset_ticker'], 'amount'=>$w['amount'], 'status'=>$w['status'], 'date'=>$w['created_at']];
        }
        usort($allTx, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
      ?>

      <?php if (empty($allTx)): ?>
        <div class="px-5 py-8 text-center text-slate-400">No transactions yet.</div>
      <?php else: ?>
        <div class="divide-y divide-slate-700">
          <?php foreach ($allTx as $tx): ?>
          <?php
            $statusColors = ['pending'=>'text-yellow-400 bg-yellow-500/10', 'approved'=>'text-emerald-400 bg-emerald-500/10', 'rejected'=>'text-red-400 bg-red-500/10'];
            $sc = $statusColors[$tx['status']] ?? 'text-slate-400 bg-slate-700';
          ?>
          <div class="px-5 py-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-white"><?= $tx['type'] ?> – <?= sanitize($tx['asset']) ?></p>
              <p class="text-xs text-slate-400"><?= sanitize($tx['date']) ?></p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-white"><?= format_currency((float)$tx['amount']) ?></p>
              <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($tx['status']) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

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
