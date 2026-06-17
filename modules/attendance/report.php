<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

// Filters
$from     = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$to       = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');
$teacher  = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// Get all teachers for filter dropdown
$all_teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");

// Build query
$sql = "SELECT
        a.attendance_id,
        a.teacher_id,
        a.recorded_by,
        a.`subject` AS lesson_subject,
        a.`class` AS lesson_class,
        a.`date` AS lesson_date,
        a.`status` AS lesson_status,
        u.name AS teacher_name,
        r.name AS recorder_name
        FROM attendance a
        JOIN users u ON a.teacher_id = u.user_id
        JOIN users r ON a.recorded_by = r.user_id
    WHERE a.`date` BETWEEN '$from' AND '$to'";

if ($teacher > 0) {
    $sql .= " AND a.teacher_id = $teacher";
}

$sql .= " ORDER BY a.`date` DESC, u.name ASC";
$records = mysqli_query($conn, $sql);
$total   = mysqli_num_rows($records);

// Summary stats for the period
$present_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status='present' AND date BETWEEN '$from' AND '$to'" . ($teacher > 0 ? " AND teacher_id=$teacher" : "")))['total'];
$absent_total  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status='absent' AND date BETWEEN '$from' AND '$to'" . ($teacher > 0 ? " AND teacher_id=$teacher" : "")))['total'];
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Attendance Report</h1>
    <p>View and filter teacher lesson attendance records</p>
</div>

<!-- Filter Form -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Filter Records</h2></div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" class="form-control" value="<?php echo $from; ?>">
                </div>
                <div class="form-group-block">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" class="form-control" value="<?php echo $to; ?>">
                </div>
            </div>
            <div class="form-group-block">
                <label class="form-label">Teacher (Optional)</label>
                <select name="teacher_id" class="form-control">
                    <option value="0">All Teachers</option>
                    <?php while ($t = mysqli_fetch_assoc($all_teachers)): ?>
                    <option value="<?php echo $t['user_id']; ?>" <?php echo $teacher==$t['user_id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($t['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="/radts/modules/attendance/report.php" class="btn btn-success">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Records</div>
        <div class="stat-value"><?php echo $total; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Present</div>
        <div class="stat-value"><?php echo $present_total; ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Absent</div>
        <div class="stat-value"><?php echo $absent_total; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Attendance Rate</div>
        <div class="stat-value">
            <?php echo $total > 0 ? round(($present_total / $total) * 100) . '%' : '0%'; ?>
        </div>
    </div>
</div>

<!-- Records Table -->
<div class="card">
    <div class="card-header">
        <h2>Records — <?php echo date('d M Y', strtotime($from)); ?> to <?php echo date('d M Y', strtotime($to)); ?></h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Teacher</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#6B7280;padding:30px">
                        No attendance records found for this period.
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($rec = mysqli_fetch_assoc($records)): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($rec['teacher_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($rec['lesson_subject']); ?></td>
                    <td><?php echo htmlspecialchars($rec['lesson_class']); ?></td>
                    <td><?php echo date('d M Y', strtotime($rec['lesson_date'])); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($rec['lesson_status']); ?>"><?php echo ucfirst(htmlspecialchars($rec['lesson_status'])); ?></span></td>
                    <td><?php echo htmlspecialchars($rec['recorder_name']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>