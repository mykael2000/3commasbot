<?php
/**
 * TEMPORARY email debug page — DELETE before going live.
 * Access: http://localhost:8000/mail_test.php
 */
declare(strict_types=1);

// Basic protection — remove or lock this down after testing
if (!isset($_GET['secret']) || $_GET['secret'] !== 'debug1234') {
    http_response_code(403);
    exit('Forbidden. Add ?secret=debug1234 to the URL.');
}

require_once __DIR__ . '/../src/config.php';

// Show all errors on screen
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo '<pre>';
echo "WEB_ROOT: " . WEB_ROOT . "\n";
echo "vendor/autoload.php exists: " . (file_exists(WEB_ROOT . '/vendor/autoload.php') ? 'YES' : 'NO') . "\n";
echo "SES_FROM_EMAIL: " . env('SES_FROM_EMAIL', '(not set)') . "\n";
echo "AWS_REGION: "      . env('AWS_REGION', '(not set)') . "\n";
echo "AWS_ACCESS_KEY_ID: " . (env('AWS_ACCESS_KEY_ID', '') !== '' ? 'SET (' . substr(env('AWS_ACCESS_KEY_ID'), 0, 4) . '...)' : '(not set)') . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . (env('AWS_SECRET_ACCESS_KEY', '') !== '' ? 'SET' : '(not set)') . "\n\n";

require_once __DIR__ . '/../src/email.php';

$to = $_GET['to'] ?? '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Usage: mail_test.php?secret=debug1234&to=you@example.com\n";
    echo '</pre>';
    exit;
}

echo "Sending test email to: $to\n";

$result = send_email(
    $to,
    'Test Email from 3Commas',
    '<h2>It works!</h2><p>This is a test email sent at ' . date('Y-m-d H:i:s') . '.</p>'
);

echo $result ? "\nSUCCESS: email sent.\n" : "\nFAILED: check error_log above or XAMPP/server PHP error log.\n";
echo '</pre>';
