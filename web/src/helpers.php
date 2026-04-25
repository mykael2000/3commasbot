<?php
declare(strict_types=1);

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): string
{
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function sanitize(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Fetch live price from Binance public API.
 * Falls back to mock prices if the request fails.
 */
function price_for_symbol(string $symbol): float
{
    static $cache = [];
    $symbol = strtoupper(trim($symbol));

    if (isset($cache[$symbol])) {
        return $cache[$symbol];
    }

    $mock = [
        'BTCUSDT'  => 65000.0,
        'ETHUSDT'  => 3200.0,
        'BNBUSDT'  => 580.0,
        'SOLUSDT'  => 155.0,
        'ADAUSDT'  => 0.45,
        'XRPUSDT'  => 0.52,
        'DOGEUSDT' => 0.12,
    ];

    try {
        $url = 'https://api.binance.com/api/v3/ticker/price?symbol=' . urlencode($symbol);
        $ctx = stream_context_create([
            'http' => ['timeout' => 3, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw !== false) {
            $data = json_decode($raw, true);
            if (isset($data['price'])) {
                $price = (float) $data['price'];
                $cache[$symbol] = $price;
                return $price;
            }
        }
    } catch (Throwable) {
        // fall through to mock
    }

    $price = $mock[$symbol] ?? 1.0;
    $cache[$symbol] = $price;
    return $price;
}

function format_currency(float $n, int $decimals = 2): string
{
    return number_format($n, $decimals, '.', ',');
}
