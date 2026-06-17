<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

// Search and filter
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT d.*, u.name as uploader_name FROM documents d
        JOIN users u ON d.uploaded_by = u.user_id
        WHERE 1=1";

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (d.title LIKE '%$search_safe%' OR d.category LIKE '%$search_safe%')";
}

if (!empty($category)) {
    $cat_safe = mysqli_real_escape_string($conn, $category);
    $sql .= " AND d.category = '$cat_safe'";
}

$sql .= " ORDER BY d.upload_date DESC";
$documents = mysqli_query($conn, $sql);
$total     = mysqli_num_rows($documents);
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Document Management</h1>
    <p>Search, view and download administrative documents</p>
</div>

<!-- Search and Filter Toolbar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="">
            <div class="toolbar">
                <input type="text" name="search" class="search-input"
                    placeholder="🔍 Search by title or category..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <select name="category" class="form-control" style="width:200px">
                    <option value="">All Categories</option>
                    <option value="Staff File" <?php echo $category=='Staff File'?'selected':''; ?>>Staff File</option>
                    <option value="Policy" <?php echo $category=='Policy'?'selected':''; ?>>Policy</option>
                    <option value="Inspection Report" <?php echo $category=='Inspection Report'?'selected':''; ?>>Inspection Report</option>
                    <option value="Curriculum" <?php echo $category=='Curriculum'?'selected':''; ?>>Curriculum</option>
                    <option value="Official Correspondence" <?php echo $category=='Official Correspondence'?'selected':''; ?>>Official Correspondence</option>
                    <option value="Financial Record" <?php echo $category=='Financial Record'?'selected':''; ?>>Financial Record</option>
                    <option value="Other" <?php echo $category=='Other'?'selected':''; ?>>Other</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/radts/modules/documents/index.php" class="btn btn-success">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Documents Table -->
<div class="card">
    <div class="card-header">
        <h2>Documents (<?php echo $total; ?> found)</h2>
        <a href="/radts/modules/documents/upload.php" class="btn btn-primary">+ Upload Document</a>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#6B7280;padding:30px">
                        No documents found. <a href="/radts/modules/documents/upload.php">Upload one now.</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while ($doc = mysqli_fetch_assoc($documents)): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($doc['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($doc['category']); ?></td>
                    <td><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                    <td><?php echo date('d M Y', strtotime($doc['upload_date'])); ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="/radts/modules/documents/download.php?id=<?php echo $doc['document_id']; ?>"
                           class="btn btn-success btn-sm">Download</a>
                                <a href="/radts/modules/documents/edit.php?id=<?php echo $doc['document_id']; ?>"
                                    class="btn btn-warning btn-sm">Edit</a>
                        <a href="/radts/modules/documents/delete.php?id=<?php echo $doc['document_id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this document permanently?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>