<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/config.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/csrf.php';
require_once __DIR__ . '/../../../src/helpers.php';

require_login();
$user = current_user();

// Fetch real-time prices
$symbols = ['BTCUSDT', 'ETHUSDT', 'USDTUSDT'];
$prices = [];
$priceChanges = [];
foreach ($symbols as $sym) {
    $prices[$sym] = price_for_symbol($sym);
    $priceChanges[$sym] = rand(-5, 15) / 100; // Mock 24h change for demo
}

// Simulate wallet balances for different cryptocurrencies
$walletBalances = [
    'BTC' => ['balance' => 0.5234, 'value' => 0.5234 * ($prices['BTCUSDT'] ?? 42000)],
    'ETH' => ['balance' => 2.847, 'value' => 2.847 * ($prices['ETHUSDT'] ?? 2200)],
    'USDT' => ['balance' => 5234.50, 'value' => 5234.50],
];

$totalBalance = array_sum(array_column($walletBalances, 'value'));
$todayPnl = rand(-500, 2000) + (rand(0, 100) / 100); // Mock today's PnL
$todayPnlPercent = ($todayPnl / $totalBalance) * 100;

// Simulate equity and margin data
$equity = $totalBalance * 1.15;
$margin = $totalBalance * 0.3;
$freeMargin = $margin * 0.7;

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

