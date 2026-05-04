<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Send an email via AWS SES (SesV2Client).
 * Wraps in try/catch so a missing SES config won't crash the app.
 */
function send_email(string $to, string $subject, string $htmlBody): bool
{
    static $sdkLoaded = null;

    if ($sdkLoaded === null) {
        $vendorAutoload = WEB_ROOT . '/vendor/autoload.php';
        $sdkLoaded = file_exists($vendorAutoload);
        if ($sdkLoaded) {
            require_once $vendorAutoload;
        }
    }

    try {
        if (!$sdkLoaded) {
            error_log('[email] AWS SDK not installed; skipping email to ' . $to);
            return false;
        }

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
    $appUrl   = env('APP_URL', 'http://3commasbot.io');
    $resetUrl = $appUrl . '/reset_password.php?token=' . urlencode($token);
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
    $appUrl  = env('APP_URL', 'http://3commasbot.io');
    $dashUrl = $appUrl . '/app/index.php';
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

function send_verification_email(string $email, string $name, string $code, string $token): bool
{
    $appUrl     = env('APP_URL', 'http://3commasbot.io');
    $confirmUrl = $appUrl . '/verify_email.php?token=' . urlencode($token);
    $subject    = 'Welcome to 3Commas!';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
      <h2 style="color:#10b981;margin-bottom:8px;">Welcome to 3Commas!</h2>
      <p style="color:#cbd5e1;">Use the code below to confirm your 3Commas registration:</p>
      <p style="text-align:center;margin:28px 0;">
        <span style="display:inline-block;background:#111827;border:1px solid #334155;color:#10b981;padding:16px 36px;border-radius:10px;font-size:36px;font-weight:bold;letter-spacing:12px;">{$code}</span>
      </p>
      <p style="text-align:center;color:#94a3b8;margin-bottom:12px;">Or confirm automatically:</p>
      <p style="text-align:center;margin:0 0 8px;">
        <a href="{$confirmUrl}" style="background:#10b981;color:#fff;padding:13px 30px;border-radius:7px;text-decoration:none;font-weight:bold;display:inline-block;font-size:15px;">Confirm email</a>
      </p>
      <p style="font-size:12px;color:#64748b;text-align:center;margin-top:10px;">Use automatic confirmation in the same browser where you plan to open the 3Commas website</p>
      <hr style="border-color:#334155;margin:24px 0;">
      <p style="font-size:12px;color:#64748b;"><strong style="color:#94a3b8;">Note:</strong> Your confirmation code is valid for 30 minutes. Do not share it with anybody (including 3Commas team members) under any circumstances.</p>
    </div>
    HTML;
    return send_email($email, $subject, $html);
}

function send_login_notification_email(string $email, string $name, string $ip, string $userAgent, string $loginTime): bool
{
    $safeIp        = htmlspecialchars($ip,        ENT_QUOTES, 'UTF-8');
    $safeUa        = htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8');
    $safeTime      = htmlspecialchars($loginTime, ENT_QUOTES, 'UTF-8');
    $subject       = '3Commas Login Notification';
    $html = <<<HTML
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
      <h2 style="color:#10b981;">3Commas Login</h2>
      <p>Dear {$name},</p>
      <p>This is to notify you of a successful login to your account.</p>
      <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;">
        <tr style="border-bottom:1px solid #334155;">
          <td style="padding:10px 0;color:#94a3b8;width:130px;">Login Time</td>
          <td style="padding:10px 0;color:#f1f5f9;">{$safeTime} UTC</td>
        </tr>
        <tr style="border-bottom:1px solid #334155;">
          <td style="padding:10px 0;color:#94a3b8;">IP Address</td>
          <td style="padding:10px 0;color:#f1f5f9;">{$safeIp}</td>
        </tr>
        <tr>
          <td style="padding:10px 0;color:#94a3b8;vertical-align:top;">User Agent</td>
          <td style="padding:10px 0;color:#f1f5f9;word-break:break-all;">{$safeUa}</td>
        </tr>
      </table>
      <p style="font-size:14px;color:#cbd5e1;">If you were not the one to initiate this action or suspect there may be suspicious activity, please disable your account and contact our support at <a href="mailto:support@3commasbot.io" style="color:#10b981;">support@3commasbot.io</a> immediately. In this case your account may be blocked for security reasons, for 48 hours or more from the moment you contact our support team.</p>
      <hr style="border-color:#334155;margin:24px 0;">
      <p style="font-size:12px;color:#64748b;">3Commas Platform &middot; Automated Crypto Trading</p>
    </div>
    HTML;
    return send_email($email, $subject, $html);
}

function send_security_otp_email(string $email, string $name, string $otpCode, string $actionLabel = 'security action'): bool
{
        $safeAction = htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8');
        $subject = 'Your Security OTP Code - 3Commas';
        $html = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#0f172a;color:#fff;padding:32px;border-radius:8px;">
            <h2 style="color:#10b981;">Security Verification Code</h2>
            <p>Hi {$name},</p>
            <p>Use this one-time code to confirm your {$safeAction}:</p>
            <p style="text-align:center;margin:24px 0;">
                <span style="display:inline-block;background:#111827;border:1px solid #334155;color:#10b981;padding:12px 22px;border-radius:8px;font-size:28px;font-weight:bold;letter-spacing:6px;">{$otpCode}</span>
            </p>
            <p>This code expires in 10 minutes. If you did not initiate this request, secure your account immediately.</p>
            <hr style="border-color:#334155;margin:24px 0;">
            <p style="font-size:12px;color:#64748b;">3Commas Platform - Automated Crypto Trading</p>
        </div>
        HTML;
        return send_email($email, $subject, $html);
}
