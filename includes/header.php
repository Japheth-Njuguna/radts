<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADTS Dashboard</title>
    <link rel="stylesheet" href="/radts/assets/css/style.css?v=20260619">
</head>
<body>
<?php
$role = $_SESSION['role'] ?? '';
$dashboardUrl = '/radts/index.php';

switch ($role) {
    case 'admin':
        $dashboardUrl = '/radts/dashboard/admin.php';
        break;
    case 'deputy':
        $dashboardUrl = '/radts/dashboard/deputy.php';
        break;
    case 'teacher':
        $dashboardUrl = '/radts/dashboard/teacher.php';
        break;
    case 'student_leader':
        $dashboardUrl = '/radts/dashboard/student.php';
        break;
}
?>

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand-block">
            <h2>RADTS</h2>
            <p>Resource And Daily Tracking System</p>
        </div>
        <nav class="topbar-nav">
            <a href="<?php echo $dashboardUrl; ?>">Dashboard</a>
            <a href="/radts/index.php?force_login=1">Login Page</a>
            <a href="/radts/logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="container">
