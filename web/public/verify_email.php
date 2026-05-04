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

$tokenError = null;

// ── Auto-verify via URL token ───────────────────────────────────────────────
if (isset($_GET['token'])) {
    $tok = trim($_GET['token']);
    try {
        $stmt = db()->prepare(
            'SELECT * FROM users WHERE email_verify_token = ? AND email_verify_expires > NOW() LIMIT 1'
        );
        $stmt->execute([$tok]);
        $u = $stmt->fetch();
        if ($u) {
            db()->prepare(
                'UPDATE users SET email_verified=1, email_verify_code=NULL, email_verify_token=NULL, email_verify_expires=NULL WHERE id=?'
            )->execute([$u['id']]);
            login_user($u);
            unset($_SESSION['pending_verify_user_id']);
            redirect('app/index.php');
        } else {
            $tokenError = 'This confirmation link is invalid or has expired.';
        }
    } catch (Throwable $e) {
        $tokenError = 'Verification failed. Please try again.';
    }
}

// ── Require pending session ─────────────────────────────────────────────────
$userId = $_SESSION['pending_verify_user_id'] ?? null;
if (!$userId && !$tokenError) {
    redirect('index.php');
}

$pageUser = null;
if ($userId) {
    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $pageUser = $stmt->fetch() ?: null;
        if (!$pageUser || $pageUser['email_verified']) {
            unset($_SESSION['pending_verify_user_id']);
            redirect('index.php');
        }
    } catch (Throwable $e) {
        redirect('index.php');
    }
}

$error   = get_flash('error');
$success = get_flash('success');

// ── Form handling ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!$pageUser) {
        redirect('index.php');
    }

    $action = $_POST['action'] ?? 'verify';

    // Resend code
    if ($action === 'resend') {
        try {
            $code    = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $newTok  = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            db()->prepare(
                'UPDATE users SET email_verify_code=?, email_verify_token=?, email_verify_expires=? WHERE id=?'
            )->execute([$code, $newTok, $expires, $userId]);
            send_verification_email($pageUser['email'], $pageUser['name'], $code, $newTok);
            flash('success', 'A new code has been sent to your email.');
        } catch (Throwable $e) {
            flash('error', 'Failed to resend. Please try again.');
        }
        redirect('verify_email.php');
    }

    // Verify code
    $inputCode = preg_replace('/\D/', '', trim($_POST['code'] ?? ''));
    if (strlen($inputCode) !== 6) {
        flash('error', 'Please enter the 6-digit code.');
        redirect('verify_email.php');
    }

    try {
        $stmt = db()->prepare(
            'SELECT * FROM users WHERE id = ? AND email_verify_code = ? AND email_verify_expires > NOW() LIMIT 1'
        );
        $stmt->execute([$userId, $inputCode]);
        $confirmed = $stmt->fetch();
        if ($confirmed) {
            db()->prepare(
            'UPDATE users SET status="active", email_verified=1, email_verify_code=NULL, email_verify_token=NULL, email_verify_expires=NULL WHERE id=?'
            )->execute([$userId]);
            login_user($confirmed);
            unset($_SESSION['pending_verify_user_id']);
            redirect('app/index.php');
        } else {
            flash('error', 'Invalid or expired code. Please try again.');
            redirect('verify_email.php');
        }
    } catch (Throwable $e) {
        flash('error', 'Verification failed. Please try again.');
        redirect('verify_email.php');
    }
}

