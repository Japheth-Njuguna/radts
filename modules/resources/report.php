<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

// Ensure return-date tracking exists for reports.
mysqli_query($conn, "ALTER TABLE resource_allocation ADD COLUMN IF NOT EXISTS date_returned DATE NULL AFTER date_confirmed");

// Filters
$status  = isset($_GET['status']) ? $_GET['status'] : '';
$teacher = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

$all_teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");

$sql = "SELECT
        ra.allocation_id,
        ra.resource_id,
        ra.teacher_id,
        ra.allocated_by,
        ra.quantity,
        ra.date_allocated,
        ra.status AS status,
        ra.date_confirmed,
        ra.date_returned,
        u.name AS teacher_name,
        r.name AS resource_name,
        r.type AS resource_type,
        d.name AS deputy_name
        FROM resource_allocation ra
        JOIN users u ON ra.teacher_id = u.user_id
        JOIN resources r ON ra.resource_id = r.resource_id
        JOIN users d ON ra.allocated_by = d.user_id
        WHERE 1=1";

if (!empty($status)) {
    $sql .= " AND ra.status = '$status'";
}

if ($teacher > 0) {
    $sql .= " AND ra.teacher_id = $teacher";
}

$sql .= " ORDER BY r.name ASC, ra.date_allocated DESC";

$allocations = mysqli_query($conn, $sql);
$total       = mysqli_num_rows($allocations);

$total_pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE status='pending'"))['total'];
$total_confirmed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE status='confirmed'"))['total'];
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Resource Distribution Report</h1>
    <p>Track all resource allocations and confirmation status</p>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Allocations</div>
        <div class="stat-value"><?php echo $total_pending + $total_confirmed; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Confirmed</div>
        <div class="stat-value"><?php echo $total_confirmed; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?php echo $total_pending; ?></div>
    </div>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h2>Filter</h2></div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status=='pending'?'selected':''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status=='confirmed'?'selected':''; ?>>Confirmed</option>
                    </select>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-control">
                        <option value="0">All Teachers</option>
                        <?php while ($t = mysqli_fetch_assoc($all_teachers)): ?>
                        <option value="<?php echo $t['user_id']; ?>" <?php echo $teacher==$t['user_id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/radts/modules/resources/report.php" class="btn btn-success">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Allocations Table -->
<div class="card">
    <div class="card-header">
        <h2>Allocation Records (<?php echo $total; ?>)</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Resource</th>
                    <th>Teacher</th>
                    <th>Qty</th>
                    <th>Date Issued</th>
                    <th>Status</th>
                    <th>Date Confirmed</th>
                    <th>Date Returned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#6B7280;padding:30px">
                        No allocation records found.
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($alloc = mysqli_fetch_assoc($allocations)): ?>
                <?php $allocStatus = $alloc['status'] ?? ($alloc['STATUS'] ?? 'pending'); ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($alloc['resource_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($alloc['teacher_name']); ?></td>
                    <td><?php echo $alloc['quantity']; ?></td>
                    <td><?php echo date('d M Y', strtotime($alloc['date_allocated'])); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($allocStatus); ?>"><?php echo ucfirst(htmlspecialchars($allocStatus)); ?></span></td>
                    <td>
                        <?php echo $alloc['date_confirmed'] ? date('d M Y', strtotime($alloc['date_confirmed'])) : '—'; ?>
                    </td>
                    <td>
                        <?php echo $alloc['date_returned'] ? date('d M Y', strtotime($alloc['date_returned'])) : '—'; ?>
                    </td>
                    <td>
                        <a href="/radts/modules/resources/edit_allocation.php?id=<?php echo (int)$alloc['allocation_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>