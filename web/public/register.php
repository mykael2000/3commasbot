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

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    // Validation
    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'All fields are required.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }

    if (strlen($password) < 8) {
        flash('error', 'Password must be at least 8 characters long.');
        redirect('register.php');
    }

    if ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
        redirect('register.php');
    }

    try {
        $pdo = db();

        // Check for duplicate email
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            flash('error', 'An account with that email already exists.');
            redirect('register.php');
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $pdo->prepare(
            'INSERT INTO users (name, email, password, role, status, balance) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hashed, 'user', 'active', 0.0]);
        $userId = (int) $pdo->lastInsertId();

        // Auto-login
        $user = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $user->execute([$userId]);
        login_user($user->fetch());

        // Send welcome email (non-fatal)
        try {
            send_welcome_email($email, $name);
        } catch (Throwable $emailErr) {
            error_log('[register] Welcome email failed: ' . $emailErr->getMessage());
        }

        redirect('app/index.php');
    } catch (Throwable $e) {
        flash('error', 'Registration failed. Please try again.');
        redirect('register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .gold-btn { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%); }
    .gold-btn:hover { background: linear-gradient(135deg, #fbbf24 0%, #fde68a 50%, #fbbf24 100%); }
    .reg-card { background: linear-gradient(145deg, #0d1117 0%, #161b27 100%); }
  </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

  <!-- Ambient glow -->
  <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-emerald-500/8 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-amber-500/8 rounded-full blur-3xl"></div>
  </div>

  <div class="w-full max-w-md relative">
    <!-- Back to home link -->
    <div class="text-center mb-6">
      <a href="index.php" class="text-2xl font-extrabold text-emerald-400 tracking-tight">3Commas</a>
    </div>

    <!-- Card -->
    <div class="reg-card border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
      <!-- Gradient top accent -->
      <div class="h-1 bg-gradient-to-r from-amber-500 via-yellow-300 to-amber-500"></div>

      <div class="p-8">
        <!-- Heading -->
        <div class="text-center mb-7">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/15 mb-3">
            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-white">Create Account</h2>
          <p class="text-slate-400 text-sm mt-1">Join 3Commas and start trading smarter</p>
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

        <form method="POST" action="register.php" class="space-y-4">
          <?= csrf_field() ?>

          <!-- Full Name -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="name">Full Name</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
              </div>
              <input id="name" type="text" name="name" required autocomplete="name"
                class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                placeholder="John Doe">
            </div>
          </div>

          <!-- Email -->
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

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">Password</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
              </div>
              <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-11 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                placeholder="Min. 8 characters">
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

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password_confirm">Confirm Password</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
              </div>
              <input id="password_confirm" type="password" name="password_confirm" required autocomplete="new-password"
                class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-11 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                placeholder="Repeat password">
              <button type="button" id="toggleConfirm"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition"
                aria-label="Toggle confirm password visibility">
                <svg id="eyeOpen2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                </svg>
                <svg id="eyeClosed2" class="hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                  <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                  <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- CTA -->
          <button type="submit"
            class="gold-btn w-full text-gray-900 font-bold py-3.5 rounded-xl transition shadow-lg shadow-amber-500/20 text-base mt-2">
            Create Account
          </button>
        </form>

        <!-- Bottom helper -->
        <p class="text-center text-slate-400 text-sm mt-5">
          Already have an account?
          <a href="index.php" class="text-amber-400 hover:text-amber-300 transition font-medium">Sign in</a>
        </p>
      </div>
    </div>
  </div>

<script>
  function makeToggle(btnId, inputId, openId, closedId) {
    const btn    = document.getElementById(btnId);
    const input  = document.getElementById(inputId);
    const open   = document.getElementById(openId);
    const closed = document.getElementById(closedId);
    if (!btn) return;
    btn.addEventListener('click', () => {
      if (input.type === 'password') {
        input.type = 'text';
        open.classList.add('hidden');
        closed.classList.remove('hidden');
      } else {
        input.type = 'password';
        open.classList.remove('hidden');
        closed.classList.add('hidden');
      }
    });
  }
  makeToggle('togglePass',    'password',         'eyeOpen',  'eyeClosed');
  makeToggle('toggleConfirm', 'password_confirm', 'eyeOpen2', 'eyeClosed2');
</script>
</body>
</html>

