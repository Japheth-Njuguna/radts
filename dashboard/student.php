<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

if (!hasRole('student_leader')) {
    header("Location: /radts/index.php");
    exit();
}

// Count attendance recorded today by this student
$user_id = $_SESSION['user_id'];
$today_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE recorded_by=$user_id AND date=CURDATE()"))['total'];
$total_recorded = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE recorded_by=$user_id"))['total'];

// Recent entries by this student
$recent = mysqli_query($conn, "SELECT a.attendance_id, a.teacher_id, a.recorded_by, a.`subject` AS lesson_subject, a.`class` AS lesson_class, a.`date` AS lesson_date, a.`status` AS lesson_status, u.name AS teacher_name FROM attendance a JOIN users u ON a.teacher_id = u.user_id WHERE a.recorded_by=$user_id ORDER BY a.`date` DESC, a.attendance_id DESC LIMIT 10");
?>

<?php require_once '../includes/header.php'; ?>

<div class="page-header">
    <h1>Student Leader Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?> — <?php echo date('l, d F Y'); ?></p>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Recorded Today</div>
        <div class="stat-value"><?php echo $today_count; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Records Submitted</div>
        <div class="stat-value"><?php echo $total_recorded; ?></div>
    </div>
</div>

<!-- Quick Action -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h2>Quick Action</h2></div>
    <div class="card-body">
        <a href="/radts/modules/attendance/record.php" class="btn btn-primary">✅ Record Lesson Attendance</a>
    </div>
</div>

<!-- Recent Records -->
<div class="card">
    <div class="card-header">
        <h2>My Recent Attendance Records</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rec = mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rec['teacher_name']); ?></td>
                    <td><?php echo htmlspecialchars($rec['lesson_subject']); ?></td>
                    <td><?php echo htmlspecialchars($rec['lesson_class']); ?></td>
                    <td><?php echo date('d M Y', strtotime($rec['lesson_date'])); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($rec['lesson_status']); ?>"><?php echo ucfirst(htmlspecialchars($rec['lesson_status'])); ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_recorded == 0): ?>
                <tr><td colspan="5" style="text-align:center;color:#6B7280;padding:20px">No attendance records submitted yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>