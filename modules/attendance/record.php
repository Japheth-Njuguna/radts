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

// Get all teachers
$teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = $_POST['date'];
    $subject    = trim($_POST['subject']);
    $class      = trim($_POST['class']);
    $recorded_by = $_SESSION['user_id'];

    if (empty($date) || empty($subject) || empty($class)) {
        $error = "Please fill in all fields.";
    } elseif (!isset($_POST['attendance']) || empty($_POST['attendance'])) {
        $error = "Please record attendance for at least one teacher.";
    } else {
        $all_ok = true;

        foreach ($_POST['attendance'] as $teacher_id => $status) {
            $teacher_id = (int)$teacher_id;

            // Check if attendance already recorded for this teacher today
            $check = mysqli_prepare($conn, "SELECT attendance_id FROM attendance WHERE teacher_id=? AND date=? AND subject=? AND class=?");
            mysqli_stmt_bind_param($check, "isss", $teacher_id, $date, $subject, $class);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Attendance already recorded for this lesson. Please check your entries.";
                $all_ok = false;
                break;
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO attendance (teacher_id, recorded_by, date, subject, class, status) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iissss", $teacher_id, $recorded_by, $date, $subject, $class, $status);

            if (!mysqli_stmt_execute($stmt)) {
                $all_ok = false;
                $error  = "An error occurred while saving. Please try again.";
                break;
            }
        }

        if ($all_ok && empty($error)) {
            $success = "Attendance recorded successfully for " . count($_POST['attendance']) . " teacher(s).";
        }
    }

    // Re-fetch teachers after submission
    $teachers = mysqli_query($conn, "SELECT user_id, name FROM users WHERE role='teacher' ORDER BY name");
}
?>

<?php require_once '../../includes/header.php'; ?>

<div class="page-header">
    <h1>Record Teacher Lesson Attendance</h1>
    <p>Complete all fields then mark each teacher present or absent</p>
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
                    <label class="form-label">Class *</label>
                    <select name="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php
                        $classes = ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9'];
                        foreach ($classes as $c):
                        ?>
                        <option value="<?php echo $c; ?>" <?php echo (isset($_POST['class']) && $_POST['class']==$c)?'selected':''; ?>>
                            <?php echo $c; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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

            <!-- Teacher Attendance Table -->
            <div style="margin-top:20px;margin-bottom:16px">
                <label class="form-label">Teacher Attendance *</label>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Teacher Name</th>
                            <th style="text-align:center">Present</th>
                            <th style="text-align:center">Absent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                            <td style="text-align:center">
                                <input type="radio"
                                    name="attendance[<?php echo $teacher['user_id']; ?>]"
                                    value="present"
                                    <?php echo (isset($_POST['attendance'][$teacher['user_id']]) && $_POST['attendance'][$teacher['user_id']] === 'present') ? 'checked' : 'checked'; ?>
                                    required>
                            </td>
                            <td style="text-align:center">
                                <input type="radio"
                                    name="attendance[<?php echo $teacher['user_id']; ?>]"
                                    value="absent"
                                    <?php echo (isset($_POST['attendance'][$teacher['user_id']]) && $_POST['attendance'][$teacher['user_id']] === 'absent') ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-primary">Submit Attendance</button>

        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>