<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

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
    mysqli_query($conn, "ALTER TABLE password_reset_requests ADD INDEX IF NOT EXISTS idx_prr_token_hash (token_hash)");
}

ensurePasswordResetSchema($conn);

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = '';
$success = '';
$request = null;

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'Invalid password reset link.';
} else {
    $tokenHash = hash('sha256', $token);
    $stmt = mysqli_prepare(
        $conn,
        "SELECT request_id, user_id, email, status, expires_at, used_at
         FROM password_reset_requests
         WHERE token_hash = ?
         ORDER BY request_id DESC
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 's', $tokenHash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);

    if (!$request) {
        $error = 'This reset link is invalid.';
    } elseif ($request['status'] !== 'pending' || $request['used_at'] !== null) {
        $error = 'This reset link has already been used.';
    } elseif ($request['expires_at'] === null || strtotime($request['expires_at']) < time()) {
        $error = 'This reset link has expired. Please request a new one.';
    }
}

if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirmPassword === '') {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirm password do not match.';
    } else {
        mysqli_begin_transaction($conn);

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $updateUser = mysqli_prepare($conn, "UPDATE users SET `PASSWORD` = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($updateUser, 'si', $passwordHash, $request['user_id']);
            mysqli_stmt_execute($updateUser);

            $completeRequest = mysqli_prepare(
                $conn,
                "UPDATE password_reset_requests SET status = 'completed', used_at = NOW() WHERE request_id = ?"
            );
            mysqli_stmt_bind_param($completeRequest, 'i', $request['request_id']);
            mysqli_stmt_execute($completeRequest);

            $rejectOthers = mysqli_prepare(
                $conn,
                "UPDATE password_reset_requests
                 SET status = 'rejected'
                 WHERE user_id = ? AND request_id <> ? AND status = 'pending'"
            );
            mysqli_stmt_bind_param($rejectOthers, 'ii', $request['user_id'], $request['request_id']);
            mysqli_stmt_execute($rejectOthers);

            mysqli_commit($conn);
            $success = 'Your password has been reset successfully. You can now log in.';
        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADTS - Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon">R</div>
            <h2>Reset Password</h2>
            <p>Set a new password for your account.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <div class="form-helper" style="text-align:center;margin-top:10px">
                <a href="forgot_password.php">Request a new reset link</a>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <div class="form-helper" style="text-align:center;margin-top:10px">
                <a href="index.php?force_login=1">Go to Login</a>
            </div>
        <?php endif; ?>

        <?php if ($error === '' && $success === ''): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="At least 8 characters"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter password"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Reset Password</button>

                <div class="form-helper" style="text-align:center;margin-top:16px">
                    <a href="index.php?force_login=1">Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
