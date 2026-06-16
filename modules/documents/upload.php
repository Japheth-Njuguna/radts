<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy')) {
    header("Location: /radts/index.php");
    exit();
}

$success = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']);
    $category = trim($_POST['category']);
    $uploader = $_SESSION['user_id'];

    if (empty($title) || empty($category)) {
        $error = "Please fill in all fields.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== 0) {
        $error = "Please select a file to upload.";
    } else {
        $file     = $_FILES['document'];
        $filename = time() . '_' . basename($file['name']);
        $allowed  = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','png'];
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "File type not allowed. Allowed types: PDF, Word, Excel, PowerPoint, TXT, JPG, PNG.";
        } else {
            // Create uploads folder if it does not exist
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/radts/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_path = 'uploads/' . $filename;
            $full_path = $_SERVER['DOCUMENT_ROOT'] . '/radts/' . $file_path;

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO documents (title, category, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssi", $title, $category, $file_path, $uploader);

                if (mysqli_stmt_execute($stmt)) {
                    $success = "Document uploaded successfully.";
                } else {
                    $error = "Database error. Please try again.";
                }
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Upload Document</h1>
    <p>Upload an administrative document to the system</p>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <h2>Document Details</h2>
        <a href="/radts/modules/documents/index.php" class="btn btn-primary btn-sm">← Back to Documents</a>
    </div>
    <div class="card-body">

        <?php if (!empty($success)): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group-block">
                <label class="form-label">Document Title *</label>
                <input type="text" name="title" class="form-control"
                    placeholder="e.g. Staff Employment Records 2026"
                    value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                    required>
            </div>

            <div class="form-group-block">
                <label class="form-label">Category *</label>
                <select name="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <option value="Staff File" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Staff File') ? 'selected' : ''; ?>>Staff File</option>
                    <option value="Policy" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Policy') ? 'selected' : ''; ?>>Policy</option>
                    <option value="Inspection Report" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Inspection Report') ? 'selected' : ''; ?>>Inspection Report</option>
                    <option value="Curriculum" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Curriculum') ? 'selected' : ''; ?>>Curriculum</option>
                    <option value="Official Correspondence" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Official Correspondence') ? 'selected' : ''; ?>>Official Correspondence</option>
                    <option value="Financial Record" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Financial Record') ? 'selected' : ''; ?>>Financial Record</option>
                    <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Select File *</label>
                <input type="file" name="document" class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.png"
                    required>
                <small style="color:#6B7280;font-size:11px;margin-top:4px;display:block">
                    Allowed: PDF, Word, Excel, PowerPoint, TXT, JPG, PNG
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Upload Document</button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>