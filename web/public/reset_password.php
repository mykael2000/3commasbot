<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

$token   = trim($_GET['token'] ?? '');
$error   = get_flash('error');
$success = get_flash('success');
$valid   = false;
$reset   = null;

if ($token === '') {
    flash('error', 'Invalid or missing reset token.');
    redirect('forgot_password.php');
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    $valid = (bool) $reset;
} catch (Throwable) {
    $valid = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!$valid) {
        flash('error', 'This reset link is invalid or has expired.');
        redirect('forgot_password.php');
    }

    $newPass = $_POST['password']         ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($newPass) < 8) {
        flash('error', 'Password must be at least 8 characters.');
        redirect('reset_password.php?token=' . urlencode($token));
    }

    if ($newPass !== $confirm) {
        flash('error', 'Passwords do not match.');
        redirect('reset_password.php?token=' . urlencode($token));
    }

    try {
        $pdo    = db();
        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);

        $upd = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
        $upd->execute([$hashed, $reset['email']]);

        $mark = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
        $mark->execute([$token]);

        flash('success', 'Password updated successfully! Please log in.');
        redirect('login.php');
    } catch (Throwable) {
        flash('error', 'A system error occurred. Please try again.');
        redirect('reset_password.php?token=' . urlencode($token));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <a href="index.php" class="text-3xl font-extrabold text-emerald-400">3Commas</a>
      <p class="text-slate-400 mt-2">Set a new password</p>
    </div>

    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 shadow-xl">

      <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          <?= sanitize($error) ?>
        </div>
      <?php endif; ?>

      <?php if (!$valid): ?>
        <div class="text-center">
          <p class="text-red-400 mb-4">This reset link is invalid or has expired.</p>
          <a href="forgot_password.php" class="text-emerald-400 hover:text-emerald-300 transition">Request a new link</a>
        </div>
      <?php else: ?>
        <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>" class="space-y-5">
          <?= csrf_field() ?>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
              class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-500"
              placeholder="Min. 8 characters">
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password_confirm">Confirm new password</label>
            <input id="password_confirm" type="password" name="password_confirm" required autocomplete="new-password"
              class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-500"
              placeholder="Repeat new password">
          </div>

          <button type="submit"
            class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20">
            Update Password
          </button>
        </form>
      <?php endif; ?>

      <p class="text-center text-slate-400 text-sm mt-6">
        <a href="login.php" class="text-emerald-400 hover:text-emerald-300 transition">&larr; Back to Login</a>
      </p>
    </div>
  </div>

</body>
</html>
