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

$balances = [
    'USDT' => (float)($user['balance']     ?? 0),
    'BTC'  => (float)($user['btc_balance'] ?? 0),
    'ETH'  => (float)($user['eth_balance'] ?? 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    csrf_verify();

    $method = in_array($_POST['method'] ?? '', ['crypto', 'bank'], true) ? $_POST['method'] : 'crypto';
    $asset  = strtoupper(trim($_POST['asset_ticker'] ?? 'USDT'));
    if (!array_key_exists($asset, $balances)) {
        $asset = 'USDT';
    }
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        flash('error', 'Please enter a valid amount.');
        redirect('withdraw.php');
    }

    if ($amount > $balances[$asset]) {
        flash('error', 'Insufficient ' . $asset . ' balance.');
        redirect('withdraw.php');
    }

    if ($method === 'crypto') {
        $address = trim($_POST['crypto_address'] ?? '');
        if ($address === '') {
            flash('error', 'Wallet address is required for crypto withdrawal.');
            redirect('withdraw.php');
        }
        $addressField = $address;
    } else {
        $bankName      = trim($_POST['bank_name']      ?? '');
        $accountName   = trim($_POST['account_name']   ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $routing       = trim($_POST['routing']        ?? '');
        if ($bankName === '' || $accountName === '' || $accountNumber === '') {
            flash('error', 'Bank name, account name, and account number are required.');
            redirect('withdraw.php');
        }
        $addressField = "Bank: {$bankName} | Acct Name: {$accountName} | Acct #: {$accountNumber}" . ($routing !== '' ? " | Routing: {$routing}" : '');
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO withdrawal_requests (user_id, asset_ticker, amount, address, status, method)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $asset, $amount, $addressField, 'pending', $method]);
        flash('success', 'Withdrawal request submitted! It will be processed within 24 hours.');
    } catch (Throwable) {
        flash('error', 'Failed to submit withdrawal request.');
    }
    redirect('withdraw.php');
}

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
  <title>Withdraw – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center gap-3">
    <a href="wallet.php" class="text-slate-400 hover:text-white transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span class="text-xl font-extrabold text-yellow-400 flex-1">Withdraw</span>
    <a href="../logout.php" class="text-slate-400 hover:text-red-400 transition text-xs">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="withdraw.php" id="withdrawForm" class="space-y-5">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="withdraw">
      <input type="hidden" name="method" id="methodInput" value="crypto">

      <!-- Step 1: Choose method -->
      <div class="bg-slate-800 rounded-2xl p-5">
        <h2 class="font-bold text-white text-lg mb-4">Withdrawal Method</h2>
        <div class="grid grid-cols-2 gap-3">
          <!-- Bank Card -->
          <button type="button" id="btnBank"
            onclick="selectMethod('bank')"
            class="method-card border-2 border-slate-600 rounded-xl p-4 text-center transition hover:border-yellow-500 focus:outline-none">
            <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
              </svg>
            </div>
            <p class="text-sm font-bold text-slate-300">Bank Transfer</p>
            <p class="text-xs text-slate-500 mt-0.5">Wire / ACH</p>
          </button>
          <!-- Crypto Card -->
          <button type="button" id="btnCrypto"
            onclick="selectMethod('crypto')"
            class="method-card border-2 border-emerald-500 rounded-xl p-4 text-center transition hover:border-emerald-400 focus:outline-none bg-emerald-500/5">
            <div class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-2">
              <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <p class="text-sm font-bold text-slate-300">Crypto</p>
            <p class="text-xs text-slate-500 mt-0.5">BTC / ETH / USDT</p>
          </button>
        </div>
      </div>

      <!-- Step 2: Balance + Amount -->
      <div class="bg-slate-800 rounded-2xl p-5 space-y-4">
        <h2 class="font-bold text-white text-lg">Amount</h2>

        <!-- Balance selector -->
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Withdraw from</label>
          <select name="asset_ticker" id="assetSelect"
            onchange="updateAvailable()"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="USDT">USDT – Tether ($<?= number_format($balances['USDT'], 2) ?> available)</option>
            <option value="BTC">BTC – Bitcoin (<?= number_format($balances['BTC'], 8) ?> available)</option>
            <option value="ETH">ETH – Ethereum (<?= number_format($balances['ETH'], 8) ?> available)</option>
          </select>
        </div>

        <!-- Amount input -->
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Amount</label>
          <input type="number" name="amount" id="amountInput"
            min="0.00000001" step="0.00000001" required
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
          <p class="text-slate-500 text-xs mt-1" id="availableText">Available: $<?= number_format($balances['USDT'], 2) ?></p>
        </div>

        <!-- Quick amount chips -->
        <div class="flex gap-2 flex-wrap">
          <?php foreach ([100, 500, 1000, 2500] as $chip): ?>
          <button type="button"
            onclick="setAmount(<?= $chip ?>)"
            class="bg-slate-700 hover:bg-slate-600 border border-slate-600 hover:border-emerald-500 text-slate-300 hover:text-white text-sm px-4 py-1.5 rounded-lg transition font-medium">
            $<?= number_format($chip) ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Bank Fields -->
      <div id="bankFields" class="hidden bg-slate-800 rounded-2xl p-5 space-y-3">
        <h2 class="font-bold text-white text-lg">Bank Details</h2>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Bank Name</label>
          <input type="text" name="bank_name"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            placeholder="e.g. Chase, Bank of America">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Account Name</label>
          <input type="text" name="account_name"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            placeholder="Full name on account">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Account Number</label>
          <input type="text" name="account_number"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            placeholder="Account number">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Routing / SWIFT / IBAN (optional)</label>
          <input type="text" name="routing"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            placeholder="Routing or SWIFT code">
        </div>
      </div>

      <!-- Crypto Fields -->
      <div id="cryptoFields" class="bg-slate-800 rounded-2xl p-5 space-y-3">
        <h2 class="font-bold text-white text-lg">Wallet Address</h2>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Receiving Address</label>
          <input type="text" name="crypto_address"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            placeholder="Your destination wallet address">
        </div>
      </div>

      <button type="submit"
        class="w-full bg-yellow-500 hover:bg-yellow-400 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-yellow-900/30">
        Request Withdrawal
      </button>
    </form>

    <!-- Withdrawal History -->
    <?php if (!empty($withdrawHistory)): ?>
    <div class="bg-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-700">
        <h2 class="font-bold text-white">Recent Withdrawals</h2>
      </div>
      <div class="divide-y divide-slate-700">
        <?php foreach ($withdrawHistory as $w):
          $statusColors = ['pending'=>'text-yellow-400 bg-yellow-500/10', 'approved'=>'text-emerald-400 bg-emerald-500/10', 'rejected'=>'text-red-400 bg-red-500/10'];
          $sc = $statusColors[$w['status']] ?? 'text-slate-400 bg-slate-700';
        ?>
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-white">Withdraw – <?= sanitize($w['asset_ticker']) ?></p>
            <p class="text-xs text-slate-400"><?= sanitize($w['created_at']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold text-white"><?= format_currency((float)$w['amount']) ?></p>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($w['status']) ?></span>
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
    <a href="wallet.php" class="flex flex-col items-center text-xs text-yellow-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="profile.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

  <script>
  const balances = <?= json_encode($balances, JSON_THROW_ON_ERROR) ?>;

  function selectMethod(method) {
    document.getElementById('methodInput').value = method;
    const bankBtn   = document.getElementById('btnBank');
    const cryptoBtn = document.getElementById('btnCrypto');
    const bankFields   = document.getElementById('bankFields');
    const cryptoFields = document.getElementById('cryptoFields');

    if (method === 'bank') {
      bankBtn.classList.add('border-yellow-500', 'bg-yellow-500/5');
      bankBtn.classList.remove('border-slate-600');
      cryptoBtn.classList.remove('border-emerald-500', 'bg-emerald-500/5');
      cryptoBtn.classList.add('border-slate-600');
      bankFields.classList.remove('hidden');
      cryptoFields.classList.add('hidden');
    } else {
      cryptoBtn.classList.add('border-emerald-500', 'bg-emerald-500/5');
      cryptoBtn.classList.remove('border-slate-600');
      bankBtn.classList.remove('border-yellow-500', 'bg-yellow-500/5');
      bankBtn.classList.add('border-slate-600');
      bankFields.classList.add('hidden');
      cryptoFields.classList.remove('hidden');
    }
  }

  function setAmount(val) {
    document.getElementById('amountInput').value = val;
  }

  function updateAvailable() {
    const asset = document.getElementById('assetSelect').value;
    const bal   = balances[asset] || 0;
    const text  = document.getElementById('availableText');
    if (asset === 'USDT') {
      text.textContent = 'Available: $' + bal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
      text.textContent = 'Available: ' + bal.toLocaleString('en-US', {minimumFractionDigits: 8, maximumFractionDigits: 8}) + ' ' + asset;
    }
  }
  </script>

</body>
</html>
