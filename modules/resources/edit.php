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
    header('Location: /radts/modules/resources/index.php');
    exit();
}

$success = '';
$error = '';

$stmt = mysqli_prepare($conn, 'SELECT resource_id, name, type, quantity_available FROM resources WHERE resource_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$resource = mysqli_fetch_assoc($result);

if (!$resource) {
    header('Location: /radts/modules/resources/index.php');
    exit();
}

$types = ['textbook', 'teaching_guide', 'supplementary'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : -1;

    if ($name === '' || !in_array($type, $types, true) || $quantity < 0) {
        $error = 'Please provide valid values for all fields.';
    } else {
        $update = mysqli_prepare($conn, 'UPDATE resources SET name = ?, type = ?, quantity_available = ? WHERE resource_id = ?');
        mysqli_stmt_bind_param($update, 'ssii', $name, $type, $quantity, $id);

        if (mysqli_stmt_execute($update)) {
            header('Location: /radts/modules/resources/edit.php?id=' . $id . '&updated=1');
            exit();
        }

        $error = 'Failed to update resource. Please try again.';
    }

    $stmt = mysqli_prepare($conn, 'SELECT resource_id, name, type, quantity_available FROM resources WHERE resource_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resource = mysqli_fetch_assoc($result);
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Resource updated successfully.';
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Edit Resource</h1>
    <p>Correct resource details and available quantity.</p>
</div>

<div class="card" style="max-width:560px">
    <div class="card-header">
        <h2>Resource Details</h2>
        <a href="/radts/modules/resources/index.php" class="btn btn-primary btn-sm">← Back</a>
    </div>
    <div class="card-body">

        <?php if ($success !== ''): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error-msg">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

            <div class="form-group-block">
                <label class="form-label">Resource Name *</label>
                <input type="text" name="name" class="form-control"
                    value="<?php echo htmlspecialchars($resource['name']); ?>"
                    required>
            </div>

            <div class="form-group-block">
                <label class="form-label">Type *</label>
                <select name="type" class="form-control" required>
                    <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($resource['type'] === $type) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Quantity Available *</label>
                <input type="number" name="quantity" class="form-control" min="0"
                    value="<?php echo (int)$resource['quantity_available']; ?>"
                    required>
            </div>

            <button type="submit" class="btn btn-warning">Update Resource</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>