// Mask email for display
$maskedEmail = '';
if ($pageUser) {
    [$local, $domain] = explode('@', $pageUser['email'], 2);
    $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 antialiased min-h-screen flex flex-col items-center justify-center p-4">

  <div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="index.php">
        <svg width="110px" height="28px" viewBox="0 0 125 31" fill="none" xmlns="http://www.w3.org/2000/svg"><g fill-rule="evenodd"><path fill="currentColor" d="M30.795 0v30.918H0V0z" style="color:#05ab8c"></path><path fill="currentColor" d="M20.354 19.093h3.167a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.074-.14a.2.2 0 00-.177-.106h-4.024a.2.2 0 00-.198.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.197.237zm-7.804 0h3.155a.2.2 0 00.19-.137l1.144-3.417a.2.2 0 00.002-.007l1.004-3.434a.2.2 0 00-.015-.15l-.076-.14a.2.2 0 00-.176-.106h-4.054a.2.2 0 00-.198.168l-.592 3.664v.003l-.58 3.321a.2.2 0 00.196.235zm-7.594 0h3.168a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.075-.14a.2.2 0 00-.176-.106H6.158a.2.2 0 00-.197.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.196.237z" style="color:#fff"></path><path d="M47.384 18.37c0 2.589-1.979 4.338-5.164 4.338-1.66 0-3.253-.5-4.14-1.363l.978-1.885c.66.704 1.706 1.09 2.866 1.09 1.729 0 2.776-.886 2.776-2.18s-1.024-2.112-2.594-2.112c-.705 0-1.296.136-1.842.431l-.705-1.294 3.73-4.27h-4.617V8.99h7.984v1.613l-3.503 3.725c2.571.045 4.231 1.68 4.231 4.042zm.842-2.657c0-4.156 2.866-6.904 7.188-6.904 2.207 0 4.004.727 5.346 2.18l-1.638 1.635c-.774-.976-2.07-1.68-3.685-1.68-2.73 0-4.55 1.93-4.55 4.792 0 2.906 1.843 4.837 4.573 4.837 1.842 0 3.093-.818 3.958-2.135l1.751 1.544c-1.296 1.772-3.275 2.726-5.755 2.726-4.299 0-7.188-2.794-7.188-6.995zm13.193 1.885c0-3.066 2.116-5.11 5.301-5.11 3.162 0 5.277 2.044 5.277 5.11 0 3.066-2.115 5.132-5.277 5.132-3.162-.022-5.301-2.066-5.301-5.132zm7.985 0c0-1.794-1.092-2.975-2.684-2.975-1.638 0-2.707 1.181-2.707 2.975s1.091 2.975 2.707 2.975c1.615 0 2.684-1.181 2.684-2.975zm19.404-1.272v6.2h-2.502v-5.791c0-1.272-.796-2.112-2.025-2.112-1.205 0-2.024.84-2.024 2.112v5.791h-2.503v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.046 1.181c.705-.886 1.751-1.363 2.957-1.363 1.297 0 2.343.545 2.98 1.476.705-.976 1.865-1.476 3.185-1.476 2.411 0 4.026 1.544 4.026 3.838zm17.242 0v6.2h-2.5v-5.791c0-1.272-.8-2.112-2.03-2.112-1.2 0-2.021.84-2.021 2.112v5.791h-2.502v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.045 1.181c.706-.886 1.752-1.363 2.958-1.363 1.296 0 2.343.545 2.98 1.476.705-.976 1.86-1.476 3.18-1.476 2.44 0 4.03 1.544 4.03 3.838zm9.85 0v6.2h-2.39l-.04-1.408c-.66 1.022-1.8 1.59-3.01 1.59-2.04 0-3.43-1.227-3.43-3.066 0-1.908 1.68-3.157 4.21-3.157.68 0 1.43.068 2.18.227v-.182c0-1.317-.89-2.112-2.39-2.112-.93 0-1.68.273-2.29.795l-1.1-1.453c1.07-.863 2.3-1.272 4.03-1.272 2.53 0 4.23 1.522 4.23 3.838zm-2.5 2.09a9.19 9.19 0 00-1.87-.205c-1.18 0-1.93.545-1.93 1.385 0 .795.52 1.34 1.55 1.34 1.16 0 2.25-.908 2.25-2.52zm3.73 3.134l.93-1.499c.94.545 1.87.726 2.84.726.92 0 1.55-.386 1.55-.976 0-.591-.7-.931-1.68-1.181l-.82-.227c-1.66-.432-2.8-1.09-2.8-2.635 0-1.953 1.55-3.247 3.89-3.247 1.48 0 2.75.318 3.73.976l-1.04 1.635a5.218 5.218 0 00-2.51-.635c-.88 0-1.52.34-1.52.885 0 .591.61.863 1.48 1.09l.82.228c1.68.431 3.02 1.203 3.02 2.952 0 1.862-1.64 3.111-4.12 3.111-1.54-.045-2.86-.431-3.77-1.203z" fill="currentColor" style="color:#334155"></path></g></svg>
      </a>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-xl p-8">

      <!-- Icon -->
      <div class="flex justify-center mb-5">
        <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
          <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>

      <?php if ($tokenError): ?>
        <!-- Expired / invalid token state -->
        <div class="text-center">
          <h1 class="text-xl font-bold text-slate-900 mb-2">Link Expired</h1>
          <p class="text-slate-500 text-sm mb-6"><?= sanitize($tokenError) ?></p>
          <a href="index.php" class="text-sm text-emerald-600 hover:underline">← Back to sign in</a>
        </div>

      <?php else: ?>
        <h1 class="text-xl font-bold text-slate-900 text-center mb-1">Check your email</h1>
        <?php if ($maskedEmail): ?>
          <p class="text-slate-500 text-sm text-center mb-6">
            We sent a 6-digit code to <span class="font-medium text-slate-700"><?= sanitize($maskedEmail) ?></span>
          </p>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-lg px-4 py-3 mb-4">
            <?= sanitize($error) ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-lg px-4 py-3 mb-4">
            <?= sanitize($success) ?>
          </div>
        <?php endif; ?>

        <!-- Code entry -->
        <form method="POST" action="" class="space-y-4">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="verify">

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="verify_code">Verification code</label>
            <input id="verify_code" type="text" name="code" required
              maxlength="6" inputmode="numeric" autocomplete="one-time-code"
              class="w-full bg-white border border-slate-300 text-slate-900 rounded-xl px-4 py-4 text-center text-3xl font-bold tracking-[0.6em] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder-slate-300"
              placeholder="──────">
          </div>

          <button type="submit"
            class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 text-base">
            Confirm email
          </button>
        </form>

        <!-- Resend -->
        <form method="POST" action="" class="mt-4 text-center">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="resend">
          <p class="text-sm text-slate-500">
            Didn't receive it?
            <button type="submit" class="text-emerald-600 hover:underline font-medium">Resend code</button>
          </p>
        </form>

        <p class="mt-5 text-center text-xs text-slate-400 leading-relaxed">
          Code valid for 30 minutes. Do not share it with anybody,<br>including 3Commas team members.
        </p>
      <?php endif; ?>
    </div>

    <p class="mt-6 text-center text-sm text-slate-500">
      <a href="index.php" class="text-emerald-600 hover:underline">← Back to sign in</a>
    </p>
  </div>

</body>
</html>
