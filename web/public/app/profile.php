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

// ── Handle personal info update ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    csrf_verify();
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $country = trim($_POST['country'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        flash('error', 'Name is required.');
        redirect('profile.php');
    }

    try {
        db()->prepare(
            'UPDATE users SET name = ?, phone = ?, country = ?, address = ? WHERE id = ?'
        )->execute([$name, $phone ?: null, $country ?: null, $address ?: null, $user['id']]);
        $_SESSION['name'] = $name;
        flash('success', 'Personal information updated.');
    } catch (Throwable) {
        flash('error', 'Failed to update information. Please try again.');
    }
    redirect('profile.php');
}

// ── Handle password change ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    csrf_verify();
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']     ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, $user['password'])) {
        flash('error', 'Current password is incorrect.');
        redirect('profile.php');
    }
    if (strlen($newPw) < 8) {
        flash('error', 'New password must be at least 8 characters.');
        redirect('profile.php');
    }
    if ($newPw !== $confirm) {
        flash('error', 'Passwords do not match.');
        redirect('profile.php');
    }
    try {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')
             ->execute([password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]), $user['id']]);
        flash('success', 'Password updated successfully.');
    } catch (Throwable) {
        flash('error', 'Failed to update password. Please try again.');
    }
    redirect('profile.php');
}

// Reload fresh user data after any POST
$user = current_user();

