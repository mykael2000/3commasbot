<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user = current_user();

// Coin → DB column map (used by swap handler below)
$COIN_COLS = [
    'USDT' => 'balance',
    'BTC'  => 'btc_balance',
    'ETH'  => 'eth_balance',
    'BNB'  => 'bnb_balance',
    'SOL'  => 'sol_balance',
];

// Flash messages (set by swap handler redirects)
$flashSuccess = get_flash('success');
$flashError   = get_flash('error');

// Handle currency swap (PRG pattern – redirect after POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'swap') {
    csrf_verify();
    $fromCurrency = strtoupper(trim($_POST['from_currency'] ?? ''));
    $toCurrency   = strtoupper(trim($_POST['to_currency']   ?? ''));
    $amount       = (float)($_POST['amount'] ?? 0);

    if (!isset($COIN_COLS[$fromCurrency]) || !isset($COIN_COLS[$toCurrency])) {
        flash('error', 'Please select valid currencies.');
        redirect('index.php');
    }
    if ($fromCurrency === $toCurrency) {
        flash('error', 'From and To currencies must be different.');
        redirect('index.php');
    }
    if ($amount < 0.00000001) {
        flash('error', 'Please enter a valid amount greater than zero.');
        redirect('index.php');
    }

    $fromPrice = $fromCurrency === 'USDT' ? 1.0 : price_for_symbol($fromCurrency . 'USDT');
    $toPrice   = $toCurrency   === 'USDT' ? 1.0 : price_for_symbol($toCurrency   . 'USDT');
    $rate      = $toPrice > 0 ? ($fromPrice / $toPrice) : 0.0;
    $toAmount  = round($amount * $rate, 8);

    $fromCol = $COIN_COLS[$fromCurrency];
    $toCol   = $COIN_COLS[$toCurrency];

    // Secondary whitelist check on the resolved column names.
    // PDO cannot parameterize column/table identifiers, so we validate against
    // an explicit static list here. Values come from a hardcoded map above, but
    // this double-check ensures safety if the map is ever changed.
    $allowedCols = ['balance', 'btc_balance', 'eth_balance', 'bnb_balance', 'sol_balance'];
    if (!in_array($fromCol, $allowedCols, true) || !in_array($toCol, $allowedCols, true)) {
        flash('error', 'Invalid currency mapping.');
        redirect('index.php');
    }

    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Lock row and read current balance
        $st = $pdo->prepare('SELECT ' . $fromCol . ' FROM users WHERE id = ? FOR UPDATE');
        $st->execute([$user['id']]);
        $currentBal = (float)($st->fetchColumn() ?? 0);

        if ($currentBal < $amount) {
            $pdo->rollBack();
            flash('error', 'Insufficient ' . $fromCurrency . ' balance for this swap.');
            redirect('index.php');
        }

        $pdo->prepare('UPDATE users SET ' . $fromCol . ' = ' . $fromCol . ' - ? WHERE id = ?')
            ->execute([$amount, $user['id']]);
        $pdo->prepare('UPDATE users SET ' . $toCol . ' = ' . $toCol . ' + ? WHERE id = ?')
            ->execute([$toAmount, $user['id']]);
        $pdo->prepare(
            'INSERT INTO swaps (user_id, from_coin, to_coin, from_amount, to_amount, rate_used)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$user['id'], $fromCurrency, $toCurrency, $amount, $toAmount, $rate]);

        $pdo->commit();
        flash('success', 'Swapped ' . rtrim(number_format($amount, 8, '.', ''), '0') . ' ' . $fromCurrency
            . ' → ' . rtrim(number_format($toAmount, 8, '.', ''), '0') . ' ' . $toCurrency . '.');
    } catch (Throwable) {
        try { if (db()->inTransaction()) db()->rollBack(); } catch (Throwable) {}
        flash('error', 'Swap failed. Please try again.');
    }
    redirect('index.php');
}

// Re-read user to get fresh balances (post-swap redirect will reload)
$user = current_user();

// Fetch real-time prices for all 5 coins (USDT is always $1)
$symbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT'];
$prices  = [];
foreach ($symbols as $sym) {
    $prices[$sym] = price_for_symbol($sym);
}
$prices['USDTUSDT'] = 1.0;

// Mock 24h change percentages – demo data only
$priceChanges = [
    'BTCUSDT'  => (mt_rand(-800, 1500) / 100),
    'ETHUSDT'  => (mt_rand(-600, 1200) / 100),
    'BNBUSDT'  => (mt_rand(-500, 1000) / 100),
    'SOLUSDT'  => (mt_rand(-700, 1400) / 100),
    'USDTUSDT' => 0.00,
];

