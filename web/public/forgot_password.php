<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/email.php';

if (is_logged_in()) {
    redirect('app/index.php');
}

$error   = get_flash('error');
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('forgot_password.php');
    }

    try {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? AND status = ? LIMIT 1');
        $stmt->execute([$email, 'active']);
        $user = $stmt->fetch();

        // Always show success to avoid email enumeration
        if ($user) {
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $ins = $pdo->prepare(
                'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)'
            );
            $ins->execute([$email, $token, $expiresAt]);

            try {
                send_password_reset_email($email, $token, $user['name']);
            } catch (Throwable) {}
        }

        flash('success', 'If that email exists in our system, a reset link has been sent.');
        redirect('forgot_password.php');
    } catch (Throwable) {
        flash('error', 'A system error occurred. Please try again.');
        redirect('forgot_password.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <a href="index.php" class="text-3xl font-extrabold text-emerald-400">3Commas</a>
      <p class="text-slate-400 mt-2">Reset your password</p>
    </div>

    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 shadow-xl">

      <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          <?= sanitize($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3 mb-6">
          <?= sanitize($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="forgot_password.php" class="space-y-5">
        <?= csrf_field() ?>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5" for="email">Email address</label>
          <input id="email" type="email" name="email" required autocomplete="email"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-500"
            placeholder="you@example.com">
        </div>

        <button type="submit"
          class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20">
          Send Reset Link
        </button>
      </form>

      <p class="text-center text-slate-400 text-sm mt-6">
        <a href="login.php" class="text-emerald-400 hover:text-emerald-300 transition">&larr; Back to Login</a>
      </p>
    </div>
  </div>

</body>
</html>
