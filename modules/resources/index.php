<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

// Get all resources
$resources = mysqli_query($conn, "SELECT * FROM resources ORDER BY name ASC");
$total     = mysqli_num_rows($resources);

// Get allocation summary per resource
$alloc_summary = [];
$summary = mysqli_query($conn, "SELECT resource_id, COUNT(*) as total_alloc, SUM(quantity) as total_qty FROM resource_allocation GROUP BY resource_id");
while ($row = mysqli_fetch_assoc($summary)) {
    $alloc_summary[$row['resource_id']] = $row;
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Resource Management</h1>
    <p>Manage and track learning resource allocation</p>
</div>

<!-- Quick Actions -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap">
    <a href="/radts/modules/resources/allocate.php" class="btn btn-primary">+ Allocate Resource</a>
    <a href="/radts/modules/resources/add.php" class="btn btn-success">+ Add New Resource</a>
    <a href="/radts/modules/resources/report.php" class="btn btn-warning">📊 View Report</a>
</div>

<!-- Resources Table -->
<div class="card">
    <div class="card-header">
        <h2>Available Resources (<?php echo $total; ?>)</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Resource Name</th>
                    <th>Type</th>
                    <th>Qty Available</th>
                    <th>Total Allocated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#6B7280;padding:30px">
                        No resources found. <a href="/radts/modules/resources/add.php">Add one now.</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($res = mysqli_fetch_assoc($resources)): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($res['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($res['type']); ?></td>
                    <td>
                        <span style="color:<?php echo $res['quantity_available'] < 5 ? '#DC2626' : '#065F46'; ?>;font-weight:600">
                            <?php echo $res['quantity_available']; ?>
                        </span>
                    </td>
                    <td>
                        <?php echo isset($alloc_summary[$res['resource_id']]) ? $alloc_summary[$res['resource_id']]['total_qty'] : 0; ?>
                    </td>
                    <td>
                        <a href="/radts/modules/resources/allocate.php?resource_id=<?php echo $res['resource_id']; ?>" class="btn btn-primary btn-sm">Allocate</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>