<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

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
</head>
<body class="bg-slate-900 text-white antialiased">

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<header class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur border-b border-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
    <a href="../public/index.php" class="text-2xl font-extrabold text-emerald-400 tracking-tight">3Commas</a>
    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
      <a href="#features" class="hover:text-white transition">Features</a>
      <a href="#pricing"  class="hover:text-white transition">Pricing</a>
      <a href="../public/login.php"    class="hover:text-white transition">Login</a>
      <a href="../public/register.php" class="bg-emerald-500 hover:bg-emerald-400 text-white px-4 py-2 rounded-lg transition font-semibold">Get Started</a>
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
    <a href="#features" class="block text-slate-300 hover:text-white">Features</a>
    <a href="#pricing"  class="block text-slate-300 hover:text-white">Pricing</a>
    <a href="../public/login.php"    class="block text-slate-300 hover:text-white">Login</a>
    <a href="../public/register.php" class="block bg-emerald-500 text-white px-4 py-2 rounded-lg text-center font-semibold">Get Started</a>
  </div>
</header>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="relative overflow-hidden py-24 md:py-36">
  <!-- Background grid decoration -->
  <div class="absolute inset-0 opacity-10">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
          <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#grid)"/>
    </svg>
  </div>
  <div class="relative max-w-5xl mx-auto px-4 sm:px-6 text-center">
    <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm px-4 py-1.5 rounded-full mb-6">
      <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
      Live Trading Automation
    </div>
    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight tracking-tight mb-6">
      Automated<br>
      <span class="text-emerald-400">Crypto Trading</span>
    </h1>
    <p class="text-xl md:text-2xl text-slate-400 max-w-3xl mx-auto mb-10">
      Smart bots, portfolio tracking and risk management — all in one platform built for serious crypto traders.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="../public/register.php" class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-8 py-4 rounded-xl text-lg transition shadow-lg shadow-emerald-500/25">
        Get Started Free
      </a>
      <a href="#features" class="border border-slate-600 hover:border-slate-400 text-slate-300 hover:text-white font-bold px-8 py-4 rounded-xl text-lg transition">
        ▶ Watch Demo
      </a>
    </div>
    <!-- Stats row -->
    <div class="mt-16 grid grid-cols-3 gap-6 max-w-2xl mx-auto">
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
    </div>
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
          <a href="/web/public/register.php" class="<?= $hl ? 'bg-emerald-500 hover:bg-emerald-400' : 'bg-slate-700 hover:bg-slate-600' ?> text-white font-bold px-6 py-3 rounded-xl text-center transition">
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
          <a href="/web/public/register.php" class="<?= isset($p['popular']) ? 'bg-emerald-500 hover:bg-emerald-400' : 'bg-slate-700 hover:bg-slate-600' ?> text-white font-bold px-6 py-3 rounded-xl text-center transition">Get Started</a>
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
        <a href="/web/public/index.php" class="text-2xl font-extrabold text-emerald-400 mb-3 block">3Commas</a>
        <p class="text-slate-400 text-sm max-w-sm">Automated crypto trading platform trusted by over 120,000 traders worldwide.</p>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4">Platform</h4>
        <ul class="space-y-2 text-slate-400 text-sm">
          <li><a href="#features" class="hover:text-white transition">Features</a></li>
          <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
          <li><a href="/web/public/register.php" class="hover:text-white transition">Sign Up</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white font-semibold mb-4">Account</h4>
        <ul class="space-y-2 text-slate-400 text-sm">
          <li><a href="/web/public/login.php" class="hover:text-white transition">Login</a></li>
          <li><a href="/web/public/forgot_password.php" class="hover:text-white transition">Reset Password</a></li>
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
</script>
</body>
</html>
