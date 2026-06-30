<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/includes/mail_config.php')) {
    require_once __DIR__ . '/includes/mail_config.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';
$debugResetLink = '';
$debugMailIssue = '';

function ensurePasswordResetSchema(mysqli $conn): void {
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS password_reset_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            email VARCHAR(150) NOT NULL,
            status ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD COLUMN IF NOT EXISTS token_hash VARCHAR(64) NULL AFTER email");
    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER status");
    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD COLUMN IF NOT EXISTS used_at DATETIME NULL AFTER expires_at");
    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD INDEX IF NOT EXISTS idx_prr_email (email)");
    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD INDEX IF NOT EXISTS idx_prr_token_hash (token_hash)");
}

ensurePasswordResetSchema($conn);

function isLocalRequest(): bool {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    return str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '[::1]');
}

function envValue(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }

    return $default;
}

function smtpValue(string $key, string $default = ''): string {
    if (defined($key) && constant($key) !== '') {
        return (string)constant($key);
    }

    return envValue($key, $default);
}

function sendResetEmail(string $recipientEmail, string $resetLink, string &$failureReason = ''): bool {
    $smtpHost = smtpValue('RADTS_SMTP_HOST', 'smtp.gmail.com');
    $smtpPort = (int)smtpValue('RADTS_SMTP_PORT', '587');
    $smtpUser = smtpValue('RADTS_SMTP_USER');
    $smtpPass = smtpValue('RADTS_SMTP_PASS');
    $smtpSecure = strtolower(smtpValue('RADTS_SMTP_SECURE', 'tls'));
    $fromEmail = smtpValue('RADTS_SMTP_FROM', $smtpUser !== '' ? $smtpUser : 'no-reply@stmarys.ac.ke');
    $fromName = smtpValue('RADTS_SMTP_FROM_NAME', 'RADTS');

    if ($smtpUser === '' || $smtpPass === '') {
        $failureReason = 'SMTP credentials are missing. Set RADTS_SMTP_USER and RADTS_SMTP_PASS in your environment (.env file).';
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        if ($smtpSecure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = 'RADTS Password Reset';
        $safeResetLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $mail->Body = '<p>Hello,</p>'
            . '<p>A password reset was requested for your RADTS account.</p>'
            . '<p><a href="' . $safeResetLink . '"><strong>Reset Password</strong></a></p>'
            . '<p>This link expires in 15 minutes and can be used once.</p>'
            . '<p>If you did not request this reset, you can ignore this email.</p>';
        $mail->AltBody = "Hello,\n\n"
            . "A password reset was requested for your RADTS account.\n\n"
            . "Reset Password: " . $resetLink . "\n\n"
            . "This link expires in 15 minutes and can be used once.\n\n"
            . "If you did not request this reset, you can ignore this email.\n";

        return $mail->send();
    } catch (Exception $e) {
        $failureReason = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
        error_log('RADTS SMTP mail failed: ' . $failureReason);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, role FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        $userId = $user['user_id'] ?? null;
        $userRole = $user['role'] ?? '';

        if ($userId !== null && in_array($userRole, ['teacher', 'deputy', 'admin'], true)) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO password_reset_requests (user_id, email, token_hash, status, expires_at) VALUES (?, ?, ?, 'pending', ?)"
            );
            mysqli_stmt_bind_param($insert, 'isss', $userId, $email, $tokenHash, $expiresAt);
            mysqli_stmt_execute($insert);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetLink = $scheme . '://' . $host . '/radts/reset_password.php?token=' . urlencode($token);

            $mailFailureReason = '';
            $mailSent = sendResetEmail($email, $resetLink, $mailFailureReason);
            if (!$mailSent) {
                error_log('RADTS reset mail failed. Link for testing: ' . $resetLink);
                // Local XAMPP setups often do not have SMTP configured.
                // Show a temporary link only on local requests to unblock testing.
                if (isLocalRequest()) {
                    $debugResetLink = $resetLink;
                    $debugMailIssue = $mailFailureReason;
                }
            }
        }

        // Keep response generic so account existence is not disclosed.
        $message = 'If the email exists in our system, a password reset link has been sent.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADTS - Forgot Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon">R</div>
            <h2>Forgot Password</h2>
            <p>Enter your account email to receive a reset link.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="info-msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($debugResetLink !== ''): ?>
            <div class="info-msg" style="margin-top:0">
                Email delivery is not configured on this local server. Use this temporary reset link:<br>
                <a href="<?php echo htmlspecialchars($debugResetLink); ?>"><?php echo htmlspecialchars($debugResetLink); ?></a>
            </div>
        <?php endif; ?>

        <?php if ($debugMailIssue !== '' && isLocalRequest()): ?>
            <div class="error-msg" style="margin-top:0">
                SMTP issue: <?php echo htmlspecialchars($debugMailIssue); ?>
            </div>
        <?php endif; ?>

        <div class="info-msg" style="margin-top:0">
            Password reset link is available for teacher, deputy and admin accounts only.
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="e.g. admin@stmarys.ac.ke"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
            </div>

            <button type="submit" class="btn-login">Request Password Reset</button>

            <div class="form-helper" style="text-align:center;margin-top:16px">
                <a href="index.php?force_login=1">Back to Login</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
