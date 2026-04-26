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

$login_error = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_login'])) {
    csrf_verify();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('index.php');
    }

    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Invalid email or password.');
            redirect('index.php');
        }

        if ($user['status'] === 'disabled') {
            flash('error', 'Your account has been disabled. Please contact support.');
            redirect('index.php');
        }

        login_user($user);

        if ($remember) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        redirect('app/index.php');
    } catch (Throwable $e) {
        flash('error', 'A system error occurred. Please try again.');
        redirect('index.php');
    }
}

// Fetch active plans from DB (gracefully handle no DB)
$plans = [];
try {
    $plans = db()->query('SELECT * FROM investment_plans WHERE active = 1 ORDER BY min_deposit ASC')->fetchAll();
} catch (Throwable) {}
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
  <style>
    .gold-btn { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%); }
    .gold-btn:hover { background: linear-gradient(135deg, #fbbf24 0%, #fde68a 50%, #fbbf24 100%); }
    .login-card { background: linear-gradient(145deg, #0d1117 0%, #161b27 100%); }
  </style>
</head>
<body class="bg-slate-900 text-white antialiased">

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<header class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur border-b border-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
    <a href="index.php" class="text-2xl font-extrabold text-emerald-400 tracking-tight">3Commas</a>
    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
      <a href="#features" class="hover:text-white transition">Features</a>
      <a href="#pricing"  class="hover:text-white transition">Pricing</a>
      <a href="#login"    class="hover:text-white transition">Login</a>
      <a href="register.php" class="bg-emerald-500 hover:bg-emerald-400 text-white px-4 py-2 rounded-lg transition font-semibold">Get Started</a>
    </nav>
    <!-- Mobile hamburger -->
    <button id="mobileMenuBtn" class="md:hidden text-slate-300 hover:text-white focus:outline-none">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>
  <!-- Mobile menu -->
  <div id="mobileMenu" class="hidden md:hidden bg-slate-800 border-t border-slate-700 px-4 py-4 space-y-3">
    <a href="#features"    class="block text-slate-300 hover:text-white">Features</a>
    <a href="#pricing"     class="block text-slate-300 hover:text-white">Pricing</a>
    <a href="#login"       class="block text-slate-300 hover:text-white">Login</a>
    <a href="register.php" class="block bg-emerald-500 text-white px-4 py-2 rounded-lg text-center font-semibold">Get Started</a>
  </div>
</header>

<!-- ============================================================
     HERO + LOGIN CARD (two-column layout)
     ============================================================ -->
<section id="login" class="relative overflow-hidden py-20 md:py-28">
  <!-- Background grid decoration -->
  <div class="absolute inset-0 opacity-[0.06]">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)"/>
    </svg>
  </div>
  <!-- Ambient glow blobs -->
  <div class="absolute top-20 left-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-10 right-1/4 w-80 h-80 bg-amber-500/8 rounded-full blur-3xl pointer-events-none"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- ── Left: Hero content ── -->
      <div>
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm px-4 py-1.5 rounded-full mb-6">
          <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
          Live Trading Automation
        </div>
        <h1 class="text-5xl md:text-6xl font-extrabold leading-tight tracking-tight mb-6">
          Automated<br>
          <span class="text-emerald-400">Crypto Trading</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-lg mb-8">
          Smart bots, portfolio tracking and risk management — all in one platform built for serious crypto traders.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 mb-10">
          <a href="register.php" class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-7 py-3.5 rounded-xl text-base transition shadow-lg shadow-emerald-500/25 text-center">
            Get Started Free
          </a>
          <a href="#features" class="border border-slate-600 hover:border-slate-400 text-slate-300 hover:text-white font-bold px-7 py-3.5 rounded-xl text-base transition text-center">
            ▶ See Features
          </a>
        </div>
        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-6 max-w-sm">
          <div>
            <div class="text-2xl font-extrabold text-emerald-400">$2.4B+</div>
            <div class="text-slate-400 text-xs">Trading Volume</div>
          </div>
          <div>
            <div class="text-2xl font-extrabold text-emerald-400">120K+</div>
            <div class="text-slate-400 text-xs">Active Traders</div>
          </div>
          <div>
            <div class="text-2xl font-extrabold text-emerald-400">99.9%</div>
            <div class="text-slate-400 text-xs">Uptime</div>
          </div>
        </div>
      </div>

      <!-- ── Right: Login card ── -->
      <div class="w-full max-w-md mx-auto lg:mx-0 lg:ml-auto">
        <div class="login-card border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
          <!-- Gradient top accent bar -->
          <div class="h-1 bg-gradient-to-r from-amber-500 via-yellow-300 to-amber-500"></div>

          <div class="p-8">
            <!-- Card heading -->
            <div class="text-center mb-7">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/15 mb-3">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <h2 class="text-xl font-bold text-white">Welcome Back</h2>
              <p class="text-slate-400 text-sm mt-1">Sign in to your 3Commas account</p>
            </div>

            <?php if ($login_error): ?>
              <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-xl px-4 py-3 mb-5">
                <?= sanitize($login_error) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-4">
              <?= csrf_field() ?>
              <input type="hidden" name="_login" value="1">

              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5" for="email">Email Address</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-4.5 w-4.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                  </div>
                  <input id="email" type="email" name="email" required autocomplete="email"
                    class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                    placeholder="you@example.com">
                </div>
              </div>

              <!-- Password -->
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                      <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-11 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                    placeholder="••••••••">
                  <button type="button" id="togglePass"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition"
                    aria-label="Toggle password visibility">
                    <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                    <svg id="eyeClosed" class="hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                      <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                      <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Remember + Forgot -->
              <div class="flex items-center justify-between text-sm pt-1">
                <label class="flex items-center gap-2 text-slate-400 cursor-pointer select-none">
                  <input type="checkbox" name="remember" class="w-4 h-4 rounded border-white/20 bg-white/5 accent-amber-500">
                  Remember me
                </label>
                <a href="forgot_password.php" class="text-amber-400 hover:text-amber-300 transition">Forgot password?</a>
              </div>

              <!-- CTA button -->
              <button type="submit"
                class="gold-btn w-full text-gray-900 font-bold py-3.5 rounded-xl transition shadow-lg shadow-amber-500/20 text-base mt-2">
                Sign In
              </button>
            </form>

            <!-- Bottom helper -->
            <p class="text-center text-slate-400 text-sm mt-5">
              Don't have an account?
              <a href="register.php" class="text-amber-400 hover:text-amber-300 transition font-medium">Create account</a>
            </p>
          </div>
        </div>
      </div>
      <!-- end login card -->

    </div><!-- end grid -->
  </div>
</section>

<!-- ============================================================
     FEATURES
     ============================================================ -->
<section id="features" class="py-20 bg-slate-800/50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <h2 class="text-4xl font-extrabold text-white mb-4">Everything you need to trade smarter</h2>
      <p class="text-slate-400 text-lg max-w-2xl mx-auto">From automated bots to risk management, we've got you covered.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
      <!-- Card 1 -->
      <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 hover:border-emerald-500/50 transition group">
        <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-emerald-500/20 transition">
          <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-white mb-3">Smart Bots</h3>
        <p class="text-slate-400">Deploy DCA, Grid, and Signal bots 24/7. Automate your strategy without watching charts all day.</p>
      </div>
      <!-- Card 2 -->
      <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 hover:border-emerald-500/50 transition group">
        <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-emerald-500/20 transition">
          <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-white mb-3">Portfolio Tracking</h3>
        <p class="text-slate-400">Real-time portfolio overview across multiple exchanges. Track P&L, allocation, and performance.</p>
      </div>
      <!-- Card 3 -->
      <div class="bg-slate-800 border border-slate-700 rounded-2xl p-8 hover:border-emerald-500/50 transition group">
        <div class="w-14 h-14 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-emerald-500/20 transition">
          <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-white mb-3">Risk Management</h3>
        <p class="text-slate-400">Set stop-loss, take-profit, and trailing stops. Protect your capital with advanced risk controls.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     PRICING / PLANS
     ============================================================ -->
<section id="pricing" class="py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <h2 class="text-4xl font-extrabold text-white mb-4">Investment Plans</h2>
      <p class="text-slate-400 text-lg max-w-2xl mx-auto">Choose the plan that matches your investment goals.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
      <?php if (!empty($plans)): ?>
        <?php
          $highlights = [0 => false, 1 => true, 2 => false]; // highlight middle plan
          foreach ($plans as $i => $plan):
            $hl = $highlights[$i] ?? false;
        ?>
        <div class="relative <?= $hl ? 'bg-emerald-500/10 border-2 border-emerald-500' : 'bg-slate-800 border border-slate-700' ?> rounded-2xl p-8 flex flex-col">
          <?php if ($hl): ?>
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-xs font-bold px-4 py-1 rounded-full">MOST POPULAR</div>
          <?php endif; ?>
          <h3 class="text-2xl font-bold text-white mb-2"><?= sanitize($plan['name']) ?></h3>
          <p class="text-slate-400 text-sm mb-6"><?= sanitize($plan['description']) ?></p>
          <div class="text-4xl font-extrabold text-emerald-400 mb-1"><?= format_currency((float)$plan['roi_percent'], 2) ?>%</div>
          <div class="text-slate-400 text-sm mb-6">ROI over <?= (int)$plan['duration_days'] ?> days</div>
          <ul class="space-y-3 text-slate-300 text-sm mb-8 flex-1">
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Min: $<?= format_currency((float)$plan['min_deposit']) ?>
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Max: $<?= format_currency((float)$plan['max_deposit']) ?>
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Duration: <?= (int)$plan['duration_days'] ?> days
            </li>
          </ul>
          <a href="register.php" class="<?= $hl ? 'bg-emerald-500 hover:bg-emerald-400' : 'bg-slate-700 hover:bg-slate-600' ?> text-white font-bold px-6 py-3 rounded-xl text-center transition">
            Get Started
          </a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Placeholder cards if DB not connected -->
        <?php
          $placeholders = [
            ['name'=>'Starter',    'roi'=>'5.00',  'days'=>30, 'min'=>'100',   'max'=>'999',    'desc'=>'Perfect for beginners.'],
            ['name'=>'Growth',     'roi'=>'12.00', 'days'=>60, 'min'=>'1,000', 'max'=>'4,999',  'desc'=>'Balanced plan for growing your portfolio.', 'popular'=>true],
            ['name'=>'Pro Trader', 'roi'=>'25.00', 'days'=>90, 'min'=>'5,000', 'max'=>'99,999', 'desc'=>'High-yield plan for serious investors.'],
          ];
          foreach ($placeholders as $p):
        ?>
        <div class="relative <?= isset($p['popular']) ? 'bg-emerald-500/10 border-2 border-emerald-500' : 'bg-slate-800 border border-slate-700' ?> rounded-2xl p-8 flex flex-col">
          <?php if (isset($p['popular'])): ?>
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-xs font-bold px-4 py-1 rounded-full">MOST POPULAR</div>
          <?php endif; ?>
          <h3 class="text-2xl font-bold text-white mb-2"><?= $p['name'] ?></h3>
          <p class="text-slate-400 text-sm mb-6"><?= $p['desc'] ?></p>
          <div class="text-4xl font-extrabold text-emerald-400 mb-1"><?= $p['roi'] ?>%</div>
          <div class="text-slate-400 text-sm mb-6">ROI over <?= $p['days'] ?> days</div>
          <ul class="space-y-3 text-slate-300 text-sm mb-8 flex-1">
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Min: $<?= $p['min'] ?></li>
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Max: $<?= $p['max'] ?></li>
            <li class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Duration: <?= $p['days'] ?> days</li>
          </ul>
          <a href="register.php" class="<?= isset($p['popular']) ? 'bg-emerald-500 hover:bg-emerald-400' : 'bg-slate-700 hover:bg-slate-600' ?> text-white font-bold px-6 py-3 rounded-xl text-center transition">Get Started</a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="border-t border-slate-800 py-12 mt-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid md:grid-cols-4 gap-8 mb-8">
      <div class="md:col-span-2">
        <a href="index.php" class="text-2xl font-extrabold text-emerald-400 mb-3 block">3Commas</a>
        <p class="text-slate-400 text-sm max-w-sm">Automated crypto trading platform trusted by over 120,000 traders worldwide.</p>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4">Platform</h4>
        <ul class="space-y-2 text-slate-400 text-sm">
          <li><a href="#features" class="hover:text-white transition">Features</a></li>
          <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
          <li><a href="register.php" class="hover:text-white transition">Sign Up</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4">Account</h4>
        <ul class="space-y-2 text-slate-400 text-sm">
          <li><a href="#login" class="hover:text-white transition">Login</a></li>
          <li><a href="forgot_password.php" class="hover:text-white transition">Reset Password</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-800 pt-8 text-center text-slate-500 text-sm">
      &copy; <?= date('Y') ?> 3Commas Platform. All rights reserved.
    </div>
  </div>
</footer>

<script>
  document.getElementById('mobileMenuBtn').addEventListener('click', () => {
    document.getElementById('mobileMenu').classList.toggle('hidden');
  });

  // Password eye toggle
  const togglePass = document.getElementById('togglePass');
  if (togglePass) {
    togglePass.addEventListener('click', () => {
      const passInput = document.getElementById('password');
      const eyeOpen   = document.getElementById('eyeOpen');
      const eyeClosed = document.getElementById('eyeClosed');
      if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
      } else {
        passInput.type = 'password';
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
      }
    });
  }
</script>
</body>
</html>
