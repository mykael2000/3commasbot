<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Send an email via AWS SES (SesV2Client).
 * Wraps in try/catch so a missing SES config won't crash the app.
 */
function send_email(string $to, string $subject, string $htmlBody): bool
{
    try {
        // Attempt to use AWS SDK if autoloaded
        $vendorAutoload = WEB_ROOT . '/vendor/autoload.php';
        if (!file_exists($vendorAutoload)) {
            error_log('[email] AWS SDK not installed; skipping email to ' . $to);
            return false;
        }
        require_once $vendorAutoload;

        $fromEmail = env('SES_FROM_EMAIL', '');
        $fromName  = env('SES_FROM_NAME', '3Commas');
        if ($fromEmail === '') {
            error_log('[email] SES_FROM_EMAIL not configured; skipping email to ' . $to);
            return false;
        }

        $client = new \Aws\SesV2\SesV2Client([
            'version'     => 'latest',
            'region'      => env('AWS_REGION', 'us-east-1'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID', ''),
                'secret' => env('AWS_SECRET_ACCESS_KEY', ''),
            ],
        ]);

        $client->sendEmail([
            'FromEmailAddress' => sprintf('%s <%s>', $fromName, $fromEmail),
            'Destination'      => ['ToAddresses' => [$to]],
            'Content'          => [
                'Simple' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body'    => [
                        'Html' => ['Data' => $htmlBody, 'Charset' => 'UTF-8'],
                    ],
                ],
            ],
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('[email] Failed to send to ' . $to . ': ' . $e->getMessage());
        return false;
    }
}

function send_password_reset_email(string $email, string $token, string $name): bool
{
    $appUrl   = env('APP_URL', 'http://localhost:8000');
    $resetUrl = $appUrl . '/web/public/reset_password.php?token=' . urlencode($token);
    $subject  = 'Reset Your Password – 3Commas';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
      <h2 style="color:#10b981;">3Commas – Password Reset</h2>
      <p>Hi {$name},</p>
      <p>We received a request to reset your password. Click the button below to set a new password. This link expires in 1 hour.</p>
      <p style="text-align:center;margin:32px 0;">
        <a href="{$resetUrl}" style="background:#10b981;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;">Reset Password</a>
      </p>
      <p>If you did not request a password reset, you can safely ignore this email.</p>
      <hr style="border-color:#334155;margin:24px 0;">
      <p style="font-size:12px;color:#64748b;">3Commas Platform &middot; Automated Crypto Trading</p>
    </div>
    HTML;
    return send_email($email, $subject, $html);
}

function send_welcome_email(string $email, string $name): bool
{
    $appUrl  = env('APP_URL', 'http://localhost:8000');
    $dashUrl = $appUrl . '/web/public/app/index.php';
    $subject = 'Welcome to 3Commas!';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
      <h2 style="color:#10b981;">Welcome to 3Commas, {$name}!</h2>
      <p>Your account has been created successfully. You can now log in and start exploring automated crypto trading.</p>
      <p style="text-align:center;margin:32px 0;">
        <a href="{$dashUrl}" style="background:#10b981;color:#fff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;">Go to Dashboard</a>
      </p>
      <hr style="border-color:#334155;margin:24px 0;">
      <p style="font-size:12px;color:#64748b;">3Commas Platform &middot; Automated Crypto Trading</p>
    </div>
    HTML;
    return send_email($email, $subject, $html);
}

function send_withdrawal_status_email(
    string $email,
    string $name,
    string $status,
    string $amount,
    string $asset,
    string $note
): bool {
    $statusLabel = ucfirst($status);
    $statusColor = $status === 'approved' ? '#10b981' : '#ef4444';
    $subject     = "Withdrawal Request {$statusLabel} – 3Commas";
    $noteHtml    = $note ? "<p><strong>Note:</strong> " . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . "</p>" : '';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
      <h2 style="color:{$statusColor};">Withdrawal {$statusLabel}</h2>
      <p>Hi {$name},</p>
      <p>Your withdrawal request for <strong>{$amount} {$asset}</strong> has been <strong style="color:{$statusColor};">{$statusLabel}</strong>.</p>
      {$noteHtml}
      <hr style="border-color:#334155;margin:24px 0;">
      <p style="font-size:12px;color:#64748b;">3Commas Platform &middot; Automated Crypto Trading</p>
    </div>
    HTML;
    return send_email($email, $subject, $html);
}
