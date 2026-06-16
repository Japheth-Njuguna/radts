<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

if (!hasRole('deputy')) {
    header("Location: /radts/index.php");
    exit();
}

// Count stats
$total_docs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM documents"))['total'];
$total_present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status='present' AND date=CURDATE()"))['total'];
$total_absent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE status='absent' AND date=CURDATE()"))['total'];
$total_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM resource_allocation WHERE status='pending'"))['total'];

// Recent documents
$recent_docs = mysqli_query($conn, "SELECT d.*, u.name as uploader FROM documents d JOIN users u ON d.uploaded_by = u.user_id ORDER BY d.upload_date DESC LIMIT 5");

// Recent allocations
$recent_allocs = mysqli_query($conn, "SELECT ra.*, u.name as teacher_name, r.name as resource_name FROM resource_allocation ra JOIN users u ON ra.teacher_id = u.user_id JOIN resources r ON ra.resource_id = r.resource_id ORDER BY ra.date_allocated DESC LIMIT 5");
?>

<?php require_once '../includes/header.php'; ?>

<div class="page-header">
    <h1>Deputy Head Teacher Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?> — <?php echo date('l, d F Y'); ?></p>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Documents</div>
        <div class="stat-value"><?php echo $total_docs; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Present Today</div>
        <div class="stat-value"><?php echo $total_present; ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Absent Today</div>
        <div class="stat-value"><?php echo $total_absent; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Pending Confirmations</div>
        <div class="stat-value"><?php echo $total_pending; ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="/radts/modules/documents/upload.php" class="btn btn-primary">📁 Upload Document</a>
        <a href="/radts/modules/documents/index.php" class="btn btn-success">🔍 Search Documents</a>
        <a href="/radts/modules/resources/allocate.php" class="btn btn-warning">📦 Allocate Resource</a>
        <a href="/radts/modules/attendance/report.php" class="btn btn-primary">✅ View Attendance Report</a>
        <a href="/radts/modules/resources/report.php" class="btn btn-success">📊 Resource Report</a>
    </div>
</div>

<!-- Recent Documents -->
<div class="card">
    <div class="card-header">
        <h2>Recent Documents</h2>
        <a href="/radts/modules/documents/index.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($doc = mysqli_fetch_assoc($recent_docs)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($doc['title']); ?></td>
                    <td><?php echo htmlspecialchars($doc['category']); ?></td>
                    <td><?php echo htmlspecialchars($doc['uploader']); ?></td>
                    <td><?php echo date('d M Y', strtotime($doc['upload_date'])); ?></td>
                    <td><a href="/radts/modules/documents/download.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-success btn-sm">Download</a></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_docs == 0): ?>
                <tr><td colspan="5" style="text-align:center;color:#6B7280;padding:20px">No documents uploaded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Allocations -->
<div class="card">
    <div class="card-header">
        <h2>Recent Resource Allocations</h2>
        <a href="/radts/modules/resources/index.php" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Teacher</th>
                    <th>Quantity</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($alloc = mysqli_fetch_assoc($recent_allocs)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($alloc['resource_name']); ?></td>
                    <td><?php echo htmlspecialchars($alloc['teacher_name']); ?></td>
                    <td><?php echo $alloc['quantity']; ?></td>
                    <td><?php echo date('d M Y', strtotime($alloc['date_allocated'])); ?></td>
                    <td><span class="badge badge-<?php echo $alloc['status']; ?>"><?php echo ucfirst($alloc['status']); ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_pending == 0 && !mysqli_num_rows($recent_allocs)): ?>
                <tr><td colspan="5" style="text-align:center;color:#6B7280;padding:20px">No allocations yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>