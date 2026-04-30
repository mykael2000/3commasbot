<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

require_admin();
$user = current_user();

// Fetch stats
$stats = ['users' => 0, 'pending_withdrawals' => 0, 'pending_deposits' => 0, 'active_plans' => 0];
try {
    $pdo = db();
    $stats['users']               = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['pending_withdrawals'] = (int) $pdo->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status='pending'")->fetchColumn();
    $stats['pending_deposits']    = (int) $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status='pending'")->fetchColumn();
    $stats['active_plans']        = (int) $pdo->query("SELECT COUNT(*) FROM user_plans WHERE status='active'")->fetchColumn();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-800 text-white min-h-screen">

  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 min-h-screen p-4 flex-shrink-0">
      <div class="text-white font-bold text-xl mb-8 text-emerald-400">3Commas Admin</div>
      <nav class="space-y-1">
        <a href="index.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-emerald-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
          Dashboard
        </a>
        <a href="plans.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Plans
        </a>
        <a href="addresses.php"   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          Addresses
        </a>
        <a href="withdrawals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
          Withdrawals
          <?php if ($stats['pending_withdrawals'] > 0): ?>
          <span class="ml-auto bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?= $stats['pending_withdrawals'] ?></span>
          <?php endif; ?>
        </a>
        <a href="users.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          Users
        </a>
        <hr class="border-slate-700 my-3">
        <a href="../app/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white transition">
          ← User Dashboard
        </a>
        <a href="../logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 transition">
          Logout
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 bg-slate-800 p-6">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Dashboard</h1>
        <p class="text-slate-400 text-sm mt-1">Welcome back, <?= sanitize($user['name']) ?>!</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-700 rounded-2xl p-5">
          <p class="text-slate-400 text-sm mb-1">Total Users</p>
          <p class="text-3xl font-extrabold text-white"><?= $stats['users'] ?></p>
        </div>
        <div class="bg-slate-700 rounded-2xl p-5">
          <p class="text-slate-400 text-sm mb-1">Pending Withdrawals</p>
          <p class="text-3xl font-extrabold <?= $stats['pending_withdrawals'] > 0 ? 'text-red-400' : 'text-white' ?>"><?= $stats['pending_withdrawals'] ?></p>
        </div>
        <div class="bg-slate-700 rounded-2xl p-5">
          <p class="text-slate-400 text-sm mb-1">Pending Deposits</p>
          <p class="text-3xl font-extrabold <?= $stats['pending_deposits'] > 0 ? 'text-yellow-400' : 'text-white' ?>"><?= $stats['pending_deposits'] ?></p>
        </div>
        <div class="bg-slate-700 rounded-2xl p-5">
          <p class="text-slate-400 text-sm mb-1">Active Plans</p>
          <p class="text-3xl font-extrabold text-emerald-400"><?= $stats['active_plans'] ?></p>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid md:grid-cols-2 gap-4">
        <a href="withdrawals.php" class="bg-slate-700 hover:bg-slate-600 rounded-2xl p-5 transition block">
          <h3 class="font-bold text-white mb-1">Review Withdrawals</h3>
          <p class="text-slate-400 text-sm"><?= $stats['pending_withdrawals'] ?> pending requests</p>
        </a>
        <a href="users.php" class="bg-slate-700 hover:bg-slate-600 rounded-2xl p-5 transition block">
          <h3 class="font-bold text-white mb-1">Manage Users</h3>
          <p class="text-slate-400 text-sm"><?= $stats['users'] ?> total users</p>
        </a>
        <a href="plans.php" class="bg-slate-700 hover:bg-slate-600 rounded-2xl p-5 transition block">
          <h3 class="font-bold text-white mb-1">Investment Plans</h3>
          <p class="text-slate-400 text-sm">Create and manage plans</p>
        </a>
        <a href="addresses.php" class="bg-slate-700 hover:bg-slate-600 rounded-2xl p-5 transition block">
          <h3 class="font-bold text-white mb-1">Deposit Addresses</h3>
          <p class="text-slate-400 text-sm">Manage receiving addresses</p>
        </a>
      </div>
    </main>
  </div>

</body>
</html>
