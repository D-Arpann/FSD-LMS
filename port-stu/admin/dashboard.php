<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');

$user = getCurrentUser();
$pdo = getDBConnection();

// Get stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$pendingInstructors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor' AND verified = 0")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();

// Recent users
$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent enrollments
$recentEnrollments = $pdo->query("
    SELECT e.*, u.name as user_name, c.title as course_title
    FROM enrollments e
    INNER JOIN users u ON e.user_id = u.id
    INNER JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem;">
            <i data-lucide="layout-dashboard"></i> Admin Dashboard
        </h1>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalUsers; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="users"></i> Total Users
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalCourses; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="book-open"></i> Courses
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalEnrollments; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="user-check"></i> Enrollments
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value text-warning">
                    <?php echo $pendingInstructors; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="clock"></i> Pending Instructors
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value text-orange">
                    <?php echo $openTickets; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="message-square"></i> Open Tickets
                </div>
            </div>
        </div>

        <div class="grid grid-2" style="gap: 2rem;">
            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i data-lucide="users"></i> Recent Users</h2>
                    <a href="<?php echo BASE_PATH; ?>/admin/users.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <?php echo e($u['name']); ?>
                                    </td>
                                    <td><span class="badge badge-primary">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span></td>
                                    <td>
                                        <?php if ($u['role'] === 'instructor' && !$u['verified']): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Enrollments -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i data-lucide="user-check"></i> Recent Enrollments</h2>
                    <a href="<?php echo BASE_PATH; ?>/admin/enrollments.php" class="btn btn-ghost btn-sm">View All</a>
                </div>
                <?php if (empty($recentEnrollments)): ?>
                    <p class="text-dim text-center">No enrollments yet.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEnrollments as $e): ?>
                                    <tr>
                                        <td>
                                            <?php echo e($e['user_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo e($e['course_title']); ?>
                                        </td>
                                        <td class="text-dim text-sm">
                                            <?php echo date('M j', strtotime($e['enrolled_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>