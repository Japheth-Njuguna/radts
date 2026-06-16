<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('teacher')) {
    header("Location: /radts/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Stats
$present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE teacher_id=$user_id AND status='present'"))['total'];
$absent  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE teacher_id=$user_id AND status='absent'"))['total'];
$total   = $present + $absent;

// All my attendance records
$records = mysqli_query($conn, "SELECT a.*, u.name as recorder FROM attendance a JOIN users u ON a.recorded_by = u.user_id WHERE a.teacher_id=$user_id ORDER BY a.date DESC");
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>My Attendance Records</h1>
    <p>View your full attendance history</p>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label">Total Lessons Recorded</div>
        <div class="stat-value"><?php echo $total; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Present</div>
        <div class="stat-value"><?php echo $present; ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Absent</div>
        <div class="stat-value"><?php echo $absent; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Attendance Rate</div>
        <div class="stat-value">
            <?php echo $total > 0 ? round(($present / $total) * 100) . '%' : '0%'; ?>
        </div>
    </div>
</div>

<!-- Records Table -->
<div class="card">
    <div class="card-header">
        <h2>Full Attendance History</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#6B7280;padding:30px">
                        No attendance records found yet.
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($rec = mysqli_fetch_assoc($records)): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo date('d M Y', strtotime($rec['date'])); ?></td>
                    <td><?php echo htmlspecialchars($rec['subject']); ?></td>
                    <td><?php echo htmlspecialchars($rec['class']); ?></td>
                    <td><span class="badge badge-<?php echo $rec['status']; ?>"><?php echo ucfirst($rec['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($rec['recorder']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>