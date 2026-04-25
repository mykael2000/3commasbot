<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/helpers.php';

// Already logged in → go to dashboard
if (is_logged_in()) {
    redirect('/web/public/app/index.php');
}

$error = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('/web/public/login.php');
    }

    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Invalid email or password.');
            redirect('/web/public/login.php');
        }

        if ($user['status'] === 'disabled') {
            flash('error', 'Your account has been disabled. Please contact support.');
            redirect('/web/public/login.php');
        }

        login_user($user);

        if ($remember) {
            // Extend cookie lifetime to 30 days
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        redirect('/web/public/app/index.php');
    } catch (Throwable $e) {
        flash('error', 'A system error occurred. Please try again.');
        redirect('/web/public/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="/web/public/index.php" class="text-3xl font-extrabold text-emerald-400">3Commas</a>
      <p class="text-slate-400 mt-2">Sign in to your account</p>
    </div>

    <!-- Card -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 shadow-xl">

      <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          <?= sanitize($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/web/public/login.php" class="space-y-5">
        <?= csrf_field() ?>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5" for="email">Email address</label>
          <input id="email" type="email" name="email" required autocomplete="email"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-500"
            placeholder="you@example.com">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
          <input id="password" type="password" name="password" required autocomplete="current-password"
            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-500"
            placeholder="••••••••">
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
            <input type="checkbox" name="remember" class="w-4 h-4 accent-emerald-500">
            Remember me
          </label>
          <a href="/web/public/forgot_password.php" class="text-emerald-400 hover:text-emerald-300 transition">Forgot password?</a>
        </div>

        <button type="submit"
          class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 text-base">
          Sign In
        </button>
      </form>

      <p class="text-center text-slate-400 text-sm mt-6">
        Don't have an account?
        <a href="/web/public/register.php" class="text-emerald-400 hover:text-emerald-300 transition font-medium">Create one free</a>
      </p>
    </div>
  </div>

</body>
</html>
