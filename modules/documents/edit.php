<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header('Location: /radts/index.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : $id;
}

if ($id <= 0) {
    header('Location: /radts/modules/documents/index.php');
    exit();
}

$success = '';
$error = '';

$stmt = mysqli_prepare($conn, 'SELECT * FROM documents WHERE document_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$doc_result = mysqli_stmt_get_result($stmt);
$doc = mysqli_fetch_assoc($doc_result);

if (!$doc) {
    header('Location: /radts/modules/documents/index.php');
    exit();
}

$categories = [
    'Staff File',
    'Policy',
    'Inspection Report',
    'Curriculum',
    'Official Correspondence',
    'Financial Record',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $new_file_path = $doc['file_path'];

    if ($title === '' || $category === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($category, $categories, true)) {
        $error = 'Invalid category selected.';
    } else {
        if (isset($_FILES['document']) && (int)$_FILES['document']['error'] === 0) {
            $file = $_FILES['document'];
            $filename = time() . '_' . basename($file['name']);
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'png'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $error = 'File type not allowed. Allowed types: PDF, Word, Excel, PowerPoint, TXT, JPG, PNG.';
            } else {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/radts/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_file_path = 'uploads/' . $filename;
                $full_path = $_SERVER['DOCUMENT_ROOT'] . '/radts/' . $new_file_path;

                if (!move_uploaded_file($file['tmp_name'], $full_path)) {
                    $error = 'Failed to upload replacement file. Please try again.';
                    $new_file_path = $doc['file_path'];
                }
            }
        }
    }

    if ($error === '') {
        $update = mysqli_prepare($conn, 'UPDATE documents SET title = ?, category = ?, file_path = ? WHERE document_id = ?');
        mysqli_stmt_bind_param($update, 'sssi', $title, $category, $new_file_path, $id);

        if (mysqli_stmt_execute($update)) {
            if ($new_file_path !== $doc['file_path']) {
                $old_full_path = $_SERVER['DOCUMENT_ROOT'] . '/radts/' . $doc['file_path'];
                if (is_file($old_full_path)) {
                    unlink($old_full_path);
                }
            }

            header('Location: /radts/modules/documents/edit.php?id=' . $id . '&updated=1');
            exit();
        }

        $error = 'Failed to update document. Please try again.';
    }

    $stmt = mysqli_prepare($conn, 'SELECT * FROM documents WHERE document_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $doc_result = mysqli_stmt_get_result($stmt);
    $doc = mysqli_fetch_assoc($doc_result);
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Document updated successfully.';
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Edit Document</h1>
    <p>Correct document details or replace the uploaded file.</p>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header">
        <h2>Document Details</h2>
        <a href="/radts/modules/documents/index.php" class="btn btn-primary btn-sm">← Back to Documents</a>
    </div>
    <div class="card-body">

        <?php if ($success !== ''): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error-msg">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

            <div class="form-group-block">
                <label class="form-label">Document Title *</label>
                <input type="text" name="title" class="form-control"
                    value="<?php echo htmlspecialchars($doc['title']); ?>"
                    required>
            </div>

            <div class="form-group-block">
                <label class="form-label">Category *</label>
                <select name="category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo ($doc['category'] === $cat) ? 'selected' : ''; ?>>
                        <?php echo $cat; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Replace File (Optional)</label>
                <input type="file" name="document" class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.png">
                <small style="color:#6B7280;font-size:11px;margin-top:4px;display:block">
                    Leave empty to keep current file.
                </small>
            </div>

            <button type="submit" class="btn btn-warning">Update Document</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>