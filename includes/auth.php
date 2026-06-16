<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /radts/index.php");
        exit();
    }
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect based on role after login
function redirectByRole($role) {
    switch ($role) {
        case 'admin':
            header("Location: /radts/dashboard/admin.php");
            break;
        case 'deputy':
            header("Location: /radts/dashboard/deputy.php");
            break;
        case 'teacher':
            header("Location: /radts/dashboard/teacher.php");
            break;
        case 'student_leader':
            header("Location: /radts/dashboard/student.php");
            break;
        default:
            header("Location: /radts/index.php");
    }
    exit();
}
?>