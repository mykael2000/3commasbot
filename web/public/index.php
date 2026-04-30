<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

// Already logged in → go to dashboard
if (is_logged_in()) {
    redirect('app/index.php');
}

$error = get_flash('error');
$activeAuthTab = $_GET['tab'] ?? 'signin';

if (!in_array($activeAuthTab, ['signin', 'signup'], true)) {
  $activeAuthTab = 'signin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

  $authMode = $_POST['auth_mode'] ?? 'signin';

  if ($authMode === 'signup') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
      flash('error', 'All fields are required.');
      redirect('index.php?tab=signup');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      flash('error', 'Please enter a valid email address.');
      redirect('index.php?tab=signup');
    }

    if (strlen($password) < 8) {
      flash('error', 'Password must be at least 8 characters long.');
      redirect('index.php?tab=signup');
    }

    if ($password !== $confirm) {
      flash('error', 'Passwords do not match.');
      redirect('index.php?tab=signup');
    }

    try {
      $pdo = db();

      $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $check->execute([$email]);

      if ($check->fetch()) {
        flash('error', 'An account with that email already exists.');
        redirect('index.php?tab=signup');
      }

      $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
      $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status, balance) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$name, $email, $hashed, 'user', 'active', 0.0]);
      $userId = (int) $pdo->lastInsertId();

      $user = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
      $user->execute([$userId]);
      login_user($user->fetch());

      redirect('app/index.php');
    } catch (Throwable $e) {
      flash('error', 'Registration failed. Please try again.');
      redirect('index.php?tab=signup');
    }
  }

  $email    = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
    redirect('index.php?tab=signin');
    }

    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Invalid email or password.');
      redirect('index.php?tab=signin');
        }

        if ($user['status'] === 'disabled') {
            flash('error', 'Your account has been disabled. Please contact support.');
      redirect('index.php?tab=signin');
        }

        login_user($user);

        if ($remember) {
            // Extend cookie lifetime to 30 days
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        redirect('app/index.php');
    } catch (Throwable $e) {
        flash('error', 'A system error occurred. Please try again.');
        redirect('index.php?tab=signin');
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>3Commas – Automated Crypto Trading</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: '#10b981'
          }
        }
      }
    }
  </script>
