<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: /radts/index.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'teacher');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $allowedRoles = ['teacher', 'student_leader', 'deputy', 'admin'];

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Invalid role selected.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirm password do not match.';
    } else {
        $emailCheck = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($emailCheck, 's', $email);
        mysqli_stmt_execute($emailCheck);
        $existing = mysqli_stmt_get_result($emailCheck);

        if (mysqli_num_rows($existing) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = mysqli_prepare($conn, 'INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            mysqli_stmt_bind_param($insert, 'ssss', $name, $email, $passwordHash, $role);

            if (mysqli_stmt_execute($insert)) {
                $success = 'User account created successfully.';
                $_POST = [];
            } else {
                $error = 'Failed to create user account. Please try again.';
            }
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Create User Account</h1>
    <p>Add a new staff or student leader account with login email.</p>
</div>

<div class="card" style="max-width:620px">
    <div class="card-header">
        <h2>User Details</h2>
        <a href="/radts/dashboard/admin.php" class="btn btn-primary btn-sm">← Back to Admin Dashboard</a>
    </div>
    <div class="card-body">
        <?php if ($success !== ''): ?>
            <div class="success-msg">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error-msg">⚠ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group-block">
                <label class="form-label">Full Name *</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    placeholder="e.g. James Kamau"
                    required
                >
            </div>

            <div class="form-group-block">
                <label class="form-label">Email Address *</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    placeholder="e.g. teacher@stmarys.ac.ke"
                    required
                >
            </div>

            <div class="form-group-block">
                <label class="form-label">Role *</label>
                <select name="role" class="form-control" required>
                    <option value="teacher" <?php echo (($_POST['role'] ?? 'teacher') === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="student_leader" <?php echo (($_POST['role'] ?? '') === 'student_leader') ? 'selected' : ''; ?>>Student Leader</option>
                    <option value="deputy" <?php echo (($_POST['role'] ?? '') === 'deputy') ? 'selected' : ''; ?>>Deputy</option>
                    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Password *</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        minlength="8"
                        required
                    >
                </div>
                <div class="form-group-block">
                    <label class="form-label">Confirm Password *</label>
                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>