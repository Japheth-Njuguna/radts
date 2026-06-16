<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS password_reset_requests (
                request_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                email VARCHAR(150) NOT NULL,
                status ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
                requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        $userId = $user['user_id'] ?? null;

        $insert = mysqli_prepare($conn, "INSERT INTO password_reset_requests (user_id, email) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert, 'is', $userId, $email);
        mysqli_stmt_execute($insert);

        // Keep response generic so account existence is not disclosed.
        $message = 'If the email exists in our system, a reset request has been recorded. Please contact the administrator to complete the reset.';
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
            <p>Enter your account email to request a reset.</p>
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
