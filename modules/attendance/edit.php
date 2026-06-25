<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

$can_manage_all = hasRole('admin') || hasRole('deputy');
$can_edit_own   = hasRole('student_leader');

if (!$can_manage_all && !$can_edit_own) {
    header("Location: /radts/index.php");
    exit();
}

$attendance_id = isset($_GET['attendance_id']) ? (int)$_GET['attendance_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_id = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : $attendance_id;
}

if ($attendance_id <= 0) {
    header("Location: /radts/modules/attendance/report.php");
    exit();
}

$success = '';
$error = '';
$current_user_id = (int)$_SESSION['user_id'];

$record_stmt = mysqli_prepare(
    $conn,
    "SELECT a.attendance_id, a.teacher_id, a.recorded_by, a.date, a.subject, a.class, a.status, u.name AS teacher_name
     FROM attendance a
     JOIN users u ON a.teacher_id = u.user_id
     WHERE a.attendance_id = ?"
);
mysqli_stmt_bind_param($record_stmt, "i", $attendance_id);
mysqli_stmt_execute($record_stmt);
$record_result = mysqli_stmt_get_result($record_stmt);
$record = mysqli_fetch_assoc($record_result);

if (!$record) {
    header("Location: /radts/modules/attendance/report.php");
    exit();
}

if ($can_edit_own && !$can_manage_all && (int)$record['recorded_by'] !== $current_user_id) {
    header("Location: /radts/dashboard/student.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $class = isset($_POST['class']) ? trim($_POST['class']) : '';
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if (empty($date) || empty($subject) || empty($class) || $teacher_id <= 0 || !in_array($status, ['present', 'absent'], true)) {
        $error = 'Please fill in all fields correctly.';
    } else {
        $dup_stmt = mysqli_prepare($conn, "SELECT attendance_id FROM attendance WHERE date=? AND subject=? AND class=? AND attendance_id<>?");
        mysqli_stmt_bind_param($dup_stmt, "sssi", $date, $subject, $class, $attendance_id);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);

        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $error = 'Another attendance record already exists for this class, subject and date.';
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE attendance SET teacher_id=?, date=?, subject=?, class=?, status=? WHERE attendance_id=?");
            mysqli_stmt_bind_param($update_stmt, "issssi", $teacher_id, $date, $subject, $class, $status, $attendance_id);

            if (mysqli_stmt_execute($update_stmt)) {
                $redirect_base = $can_manage_all ? '/radts/modules/attendance/report.php' : '/radts/dashboard/student.php';
                header('Location: /radts/modules/attendance/edit.php?attendance_id=' . $attendance_id . '&updated=1&back=' . urlencode($redirect_base));
                exit();
            }

            $error = 'Failed to update attendance record. Please try again.';
        }
    }

    if (empty($error)) {
        $refresh_stmt = mysqli_prepare($conn, "SELECT attendance_id, teacher_id, recorded_by, date, subject, class, status FROM attendance WHERE attendance_id = ?");
        mysqli_stmt_bind_param($refresh_stmt, "i", $attendance_id);
        mysqli_stmt_execute($refresh_stmt);
        $refresh_result = mysqli_stmt_get_result($refresh_stmt);
        $record = mysqli_fetch_assoc($refresh_result);
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Attendance record updated successfully.';
}

$teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");
$classes = ['1a','1b','2a','2b','3a','3b','4a','4b','5a','5b','6a','6b','7a','7b','8a','8b'];
$subjects = ['Mathematics','English','Kiswahili','Science','Social Studies','CRE','IRE','Physical Education','Creative Arts','Home Science','Agriculture','Music'];
$back_link = '/radts/modules/attendance/report.php';
if ($can_edit_own && !$can_manage_all) {
    $back_link = '/radts/dashboard/student.php';
}
if (isset($_GET['back']) && is_string($_GET['back']) && strpos($_GET['back'], '/radts/') === 0) {
    $back_link = $_GET['back'];
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Edit Lesson Attendance</h1>
    <p>Correct attendance details when a recording mistake is found.</p>
</div>

<div class="card">
    <div class="card-header">
        <h2>Update Attendance Record</h2>
        <a href="<?php echo htmlspecialchars($back_link); ?>" class="btn btn-primary btn-sm">← Back</a>
    </div>
    <div class="card-body">

        <?php if (!empty($success)): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="attendance_id" value="<?php echo (int)$attendance_id; ?>">

            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-control"
                        value="<?php echo htmlspecialchars($record['date']); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Class *</label>
                    <select name="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo ($record['class'] === $c) ? 'selected' : ''; ?>>
                            <?php echo 'Grade ' . strtoupper($c); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group-block">
                <label class="form-label">Subject *</label>
                <select name="subject" class="form-control" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                    <option value="<?php echo $sub; ?>" <?php echo ($record['subject'] === $sub) ? 'selected' : ''; ?>>
                        <?php echo $sub; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-block" style="margin-top:20px">
                <label class="form-label">Teacher *</label>
                <select name="teacher_id" class="form-control" required>
                    <option value="">-- Select Teacher --</option>
                    <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                    <option value="<?php echo (int)$teacher['user_id']; ?>" <?php echo ((int)$record['teacher_id'] === (int)$teacher['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($teacher['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group-block" style="margin-top:16px;margin-bottom:16px">
                <label class="form-label">Attendance Status *</label>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;margin:0">
                        <input type="radio" name="status" value="present" <?php echo ($record['status'] === 'present') ? 'checked' : ''; ?> required>
                        Present
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;margin:0">
                        <input type="radio" name="status" value="absent" <?php echo ($record['status'] === 'absent') ? 'checked' : ''; ?>>
                        Absent
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-warning">Update Attendance</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>