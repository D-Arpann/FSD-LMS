<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('instructor');

$user = getCurrentUser();
$pdo = getDBConnection();

// Check if verified
if (!$user['verified']) {
    $pageTitle = 'Pending Approval';
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/navbar.php';
    ?>
    <main class="main-content">
        <div class="container">
            <div class="card pending-card">
                <i data-lucide="clock" class="pending-icon"></i>
                <h1>Pending Approval</h1>
                <p class="text-dim">
                    Your instructor account is pending admin verification.
                    You will be able to access the instructor dashboard once approved.
                </p>
            </div>
        </div>
    </main>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Get assigned courses count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
$stmt->execute([$user['id']]);
$assignedCourses = $stmt->fetchColumn();

// Get total students enrolled in instructor's courses
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.user_id) 
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.id
    WHERE c.instructor_id = ?
");
$stmt->execute([$user['id']]);
$totalStudents = $stmt->fetchColumn();

// Get total lessons in instructor's courses
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE c.instructor_id = ?
");
$stmt->execute([$user['id']]);
$totalLessons = $stmt->fetchColumn();

// Get recent courses
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recentCourses = $stmt->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 class="page-title mb-3">
            <i data-lucide="layout-dashboard"></i>
            <span>Instructor Dashboard</span>
        </h1>

        <p class="text-dim mb-3">Welcome back, <span class="text-orange">
                <?php echo e($user['name']); ?>
            </span></p>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $assignedCourses; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="book-open"></i>
                    <span>Assigned Courses</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalStudents; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="users"></i>
                    <span>Total Students</span>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalLessons; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="play-circle"></i>
                    <span>Total Lessons</span>
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="briefcase"></i>
                    <span>My Assigned Courses</span>
                </h2>
                <a href="<?php echo BASE_PATH; ?>/instructor/my-courses.php" class="btn btn-ghost btn-sm">
                    <span>View All</span>
                    <i data-lucide="arrow-right"></i>
                </a>
            </div>

            <?php if (empty($recentCourses)): ?>
                <p class="text-dim text-center">
                    No courses assigned yet. Contact admin for course assignment.
                </p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Students</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCourses as $course): ?>
                                <tr>
                                    <td>
                                        <?php echo e($course['title']); ?>
                                    </td>
                                    <td class="text-dim">
                                        <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo $course['student_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_PATH; ?>/instructor/lessons.php?course_id=<?php echo $course['id']; ?>"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="layers"></i>
                                            <span>Lessons</span>
                                        </a>
                                        <a href="<?php echo BASE_PATH; ?>/instructor/students.php?course_id=<?php echo $course['id']; ?>"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="users"></i>
                                            <span>Students</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>