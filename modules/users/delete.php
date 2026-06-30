<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: /radts/index.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /radts/dashboard/admin.php?error=' . urlencode('Invalid user selected for deletion.'));
    exit();
}

if ((int)($_SESSION['user_id'] ?? 0) === $id) {
    header('Location: /radts/dashboard/admin.php?error=' . urlencode('You cannot delete your own account while logged in.'));
    exit();
}

$existsStmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($existsStmt, 'i', $id);
mysqli_stmt_execute($existsStmt);
$existsResult = mysqli_stmt_get_result($existsStmt);

if (mysqli_num_rows($existsResult) === 0) {
    header('Location: /radts/dashboard/admin.php?error=' . urlencode('User not found.'));
    exit();
}

$deleteStmt = mysqli_prepare($conn, 'DELETE FROM users WHERE user_id = ?');
mysqli_stmt_bind_param($deleteStmt, 'i', $id);
$deleted = mysqli_stmt_execute($deleteStmt);

if ($deleted) {
    header('Location: /radts/dashboard/admin.php?success=' . urlencode('User deleted successfully.'));
    exit();
}

header('Location: /radts/dashboard/admin.php?error=' . urlencode('Failed to delete user. The account may be referenced by other records.'));
exit();
