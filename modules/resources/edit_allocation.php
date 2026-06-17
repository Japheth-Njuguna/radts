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
    header('Location: /radts/modules/resources/report.php');
    exit();
}

$success = '';
$error = '';

$load_stmt = mysqli_prepare(
    $conn,
    'SELECT allocation_id, resource_id, teacher_id, quantity, date_allocated, status, date_confirmed
     FROM resource_allocation
     WHERE allocation_id = ?'
);
mysqli_stmt_bind_param($load_stmt, 'i', $id);
mysqli_stmt_execute($load_stmt);
$allocation_result = mysqli_stmt_get_result($load_stmt);
$allocation = mysqli_fetch_assoc($allocation_result);

if (!$allocation) {
    header('Location: /radts/modules/resources/report.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
    $new_teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $new_quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $new_date_allocated = isset($_POST['date_allocated']) ? $_POST['date_allocated'] : '';
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $new_date_confirmed = isset($_POST['date_confirmed']) ? $_POST['date_confirmed'] : '';

    if ($new_resource_id <= 0 || $new_teacher_id <= 0 || $new_quantity <= 0 || $new_date_allocated === '' || !in_array($new_status, ['pending', 'confirmed'], true)) {
        $error = 'Please fill in all fields correctly.';
    }

    if ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            $old_resource_id = (int)$allocation['resource_id'];
            $old_quantity = (int)$allocation['quantity'];

            if ($new_resource_id === $old_resource_id) {
                $delta = $new_quantity - $old_quantity;

                if ($delta > 0) {
                    $stock_stmt = mysqli_prepare($conn, 'SELECT quantity_available FROM resources WHERE resource_id = ? FOR UPDATE');
                    mysqli_stmt_bind_param($stock_stmt, 'i', $new_resource_id);
                    mysqli_stmt_execute($stock_stmt);
                    $stock_result = mysqli_stmt_get_result($stock_stmt);
                    $stock = mysqli_fetch_assoc($stock_result);

                    if (!$stock || (int)$stock['quantity_available'] < $delta) {
                        throw new Exception('Insufficient quantity available for the selected resource.');
                    }
                }

                if ($delta !== 0) {
                    $adjust_same_stmt = mysqli_prepare($conn, 'UPDATE resources SET quantity_available = quantity_available - ? WHERE resource_id = ?');
                    mysqli_stmt_bind_param($adjust_same_stmt, 'ii', $delta, $new_resource_id);
                    mysqli_stmt_execute($adjust_same_stmt);
                }
            } else {
                $new_stock_stmt = mysqli_prepare($conn, 'SELECT quantity_available FROM resources WHERE resource_id = ? FOR UPDATE');
                mysqli_stmt_bind_param($new_stock_stmt, 'i', $new_resource_id);
                mysqli_stmt_execute($new_stock_stmt);
                $new_stock_result = mysqli_stmt_get_result($new_stock_stmt);
                $new_stock = mysqli_fetch_assoc($new_stock_result);

                if (!$new_stock || (int)$new_stock['quantity_available'] < $new_quantity) {
                    throw new Exception('Insufficient quantity available for the selected new resource.');
                }

                $restore_old_stmt = mysqli_prepare($conn, 'UPDATE resources SET quantity_available = quantity_available + ? WHERE resource_id = ?');
                mysqli_stmt_bind_param($restore_old_stmt, 'ii', $old_quantity, $old_resource_id);
                mysqli_stmt_execute($restore_old_stmt);

                $consume_new_stmt = mysqli_prepare($conn, 'UPDATE resources SET quantity_available = quantity_available - ? WHERE resource_id = ?');
                mysqli_stmt_bind_param($consume_new_stmt, 'ii', $new_quantity, $new_resource_id);
                mysqli_stmt_execute($consume_new_stmt);
            }

            $final_date_confirmed = null;
            if ($new_status === 'confirmed') {
                $final_date_confirmed = $new_date_confirmed !== '' ? $new_date_confirmed : date('Y-m-d');
            }

            $update_stmt = mysqli_prepare(
                $conn,
                'UPDATE resource_allocation
                 SET resource_id = ?, teacher_id = ?, quantity = ?, date_allocated = ?, status = ?, date_confirmed = ?
                 WHERE allocation_id = ?'
            );
            mysqli_stmt_bind_param(
                $update_stmt,
                'iiisssi',
                $new_resource_id,
                $new_teacher_id,
                $new_quantity,
                $new_date_allocated,
                $new_status,
                $final_date_confirmed,
                $id
            );
            mysqli_stmt_execute($update_stmt);

            mysqli_commit($conn);
            header('Location: /radts/modules/resources/edit_allocation.php?id=' . $id . '&updated=1');
            exit();
        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $error = $ex->getMessage();
        }
    }

    $reload_stmt = mysqli_prepare(
        $conn,
        'SELECT allocation_id, resource_id, teacher_id, quantity, date_allocated, status, date_confirmed
         FROM resource_allocation
         WHERE allocation_id = ?'
    );
    mysqli_stmt_bind_param($reload_stmt, 'i', $id);
    mysqli_stmt_execute($reload_stmt);
    $allocation_result = mysqli_stmt_get_result($reload_stmt);
    $allocation = mysqli_fetch_assoc($allocation_result);
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Allocation updated successfully.';
}

$resources = mysqli_query($conn, 'SELECT resource_id, name, quantity_available FROM resources ORDER BY name ASC');
$teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name ASC");
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Edit Resource Allocation</h1>
    <p>Correct resource allocation details and keep stock levels accurate.</p>
</div>

<div class="card" style="max-width:680px">
    <div class="card-header">
        <h2>Allocation Details</h2>
        <a href="/radts/modules/resources/report.php" class="btn btn-primary btn-sm">← Back to Report</a>
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
                <label class="form-label">Resource *</label>
                <select name="resource_id" class="form-control" required>
                    <option value="">-- Select Resource --</option>
                    <?php while ($res = mysqli_fetch_assoc($resources)): ?>
                    <option value="<?php echo (int)$res['resource_id']; ?>" <?php echo ((int)$allocation['resource_id'] === (int)$res['resource_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($res['name']); ?> (<?php echo (int)$res['quantity_available']; ?> available)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Teacher *</label>
                <select name="teacher_id" class="form-control" required>
                    <option value="">-- Select Teacher --</option>
                    <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                    <option value="<?php echo (int)$teacher['user_id']; ?>" <?php echo ((int)$allocation['teacher_id'] === (int)$teacher['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($teacher['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" class="form-control" min="1"
                        value="<?php echo (int)$allocation['quantity']; ?>"
                        required>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Date Issued *</label>
                    <input type="date" name="date_allocated" class="form-control"
                        value="<?php echo htmlspecialchars($allocation['date_allocated']); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?php echo ($allocation['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo ($allocation['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    </select>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Date Confirmed (Optional)</label>
                    <input type="date" name="date_confirmed" class="form-control"
                        value="<?php echo $allocation['date_confirmed'] ? htmlspecialchars($allocation['date_confirmed']) : ''; ?>"
                        max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-warning">Update Allocation</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>