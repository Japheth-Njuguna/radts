<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$message = '';
$error = '';

function ensurePasswordResetSchema($conn) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        $userId = $user['user_id'] ?? null;

        if ($userId !== null) {
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

            $subject = 'RADTS Password Reset';
            $body = "Hello,\n\n" .
                "A password reset was requested for your RADTS account.\n\n" .
                "Use the link below to set a new password:\n" . $resetLink . "\n\n" .
                "This link expires in 15 minutes and can be used once.\n\n" .
                "If you did not request this reset, you can ignore this email.\n";
            $headers = "From: no-reply@stmarys.ac.ke\r\n" .
                "Reply-To: no-reply@stmarys.ac.ke\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n";

            $mailSent = @mail($email, $subject, $body, $headers);
            if (!$mailSent) {
                error_log('RADTS reset mail failed. Link for testing: ' . $resetLink);
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
