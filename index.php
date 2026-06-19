<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// If already logged in redirect to dashboard
$forceLoginView = isset($_GET['force_login']) && $_GET['force_login'] === '1';
if (isLoggedIn() && !$forceLoginView) {
    redirectByRole($_SESSION['role']);
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Find user by email
        $stmt = mysqli_prepare($conn, "SELECT user_id, `NAME` AS name, email, `PASSWORD` AS password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        $storedHash = $user['password'] ?? '';

        if ($user && $storedHash !== '' && password_verify($password, $storedHash)) {
            // Login successful — set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            redirectByRole($user['role']);
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADTS — Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="logo-icon">R</div>
            <h2>RADTS</h2>
            <p>St. Mary's Primary School, Karen</p>
        </div>

        <!-- Error message -->
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Login form -->
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

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
                <div class="form-helper">
                    <a href="forgot_password.php">Forgot password? (Teacher/Deputy/Admin)</a>
                </div>
            </div>

            <button type="submit" class="btn-login">Log In</button>

        </form>

    </div>
</div>

</body>
</html>