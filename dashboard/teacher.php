<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

if (!hasRole('teacher')) {
    header("Location: /radts/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// My attendance stats
$present_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE teacher_id=$user_id AND status='present'"))['total'];
$absent_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE teacher_id=$user_id AND status='absent'"))['total'];
$pending_resources = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE teacher_id=$user_id AND status='pending'"))['total'];

// My recent attendance
$my_attendance = mysqli_query($conn, "SELECT `date` AS lesson_date, `subject` AS lesson_subject, `class` AS lesson_class, `status` AS lesson_status FROM attendance WHERE teacher_id=$user_id ORDER BY `date` DESC LIMIT 7");

// My pending resources
$my_resources = mysqli_query($conn, "SELECT ra.*, r.name as resource_name FROM resource_allocation ra JOIN resources r ON ra.resource_id = r.resource_id WHERE ra.teacher_id=$user_id AND ra.status='pending' ORDER BY ra.date_allocated DESC");
?>

<?php require_once '../includes/header.php'; ?>

<div class="page-header">
    <h1>Teacher Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?> — <?php echo date('l, d F Y'); ?></p>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Times Present</div>
        <div class="stat-value"><?php echo $present_count; ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Times Absent</div>
        <div class="stat-value"><?php echo $absent_count; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Pending Resource Confirmations</div>
        <div class="stat-value"><?php echo $pending_resources; ?></div>
    </div>
</div>

<!-- Quick Access Modules -->
<div class="card">
    <div class="card-header">
        <h2>Teacher Modules</h2>
    </div>
    <div class="card-body">
        <a href="/radts/modules/attendance/teacher_attendance.php" class="btn btn-primary">View Attendance</a>
        <a href="/radts/modules/resources/teachers_resource.php" class="btn btn-success" style="margin-left:8px">View Resources Allocated</a>
    </div>
</div>

<!-- Pending Resources to Confirm -->
<?php if ($pending_resources > 0): ?>
<div class="card">
    <div class="card-header">
        <h2>⚠ Resources Awaiting Your Confirmation</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Quantity</th>
                    <th>Date Issued</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($res = mysqli_fetch_assoc($my_resources)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($res['resource_name']); ?></td>
                    <td><?php echo $res['quantity']; ?></td>
                    <td><?php echo date('d M Y', strtotime($res['date_allocated'])); ?></td>
                    <td>
                        <a href="/radts/modules/resources/confirm.php?id=<?php echo $res['allocation_id']; ?>"
                           class="btn btn-success btn-sm"
                           onclick="return confirm('Confirm you have received this resource?')">
                           Confirm Receipt
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- My Attendance -->
<div class="card">
    <div class="card-header">
        <h2>My Recent Attendance</h2>
        <a href="/radts/modules/attendance/teacher_attendance.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($att = mysqli_fetch_assoc($my_attendance)): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($att['lesson_date'])); ?></td>
                    <td><?php echo htmlspecialchars($att['lesson_subject']); ?></td>
                    <td><?php echo htmlspecialchars($att['lesson_class']); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($att['lesson_status']); ?>"><?php echo ucfirst(htmlspecialchars($att['lesson_status'])); ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($present_count == 0 && $absent_count == 0): ?>
                <tr><td colspan="4" style="text-align:center;color:#6B7280;padding:20px">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>