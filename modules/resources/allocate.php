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

// Get all resources
$resources = mysqli_query($conn, "SELECT * FROM resources WHERE quantity_available > 0 ORDER BY name");

// Get all teachers
$teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");

// Pre-select resource if passed via URL
$preselect_resource = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id  = (int)$_POST['resource_id'];
    $teacher_id   = (int)$_POST['teacher_id'];
    $quantity     = (int)$_POST['quantity'];
    $date         = $_POST['date_allocated'];
    $allocated_by = $_SESSION['user_id'];

    if ($resource_id <= 0 || $teacher_id <= 0 || $quantity <= 0 || empty($date)) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Check available quantity
        $res_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT quantity_available FROM resources WHERE resource_id=$resource_id"));

        if (!$res_check || $res_check['quantity_available'] < $quantity) {
            $error = "Insufficient quantity available. Only " . ($res_check['quantity_available'] ?? 0) . " left.";
        } else {
            // Insert allocation
            $stmt = mysqli_prepare($conn, "INSERT INTO resource_allocation (resource_id, teacher_id, allocated_by, quantity, date_allocated) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiiis", $resource_id, $teacher_id, $allocated_by, $quantity, $date);

            if (mysqli_stmt_execute($stmt)) {
                // Reduce available quantity
                mysqli_query($conn, "UPDATE resources SET quantity_available = quantity_available - $quantity WHERE resource_id = $resource_id");
                $success = "Resource allocated successfully. The teacher will confirm receipt upon login.";

                // Re-fetch resources
                $resources = mysqli_query($conn, "SELECT * FROM resources WHERE quantity_available > 0 ORDER BY name");
                $teachers  = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");
            } else {
                $error = "Failed to allocate resource. Please try again.";
            }
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Allocate Learning Resource</h1>
    <p>Issue a resource to a teacher and track the allocation</p>
</div>

<div class="card" style="max-width:560px">
    <div class="card-header">
        <h2>Allocation Details</h2>
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
                <label class="form-label">Resource *</label>
                <select name="resource_id" class="form-control" required>
                    <option value="">-- Select Resource --</option>
                    <?php while ($res = mysqli_fetch_assoc($resources)): ?>
                    <option value="<?php echo $res['resource_id']; ?>"
                        <?php echo ($preselect_resource == $res['resource_id'] || (isset($_POST['resource_id']) && $_POST['resource_id'] == $res['resource_id'])) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($res['name']); ?> (<?php echo $res['quantity_available']; ?> available)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group-block">
                <label class="form-label">Teacher *</label>
                <select name="teacher_id" class="form-control" required>
                    <option value="">-- Select Teacher --</option>
                    <?php while ($t = mysqli_fetch_assoc($teachers)): ?>
                    <option value="<?php echo $t['user_id']; ?>"
                        <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $t['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="quantity" class="form-control"
                        min="1" placeholder="e.g. 2"
                        value="<?php echo isset($_POST['quantity']) ? (int)$_POST['quantity'] : ''; ?>"
                        required>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Date Allocated *</label>
                    <input type="date" name="date_allocated" class="form-control"
                        value="<?php echo isset($_POST['date_allocated']) ? $_POST['date_allocated'] : date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Allocate Resource</button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>