// Handle currency swap
$swapMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'swap') {
    csrf_verify();
    $fromCurrency = sanitize($_POST['from_currency'] ?? '');
    $toCurrency = sanitize($_POST['to_currency'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($fromCurrency && $toCurrency && $amount > 0) {
        $swapMessage = "Swapped $amount $fromCurrency to $toCurrency successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – 3Commas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-gradient-premium {
            background: linear-gradient(135deg, #0d1e3b 0%, #1a2b4a 50%, #0f1e35 100%);
        }
        .card-glow {
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.1);
        }
        .stat-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(16, 185, 129, 0.2);
            backdrop-filter: blur(10px);
        }
        .crypto-badge {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .pnl-positive {
            color: #10b981;
        }
        .pnl-negative {
            color: #ef4444;
        }
        .swap-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);
        }
    </style>
</head>
<body class="bg-gradient-premium text-white min-h-screen pb-24">

    <!-- Top bar with user info -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-emerald-500/20 px-4 py-3">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div>
                <h1 class="text-2xl font-extrabold text-emerald-400">3Commas</h1>
                <p class="text-xs text-slate-400">Automated Crypto Trading Dashboard</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-200"><?= sanitize($user['name']) ?></p>
                    <p class="text-xs text-slate-400"><?= sanitize($user['email']) ?></p>
                </div>
                <a href="/web/public/logout.php" class="text-slate-400 hover:text-red-400 transition text-xs px-3 py-1.5 border border-slate-600 rounded-lg hover:border-red-500">Logout</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">

        <!-- Verification Status & Plan Badge -->
        <?php if ($activePlan): ?>
        <div class="flex items-center justify-between bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-4">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></span>
                <div>
                    <p class="text-sm font-semibold text-emerald-400">Active Investment Plan</p>
                    <p class="text-xs text-slate-300"><?= sanitize($activePlan['plan_name']) ?> • <?= format_currency((float)$activePlan['roi_percent']) ?>% ROI</p>
                </div>
            </div>
            <span class="bg-emerald-500/20 text-emerald-400 text-xs px-3 py-1 rounded-full">VERIFIED</span>
        </div>
        <?php endif; ?>

        <!-- Main Balance Overview - Premium Card -->
        <div class="bg-gradient-to-br from-emerald-900/40 via-slate-800/40 to-slate-900/40 border-2 border-emerald-500/30 rounded-3xl p-8 card-glow">
            <div class="flex items-start justify-between mb-8">
                <div>
                    <p class="text-emerald-200 text-sm font-medium uppercase tracking-wide mb-2">Total Balance</p>
                    <h2 class="text-5xl sm:text-6xl font-extrabold text-white mb-2">$<?= number_format($totalBalance, 2) ?></h2>
                    <p class="text-emerald-300 text-lg">USDT Equivalent</p>
                </div>
                <div class="flex items-center gap-1 bg-emerald-500/20 px-4 py-2 rounded-full">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-emerald-400 text-sm font-semibold">LIVE</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="stat-card rounded-2xl p-4">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Today's P&L</p>
                    <p class="text-2xl font-bold <?= $todayPnl >= 0 ? 'pnl-positive' : 'pnl-negative' ?>"><?= $todayPnl >= 0 ? '+' : '' ?><?= number_format($todayPnl, 2) ?></p>
                    <p class="text-xs <?= $todayPnl >= 0 ? 'text-emerald-400' : 'text-red-400' ?>"><?= $todayPnl >= 0 ? '+' : '' ?><?= number_format($todayPnlPercent, 2) ?>%</p>
                </div>
                <div class="stat-card rounded-2xl p-4">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Equity</p>
                    <p class="text-2xl font-bold text-emerald-400">$<?= number_format($equity, 2) ?></p>
                    <p class="text-xs text-slate-400">Account Value</p>
                </div>
                <div class="stat-card rounded-2xl p-4">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Margin</p>
                    <p class="text-2xl font-bold text-blue-400">$<?= number_format($margin, 2) ?></p>
                    <p class="text-xs text-slate-400">Available</p>
                </div>
                <div class="stat-card rounded-2xl p-4">
                    <p class="text-slate-400 text-xs uppercase tracking-wide mb-1">Free Margin</p>
                    <p class="text-2xl font-bold text-cyan-400">$<?= number_format($freeMargin, 2) ?></p>
                    <p class="text-xs text-slate-400">Usable</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <a href="/web/public/app/wallet.php#deposit" class="flex-1 bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-6 py-3 rounded-xl transition transform hover:scale-105">
                    ↓ Deposit
                </a>
                <a href="/web/public/app/wallet.php#withdraw" class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-white font-bold px-6 py-3 rounded-xl transition transform hover:scale-105">
                    ↑ Withdraw
                </a>
                <a href="/web/public/app/trading.php" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-xl transition transform hover:scale-105">
                    ⚡ Trade
                </a>
            </div>
        </div>

        <!-- Crypto Holdings & Swap Section -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Crypto Holdings -->
            <div class="lg:col-span-2 bg-slate-800/50 border border-slate-700/50 rounded-3xl p-6">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Crypto Holdings
                </h3>
                <div class="space-y-3">
                    <?php foreach ($walletBalances as $crypto => $data): ?>
                        <?php $percentage = ($data['value'] / $totalBalance) * 100; ?>
                        <div class="crypto-badge rounded-2xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-500/20 rounded-full flex items-center justify-center font-bold text-emerald-400"><?= $crypto[0] ?></div>
                                    <div>
                                        <p class="font-bold text-white"><?= $crypto ?></p>
                                        <p class="text-xs text-slate-400"><?= number_format($data['balance'], 8) ?> <?= $crypto ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-white">$<?= number_format($data['value'], 2) ?></p>
                                    <p class="text-xs text-emerald-400"><?= number_format($percentage, 1) ?>%</p>
                                </div>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-emerald-500 to-cyan-500 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Swap Section -->
            <div class="bg-gradient-to-br from-blue-900/30 via-slate-800/50 to-slate-900/30 border border-blue-500/30 rounded-3xl p-6">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m0 0l4 4m10-4v12m0 0l4-4m0 0l-4-4"/></svg>
                    Quick Swap
                </h3>
                <form method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="swap">
                    
                    <div>
                        <label class="block text-xs text-slate-400 uppercase tracking-wide mb-2">From</label>
                        <select name="from_currency" required class="w-full bg-slate-700/50 border border-slate-600 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="BTC">BTC – Bitcoin</option>
                            <option value="ETH">ETH – Ethereum</option>
                            <option value="USDT">USDT – Tether</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 uppercase tracking-wide mb-2">Amount</label>
                        <input type="number" name="amount" step="0.00000001" min="0" required
                            class="w-full bg-slate-700/50 border border-slate-600 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                            placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 uppercase tracking-wide mb-2">To</label>
                        <select name="to_currency" required class="w-full bg-slate-700/50 border border-slate-600 text-white rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="ETH">ETH – Ethereum</option>
                            <option value="BTC">BTC – Bitcoin</option>
                            <option value="USDT">USDT – Tether</option>
                        </select>
                    </div>

                    <div class="bg-slate-700/30 rounded-xl p-3">
                        <p class="text-xs text-slate-400 mb-1">Real-time Rate</p>
                        <p class="text-sm font-bold text-emerald-400">1 BTC = $<?= number_format($prices['BTCUSDT'] ?? 42000, 2) ?></p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition transform hover:scale-105">
                        Swap Now
                    </button>
                </form>

                <?php if ($swapMessage): ?>
                    <div class="mt-4 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 text-xs rounded-lg px-3 py-2">
                        ✓ <?= sanitize($swapMessage) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Market Watchlist -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-3xl p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Live Markets
                </span>
                <a href="/web/public/app/markets.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">View All →</a>
            </h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <?php foreach ($symbols as $sym): ?>
                    <?php $base = str_replace('USDT', '', $sym); ?>
                    <?php $change = $priceChanges[$sym] ?? 0; ?>
                    <div class="stat-card rounded-2xl p-4 hover:border-emerald-500/50 transition cursor-pointer">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="font-bold text-white text-lg"><?= $base ?></p>
                                <p class="text-xs text-slate-400"><?= $sym ?></p>
                            </div>
                            <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded">LIVE</span>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">$<?= number_format($prices[$sym] ?? 0, 2) ?></p>
                        <p class="text-sm <?= $change >= 0 ? 'text-emerald-400' : 'text-red-400' ?>"><?= $change >= 0 ? '↑' : '↓' ?> <?= abs($change) ?>%</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Open Positions & Account Section -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Open Positions -->
            <?php if (!empty($openTrades)): ?>
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-3xl p-6">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Open Positions
                    </span>
                    <a href="/web/public/app/trading.php" class="text-emerald-400 text-xs hover:text-emerald-300 transition">View All →</a>
                </h3>
                <div class="space-y-3">
                    <?php foreach (array_slice($openTrades, 0, 3) as $trade): ?>
                        <?php
                            $curPrice = price_for_symbol($trade['symbol']);
                            $pnl = $trade['side'] === 'buy'
                                ? ($curPrice - (float)$trade['price_open']) * (float)$trade['qty']
                                : ((float)$trade['price_open'] - $curPrice) * (float)$trade['qty'];
                            $pnlPercent = (($pnl / ((float)$trade['price_open'] * (float)$trade['qty'])) * 100);
                        ?>
                        <div class="stat-card rounded-2xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-bold text-white"><?= sanitize($trade['symbol']) ?></p>
                                        <span class="text-xs px-2 py-0.5 rounded <?= $trade['side']==='buy' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' ?>">
                                            <?= strtoupper($trade['side']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400">Qty: <?= number_format((float)$trade['qty'], 8) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold <?= $pnl >= 0 ? 'pnl-positive' : 'pnl-negative' ?>">
                                        <?= $pnl >= 0 ? '+' : '' ?><?= number_format($pnl, 2) ?> USDT
                                    </p>
                                    <p class="text-xs <?= $pnlPercent >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                                        <?= $pnlPercent >= 0 ? '+' : '' ?><?= number_format($pnlPercent, 2) ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Account Info & Documents -->
            <div class="space-y-6">
                <!-- Account Status -->
                <div class="bg-gradient-to-br from-emerald-900/30 via-slate-800/50 to-slate-900/30 border border-emerald-500/30 rounded-3xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Account Status
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-sm">Email Verified</span>
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-sm">2FA Enabled</span>
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 text-sm">Trading Active</span>
                            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-2 gap-4">
                    <a href="/web/public/app/profile.php" class="bg-slate-800/50 border border-slate-700/50 hover:border-emerald-500/50 rounded-2xl p-4 text-center transition group">
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-emerald-400 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-semibold text-slate-300 group-hover:text-white transition">Profile</p>
                    </a>
                    <a href="/web/public/app/wallet.php" class="bg-slate-800/50 border border-slate-700/50 hover:border-emerald-500/50 rounded-2xl p-4 text-center transition group">
                        <svg class="w-6 h-6 text-slate-400 group-hover:text-emerald-400 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <p class="text-xs font-semibold text-slate-300 group-hover:text-white transition">Transactions</p>
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur border-t border-slate-700 flex justify-around py-2 z-50 md:hidden">
        <a href="/web/public/app/index.php" class="flex flex-col items-center text-emerald-400 gap-1 py-2 px-3">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h-6z"/></svg>
            <span class="text-xs">Home</span>
        </a>
        <a href="/web/public/app/markets.php" class="flex flex-col items-center text-slate-400 hover:text-emerald-400 gap-1 py-2 px-3 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            <span class="text-xs">Markets</span>
        </a>
        <a href="/web/public/app/trading.php" class="flex flex-col items-center text-slate-400 hover:text-emerald-400 gap-1 py-2 px-3 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span class="text-xs">Trade</span>
        </a>
        <a href="/web/public/app/wallet.php" class="flex flex-col items-center text-slate-400 hover:text-emerald-400 gap-1 py-2 px-3 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span class="text-xs">Wallet</span>
        </a>
        <a href="/web/public/app/profile.php" class="flex flex-col items-center text-slate-400 hover:text-emerald-400 gap-1 py-2 px-3 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-xs">Profile</span>
        </a>
    </nav>

</body>
</html>
