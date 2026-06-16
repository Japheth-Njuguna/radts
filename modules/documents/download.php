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

// Get document from database
$stmt = mysqli_prepare($conn, "SELECT * FROM documents WHERE document_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doc    = mysqli_fetch_assoc($result);

if (!$doc) {
    die("Document not found.");
}

$file_path = $_SERVER['DOCUMENT_ROOT'] . '/radts/' . $doc['file_path'];

if (!file_exists($file_path)) {
    die("File not found on server.");
}

// Force download
$filename = basename($file_path);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: public');
readfile($file_path);
exit();
?>