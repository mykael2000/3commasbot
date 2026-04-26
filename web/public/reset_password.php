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
        redirect('index.php');
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
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-white">Set New Password</h2>
          <p class="text-slate-400 text-sm mt-1">Choose a strong new password</p>
        </div>

        <?php if ($error): ?>
          <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm rounded-xl px-4 py-3 mb-5">
            <?= sanitize($error) ?>
          </div>
        <?php endif; ?>

        <?php if (!$valid): ?>
          <div class="text-center py-4">
            <p class="text-red-400 mb-4">This reset link is invalid or has expired.</p>
            <a href="forgot_password.php" class="text-amber-400 hover:text-amber-300 transition">Request a new link</a>
          </div>
        <?php else: ?>
          <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>" class="space-y-4">
            <?= csrf_field() ?>

            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password">New Password</label>
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

            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1.5" for="password_confirm">Confirm New Password</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <svg class="text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <input id="password_confirm" type="password" name="password_confirm" required autocomplete="new-password"
                  class="w-full bg-white/5 border border-white/10 text-white rounded-xl pl-10 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent placeholder-slate-500 text-sm transition"
                  placeholder="Repeat new password">
              </div>
            </div>

            <button type="submit"
              class="gold-btn w-full text-gray-900 font-bold py-3.5 rounded-xl transition shadow-lg shadow-amber-500/20 text-base mt-2">
              Update Password
            </button>
          </form>
        <?php endif; ?>

        <p class="text-center text-slate-400 text-sm mt-5">
          <a href="index.php" class="text-amber-400 hover:text-amber-300 transition">&larr; Back to Login</a>
        </p>
      </div>
    </div>
  </div>

<script>
  const togglePass = document.getElementById('togglePass');
  if (togglePass) {
    togglePass.addEventListener('click', () => {
      const input  = document.getElementById('password');
      const open   = document.getElementById('eyeOpen');
      const closed = document.getElementById('eyeClosed');
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
</script>
</body>
</html>