// Fetch KYC status
$kyc = null;
try {
    $stmt = db()->prepare('SELECT * FROM kyc_submissions WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $kyc = $stmt->fetch() ?: null;
} catch (Throwable) {}
$kycStatus = $kyc['status'] ?? 'unverified';

// Fetch active VIP subscription
$activeSub = null;
try {
    $stmt = db()->prepare(
        'SELECT up.*, ip.name AS plan_name, ip.roi_percent
         FROM user_plans up
         JOIN investment_plans ip ON ip.id = up.plan_id
         WHERE up.user_id = ? AND up.status = "active"
         ORDER BY up.created_at DESC LIMIT 1'
    );
    $stmt->execute([$user['id']]);
    $activeSub = $stmt->fetch() ?: null;
} catch (Throwable) {}

$kycColor = match($kycStatus) {
    'verified' => 'bg-emerald-50 text-emerald-600',
    'pending'  => 'bg-amber-50 text-amber-600',
    'rejected' => 'bg-red-50 text-red-600',
    default    => 'bg-slate-100 text-slate-500',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen pb-24 md:pb-6">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center justify-between md:hidden">
    <span class="text-xl font-extrabold text-emerald-400">Account</span>
    <a href="../logout.php" class="text-red-500 hover:text-red-600 transition text-sm">Logout</a>
  </header>

  <?php $activePage = 'profile.php'; include '_nav.php'; ?>

  <main class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- ── User Summary ──────────────────────────────────────────────── -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex items-center gap-4">
      <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center text-2xl font-extrabold text-emerald-400 flex-shrink-0">
        <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
      </div>
      <div class="flex-1 min-w-0">
        <h2 class="text-lg font-bold text-slate-900 truncate"><?= sanitize($user['name']) ?></h2>
        <p class="text-slate-500 text-sm truncate"><?= sanitize($user['email']) ?></p>
        <div class="flex flex-wrap items-center gap-2 mt-1.5">
          <span class="text-xs px-2 py-0.5 rounded-full <?= $user['status']==='active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?> font-medium capitalize"><?= sanitize($user['status']) ?></span>
          <span class="text-xs px-2 py-0.5 rounded-full <?= $kycColor ?> font-medium">KYC: <?= sanitize(ucfirst($kycStatus)) ?></span>
          <span class="text-xs text-slate-400">Since <?= date('M Y', strtotime($user['created_at'])) ?></span>
        </div>
      </div>
      <?php if ($user['role'] === 'admin'): ?>
      <a href="../admin/index.php" class="flex-shrink-0 text-xs px-3 py-1.5 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 font-medium transition">Admin</a>
      <?php endif; ?>
    </div>

    <!-- ── Hub Grid ──────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">

      <a href="kyc.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-100 transition">
          <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">KYC</p>
        <p class="text-xs <?= $kycColor ?> mt-0.5 font-medium rounded-full"><?= sanitize(ucfirst($kycStatus)) ?></p>
      </a>

      <a href="payment_methods.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-violet-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-violet-100 transition">
          <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Payment Methods</p>
        <p class="text-xs text-slate-500 mt-0.5">Bank &amp; crypto</p>
      </a>

      <a href="vip.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-amber-100 transition">
          <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">VIP Program</p>
        <p class="text-xs text-slate-500 mt-0.5"><?= $activeSub ? sanitize($activeSub['plan_name']) : 'No active plan' ?></p>
      </a>

      <a href="trading.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-emerald-100 transition">
          <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Trade History</p>
        <p class="text-xs text-slate-500 mt-0.5">Open &amp; closed trades</p>
      </a>

      <a href="documents.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-sky-100 transition">
          <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Documents</p>
        <p class="text-xs text-slate-500 mt-0.5">Terms &amp; reports</p>
      </a>

      <a href="trading.php" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-teal-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-teal-100 transition">
          <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Auto Trading</p>
        <p class="text-xs text-slate-500 mt-0.5">Bots &amp; strategies</p>
      </a>

      <button onclick="document.getElementById('support-modal').classList.remove('hidden')"
        class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group text-left">
        <div class="w-9 h-9 bg-pink-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-pink-100 transition">
          <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Live Support</p>
        <p class="text-xs text-slate-500 mt-0.5">Chat with us</p>
      </button>

      <a href="#settings" class="bg-white border border-slate-200 rounded-2xl p-4 hover:border-emerald-300 hover:shadow-sm transition group">
        <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center mb-3 group-hover:bg-slate-200 transition">
          <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-900">Settings</p>
        <p class="text-xs text-slate-500 mt-0.5">Password &amp; security</p>
      </a>

    </div>

    <!-- ── Personal Info Edit ──────────────────────────────────────── -->
    <div id="personal-info" class="bg-white border border-slate-200 rounded-2xl p-6 scroll-mt-20">
      <h3 class="font-bold text-slate-900 text-lg mb-5">Personal Information</h3>
      <form method="POST" action="profile.php" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_info">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required value="<?= sanitize($user['name']) ?>"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">Email <span class="text-slate-400 text-xs">(cannot change)</span></label>
            <input type="email" value="<?= sanitize($user['email']) ?>" disabled
              class="w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-lg px-4 py-3 cursor-not-allowed">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">Phone</label>
            <input type="tel" name="phone" value="<?= sanitize($user['phone'] ?? '') ?>"
              placeholder="+1 234 567 8900"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">Country</label>
            <input type="text" name="country" value="<?= sanitize($user['country'] ?? '') ?>"
              placeholder="e.g. United States"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400">
          </div>
        </div>
        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Address</label>
          <textarea name="address" rows="2" placeholder="Street, City, Postal Code"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400 resize-none"><?= sanitize($user['address'] ?? '') ?></textarea>
        </div>

        <button type="submit"
          class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 px-6 rounded-xl transition">
          Save Changes
        </button>
      </form>
    </div>

    <!-- ── Settings / Password ─────────────────────────────────────── -->
    <div id="settings" class="bg-white border border-slate-200 rounded-2xl p-6 scroll-mt-20">
      <h3 class="font-bold text-slate-900 text-lg mb-5">Change Password</h3>
      <form method="POST" action="profile.php" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">

        <div>
          <label class="block text-sm text-slate-600 mb-1.5">Current Password</label>
          <input type="password" name="current_password" required autocomplete="current-password"
            class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400"
            placeholder="••••••••">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">New Password</label>
            <input type="password" name="new_password" required autocomplete="new-password"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400"
              placeholder="Min. 8 characters">
          </div>
          <div>
            <label class="block text-sm text-slate-600 mb-1.5">Confirm Password</label>
            <input type="password" name="confirm_password" required autocomplete="new-password"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-400"
              placeholder="Repeat new password">
          </div>
        </div>

        <button type="submit"
          class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 px-6 rounded-xl transition">
          Update Password
        </button>
      </form>
    </div>

  </main>

  <!-- Support Modal -->
  <div id="support-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 flex">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
      <h3 class="font-bold text-slate-900 text-lg mb-2">Live Support</h3>
      <p class="text-slate-600 text-sm mb-4">Our support team is available 24/7. Reach us via email.</p>
      <a href="mailto:support@3commas.io"
        class="block w-full text-center bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition mb-3">
        Email Support
      </a>
      <button onclick="document.getElementById('support-modal').classList.add('hidden')"
        class="block w-full text-center text-slate-600 hover:text-slate-900 text-sm py-2 transition">
        Close
      </button>
    </div>
  </div>
  <script>
    document.getElementById('support-modal').classList.add('hidden');
  </script>

</body>
</html>