</head>
<body class="bg-white text-slate-900 antialiased">

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
    <a href="../public/index.php" class="text-2xl font-extrabold text-slate-900 tracking-tight">
      <svg aria-labelledby="logo" class="Logo_logo__5KyRR" width="150px" height="150px" viewBox="0 0 125 31" fill="none" xmlns="http://www.w3.org/2000/svg"><text id="logo" class="visually-hidden" font-size="0">3Commas logo, link to main page</text><g fill-rule="evenodd"><path fill="currentColor" d="M30.795 0v30.918H0V0z" style="color: #05ab8c;"></path><path fill="currentColor" d="M20.354 19.093h3.167a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.074-.14a.2.2 0 00-.177-.106h-4.024a.2.2 0 00-.198.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.197.237zm-7.804 0h3.155a.2.2 0 00.19-.137l1.144-3.417a.2.2 0 00.002-.007l1.004-3.434a.2.2 0 00-.015-.15l-.076-.14a.2.2 0 00-.176-.106h-4.054a.2.2 0 00-.198.168l-.592 3.664v.003l-.58 3.321a.2.2 0 00.196.235zm-7.594 0h3.168a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.075-.14a.2.2 0 00-.176-.106H6.158a.2.2 0 00-.197.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.196.237z" style="color: #fff;"></path><path d="M47.384 18.37c0 2.589-1.979 4.338-5.164 4.338-1.66 0-3.253-.5-4.14-1.363l.978-1.885c.66.704 1.706 1.09 2.866 1.09 1.729 0 2.776-.886 2.776-2.18s-1.024-2.112-2.594-2.112c-.705 0-1.296.136-1.842.431l-.705-1.294 3.73-4.27h-4.617V8.99h7.984v1.613l-3.503 3.725c2.571.045 4.231 1.68 4.231 4.042zm.842-2.657c0-4.156 2.866-6.904 7.188-6.904 2.207 0 4.004.727 5.346 2.18l-1.638 1.635c-.774-.976-2.07-1.68-3.685-1.68-2.73 0-4.55 1.93-4.55 4.792 0 2.906 1.843 4.837 4.573 4.837 1.842 0 3.093-.818 3.958-2.135l1.751 1.544c-1.296 1.772-3.275 2.726-5.755 2.726-4.299 0-7.188-2.794-7.188-6.995zm13.193 1.885c0-3.066 2.116-5.11 5.301-5.11 3.162 0 5.277 2.044 5.277 5.11 0 3.066-2.115 5.132-5.277 5.132-3.162-.022-5.301-2.066-5.301-5.132zm7.985 0c0-1.794-1.092-2.975-2.684-2.975-1.638 0-2.707 1.181-2.707 2.975s1.091 2.975 2.707 2.975c1.615 0 2.684-1.181 2.684-2.975zm19.404-1.272v6.2h-2.502v-5.791c0-1.272-.796-2.112-2.025-2.112-1.205 0-2.024.84-2.024 2.112v5.791h-2.503v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.046 1.181c.705-.886 1.751-1.363 2.957-1.363 1.297 0 2.343.545 2.98 1.476.705-.976 1.865-1.476 3.185-1.476 2.411 0 4.026 1.544 4.026 3.838zm17.242 0v6.2h-2.5v-5.791c0-1.272-.8-2.112-2.03-2.112-1.2 0-2.021.84-2.021 2.112v5.791h-2.502v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.045 1.181c.706-.886 1.752-1.363 2.958-1.363 1.296 0 2.343.545 2.98 1.476.705-.976 1.86-1.476 3.18-1.476 2.44 0 4.03 1.544 4.03 3.838zm9.85 0v6.2h-2.39l-.04-1.408c-.66 1.022-1.8 1.59-3.01 1.59-2.04 0-3.43-1.227-3.43-3.066 0-1.908 1.68-3.157 4.21-3.157.68 0 1.43.068 2.18.227v-.182c0-1.317-.89-2.112-2.39-2.112-.93 0-1.68.273-2.29.795l-1.1-1.453c1.07-.863 2.3-1.272 4.03-1.272 2.53 0 4.23 1.522 4.23 3.838zm-2.5 2.09a9.19 9.19 0 00-1.87-.205c-1.18 0-1.93.545-1.93 1.385 0 .795.52 1.34 1.55 1.34 1.16 0 2.25-.908 2.25-2.52zm3.73 3.134l.93-1.499c.94.545 1.87.726 2.84.726.92 0 1.55-.386 1.55-.976 0-.591-.7-.931-1.68-1.181l-.82-.227c-1.66-.432-2.8-1.09-2.8-2.635 0-1.953 1.55-3.247 3.89-3.247 1.48 0 2.75.318 3.73.976l-1.04 1.635a5.218 5.218 0 00-2.51-.635c-.88 0-1.52.34-1.52.885 0 .591.61.863 1.48 1.09l.82.228c1.68.431 3.02 1.203 3.02 2.952 0 1.862-1.64 3.111-4.12 3.111-1.54-.045-2.86-.431-3.77-1.203z" fill="currentColor" style="color: #334155;"></path></g></svg></a>
    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
      <a href="#features" class="hover:text-slate-900 transition">Features</a>
      <a href="#pricing" class="hover:text-slate-900 transition">Pricing</a>
      <a href="../login.php" class="hover:text-slate-900 transition">Login</a>
      <a href="../register.php" class="bg-emerald-500 hover:bg-emerald-400 text-white px-4 py-2 rounded-lg transition font-semibold shadow-sm">Get Started</a>
    </nav>
    <!-- Mobile hamburger -->
    <button id="mobileMenuBtn" class="md:hidden text-slate-700 hover:text-slate-900 focus:outline-none">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>
  <!-- Mobile menu -->
  <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-slate-200 px-4 py-4 space-y-3 shadow-sm">
    <a href="#features" class="block text-slate-600 hover:text-slate-900">Features</a>
    <a href="#pricing" class="block text-slate-600 hover:text-slate-900">Pricing</a>
    <a href="../public/login.php" class="block text-slate-600 hover:text-slate-900">Login</a>
    <a href="../public/register.php" class="block bg-emerald-500 text-white px-4 py-2 rounded-lg text-center font-semibold">Get Started</a>
  </div>
