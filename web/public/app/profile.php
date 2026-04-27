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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']     ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, $user['password'])) {
        flash('error', 'Current password is incorrect.');
        redirect('/web/public/app/profile.php');
    }

    if (strlen($newPw) < 8) {
        flash('error', 'New password must be at least 8 characters.');
        redirect('/web/public/app/profile.php');
    }

    if ($newPw !== $confirm) {
        flash('error', 'New passwords do not match.');
        redirect('/web/public/app/profile.php');
    }

    try {
        $hashed = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashed, $user['id']]);
        flash('success', 'Password updated successfully!');
    } catch (Throwable) {
        flash('error', 'Failed to update password. Please try again.');
    }
    redirect('/web/public/app/profile.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen pb-20">

  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800 px-4 py-3 flex items-center justify-between">
    <span class="text-xl font-extrabold text-emerald-400">Profile</span>
    <a href="/web/public/logout.php" class="text-red-400 hover:text-red-300 transition text-sm">Logout</a>
  </header>

  <main class="max-w-lg mx-auto px-4 py-6 space-y-6">

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- User Info -->
    <div class="bg-slate-800 rounded-2xl p-6">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center text-2xl font-extrabold text-emerald-400">
          <?= strtoupper(mb_substr($user['name'], 0, 1)) ?>
        </div>
        <div>
          <h2 class="text-xl font-bold text-white"><?= sanitize($user['name']) ?></h2>
          <p class="text-slate-400 text-sm"><?= sanitize($user['email']) ?></p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="bg-slate-700 rounded-xl p-3">
          <p class="text-slate-400">Role</p>
          <p class="font-semibold text-white capitalize mt-0.5"><?= sanitize($user['role']) ?></p>
        </div>
        <div class="bg-slate-700 rounded-xl p-3">
          <p class="text-slate-400">Status</p>
          <p class="font-semibold capitalize mt-0.5 <?= $user['status']==='active' ? 'text-emerald-400' : 'text-red-400' ?>">
            <?= sanitize($user['status']) ?>
          </p>
        </div>
        <div class="bg-slate-700 rounded-xl p-3">
          <p class="text-slate-400">Balance</p>
          <p class="font-semibold text-emerald-400 mt-0.5">$<?= number_format((float)$user['balance'], 2) ?></p>
        </div>
        <div class="bg-slate-700 rounded-xl p-3">
          <p class="text-slate-400">Member Since</p>
          <p class="font-semibold text-white mt-0.5"><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="bg-slate-800 rounded-2xl p-6">
      <h3 class="font-bold text-white text-lg mb-5">Change Password</h3>

      <form method="POST" action="/web/public/app/profile.php" class="space-y-4">
        <?= csrf_field() ?>

        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Current Password</label>
          <input type="password" name="current_password" required autocomplete="current-password"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-500"
            placeholder="••••••••">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">New Password</label>
          <input type="password" name="new_password" required autocomplete="new-password"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-500"
            placeholder="Min. 8 characters">
        </div>
        <div>
          <label class="block text-sm text-slate-400 mb-1.5">Confirm New Password</label>
          <input type="password" name="confirm_password" required autocomplete="new-password"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 placeholder-slate-500"
            placeholder="Repeat new password">
        </div>

        <button type="submit"
          class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition">
          Update Password
        </button>
      </form>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
    <div class="text-center">
      <a href="/web/public/admin/index.php" class="inline-flex items-center gap-2 bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 px-5 py-2.5 rounded-xl transition font-medium text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Admin Dashboard
      </a>
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
    <a href="/web/public/app/trading.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      Trade
    </a>
    <a href="/web/public/app/wallet.php" class="flex flex-col items-center text-xs text-slate-400 hover:text-emerald-400 transition gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Wallet
    </a>
    <a href="/web/public/app/profile.php" class="flex flex-col items-center text-xs text-emerald-400 gap-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile
    </a>
  </nav>

</body>
</html>
