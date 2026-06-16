<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: /radts/modules/documents/index.php");
    exit();
}

// Get document file path first
$stmt = mysqli_prepare($conn, "SELECT * FROM documents WHERE document_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doc    = mysqli_fetch_assoc($result);

if ($doc) {
    // Delete file from server
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/radts/' . $doc['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete from database
    $del = mysqli_prepare($conn, "DELETE FROM documents WHERE document_id = ?");
    mysqli_stmt_bind_param($del, "i", $id);
    mysqli_stmt_execute($del);
}

header("Location: /radts/modules/documents/index.php");
exit();
?>