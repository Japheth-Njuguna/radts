<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: /radts/index.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : $id;
}

if ($id <= 0) {
    header('Location: /radts/dashboard/admin.php');
    exit();
}

$success = '';
$error = '';
$allowedRoles = ['teacher', 'student_leader', 'deputy', 'admin'];
$allowedStudentAdmissions = [
    '1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008',
    '1009', '1010', '1011', '1012', '1013', '1014', '1015', '1016'
];

$load = mysqli_prepare($conn, 'SELECT user_id, `NAME` AS name, email, role FROM users WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($load, 'i', $id);
mysqli_stmt_execute($load);
$userResult = mysqli_stmt_get_result($load);
$user = mysqli_fetch_assoc($userResult);

if (!$user) {
    header('Location: /radts/dashboard/admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'teacher');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = 'Invalid role selected.';
    } elseif (in_array($role, ['teacher', 'deputy', 'admin'], true) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address for staff accounts.';
    } elseif (in_array($role, ['teacher', 'deputy', 'admin'], true) && !preg_match('/@gmail\.com$/i', $email)) {
        $error = 'Teachers, deputies and admins must use a Gmail address format (example: jameskamau@gmail.com).';
    } elseif ($role === 'student_leader' && !in_array($email, $allowedStudentAdmissions, true)) {
        $error = 'Student leader login ID must be one of the admission numbers 1001 to 1016.';
    } else {
        $emailCheck = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        mysqli_stmt_bind_param($emailCheck, 'si', $email, $id);
        mysqli_stmt_execute($emailCheck);
        $existing = mysqli_stmt_get_result($emailCheck);

        if (mysqli_num_rows($existing) > 0) {
            $error = 'Another account already uses that email.';
        }
    }

    if ($error === '' && $role !== 'student_leader') {
        if ($newPassword !== '' || $confirmPassword !== '') {
            if ($newPassword === '' || $confirmPassword === '') {
                $error = 'Fill both password fields to reset password.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Password and confirm password do not match.';
            }
        }
    }

    if ($error === '') {
        if ($role === 'student_leader') {
            $studentHash = password_hash('password', PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, 'UPDATE users SET `NAME` = ?, email = ?, role = ?, `PASSWORD` = ? WHERE user_id = ?');
            mysqli_stmt_bind_param($update, 'ssssi', $name, $email, $role, $studentHash, $id);
            $ok = mysqli_stmt_execute($update);
            if ($ok) {
                $success = 'User updated. Student leader password reset to: password';
            } else {
                $error = 'Failed to update user. Please try again.';
            }
        } else {
            if ($newPassword !== '') {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = mysqli_prepare($conn, 'UPDATE users SET `NAME` = ?, email = ?, role = ?, `PASSWORD` = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($update, 'ssssi', $name, $email, $role, $newHash, $id);
            } else {
                $update = mysqli_prepare($conn, 'UPDATE users SET `NAME` = ?, email = ?, role = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($update, 'sssi', $name, $email, $role, $id);
            }

            $ok = mysqli_stmt_execute($update);
            if ($ok) {
                $success = 'User updated successfully.';
            } else {
                $error = 'Failed to update user. Please try again.';
            }
        }

        if ($error === '') {
            $load = mysqli_prepare($conn, 'SELECT user_id, `NAME` AS name, email, role FROM users WHERE user_id = ? LIMIT 1');
            mysqli_stmt_bind_param($load, 'i', $id);
            mysqli_stmt_execute($load);
            $userResult = mysqli_stmt_get_result($load);
            $user = mysqli_fetch_assoc($userResult);
            $_POST = [];
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Edit User Account</h1>
    <p>Update account details, role, and password settings.</p>
</div>

<div class="card" style="max-width:700px">
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
            <input type="hidden" name="id" value="<?php echo (int)$user['user_id']; ?>">

            <div class="form-group-block">
                <label class="form-label">Full Name *</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>"
                    required
                >
            </div>

            <div class="form-group-block">
                <label class="form-label">Login ID *</label>
                <input
                    type="text"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>"
                    placeholder="Teacher/Admin/Deputy: name@gmail.com | Student Leader: 1001-1016"
                    required
                >
                <small style="color:#6B7280;font-size:11px;margin-top:4px;display:block">
                    Teacher/Deputy/Admin: use Gmail format. Student leader: use admission number (1001-1016).
                </small>
            </div>

            <div class="form-group-block">
                <label class="form-label">Role *</label>
                <?php $selectedRole = $_POST['role'] ?? $user['role']; ?>
                <select name="role" class="form-control" required>
                    <option value="teacher" <?php echo ($selectedRole === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    <option value="student_leader" <?php echo ($selectedRole === 'student_leader') ? 'selected' : ''; ?>>Student Leader</option>
                    <option value="deputy" <?php echo ($selectedRole === 'deputy') ? 'selected' : ''; ?>>Deputy</option>
                    <option value="admin" <?php echo ($selectedRole === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <small style="color:#6B7280;font-size:11px;margin-top:4px;display:block">
                    Student leader accounts are always reset to password: password when saved.
                </small>
            </div>

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">New Password (Optional)</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        minlength="8"
                        placeholder="Leave blank to keep current password"
                    >
                </div>
                <div class="form-group-block">
                    <label class="form-label">Confirm New Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                        placeholder="Re-enter new password"
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-warning">Update User</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
