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

// Balances from DB
$balances = [
    'USDT' => (float)($user['balance']     ?? 0),
    'BTC'  => (float)($user['btc_balance'] ?? 0),
    'ETH'  => (float)($user['eth_balance'] ?? 0),
];

// Handle Withdrawal Request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $method  = trim($_POST['withdraw_method'] ?? ''); // 'bank' or 'crypto'
    $asset   = strtoupper(trim($_POST['asset_ticker'] ?? 'USDT'));
    $amount  = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        flash('error', 'Please enter a valid amount greater than zero.');
        redirect('/app/withdraw.php');
    }

    if (!isset($balances[$asset]) || $amount > $balances[$asset]) {
        flash('error', 'Insufficient balance for ' . $asset . '.');
        redirect('/app/withdraw.php');
    }

    if ($method === 'bank') {
        $bankName    = trim($_POST['bank_name']    ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        $accountNo   = trim($_POST['account_no']   ?? '');
        $routing     = trim($_POST['routing']      ?? '');

        if ($bankName === '' || $accountName === '' || $accountNo === '') {
            flash('error', 'Bank name, account holder name, and account number are required.');
            redirect('/app/withdraw.php');
        }

        $address = "Bank: {$bankName} | {$accountName} | Acct: {$accountNo}" . ($routing ? " | Routing: {$routing}" : '');
    } else {
        $address = trim($_POST['wallet_address'] ?? '');
        if ($address === '') {
            flash('error', 'Wallet address is required for crypto withdrawal.');
            redirect('/app/withdraw.php');
        }
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
    redirect('/app/withdraw.php');
}

// Fetch withdrawal history
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
  <title>Withdraw – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="index.php" class="text-slate-600 hover:text-slate-900 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <span class="text-lg font-extrabold text-yellow-400">Withdraw</span>
    </div>
    <a href="../logout.php" class="text-slate-600 hover:text-red-500 transition text-xs">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-5 space-y-5">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Withdraw Method Selection -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">
      <h2 class="font-bold text-slate-900 text-base">Select Withdrawal Method</h2>

      <div class="grid grid-cols-2 gap-3">
        <button type="button" id="btnBank"
          onclick="selectMethod('bank')"
          class="method-card border-2 border-transparent rounded-xl p-4 text-center cursor-pointer transition bg-white hover:border-blue-500/60">
          <div class="w-10 h-10 bg-blue-500/15 rounded-xl flex items-center justify-center mx-auto mb-2">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          </div>
          <p class="text-sm font-bold text-slate-800">Bank Transfer</p>
          <p class="text-xs text-slate-600 mt-0.5">Wire / ACH</p>
        </button>

        <button type="button" id="btnCrypto"
          onclick="selectMethod('crypto')"
          class="method-card border-2 border-transparent rounded-xl p-4 text-center cursor-pointer transition bg-white hover:border-yellow-500/60">
          <div class="w-10 h-10 bg-yellow-500/15 rounded-xl flex items-center justify-center mx-auto mb-2">
            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-sm font-bold text-slate-800">Crypto</p>
          <p class="text-xs text-slate-600 mt-0.5">BTC / ETH / USDT</p>
        </button>
      </div>
    </div>

    <!-- Withdrawal Form -->
    <form method="POST" action="withdraw.php" id="withdrawForm" class="hidden space-y-5">
      <?= csrf_field() ?>
      <input type="hidden" name="withdraw_method" id="withdrawMethod" value="">

      <!-- Common: Asset + Amount -->
      <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-4">

        <!-- Balance Source Selector -->
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Withdraw from</label>
          <select name="asset_ticker" id="assetSelect"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="USDT">USDT – Tether (Available: $<?= number_format($balances['USDT'], 2) ?>)</option>
            <option value="BTC">BTC – Bitcoin (Available: <?= number_format($balances['BTC'], 8) ?> BTC)</option>
            <option value="ETH">ETH – Ethereum (Available: <?= number_format($balances['ETH'], 8) ?> ETH)</option>
          </select>
        </div>

        <!-- Amount with quick chips -->
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Amount</label>
          <input type="number" name="amount" id="amountInput"
            min="0.00000001" step="0.00000001" required
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
          <p class="text-slate-600 text-xs mt-1" id="balanceHint">Available: $<?= number_format($balances['USDT'], 2) ?> USDT</p>
          <!-- Quick amount chips -->
          <div class="flex flex-wrap gap-2 mt-2">
            <button type="button" onclick="setAmount(100)"
              class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 text-xs rounded-lg border border-slate-300 transition">$100</button>
            <button type="button" onclick="setAmount(500)"
              class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 text-xs rounded-lg border border-slate-300 transition">$500</button>
            <button type="button" onclick="setAmount(1000)"
              class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 text-xs rounded-lg border border-slate-300 transition">$1,000</button>
            <button type="button" onclick="setAmount(2500)"
              class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 text-xs rounded-lg border border-slate-300 transition">$2,500</button>
          </div>
        </div>
      </div>

      <!-- Bank Fields -->
      <div id="bankFields" class="hidden bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          Bank Details
        </h3>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Bank Name</label>
          <input type="text" name="bank_name" placeholder="e.g. Chase Bank"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Account Holder Name</label>
          <input type="text" name="account_name" placeholder="Full name on account"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Account Number / IBAN</label>
          <input type="text" name="account_no" placeholder="Account number"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Routing / SWIFT / BIC (optional)</label>
          <input type="text" name="routing" placeholder="Routing or SWIFT code"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <!-- Crypto Fields -->
      <div id="cryptoFields" class="hidden bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Destination Wallet
        </h3>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Wallet Address</label>
          <input type="text" name="wallet_address" placeholder="Paste your receiving address"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-yellow-500">
        </div>
      </div>

      <button type="submit"
        class="w-full bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-3 rounded-xl transition">
        Request Withdrawal
      </button>
    </form>

    <!-- Withdrawal History -->
    <?php if (!empty($withdrawHistory)): ?>
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900 text-sm">Recent Withdrawals</h2>
      </div>
      <div class="divide-y divide-slate-700">
        <?php foreach ($withdrawHistory as $w):
          $statusColors = ['pending'=>'text-yellow-700 bg-yellow-500/10', 'approved'=>'text-emerald-700 bg-emerald-500/10', 'rejected'=>'text-red-600 bg-red-500/10'];
          $sc = $statusColors[$w['status']] ?? 'text-slate-600 bg-white';
        ?>
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-slate-900"><?= sanitize($w['asset_ticker']) ?></p>
            <p class="text-xs text-slate-600"><?= sanitize($w['created_at']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold text-slate-900"><?= format_currency((float)$w['amount']) ?></p>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($w['status']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 bg-white/95 border-t border-slate-200 flex justify-around py-2 z-50">
    <a href="index.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Home
    </a>
    <a href="markets.php" class="flex flex-col items-center text-xs text-slate-600 hover:text-emerald-600 transition gap-1">
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

<script>
const BALANCES = <?= json_encode($balances, JSON_THROW_ON_ERROR) ?>;

function selectMethod(method) {
    document.getElementById('withdrawMethod').value = method;
    document.getElementById('withdrawForm').classList.remove('hidden');

    const btnBank   = document.getElementById('btnBank');
    const btnCrypto = document.getElementById('btnCrypto');
    const bankFields   = document.getElementById('bankFields');
    const cryptoFields = document.getElementById('cryptoFields');

    if (method === 'bank') {
        btnBank.classList.add('border-blue-500', 'bg-blue-500/10');
        btnBank.classList.remove('border-transparent', 'bg-white');
        btnCrypto.classList.remove('border-yellow-500', 'bg-yellow-500/10');
        btnCrypto.classList.add('border-transparent', 'bg-white');
        bankFields.classList.remove('hidden');
        cryptoFields.classList.add('hidden');
    } else {
        btnCrypto.classList.add('border-yellow-500', 'bg-yellow-500/10');
        btnCrypto.classList.remove('border-transparent', 'bg-white');
        btnBank.classList.remove('border-blue-500', 'bg-blue-500/10');
        btnBank.classList.add('border-transparent', 'bg-white');
        cryptoFields.classList.remove('hidden');
        bankFields.classList.add('hidden');
    }
    updateBalanceHint();
}

function setAmount(val) {
    document.getElementById('amountInput').value = val;
}

function updateBalanceHint() {
    const asset = document.getElementById('assetSelect').value;
    const bal   = BALANCES[asset] ?? 0;
    const hint  = document.getElementById('balanceHint');
    if (asset === 'USDT') {
        hint.textContent = 'Available: $' + bal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' USDT';
    } else {
        hint.textContent = 'Available: ' + bal.toFixed(8) + ' ' + asset;
    }
}

document.getElementById('assetSelect').addEventListener('change', updateBalanceHint);
</script>

</body>
</html>