// All 5 coin balances from DB (default 0)
$usdtBalance = (float)($user['balance']     ?? 0);
$btcBalance  = (float)($user['btc_balance'] ?? 0);
$ethBalance  = (float)($user['eth_balance'] ?? 0);
$bnbBalance  = (float)($user['bnb_balance'] ?? 0);
$solBalance  = (float)($user['sol_balance'] ?? 0);

$walletBalances = [
    'BTC'  => [
        'balance' => $btcBalance,
        'value'   => $btcBalance * $prices['BTCUSDT'],
        'color'   => 'orange',
    ],
    'ETH'  => [
        'balance' => $ethBalance,
        'value'   => $ethBalance * $prices['ETHUSDT'],
        'color'   => 'indigo',
    ],
    'BNB'  => [
        'balance' => $bnbBalance,
        'value'   => $bnbBalance * $prices['BNBUSDT'],
        'color'   => 'yellow',
    ],
    'SOL'  => [
        'balance' => $solBalance,
        'value'   => $solBalance * $prices['SOLUSDT'],
        'color'   => 'purple',
    ],
    'USDT' => [
        'balance' => $usdtBalance,
        'value'   => $usdtBalance,
        'color'   => 'teal',
    ],
];

$totalBalance    = array_sum(array_column($walletBalances, 'value'));
$todayPnl        = (rand(-500, 2000) + rand(0, 100) / 100);
$todayPnlPercent = $totalBalance > 0 ? ($todayPnl / $totalBalance) * 100 : 0;

// Derived account metrics
$equity     = $totalBalance * 1.15;
$margin     = $totalBalance * 0.30;
$freeMargin = $margin * 0.70;

// Fetch open demo trades
$openTrades = [];
try {
    $st = db()->prepare(
        'SELECT * FROM demo_trades WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT 5'
    );
    $st->execute([$user['id'], 'open']);
    $openTrades = $st->fetchAll();
} catch (Throwable) {}

// Fetch active user plan
$activePlan = null;
try {
    $st = db()->prepare(
        'SELECT up.*, ip.name AS plan_name, ip.roi_percent
         FROM user_plans up
         JOIN investment_plans ip ON ip.id = up.plan_id
         WHERE up.user_id = ? AND up.status = ?
         ORDER BY up.created_at DESC LIMIT 1'
    );
    $st->execute([$user['id'], 'active']);
    $activePlan = $st->fetch() ?: null;
} catch (Throwable) {}