</header>

<!-- ============================================================
     AUTH SWITCHER
     ============================================================ -->
<section class="relative py-1 md:py-2">
  <div class="max-w-5xl mx-auto px-4 sm:px-6">
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xl shadow-slate-200/60 overflow-hidden">
      <div class="p-4 sm:p-6 border-b border-slate-200 bg-slate-50/80">
        <div class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1">
          <button type="button" class="auth-tab-btn rounded-xl px-4 py-3 text-sm font-semibold transition" data-auth-tab="signin">Sign in</button>
          <button type="button" class="auth-tab-btn rounded-xl px-4 py-3 text-sm font-semibold transition" data-auth-tab="signup">Sign up</button>
        </div>
      </div>

      <div class="p-2 sm:p-4">
        <?php if ($error): ?>
          <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3 mb-5">
            <?= sanitize($error) ?>
          </div>
        <?php endif; ?>

        <div id="authPanels">
          <div class="auth-panel" data-auth-panel="signin">
            <div class="grid lg:grid-cols-2 gap-6 items-stretch">
              <div class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white border border-slate-200 p-6 lg:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Welcome back</p>
                <h2 class="mt-3 text-sm font-bold text-slate-900">Sign in to manage your trading, portfolios, and automation.</h2>
              </div>

              <div class="bg-white border border-slate-200 rounded-2xl p-2 sm:p-4">
                <form method="POST" action="" class="space-y-4">
                  <?= csrf_field() ?>
                  <input type="hidden" name="auth_mode" value="signin">

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signin_email">Email address</label>
                    <input id="signin_email" type="email" name="email" required autocomplete="email"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="you@example.com">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signin_password">Password</label>
                    <input id="signin_password" type="password" name="password" required autocomplete="current-password"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="••••••••">
                  </div>

                  <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                      <input type="checkbox" name="remember" class="w-4 h-4 accent-emerald-500">
                      Remember me
                    </label>
                    <a href="forgot_password.php" class="text-emerald-600 hover:text-emerald-500 transition">Forgot password?</a>
                  </div>

                  <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 text-base">
                    Access Platform
                  </button>
                </form>
              </div>
            </div>
          </div>

          <div class="auth-panel hidden" data-auth-panel="signup">
            <div class="grid lg:grid-cols-2 gap-6 items-stretch">
              <div class="rounded-2xl bg-gradient-to-br from-sky-50 to-white border border-slate-200 p-6 lg:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Get started</p>
                <h2 class="mt-3 text-sm font-bold text-slate-900">Create your free account and begin trading with automation.</h2>
              </div>

              <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6">
                <form method="POST" action="" class="space-y-4">
                  <?= csrf_field() ?>
                  <input type="hidden" name="auth_mode" value="signup">

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signup_name">Full name</label>
                    <input id="signup_name" type="text" name="name" required autocomplete="name"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="John Doe">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signup_email">Email address</label>
                    <input id="signup_email" type="email" name="email" required autocomplete="email"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="you@example.com">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signup_password">Password</label>
                    <input id="signup_password" type="password" name="password" required autocomplete="new-password"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="Min. 8 characters">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="signup_password_confirm">Confirm password</label>
                    <input id="signup_password_confirm" type="password" name="password_confirm" required autocomplete="new-password"
                      class="w-full bg-white border border-slate-300 text-slate-900 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-400"
                      placeholder="Repeat password">
                  </div>

                  <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 text-base">
                    Create Account
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="relative overflow-hidden bg-gradient-to-b from-white via-emerald-50/40 to-white">
  <!-- Background grid decoration -->
  <div class="absolute inset-0 opacity-20">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#cbd5e1" stroke-width="0.5"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)"/>
    </svg>
  </div>
  <div class="relative mx-auto px-4 sm:px-6 text-center">
    <!-- <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm px-4 py-1.5 rounded-full mb-6">
      <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
      
    </div> -->
    <!-- Stats row -->
    <!-- <div class="mt-16 grid grid-cols-3 gap-6 max-w-2xl mx-auto">
      <div>
        <div class="text-3xl font-extrabold text-emerald-400">$2.4B+</div>
        <div class="text-slate-400 text-sm">Trading Volume</div>
      </div>
      <div>
        <div class="text-3xl font-extrabold text-emerald-400">120K+</div>
        <div class="text-slate-400 text-sm">Active Traders</div>
      </div>
      <div>
        <div class="text-3xl font-extrabold text-emerald-400">99.9%</div>
        <div class="text-slate-400 text-sm">Uptime</div>
      </div>
    </div> -->
  </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="border-t border-slate-200 bg-slate-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <!-- <div class="flex items-center gap-3">
        <a href="../public/index.php" class="text-2xl font-extrabold text-slate-900 tracking-tight">
          <svg aria-labelledby="logo-footer" class="Logo_logo__5KyRR h-8 w-auto" viewBox="0 0 125 31" fill="none" xmlns="http://www.w3.org/2000/svg"><text id="logo-footer" class="visually-hidden" font-size="0">3Commas logo, link to main page</text><g fill-rule="evenodd"><path fill="currentColor" d="M30.795 0v30.918H0V0z" style="color: #0f172a;"></path><path fill="currentColor" d="M20.354 19.093h3.167a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.074-.14a.2.2 0 00-.177-.106h-4.024a.2.2 0 00-.198.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.197.237zm-7.804 0h3.155a.2.2 0 00.19-.137l1.144-3.417a.2.2 0 00.002-.007l1.004-3.434a.2.2 0 00-.015-.15l-.076-.14a.2.2 0 00-.176-.106h-4.054a.2.2 0 00-.198.168l-.592 3.664v.003l-.58 3.321a.2.2 0 00.196.235zm-7.594 0h3.168a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.075-.14a.2.2 0 00-.176-.106H6.158a.2.2 0 00-.197.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.196.237z" style="color: #10b981;"></path><path d="M47.384 18.37c0 2.589-1.979 4.338-5.164 4.338-1.66 0-3.253-.5-4.14-1.363l.978-1.885c.66.704 1.706 1.09 2.866 1.09 1.729 0 2.776-.886 2.776-2.18s-1.024-2.112-2.594-2.112c-.705 0-1.296.136-1.842.431l-.705-1.294 3.73-4.27h-4.617V8.99h7.984v1.613l-3.503 3.725c2.571.045 4.231 1.68 4.231 4.042zm.842-2.657c0-4.156 2.866-6.904 7.188-6.904 2.207 0 4.004.727 5.346 2.18l-1.638 1.635c-.774-.976-2.07-1.68-3.685-1.68-2.73 0-4.55 1.93-4.55 4.792 0 2.906 1.843 4.837 4.573 4.837 1.842 0 3.093-.818 3.958-2.135l1.751 1.544c-1.296 1.772-3.275 2.726-5.755 2.726-4.299 0-7.188-2.794-7.188-6.995zm13.193 1.885c0-3.066 2.116-5.11 5.301-5.11 3.162 0 5.277 2.044 5.277 5.11 0 3.066-2.115 5.132-5.277 5.132-3.162-.022-5.301-2.066-5.301-5.132zm7.985 0c0-1.794-1.092-2.975-2.684-2.975-1.638 0-2.707 1.181-2.707 2.975s1.091 2.975 2.707 2.975c1.615 0 2.684-1.181 2.684-2.975zm19.404-1.272v6.2h-2.502v-5.791c0-1.272-.796-2.112-2.025-2.112-1.205 0-2.024.84-2.024 2.112v5.791h-2.503v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.046 1.181c.705-.886 1.751-1.363 2.957-1.363 1.297 0 2.343.545 2.98 1.476.705-.976 1.865-1.476 3.185-1.476 2.411 0 4.026 1.544 4.026 3.838zm17.242 0v6.2h-2.5v-5.791c0-1.272-.8-2.112-2.03-2.112-1.2 0-2.021.84-2.021 2.112v5.791h-2.502v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.045 1.181c.706-.886 1.752-1.363 2.958-1.363 1.296 0 2.343.545 2.98 1.476.705-.976 1.86-1.476 3.18-1.476 2.44 0 4.03 1.544 4.03 3.838zm9.85 0v6.2h-2.39l-.04-1.408c-.66 1.022-1.8 1.59-3.01 1.59-2.04 0-3.43-1.227-3.43-3.066 0-1.908 1.68-3.157 4.21-3.157.68 0 1.43.068 2.18.227v-.182c0-1.317-.89-2.112-2.39-2.112-.93 0-1.68.273-2.29.795l-1.1-1.453c1.07-.863 2.3-1.272 4.03-1.272 2.53 0 4.23 1.522 4.23 3.838zm-2.5 2.09a9.19 9.19 0 00-1.87-.205c-1.18 0-1.93.545-1.93 1.385 0 .795.52 1.34 1.55 1.34 1.16 0 2.25-.908 2.25-2.52zm3.73 3.134l.93-1.499c.94.545 1.87.726 2.84.726.92 0 1.55-.386 1.55-.976 0-.591-.7-.931-1.68-1.181l-.82-.227c-1.66-.432-2.8-1.09-2.8-2.635 0-1.953 1.55-3.247 3.89-3.247 1.48 0 2.75.318 3.73.976l-1.04 1.635a5.218 5.218 0 00-2.51-.635c-.88 0-1.52.34-1.52.885 0 .591.61.863 1.48 1.09l.82.228c1.68.431 3.02 1.203 3.02 2.952 0 1.862-1.64 3.111-4.12 3.111-1.54-.045-2.86-.431-3.77-1.203z" fill="currentColor" style="color: #334155;"></path></g></svg></a>
        </a>
      </div> -->
      <div class="text-sm text-slate-500">
        <span>3D Trade Tech Ltd., registration number 2164568, address Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands</span>
        <!-- <div class="mt-2 text-slate-400">&copy; <?= date('Y') ?> 3Commas. All rights reserved.</div> -->
      </div>
    </div>
  </div>
</footer>

<script>
  const initialAuthTab = <?= json_encode($activeAuthTab) ?>;

  function setAuthTab(tab) {
    document.querySelectorAll('[data-auth-panel]').forEach((panel) => {
      panel.classList.toggle('hidden', panel.dataset.authPanel !== tab);
    });

    document.querySelectorAll('.auth-tab-btn').forEach((button) => {
      const isActive = button.dataset.authTab === tab;
      button.classList.toggle('bg-white', isActive);
      button.classList.toggle('text-slate-900', isActive);
      button.classList.toggle('shadow-sm', isActive);
      button.classList.toggle('text-slate-500', !isActive);
    });
  }

  document.querySelectorAll('.auth-tab-btn').forEach((button) => {
    button.addEventListener('click', () => setAuthTab(button.dataset.authTab));
  });

  setAuthTab(initialAuthTab);

  document.getElementById('mobileMenuBtn').addEventListener('click', () => {
    document.getElementById('mobileMenu').classList.toggle('hidden');
  });
</script>
</body>
</html>
