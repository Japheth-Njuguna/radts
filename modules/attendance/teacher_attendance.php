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

// All my attendance records (aliased to stable keys for display)
$records = mysqli_query($conn, "SELECT a.date AS lesson_date, a.subject AS lesson_subject, a.class AS lesson_class, a.status AS lesson_status, u.name as recorder FROM attendance a JOIN users u ON a.recorded_by = u.user_id WHERE a.teacher_id=$user_id ORDER BY a.date DESC");
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
                <?php if ($total === 0 || $records === false || mysqli_num_rows($records) === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#6B7280;padding:30px">
                        No attendance records found yet.
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($rec = mysqli_fetch_assoc($records)): ?>
                <?php
                    $lessonDate = $rec['lesson_date'] ?? $rec['date'] ?? $rec['Date'] ?? '';
                    $lessonSubject = $rec['lesson_subject'] ?? $rec['subject'] ?? $rec['Subject'] ?? '-';
                    $lessonClass = $rec['lesson_class'] ?? $rec['class'] ?? $rec['Class'] ?? '-';
                    $lessonStatus = strtolower((string)($rec['lesson_status'] ?? $rec['status'] ?? $rec['STATUS'] ?? 'pending'));
                    $dateTs = $lessonDate !== '' ? strtotime($lessonDate) : false;
                    $dateLabel = $dateTs ? date('d M Y', $dateTs) : '-';
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $dateLabel; ?></td>
                    <td><?php echo htmlspecialchars((string)$lessonSubject); ?></td>
                    <td><?php echo htmlspecialchars((string)$lessonClass); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($lessonStatus); ?>"><?php echo ucfirst(htmlspecialchars($lessonStatus)); ?></span></td>
                    <td><?php echo htmlspecialchars((string)($rec['recorder'] ?? '-')); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>