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

// Handle Deposit Request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $asset  = strtoupper(trim($_POST['asset_ticker'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    $txid   = trim($_POST['txid']   ?? '');
    $addr   = trim($_POST['address'] ?? '');

    if ($asset === '' || $amount <= 0) {
        flash('error', 'Asset and a positive amount are required.');
        redirect('/app/deposit.php');
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
    redirect('/app/deposit.php');
}

// Fetch deposit addresses
$depositAddresses = [];
try {
    $depositAddresses = db()->query('SELECT * FROM deposit_addresses WHERE active = 1 ORDER BY asset_ticker')->fetchAll();
} catch (Throwable) {}

// Fetch user deposit history
$depositHistory = [];
try {
    $st = db()->prepare(
        'SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
    body {
      background:
        radial-gradient(circle at top right, rgba(16, 185, 129, 0.16), transparent 28%),
        radial-gradient(circle at bottom left, rgba(14, 165, 233, 0.12), transparent 24%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 55%, #eefaf5 100%);
    }

    .advanced-card {
      background: rgba(255, 255, 255, 0.92);
      border: 1px solid rgba(226, 232, 240, 0.95);
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(18px);
    }

    .mesh-card {
      background:
        linear-gradient(145deg, rgba(255,255,255,0.96), rgba(240,253,250,0.9)),
        radial-gradient(circle at top right, rgba(16,185,129,0.16), transparent 35%);
    }

    .qr-shell {
      background: linear-gradient(145deg, #ffffff, #f8fafc);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
    }

    .copy-feedback {
      transition: opacity .2s ease, transform .2s ease;
    }
  </style>
</head>
<body class="bg-white text-slate-900 min-h-screen pb-20 md:pb-4">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="index.php" class="text-slate-600 hover:text-slate-900 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <span class="text-lg font-extrabold text-emerald-400">Deposit</span>
    </div>
    <a href="../logout.php" class="text-slate-600 hover:text-red-500 transition text-xs">Logout</a>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-5 space-y-5">

    <section class="advanced-card mesh-card overflow-hidden rounded-[28px] p-6 sm:p-8">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
          <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-700">On-chain funding</span>
          <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Deposit crypto with instant wallet access</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">Each receiving wallet now includes a scannable QR code, quick copy controls, and a cleaner submission flow so deposits are easier to confirm from mobile or desktop.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:w-auto">
          <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-left shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Review Window</p>
            <p class="mt-2 text-lg font-bold text-slate-950">24 Hours</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-left shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Networks</p>
            <p class="mt-2 text-lg font-bold text-slate-950"><?= count($depositAddresses) ?></p>
          </div>
        </div>
      </div>
    </section>

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Deposit Addresses -->
    <?php if (!empty($depositAddresses)): ?>
    <section class="advanced-card rounded-[28px] p-5 sm:p-6 space-y-4">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="font-bold text-slate-950 text-xl">Receiving Addresses</h2>
          <p class="text-slate-600 text-sm">Choose the correct asset and network, scan the QR code or tap copy, then submit the deposit once your transfer is sent.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Always send only the listed asset to the matching network.</div>
      </div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($depositAddresses as $da): ?>
      <article class="group relative overflow-hidden rounded-[24px] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-emerald-50/60 p-4 shadow-[0_18px_35px_rgba(15,23,42,0.06)] transition hover:-translate-y-1 hover:shadow-[0_24px_45px_rgba(15,23,42,0.10)]">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-emerald-400/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between gap-3">
          <div>
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700"><?= sanitize($da['asset_ticker']) ?></span>
            <p class="mt-3 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Network</p>
            <p class="mt-1 text-sm font-semibold text-slate-800"><?= sanitize($da['network']) ?></p>
          </div>
          <div class="qr-shell rounded-2xl border border-slate-200 p-2">
            <div class="qr-code h-[108px] w-[108px] overflow-hidden rounded-xl bg-white" data-address="<?= sanitize($da['address']) ?>"></div>
          </div>
        </div>

        <div class="relative mt-4 rounded-2xl border border-slate-200 bg-white/90 p-3">
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Wallet Address</p>
          <p class="mt-2 break-all font-mono text-sm leading-6 text-slate-900"><?= sanitize($da['address']) ?></p>
        </div>

        <div class="relative mt-4 flex items-center justify-between gap-3">
          <span class="copy-feedback text-xs font-medium text-slate-500">Scan or copy for your wallet app</span>
          <button type="button"
                  class="copy-address inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-950 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-600"
                  data-copy-text="<?= sanitize($da['address']) ?>">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v8a2 2 0 002 2zm-2 4h8a2 2 0 002-2"></path></svg>
            Tap Copy
          </button>
        </div>
      </article>
      <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Deposit Form -->
    <div class="advanced-card rounded-[28px] p-5 sm:p-6 space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="font-bold text-slate-950 text-xl">Submit Deposit Request</h2>
          <p class="mt-1 text-sm text-slate-600">Record the transfer details after you send funds to one of the wallet addresses above.</p>
        </div>
        <span class="hidden sm:inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">Manual review</span>
      </div>
      <form method="POST" action="deposit.php" class="space-y-3">
        <?= csrf_field() ?>

        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Asset</label>
          <select name="asset_ticker" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="BTC">BTC – Bitcoin</option>
            <option value="ETH">ETH – Ethereum</option>
            <option value="USDT" selected>USDT – Tether</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Amount</label>
          <input type="number" name="amount" min="0.00000001" step="0.00000001" required
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="0.00">
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Transaction ID (TXID)</label>
          <input type="text" name="txid"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="Paste your transaction hash">
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">From Address (optional)</label>
          <input type="text" name="address"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="Your sending wallet address">
        </div>
        <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-sky-500 py-3 font-bold text-white shadow-[0_14px_28px_rgba(16,185,129,0.24)] transition hover:-translate-y-0.5 hover:from-emerald-500 hover:to-sky-400">
          Submit Deposit Request
        </button>
      </form>
    </div>

    <!-- Deposit History -->
    <?php if (!empty($depositHistory)): ?>
    <div class="advanced-card rounded-[28px] overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900 text-sm">Recent Deposits</h2>
      </div>
      <div class="divide-y divide-slate-200">
        <?php foreach ($depositHistory as $d):
          $statusColors = ['pending'=>'text-yellow-700 bg-yellow-500/10', 'approved'=>'text-emerald-700 bg-emerald-500/10', 'rejected'=>'text-red-600 bg-red-500/10'];
          $sc = $statusColors[$d['status']] ?? 'text-slate-600 bg-white';
        ?>
        <div class="px-5 py-3 flex items-center justify-between bg-white/80">
          <div>
            <p class="text-sm font-semibold text-slate-900"><?= sanitize($d['asset_ticker']) ?></p>
            <p class="text-xs text-slate-600"><?= sanitize($d['created_at']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold text-slate-900"><?= format_currency((float)$d['amount']) ?></p>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $sc ?>"><?= ucfirst($d['status']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <!-- Navigation -->
  <?php $activePage = 'deposit.php'; include '_nav.php'; ?>

  <script>
    (function () {
      const qrNodes = document.querySelectorAll('.qr-code');
      qrNodes.forEach((node) => {
        const address = node.dataset.address || '';
        if (!address || typeof QRCode === 'undefined') {
          return;
        }

        new QRCode(node, {
          text: address,
          width: 108,
          height: 108,
          colorDark: '#0f172a',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M,
        });
      });

      async function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(value);
          return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
      }

      document.querySelectorAll('.copy-address').forEach((button) => {
        button.addEventListener('click', async function () {
          const text = this.dataset.copyText || '';
          if (!text) {
            return;
          }

          const label = this.lastChild;

          try {
            await copyText(text);
            this.classList.remove('bg-slate-950');
            this.classList.add('bg-emerald-600');
            if (label && label.nodeType === Node.TEXT_NODE) {
              label.textContent = ' Copied';
            }
          } catch (error) {
            if (label && label.nodeType === Node.TEXT_NODE) {
              label.textContent = ' Failed';
            }
          }

          window.setTimeout(() => {
            this.classList.add('bg-slate-950');
            this.classList.remove('bg-emerald-600');
            if (label && label.nodeType === Node.TEXT_NODE) {
              label.textContent = ' Tap Copy';
            }
          }, 1800);
        });
      });
    })();
  </script>

</body>
</html>


