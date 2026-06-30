<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
requireLogin();

if (!hasRole('student_leader')) {
    header("Location: /radts/index.php");
    exit();
}

$success = '';
$error   = '';

$admissionToClass = [
    '1001' => '1a',
    '1002' => '1b',
    '1003' => '2a',
    '1004' => '2b',
    '1005' => '3a',
    '1006' => '3b',
    '1007' => '4a',
    '1008' => '4b',
    '1009' => '5a',
    '1010' => '5b',
    '1011' => '6a',
    '1012' => '6b',
    '1013' => '7a',
    '1014' => '7b',
    '1015' => '8a',
    '1016' => '8b',
];

$admissionNo = isset($_SESSION['email']) ? trim((string)$_SESSION['email']) : '';
$studentClass = $admissionToClass[$admissionNo] ?? '';

if ($studentClass === '') {
    $error = 'Your account is not mapped to a class. Please contact admin.';
}

// Get all teachers
$teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = $_POST['date'];
    $subject    = trim($_POST['subject']);
    $class      = $studentClass;
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $status     = isset($_POST['status']) ? trim($_POST['status']) : '';
    $recorded_by = $_SESSION['user_id'];

    if ($studentClass === '') {
        $error = 'Your account is not mapped to a class. Please contact admin.';
    } elseif (empty($date) || empty($subject) || empty($class) || $teacher_id <= 0 || !in_array($status, ['present', 'absent'], true)) {
        $error = "Please fill in all fields.";
    } else {
        // Prevent duplicate entry for the same class + subject + date lesson.
        $check = mysqli_prepare($conn, "SELECT attendance_id FROM attendance WHERE date=? AND subject=? AND class=?");
        mysqli_stmt_bind_param($check, "sss", $date, $subject, $class);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Attendance for this class and subject has already been recorded for the selected date.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO attendance (teacher_id, recorded_by, date, subject, class, status) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iissss", $teacher_id, $recorded_by, $date, $subject, $class, $status);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Attendance recorded successfully.";
            } else {
                $error  = "An error occurred while saving. Please try again.";
            }
        }
    }

    // Re-fetch teachers after submission
    $teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Record Teacher Lesson Attendance</h1>
    <p>Record attendance for one teacher per lesson</p>
</div>

<div class="card">
    <div class="card-header">
        <h2>Attendance Form</h2>
        <a href="/radts/dashboard/student.php" class="btn btn-primary btn-sm">← Back to Dashboard</a>
    </div>
    <div class="card-body">

        <?php if (!empty($success)): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg">⚠ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <!-- Lesson Details -->
            <div class="form-row">
                <div class="form-group-block">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-control"
                        value="<?php echo isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required>
                </div>
                <div class="form-group-block">
                    <label class="form-label">Class</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?php echo $studentClass !== '' ? 'Grade ' . strtoupper($studentClass) : 'Not assigned'; ?>"
                        readonly
                    >
                </div>
            </div>

            <div class="form-group-block">
                <label class="form-label">Subject *</label>
                <select name="subject" class="form-control" required>
                    <option value="">-- Select Subject --</option>
                    <?php
                    $subjects = ['Mathematics','English','Kiswahili','Science','Social Studies','CRE','IRE','Physical Education','Creative Arts','Home Science','Agriculture','Music'];
                    foreach ($subjects as $sub):
                    ?>
                    <option value="<?php echo $sub; ?>" <?php echo (isset($_POST['subject']) && $_POST['subject']==$sub)?'selected':''; ?>>
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
                    <option value="<?php echo (int)$teacher['user_id']; ?>" <?php echo (isset($_POST['teacher_id']) && (int)$_POST['teacher_id'] === (int)$teacher['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($teacher['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group-block" style="margin-top:16px;margin-bottom:16px">
                <label class="form-label">Attendance Status *</label>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:6px;margin:0">
                        <input type="radio" name="status" value="present" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'present') ? 'checked' : ''; ?> required>
                        Present
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;margin:0">
                        <input type="radio" name="status" value="absent" <?php echo (isset($_POST['status']) && $_POST['status'] === 'absent') ? 'checked' : ''; ?>>
                        Absent
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Submit Attendance</button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>