<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user  = current_user();
$error   = get_flash('error');
$success = get_flash('success');

// Handle Deposit Request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deposit') {
    csrf_verify();

    $asset  = strtoupper(trim($_POST['asset_ticker'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    $txid   = trim($_POST['txid']   ?? '');
    $addr   = trim($_POST['address'] ?? '');

    if ($asset === '' || $amount <= 0) {
        flash('error', 'Asset and a positive amount are required.');
        redirect('/app/wallet.php#deposit');
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
    redirect('/app/wallet.php#deposit');
}

// Handle Withdrawal Request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    csrf_verify();

    $asset   = strtoupper(trim($_POST['asset_ticker'] ?? ''));
    $amount  = (float)($_POST['amount'] ?? 0);
    $address = trim($_POST['address'] ?? '');

    if ($asset === '' || $amount <= 0 || $address === '') {
        flash('error', 'Asset, amount, and wallet address are required.');
        redirect('/app/wallet.php#withdraw');
    }

    if ($amount > (float)$user['balance']) {
        flash('error', 'Insufficient balance.');
        redirect('/app/wallet.php#withdraw');
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO withdrawal_requests (user_id, asset_ticker, amount, address, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $asset, $amount, $address, 'pending']);
        flash('success', 'Withdrawal request submitted! It will be processed within 24 hours.');
    } catch (Throwable) {
        flash('error', 'Failed to submit withdrawal request.');
    }
    redirect('/app/wallet.php#withdraw');
}

// Fetch deposit addresses
$depositAddresses = [];
try {
    $depositAddresses = db()->query('SELECT * FROM deposit_addresses WHERE active = 1 ORDER BY asset_ticker')->fetchAll();
} catch (Throwable) {}

// Fetch user deposit requests
$depositHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
    );
    $st->execute([$user['id']]);
    $depositHistory = $st->fetchAll();
} catch (Throwable) {}

// Fetch user withdrawal requests
$withdrawHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM withdrawal_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
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
<body class="bg-white text-slate-900 min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">Wallet</span>
    <a href="../logout.php" class="text-slate-600 hover:text-red-500 transition text-xs">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Balance -->
    <div class="bg-gradient-to-br from-white via-emerald-50 to-sky-50 border border-slate-200 rounded-2xl p-6">
      <p class="text-emerald-700 text-sm">Available Balance</p>
      <p class="text-4xl font-extrabold text-slate-900 mt-1">$<?= number_format((float)$user['balance'], 2) ?></p>
      <p class="text-emerald-600 text-sm mt-1">USDT</p>
    </div>

    <!-- Deposit Section -->
    <div id="deposit" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
      <h2 class="font-bold text-slate-900 text-lg">Deposit Funds</h2>

      <?php if (!empty($depositAddresses)): ?>
      <div class="space-y-3">
        <p class="text-slate-600 text-sm">Send crypto to one of the addresses below, then submit your deposit request.</p>
        <?php foreach ($depositAddresses as $da): ?>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-emerald-400"><?= sanitize($da['asset_ticker']) ?></span>
            <span class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded"><?= sanitize($da['network']) ?></span>
          </div>
          <p class="text-slate-900 text-sm font-mono break-all select-all"><?= sanitize($da['address']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="wallet.php" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="deposit">

        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Asset</label>
          <select name="asset_ticker" class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="BTC">BTC – Bitcoin</option>
            <option value="ETH">ETH – Ethereum</option>
            <option value="USDT">USDT – Tether</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Amount</label>
          <input type="number" name="amount" min="0.00000001" step="0.00000001" required
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Transaction ID (TXID)</label>
          <input type="text" name="txid"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Paste your transaction hash">
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">From Address (optional)</label>
          <input type="text" name="address"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Your sending wallet address">
        </div>
        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition">
          Submit Deposit Request
        </button>
      </form>
    </div>

    <!-- Withdraw Section -->
    <div id="withdraw" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
      <h2 class="font-bold text-slate-900 text-lg">Withdraw Funds</h2>

      <form method="POST" action="wallet.php" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="withdraw">

        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Asset</label>
          <select name="asset_ticker" class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="BTC">BTC – Bitcoin</option>
            <option value="ETH">ETH – Ethereum</option>
            <option value="USDT">USDT – Tether</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Amount</label>
          <input type="number" name="amount" min="0.00000001" step="0.00000001" required
            max="<?= sanitize(number_format((float)$user['balance'], 2, '.', '')) ?>"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
          <p class="text-slate-600 text-xs mt-1">Available: $<?= number_format((float)$user['balance'], 2) ?></p>
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Withdrawal Address</label>
          <input type="text" name="address" required
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Your receiving wallet address">
        </div>
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-3 rounded-xl transition">
          Request Withdrawal
        </button>
      </form>
    </div>

    <!-- Transaction History -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900">Transaction History</h2>
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
        <div class="px-5 py-8 text-center text-slate-600">No transactions yet.</div>
      <?php else: ?>
        <div class="divide-y divide-slate-700">
          <?php foreach ($allTx as $tx): ?>
          <?php
            $statusColors = ['pending'=>'text-yellow-700 bg-yellow-500/10', 'approved'=>'text-emerald-700 bg-emerald-500/10', 'rejected'=>'text-red-600 bg-red-500/10'];
            $sc = $statusColors[$tx['status']] ?? 'text-slate-600 bg-white';
          ?>
          <div class="px-5 py-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-900"><?= $tx['type'] ?> – <?= sanitize($tx['asset']) ?></p>
              <p class="text-xs text-slate-600"><?= sanitize($tx['date']) ?></p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-slate-900"><?= format_currency((float)$tx['amount']) ?></p>
              <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($tx['status']) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>

  <!-- Navigation -->
  <?php $activePage = 'wallet.php'; include '_nav.php'; ?>

</body>
</html>


