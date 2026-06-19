<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

if (!hasRole('admin')) {
    header("Location: /radts/index.php");
    exit();
}

// Count all users
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='teacher'"))['total'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='student_leader'"))['total'];
$total_deputies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='deputy'"))['total'];

// Get all users
$users = mysqli_query($conn, "SELECT user_id, `NAME` AS name, email, role, created_at FROM users ORDER BY created_at DESC");
?>

<?php require_once '../includes/header.php'; ?>

<div class="page-header">
    <h1>System Administrator Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?php echo $total_users; ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Teachers</div>
        <div class="stat-value"><?php echo $total_teachers; ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Student Leaders</div>
        <div class="stat-value"><?php echo $total_students; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Deputy Head Teachers</div>
        <div class="stat-value"><?php echo $total_deputies; ?></div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h2>All System Users</h2>
        <a href="/radts/modules/users/create.php" class="btn btn-primary">+ Add New User</a>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Login ID</th>
                    <th>Role</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user['role'] === 'student_leader' ? 'student' : $user['role']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                          <td style="display:flex;gap:6px">
                                <a href="/radts/modules/users/edit.php?id=<?php echo $user['user_id']; ?>"
                                    class="btn btn-warning btn-sm">
                                    Edit
                                </a>
                        <a href="/radts/modules/users/delete.php?id=<?php echo $user['user_id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to delete this user?')">
                           Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>