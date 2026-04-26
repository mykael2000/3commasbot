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
  <style>
    .gold-btn { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%); }
    .gold-btn:hover { background: linear-gradient(135deg, #fbbf24 0%, #fde68a 50%, #fbbf24 100%); }
    .auth-card { background: linear-gradient(145deg, #0d1117 0%, #161b27 100%); }
  </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <a href="index.php" class="text-2xl font-extrabold text-emerald-400 tracking-tight">3Commas</a>
    </div>

    <div class="auth-card border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
      <div class="h-1 bg-gradient-to-r from-amber-500 via-yellow-300 to-amber-500"></div>

      <div class="p-8">
        <div class="text-center mb-7">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/15 mb-3">
            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-white">Reset Password</h2>
          <p class="text-slate-400 text-sm mt-1">Enter your email to receive a reset link</p>
        </div>

        <?php if ($error): ?>
          <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-xl px-4 py-3 mb-5">
            <?= sanitize($error) ?>
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl px-4 py-3 mb-5">
            <?= sanitize($success) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php" class="space-y-4">
          <?= csrf_field() ?>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="email">Email Address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
              </div>
              <input id="email" type="email" name="email" required autocomplete="email"
                class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                placeholder="you@example.com">
            </div>
          </div>

          <button type="submit"
            class="gold-btn w-full text-gray-900 font-bold py-3.5 rounded-xl transition shadow-lg shadow-amber-500/20 text-base mt-2">
            Send Reset Link
          </button>
        </form>

        <p class="text-center text-slate-400 text-sm mt-5">
          <a href="index.php" class="text-amber-400 hover:text-amber-300 transition">&larr; Back to Login</a>
        </p>
      </div>
    </div>
  </div>

</body>
</html>

