<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/csrf.php';
require_once __DIR__ . '/../../../src/helpers.php';

require_admin();

$error   = get_flash('error');
$success = get_flash('success');

// Toggle active/disabled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u) {
            $newStatus = $u['status'] === 'active' ? 'disabled' : 'active';
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            flash('success', 'User status updated to ' . $newStatus . '.');
        }
    } catch (Throwable) {
        flash('error', 'Failed to update user status.');
    }
    redirect('/web/public/admin/users.php');
}

// Toggle role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    // Prevent self-demotion
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        flash('error', 'You cannot change your own role.');
        redirect('/web/public/admin/users.php');
    }
    try {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u) {
            $newRole = $u['role'] === 'admin' ? 'user' : 'admin';
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $id]);
            flash('success', 'User role updated to ' . $newRole . '.');
        }
    } catch (Throwable) {
        flash('error', 'Failed to update user role.');
    }
    redirect('/web/public/admin/users.php');
}

// Update balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_balance') {
    csrf_verify();
    $id      = (int)($_POST['id'] ?? 0);
    $balance = (float)($_POST['balance'] ?? 0);
    if ($id <= 0 || $balance < 0) {
        flash('error', 'Invalid balance value.');
        redirect('/web/public/admin/users.php');
    }
    try {
        db()->prepare('UPDATE users SET balance = ? WHERE id = ?')->execute([$balance, $id]);
        flash('success', 'Balance updated.');
    } catch (Throwable) {
        flash('error', 'Failed to update balance.');
    }
    redirect('/web/public/admin/users.php');
}

$users = [];
try {
    $users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management – 3Commas Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-800 text-white min-h-screen">
<div class="flex min-h-screen">
  <aside class="w-64 bg-slate-900 min-h-screen p-4 flex-shrink-0">
    <div class="text-emerald-400 font-bold text-xl mb-8">3Commas Admin</div>
    <nav class="space-y-1">
      <a href="/web/public/admin/index.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">Dashboard</a>
      <a href="/web/public/admin/plans.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">Plans</a>
      <a href="/web/public/admin/addresses.php"   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">Addresses</a>
      <a href="/web/public/admin/withdrawals.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">Withdrawals</a>
      <a href="/web/public/admin/users.php"       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-emerald-400">Users</a>
      <hr class="border-slate-700 my-3">
      <a href="/web/public/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 transition">Logout</a>
    </nav>
  </aside>

  <main class="flex-1 bg-slate-800 p-6">
    <h1 class="text-2xl font-bold text-white mb-6">Users (<?= count($users) ?>)</h1>

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-4"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4"><?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="bg-slate-700 rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-600/50">
            <tr>
              <th class="text-left text-slate-400 font-medium px-4 py-3">User</th>
              <th class="text-center text-slate-400 font-medium px-4 py-3">Role</th>
              <th class="text-center text-slate-400 font-medium px-4 py-3">Status</th>
              <th class="text-right text-slate-400 font-medium px-4 py-3">Balance</th>
              <th class="text-left text-slate-400 font-medium px-4 py-3">Joined</th>
              <th class="text-right text-slate-400 font-medium px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="6" class="text-center text-slate-400 py-8">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
            <tr class="border-t border-slate-600 hover:bg-slate-600/20 transition">
              <td class="px-4 py-3">
                <p class="font-semibold text-white"><?= sanitize($u['name']) ?></p>
                <p class="text-xs text-slate-400"><?= sanitize($u['email']) ?></p>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="text-xs px-2 py-0.5 rounded-full <?= $u['role']==='admin' ? 'text-purple-400 bg-purple-500/10' : 'text-slate-300 bg-slate-600' ?>">
                  <?= ucfirst($u['role']) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <span class="text-xs px-2 py-0.5 rounded-full <?= $u['status']==='active' ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10' ?>">
                  <?= ucfirst($u['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <form method="POST" action="/web/public/admin/users.php" class="flex items-center justify-end gap-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_balance">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <input type="number" name="balance" step="0.01" min="0"
                    value="<?= sanitize(number_format((float)$u['balance'], 2, '.', '')) ?>"
                    class="w-24 bg-slate-600 border border-slate-500 text-white rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-emerald-500">
                  <button type="submit" class="text-emerald-400 hover:text-emerald-300 text-xs bg-emerald-500/10 px-2 py-1 rounded transition">Set</button>
                </form>
              </td>
              <td class="px-4 py-3 text-xs text-slate-400"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td class="px-4 py-3">
                <div class="flex gap-2 justify-end">
                  <!-- Toggle Status -->
                  <form method="POST" action="/web/public/admin/users.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit"
                      class="text-xs px-2 py-1 rounded transition <?= $u['status']==='active' ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30' : 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30' ?>">
                      <?= $u['status']==='active' ? 'Disable' : 'Enable' ?>
                    </button>
                  </form>
                  <!-- Toggle Role -->
                  <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                  <form method="POST" action="/web/public/admin/users.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_role">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit"
                      class="text-xs px-2 py-1 rounded transition bg-purple-500/20 text-purple-400 hover:bg-purple-500/30">
                      <?= $u['role']==='admin' ? '→ User' : '→ Admin' ?>
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
</body>
</html>
