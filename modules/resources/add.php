<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('deputy') && !hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $type     = trim($_POST['type']);
    $quantity = (int)$_POST['quantity'];

    if (empty($name) || empty($type) || $quantity <= 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO resources (name, type, quantity_available) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssi", $name, $type, $quantity);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Resource added successfully.";
        } else {
            $error = "Failed to add resource. Please try again.";
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Add New Resource</h1>
    <p>Add a new learning resource to the system</p>
</div>

<div class="card" style="max-width:500px">
    <div class="card-header">
        <h2>Resource Details</h2>
        <a href="/radts/modules/resources/index.php" class="btn btn-primary btn-sm">← Back</a>
    </div>
    <div class="card-body">

        <?php if (!empty($success)): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group-block">
                <label class="form-label">Resource Name *</label>
                <input type="text" name="name" class="form-control"
                    placeholder="e.g. Mathematics Textbook Grade 6"
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    required>
            </div>

            <div class="form-group-block">
                <label class="form-label">Type *</label>
                <select name="type" class="form-control" required>
                    <option value="">-- Select Type --</option>
                    <option value="textbook" <?php echo (isset($_POST['type']) && $_POST['type']=='textbook')?'selected':''; ?>>Textbook</option>
                    <option value="teaching_guide" <?php echo (isset($_POST['type']) && $_POST['type']=='teaching_guide')?'selected':''; ?>>Teaching Guide</option>
                    <option value="supplementary" <?php echo (isset($_POST['type']) && $_POST['type']=='supplementary')?'selected':''; ?>>Supplementary Material</option>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Quantity Available *</label>
                <input type="number" name="quantity" class="form-control"
                    min="1" placeholder="e.g. 30"
                    value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : ''; ?>"
                    required>
            </div>

            <button type="submit" class="btn btn-primary">Add Resource</button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>