// PHP prices JSON for JS exchange-rate widget (all 5 coins)
$pricesJson = json_encode([
    'BTC'  => $prices['BTCUSDT'],
    'ETH'  => $prices['ETHUSDT'],
    'BNB'  => $prices['BNBUSDT'],
    'SOL'  => $prices['SOLUSDT'],
    'USDT' => 1.0,
], JSON_THROW_ON_ERROR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – 3Commas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* ── Premium page background ── */
        body {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 55%, #ffffff 100%);
        }

        /* ── Balance hero glow ── */
        .card-glow {
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.08);
        }

        /* ── Glassmorphic stat cards ── */
        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
            -webkit-box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        /* ── Crypto badge colour variants ── */
        .crypto-btc  { background: rgba(249,115,22,.08); border: 1px solid rgba(249,115,22,.25); }
        .crypto-eth  { background: rgba(99,102,241,.08); border: 1px solid rgba(99,102,241,.25); }
        .crypto-usdt { background: rgba(20,184,166,.08); border: 1px solid rgba(20,184,166,.25); }

        /* ── P&L colour helpers ── */
        .pnl-pos { color: #10b981; }
        .pnl-neg { color: #ef4444; }

        /* ── Swap card gradient ── */
        .swap-gradient {
            background: linear-gradient(145deg, rgba(239,246,255,1) 0%, rgba(255,255,255,1) 100%);
            border: 1px solid #e2e8f0;
        }

        /* ── Subtle hover lift ── */
        .hover-lift { transition: transform .2s, box-shadow .2s; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 14px 24px rgba(15,23,42,.10); }
    </style>
</head>
<body class="bg-white text-slate-900 min-h-screen pb-28 md:pb-4 antialiased">

    <!-- ════════════════════════════════════════
         TOP HEADER
    ════════════════════════════════════════ -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-2.5">
        <div class="flex items-center justify-between max-w-7xl mx-auto gap-4">
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="index.php" class="flex items-center">
                <svg aria-labelledby="logo" width="110px" height="28px" viewBox="0 0 125 31" fill="none" xmlns="http://www.w3.org/2000/svg"><text id="logo" class="visually-hidden" font-size="0">3Commas logo</text><g fill-rule="evenodd"><path fill="currentColor" d="M30.795 0v30.918H0V0z" style="color: #05ab8c;"></path><path fill="currentColor" d="M20.354 19.093h3.167a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.074-.14a.2.2 0 00-.177-.106h-4.024a.2.2 0 00-.198.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.197.237zm-7.804 0h3.155a.2.2 0 00.19-.137l1.144-3.417a.2.2 0 00.002-.007l1.004-3.434a.2.2 0 00-.015-.15l-.076-.14a.2.2 0 00-.176-.106h-4.054a.2.2 0 00-.198.168l-.592 3.664v.003l-.58 3.321a.2.2 0 00.196.235zm-7.594 0h3.168a.2.2 0 00.19-.137l1.136-3.417a.2.2 0 00.002-.007l.998-3.434a.2.2 0 00-.016-.15l-.075-.14a.2.2 0 00-.176-.106H6.158a.2.2 0 00-.197.168l-.588 3.663a.2.2 0 010 .005l-.613 3.318a.2.2 0 00.196.237z" style="color: #fff;"></path><path d="M47.384 18.37c0 2.589-1.979 4.338-5.164 4.338-1.66 0-3.253-.5-4.14-1.363l.978-1.885c.66.704 1.706 1.09 2.866 1.09 1.729 0 2.776-.886 2.776-2.18s-1.024-2.112-2.594-2.112c-.705 0-1.296.136-1.842.431l-.705-1.294 3.73-4.27h-4.617V8.99h7.984v1.613l-3.503 3.725c2.571.045 4.231 1.68 4.231 4.042zm.842-2.657c0-4.156 2.866-6.904 7.188-6.904 2.207 0 4.004.727 5.346 2.18l-1.638 1.635c-.774-.976-2.07-1.68-3.685-1.68-2.73 0-4.55 1.93-4.55 4.792 0 2.906 1.843 4.837 4.573 4.837 1.842 0 3.093-.818 3.958-2.135l1.751 1.544c-1.296 1.772-3.275 2.726-5.755 2.726-4.299 0-7.188-2.794-7.188-6.995zm13.193 1.885c0-3.066 2.116-5.11 5.301-5.11 3.162 0 5.277 2.044 5.277 5.11 0 3.066-2.115 5.132-5.277 5.132-3.162-.022-5.301-2.066-5.301-5.132zm7.985 0c0-1.794-1.092-2.975-2.684-2.975-1.638 0-2.707 1.181-2.707 2.975s1.091 2.975 2.707 2.975c1.615 0 2.684-1.181 2.684-2.975zm19.404-1.272v6.2h-2.502v-5.791c0-1.272-.796-2.112-2.025-2.112-1.205 0-2.024.84-2.024 2.112v5.791h-2.503v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.046 1.181c.705-.886 1.751-1.363 2.957-1.363 1.297 0 2.343.545 2.98 1.476.705-.976 1.865-1.476 3.185-1.476 2.411 0 4.026 1.544 4.026 3.838zm17.242 0v6.2h-2.5v-5.791c0-1.272-.8-2.112-2.03-2.112-1.2 0-2.021.84-2.021 2.112v5.791h-2.502v-5.791c0-1.272-.796-2.112-2.024-2.112-1.206 0-2.025.84-2.025 2.112v5.791h-2.502V12.67h2.411l.045 1.181c.706-.886 1.752-1.363 2.958-1.363 1.296 0 2.343.545 2.98 1.476.705-.976 1.86-1.476 3.18-1.476 2.44 0 4.03 1.544 4.03 3.838zm9.85 0v6.2h-2.39l-.04-1.408c-.66 1.022-1.8 1.59-3.01 1.59-2.04 0-3.43-1.227-3.43-3.066 0-1.908 1.68-3.157 4.21-3.157.68 0 1.43.068 2.18.227v-.182c0-1.317-.89-2.112-2.39-2.112-.93 0-1.68.273-2.29.795l-1.1-1.453c1.07-.863 2.3-1.272 4.03-1.272 2.53 0 4.23 1.522 4.23 3.838zm-2.5 2.09a9.19 9.19 0 00-1.87-.205c-1.18 0-1.93.545-1.93 1.385 0 .795.52 1.34 1.55 1.34 1.16 0 2.25-.908 2.25-2.52zm3.73 3.134l.93-1.499c.94.545 1.87.726 2.84.726.92 0 1.55-.386 1.55-.976 0-.591-.7-.931-1.68-1.181l-.82-.227c-1.66-.432-2.8-1.09-2.8-2.635 0-1.953 1.55-3.247 3.89-3.247 1.48 0 2.75.318 3.73.976l-1.04 1.635a5.218 5.218 0 00-2.51-.635c-.88 0-1.52.34-1.52.885 0 .591.61.863 1.48 1.09l.82.228c1.68.431 3.02 1.203 3.02 2.952 0 1.862-1.64 3.111-4.12 3.111-1.54-.045-2.86-.431-3.77-1.203z" fill="currentColor" style="color: #334155;"></path></g></svg>
                </a>
            </div>

            <!-- Desktop navigation (hidden on mobile – see bottom nav) -->
            <nav class="hidden md:flex items-center gap-0.5 flex-1 justify-center">
                <a href="index.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-600 bg-emerald-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="markets.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    Markets
                </a>
                <a href="trading.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Trade
                </a>
                <a href="deposit.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Deposit
                </a>
                <a href="withdraw.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Withdraw
                </a>
                <a href="swap.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m10 4v12m0 0l-4-4m4 4l4-4"/></svg>
                    Swap
                </a>
                <a href="wallet.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Wallet
                </a>
                <a href="profile.php" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </a>
            </nav>

            <div class="flex items-center gap-3 flex-shrink-0">
                <!-- live badge -->
                <span class="hidden sm:flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/30 px-3 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-emerald-400 text-xs font-semibold tracking-wide">LIVE</span>
                </span>
                <div class="flex items-center gap-2">
                    <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?= sanitize($user['profile_image']) ?>" alt="Avatar"
                         class="w-8 h-8 rounded-full object-cover border border-slate-300">
                    <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-cyan-600 flex items-center justify-center text-slate-900 font-black text-sm flex-shrink-0">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-slate-800 leading-tight"><?= sanitize($user['name']) ?></p>
                        <p class="text-[11px] text-slate-500"><?= sanitize($user['email']) ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="text-slate-600 hover:text-red-500 transition text-xs px-3 py-1.5 border border-slate-300 hover:border-red-500/60 rounded-lg">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-2 py-2 space-y-3">

        <!-- Flash messages -->
        <?php if ($flashSuccess): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm rounded-xl px-4 py-3 flex items-center gap-2">
            <span class="text-emerald-500">✓</span><?= sanitize($flashSuccess) ?>
        </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-600 text-sm rounded-xl px-4 py-3 flex items-center gap-2">
            <span>✕</span><?= sanitize($flashError) ?>
        </div>
        <?php endif; ?>

        <!-- ── Active Plan Banner ── -->
        <?php if ($activePlan): ?>
        <div class="flex items-center justify-between bg-emerald-500/10 border border-emerald-500/25 rounded-2xl px-3 py-1.5">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-pulse flex-shrink-0"></span>
                <div>
                    <p class="text-sm font-semibold text-emerald-700">Active Investment Plan</p>
                    <p class="text-xs text-slate-600"><?= sanitize($activePlan['plan_name']) ?> &bull; <?= number_format((float)$activePlan['roi_percent'], 2) ?>% ROI</p>
                </div>
            </div>
            <span class="bg-emerald-500/10 text-emerald-700 text-[11px] font-bold px-3 py-1 rounded-full tracking-wide">VERIFIED</span>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════
             MAIN BALANCE HERO CARD
        ════════════════════════════════════════ -->
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-emerald-50/60 to-sky-50/70 border border-slate-200 rounded-3xl p-3 sm:p-4 card-glow">
            <!-- decorative glow orbs -->
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-cyan-500/8 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                <div>
                    <p class="text-emerald-700 text-xs font-semibold uppercase tracking-widest mb-2">Total Portfolio Balance</p>
                    <h2 class="text-5xl sm:text-6xl font-black text-slate-900 tabular-nums mb-1 leading-none">
                        $<?= number_format($totalBalance, 2) ?>
                    </h2>
                    <p class="text-emerald-600 text-base font-medium">≈ <?= number_format($totalBalance, 2) ?> USDT</p>
                </div>

                <div class="flex flex-row sm:flex-col gap-2 sm:items-end">
                    <span class="flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/30 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        <span class="text-emerald-600 text-xs font-bold tracking-wide">LIVE</span>
                    </span>
                    <span class="text-xs text-slate-500">Updated just now</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
                <!-- Today's P&L -->
                <div class="glass-card rounded-2xl p-2 hover-lift">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Today's P&amp;L</p>
                    <p class="text-xl sm:text-2xl font-black tabular-nums <?= $todayPnl >= 0 ? 'pnl-pos' : 'pnl-neg' ?>">
                        <?= $todayPnl >= 0 ? '+' : '' ?>$<?= number_format(abs($todayPnl), 2) ?>
                    </p>
                    <p class="text-xs <?= $todayPnl >= 0 ? 'text-emerald-400' : 'text-red-400' ?> font-semibold mt-1">
                        <?= $todayPnl >= 0 ? '▲' : '▼' ?> <?= number_format(abs($todayPnlPercent), 2) ?>%
                    </p>
                </div>

                <!-- Equity -->
                <div class="glass-card rounded-2xl p-2 hover-lift">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Equity</p>
                    <p class="text-xl sm:text-2xl font-black tabular-nums text-emerald-400">$<?= number_format($equity, 2) ?></p>
                    <p class="text-xs text-slate-500 mt-1">Account value</p>
                </div>

                <!-- Margin -->
                <div class="glass-card rounded-2xl p-2 hover-lift">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Margin</p>
                    <p class="text-xl sm:text-2xl font-black tabular-nums text-blue-400">$<?= number_format($margin, 2) ?></p>
                    <p class="text-xs text-slate-500 mt-1">Available</p>
                </div>

                <!-- Free Margin -->
                <div class="glass-card rounded-2xl p-2 hover-lift">
                    <p class="text-slate-500 text-[11px] font-semibold uppercase tracking-wider mb-2">Free Margin</p>
                    <p class="text-xl sm:text-2xl font-black tabular-nums text-cyan-400">$<?= number_format($freeMargin, 2) ?></p>
                    <p class="text-xs text-slate-500 mt-1">Usable</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="relative grid grid-cols-2 gap-2">
                <a href="deposit.php"
                   class="flex-1 text-center bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white font-bold px-4 py-2 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-emerald-900/40">
                    ↓ Deposit
                </a>
                <a href="withdraw.php"
                   class="flex-1 text-center bg-gradient-to-r from-amber-600 to-yellow-500 hover:from-amber-500 hover:to-yellow-400 text-white font-bold px-4 py-2 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-amber-900/40">
                    ↑ Withdraw
                </a>
            </div>
        </div>

        <!-- ════════════════════════════════════════
             CRYPTO HOLDINGS  +  SWAP WIDGET
        ════════════════════════════════════════ -->
        <div class="grid lg:grid-cols-3 gap-3">

            <!-- ── Crypto Holdings ── -->
            <div class="lg:col-span-2 glass-card rounded-3xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Crypto Holdings
                    </h3>
                    <span class="text-[11px] text-slate-500">Portfolio breakdown</span>
                </div>

                <div class="space-y-1">
                    <?php
                    $cryptoColors = [
                        'BTC'  => ['badge' => 'crypto-btc',  'icon' => 'text-orange-400', 'bar' => 'from-orange-500 to-amber-400',   'pct' => 'text-orange-400'],
                        'ETH'  => ['badge' => 'crypto-eth',  'icon' => 'text-indigo-400', 'bar' => 'from-indigo-500 to-blue-400',    'pct' => 'text-indigo-400'],
                        'BNB'  => ['badge' => 'crypto-bnb',  'icon' => 'text-yellow-500', 'bar' => 'from-yellow-500 to-amber-300',   'pct' => 'text-yellow-500'],
                        'SOL'  => ['badge' => 'crypto-sol',  'icon' => 'text-purple-400', 'bar' => 'from-purple-500 to-pink-400',    'pct' => 'text-purple-400'],
                        'USDT' => ['badge' => 'crypto-usdt', 'icon' => 'text-teal-400',   'bar' => 'from-teal-500 to-emerald-400',   'pct' => 'text-teal-400'],
                    ];
                    $cryptoDecimals = ['BTC' => 6, 'ETH' => 6, 'BNB' => 4, 'SOL' => 4, 'USDT' => 2];
                    foreach ($walletBalances as $crypto => $data):
                        $pct      = $totalBalance > 0 ? ($data['value'] / $totalBalance) * 100 : 0;
                        $col      = $cryptoColors[$crypto] ?? $cryptoColors['USDT'];
                        $decimals = $cryptoDecimals[$crypto] ?? 2;
                    ?>
                    <div class="<?= $col['badge'] ?> rounded-2xl p-2 hover-lift">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 <?= $col['badge'] ?> rounded-full flex items-center justify-center font-black <?= $col['icon'] ?> text-sm">
                                    <?= $crypto ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 leading-tight"><?= $crypto ?></p>
                                    <p class="text-xs text-slate-400"><?= number_format($data['balance'], $decimals) ?> <?= $crypto ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900 tabular-nums">$<?= number_format($data['value'], 2) ?></p>
                                <p class="text-xs <?= $col['pct'] ?> font-semibold"><?= number_format($pct, 1) ?>%</p>
                            </div>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="bg-gradient-to-r <?= $col['bar'] ?> h-1.5 rounded-full transition-all duration-500"
                                 style="width: <?= min(100, $pct) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── Quick Swap ── -->
            <div class="swap-gradient rounded-3xl p-3 flex flex-col">
                <h3 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m10 4v12m0 0l-4-4m4 4l4-4"/></svg>
                    Quick Swap
                </h3>

                <form method="POST" class="space-y-2 flex-1 flex flex-col" id="swapForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="swap">

                    <div>
                        <label class="block text-[11px] text-slate-600 font-semibold uppercase tracking-wider mb-1">From</label>
                        <select name="from_currency" id="swapFrom" required
                                class="w-full bg-white border border-slate-300 text-slate-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 text-sm appearance-none cursor-pointer">
                            <option value="USDT" selected>$ USDT – Tether</option>
                            <option value="BTC">₿ BTC – Bitcoin</option>
                            <option value="ETH">Ξ ETH – Ethereum</option>
                            <option value="BNB">◈ BNB – Binance Coin</option>
                            <option value="SOL">◎ SOL – Solana</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-600 font-semibold uppercase tracking-wider mb-1">Amount</label>
                        <input type="number" name="amount" id="swapAmount" step="0.00000001" min="0.00000001" required
                               class="w-full bg-white border border-slate-300 text-slate-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 text-sm tabular-nums"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-600 font-semibold uppercase tracking-wider mb-1">To</label>
                        <select name="to_currency" id="swapTo" required
                                class="w-full bg-white border border-slate-300 text-slate-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 text-sm appearance-none cursor-pointer">
                            <option value="USDT">$ USDT – Tether</option>
                            <option value="BTC" selected>₿ BTC – Bitcoin</option>
                            <option value="ETH">Ξ ETH – Ethereum</option>
                            <option value="BNB">◈ BNB – Binance Coin</option>
                            <option value="SOL">◎ SOL – Solana</option>
                        </select>
                    </div>

                    <!-- Live Rate Display -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 space-y-1" id="rateBox">
                        <p class="text-[11px] text-slate-600 font-semibold uppercase tracking-wider">Live Exchange Rate</p>
                        <p class="text-sm font-bold text-blue-300 tabular-nums" id="rateDisplay">— loading —</p>
                        <p class="text-[11px] text-slate-500" id="receiveDisplay"></p>
                    </div>

                    <button type="submit"
                            class="mt-auto w-full bg-gradient-to-r from-blue-700 to-blue-600 hover:from-blue-600 hover:to-blue-500 text-white font-bold py-2 rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-blue-900/40">
                        ⇄ Swap Now
                    </button>
                </form>

                <?php if ($flashSuccess || $flashError): ?>
                <div class="mt-2 <?= $flashSuccess ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700' : 'bg-red-500/10 border-red-500/30 text-red-600' ?> border rounded-xl px-3 py-2 text-sm flex items-start gap-2">
                    <span><?= $flashSuccess ? '✓' : '✕' ?></span>
                    <span><?= sanitize($flashSuccess ?: $flashError) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ════════════════════════════════════════
             LIVE MARKETS
        ════════════════════════════════════════ -->
        <div class="glass-card rounded-3xl p-3">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Live Markets
                </h3>
                <a href="markets.php" class="text-emerald-600 text-xs hover:text-emerald-500 transition font-semibold">View All →</a>
            </div>

            <div class="grid sm:grid-cols-3 gap-2">
                <?php
                $marketDefs = [
                    ['sym' => 'BTCUSDT',  'label' => 'Bitcoin',      'badge_bg' => 'bg-orange-500/15', 'badge_text' => 'text-orange-400', 'price_color' => 'text-orange-300'],
                    ['sym' => 'ETHUSDT',  'label' => 'Ethereum',     'badge_bg' => 'bg-indigo-500/15', 'badge_text' => 'text-indigo-400', 'price_color' => 'text-indigo-300'],
                    ['sym' => 'BNBUSDT',  'label' => 'BNB',          'badge_bg' => 'bg-yellow-500/15', 'badge_text' => 'text-yellow-500', 'price_color' => 'text-yellow-400'],
                    ['sym' => 'SOLUSDT',  'label' => 'Solana',       'badge_bg' => 'bg-purple-500/15', 'badge_text' => 'text-purple-400', 'price_color' => 'text-purple-300'],
                    ['sym' => 'USDTUSDT', 'label' => 'Tether',       'badge_bg' => 'bg-teal-500/15',   'badge_text' => 'text-teal-400',   'price_color' => 'text-teal-300'],
                ];
                foreach ($marketDefs as $m):
                    $base   = str_replace('USDT', '', $m['sym']);
                    $change = $priceChanges[$m['sym']] ?? 0;
                    $isPos  = $change >= 0;
                ?>
                <div class="glass-card rounded-2xl p-2 hover-lift cursor-pointer group">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 <?= $m['badge_bg'] ?> <?= $m['badge_text'] ?> rounded-full flex items-center justify-center font-black text-sm">
                                <?= $base ?>
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 text-sm leading-tight"><?= $base ?>/USDT</p>
                                <p class="text-[11px] text-slate-500"><?= $m['label'] ?></p>
                            </div>
                        </div>
                        <span class="flex items-center gap-1 text-[10px] bg-emerald-500/10 text-emerald-700 border border-emerald-500/25 px-2 py-1 rounded-full font-bold">
                            <span class="w-1 h-1 bg-emerald-400 rounded-full animate-pulse"></span>LIVE
                        </span>
                    </div>
                    <p class="text-2xl font-black <?= $m['price_color'] ?> tabular-nums mb-1">
                        $<?= number_format($prices[$m['sym']], 2) ?>
                    </p>
                    <p class="text-sm font-semibold <?= $isPos ? 'text-emerald-400' : 'text-red-400' ?>">
                        <?= $isPos ? '▲' : '▼' ?> <?= number_format(abs($change), 2) ?>%
                        <span class="text-[11px] text-slate-500 font-normal ml-1">24h</span>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ════════════════════════════════════════
             OPEN POSITIONS  +  ACCOUNT STATUS
        ════════════════════════════════════════ -->
        <div class="grid lg:grid-cols-2 gap-3">

            <?php if (!empty($openTrades)): ?>
            <!-- Open Positions -->
            <div class="glass-card rounded-3xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Open Positions
                    </h3>
                    <a href="trading.php" class="text-emerald-600 text-xs hover:text-emerald-500 transition font-semibold">View All →</a>
                </div>
                <div class="space-y-1">
                    <?php foreach (array_slice($openTrades, 0, 3) as $trade):
                        $curPrice   = price_for_symbol($trade['symbol']);
                        $openPrice  = (float)$trade['price_open'];
                        $qty        = (float)$trade['qty'];
                        $pnl        = $trade['side'] === 'buy'
                                        ? ($curPrice - $openPrice) * $qty
                                        : ($openPrice - $curPrice) * $qty;
                        $pnlPct     = $openPrice > 0 ? ($pnl / ($openPrice * $qty)) * 100 : 0;
                    ?>
                    <div class="glass-card rounded-2xl p-2 hover-lift">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="font-bold text-slate-900 text-sm"><?= sanitize($trade['symbol']) ?></p>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full font-bold <?= $trade['side']==='buy' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                                        <?= strtoupper($trade['side']) ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">Qty: <?= number_format($qty, 6) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold tabular-nums <?= $pnl >= 0 ? 'pnl-pos' : 'pnl-neg' ?>">
                                    <?= $pnl >= 0 ? '+' : '' ?>$<?= number_format(abs($pnl), 2) ?>
                                </p>
                                <p class="text-xs <?= $pnlPct >= 0 ? 'text-emerald-400' : 'text-red-400' ?> font-semibold">
                                    <?= $pnlPct >= 0 ? '▲' : '▼' ?> <?= number_format(abs($pnlPct), 2) ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Account Status + Quick Links -->
            <div class="space-y-2 <?= empty($openTrades) ? 'lg:col-span-2 grid lg:grid-cols-2 gap-2 !space-y-0' : '' ?>">

                <!-- Account Status -->
                <div class="bg-gradient-to-br from-emerald-50 via-white to-slate-50 border border-slate-200 rounded-3xl p-3">
                    <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Account Status
                    </h3>
                    <div class="space-y-1">
                        <?php
                        $statuses = [
                            ['label' => 'Email Verified',  'active' => true,                          'pulse' => false],
                            ['label' => '2FA Enabled',     'active' => true,                          'pulse' => false],
                            ['label' => 'Trading Active',  'active' => true,                          'pulse' => true],
                            ['label' => 'KYC Complete',    'active' => !empty($user['kyc_verified']), 'pulse' => false],
                        ];
                        foreach ($statuses as $s):
                        ?>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-600 text-sm"><?= $s['label'] ?></span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 <?= $s['active'] ? 'bg-emerald-500' : 'bg-slate-400' ?> rounded-full <?= ($s['active'] && $s['pulse']) ? 'animate-pulse' : '' ?>"></span>
                                <span class="text-xs <?= $s['active'] ? 'text-emerald-600' : 'text-slate-500' ?> font-semibold"><?= $s['active'] ? 'Active' : 'Pending' ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-2 gap-2">
                    
                    <a href="wallet.php"
                       class="glass-card hover:border-emerald-500/40 rounded-2xl p-2 text-center transition hover-lift group border border-slate-200">
                        <div class="w-10 h-10 bg-blue-500/15 rounded-xl flex items-center justify-center mx-auto mb-1 group-hover:bg-blue-500/25 transition">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition">Wallet</p>
                    </a>
                    <a href="documents.php"
                       class="glass-card hover:border-emerald-500/40 rounded-2xl p-2 text-center transition hover-lift group border border-slate-200">
                        <div class="w-10 h-10 bg-purple-500/15 rounded-xl flex items-center justify-center mx-auto mb-1 group-hover:bg-purple-500/25 transition">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-600 group-hover:text-slate-900 transition">Documents</p>
                    </a>
                    
                </div>
            </div>
        </div>

    </main>

    <!-- ════════════════════════════════════════
         NAVIGATION (desktop top-bar + mobile bottom-bar)
    ════════════════════════════════════════ -->
    <?php $activePage = 'index.php'; include '_nav.php'; ?>

    <!-- ════════════════════════════════════════
         SWAP LIVE-RATE JS
    ════════════════════════════════════════ -->
    <script>
    (function () {
        const PRICES   = <?= $pricesJson ?>;
        const DECIMALS = <?= json_encode(['BTC' => 6, 'ETH' => 6, 'BNB' => 4, 'SOL' => 4, 'USDT' => 2], JSON_THROW_ON_ERROR) ?>;

        const fromSel    = document.getElementById('swapFrom');
        const toSel      = document.getElementById('swapTo');
        const amountInp  = document.getElementById('swapAmount');
        const rateDisp   = document.getElementById('rateDisplay');
        const recvDisp   = document.getElementById('receiveDisplay');

        function fmt(n, dec) {
            return n.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }

        function updateRate() {
            const from   = fromSel.value;
            const to     = toSel.value;
            const amount = parseFloat(amountInp.value) || 0;

            if (from === to) {
                rateDisp.textContent  = 'Select different currencies';
                recvDisp.textContent  = '';
                return;
            }

            const fromUSD = PRICES[from] || 1;
            const toUSD   = PRICES[to]   || 1;
            const rate    = fromUSD / toUSD;

            const dec = DECIMALS[to] || 2;
            rateDisp.textContent = `1 ${from} = ${fmt(rate, dec)} ${to}`;

            if (amount > 0) {
                const recv = amount * rate;
                recvDisp.textContent = `You receive ≈ ${fmt(recv, dec)} ${to}`;
            } else {
                recvDisp.textContent = '';
            }
        }

        fromSel.addEventListener('change', updateRate);
        toSel.addEventListener('change', updateRate);
        amountInp.addEventListener('input', updateRate);

        // Prevent same-currency swap by auto-switching "To" when "From" changes
        fromSel.addEventListener('change', function () {
            if (fromSel.value === toSel.value) {
                const opts = Array.from(toSel.options).map(o => o.value);
                toSel.value = opts.find(v => v !== fromSel.value) || '';
                updateRate(); // Only needed here when "To" was auto-changed
            }
        });

        updateRate();
    })();
    </script>

</body>
</html>


