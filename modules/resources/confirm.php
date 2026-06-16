<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('teacher')) {
    header("Location: /radts/index.php");
    exit();
}

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    // Make sure this allocation belongs to this teacher
    $stmt = mysqli_prepare($conn, "SELECT * FROM resource_allocation WHERE allocation_id=? AND teacher_id=? AND status='pending'");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Confirm receipt
        $today = date('Y-m-d');
        $update = mysqli_prepare($conn, "UPDATE resource_allocation SET status='confirmed', date_confirmed=? WHERE allocation_id=?");
        mysqli_stmt_bind_param($update, "si", $today, $id);
        mysqli_stmt_execute($update);
    }
}

header("Location: /radts/dashboard/teacher.php");
exit();
?>