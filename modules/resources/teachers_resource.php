<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('teacher')) {
    header("Location: /radts/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE teacher_id=$user_id AND status='pending'"))['total'];
$confirmed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE teacher_id=$user_id AND status='confirmed'"))['total'];

$allocations = mysqli_query($conn, "SELECT ra.allocation_id, ra.resource_id, ra.quantity, ra.date_allocated, ra.date_confirmed, ra.status AS allocation_status, r.name as resource_name, r.type, u.name as allocated_by_name
    FROM resource_allocation ra
    JOIN resources r ON ra.resource_id = r.resource_id
    JOIN users u ON ra.allocated_by = u.user_id
    WHERE ra.teacher_id = $user_id
    ORDER BY ra.date_allocated DESC");
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>My Resources</h1>
    <p>View all resources allocated to you</p>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card amber">
        <div class="stat-label">Pending Confirmation</div>
        <div class="stat-value"><?php echo $pending; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Confirmed Received</div>
        <div class="stat-value"><?php echo $confirmed; ?></div>
    </div>
</div>

<!-- Allocations Table -->
<div class="card">
    <div class="card-header">
        <h2>All My Allocations</h2>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Resource</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Date Issued</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allocations === false || mysqli_num_rows($allocations) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#6B7280;padding:30px">
                        No resources have been allocated to you yet.
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($alloc = mysqli_fetch_assoc($allocations)): ?>
                <?php
                    $allocationStatus = strtolower((string)($alloc['allocation_status'] ?? $alloc['status'] ?? $alloc['STATUS'] ?? 'pending'));
                    $dateAllocatedTs = !empty($alloc['date_allocated']) ? strtotime($alloc['date_allocated']) : false;
                    $dateAllocatedLabel = $dateAllocatedTs ? date('d M Y', $dateAllocatedTs) : '-';
                    $dateConfirmedTs = !empty($alloc['date_confirmed']) ? strtotime($alloc['date_confirmed']) : false;
                    $dateConfirmedLabel = $dateConfirmedTs ? date('d M Y', $dateConfirmedTs) : '-';
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($alloc['resource_name']); ?></strong></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $alloc['type'])); ?></td>
                    <td><?php echo $alloc['quantity']; ?></td>
                    <td><?php echo $dateAllocatedLabel; ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($allocationStatus); ?>"><?php echo ucfirst(htmlspecialchars($allocationStatus)); ?></span></td>
                    <td>
                        <?php if ($allocationStatus === 'pending'): ?>
                        <a href="/radts/modules/resources/confirm.php?id=<?php echo $alloc['allocation_id']; ?>"
                           class="btn btn-success btn-sm"
                           onclick="return confirm('Confirm you have received this resource?')">
                           Confirm Receipt
                        </a>
                        <?php else: ?>
                        <span style="color:#065F46;font-size:12px">✅ Received <?php echo $dateConfirmedLabel; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>