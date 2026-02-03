<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');

$user = getCurrentUser();
$pdo = getDBConnection();

// Get enrolled courses count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
$stmt->execute([$user['id']]);
$enrolledCount = $stmt->fetchColumn();

// Get completed lessons count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND completed = 1");
$stmt->execute([$user['id']]);
$completedLessons = $stmt->fetchColumn();

// Get recent enrollments with progress
$stmt = $pdo->prepare("
    SELECT c.*, e.enrolled_at,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
           (SELECT COUNT(*) FROM lesson_progress lp 
            INNER JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.user_id = ? AND l.course_id = c.id AND lp.completed = 1) as completed_lessons
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 5
");
$stmt->execute([$user['id'], $user['id']]);
$recentCourses = $stmt->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem;">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </h1>

        <p class="text-dim mb-3">Welcome back, <span class="text-orange">
                <?php echo e($user['name']); ?>
            </span></p>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $enrolledCount; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="bookmark"></i> Enrolled Courses
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $completedLessons; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="check-circle"></i> Completed Lessons
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="clock"></i> Recent Courses
                </h2>
                <a href="<?php echo BASE_PATH; ?>/student/my-courses.php" class="btn btn-ghost btn-sm">
                    View All <i data-lucide="arrow-right"></i>
                </a>
            </div>

            <?php if (empty($recentCourses)): ?>
                <p class="text-dim text-center">
                    You haven't enrolled in any courses yet.
                    <a href="<?php echo BASE_PATH; ?>/public/courses.php">Browse courses</a>
                </p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Progress</th>
                                <th>Enrolled</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCourses as $course):
                                $progress = $course['total_lessons'] > 0
                                    ? round(($course['completed_lessons'] / $course['total_lessons']) * 100)
                                    : 0;
                                ?>
                                <tr>
                                    <td>
                                        <?php echo e($course['title']); ?>
                                    </td>
                                    <td style="width: 200px;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="progress" style="flex: 1;">
                                                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                                            </div>
                                            <span class="text-xs">
                                                <?php echo $progress; ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-dim text-sm">
                                        <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="play"></i> Continue
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