<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');

$user = getCurrentUser();
$pdo = getDBConnection();

// Get enrolled courses with progress
$stmt = $pdo->prepare("
    SELECT c.*, e.enrolled_at, cat.name as category_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
           (SELECT COUNT(*) FROM lesson_progress lp 
            INNER JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.user_id = ? AND l.course_id = c.id AND lp.completed = 1) as completed_lessons
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$user['id'], $user['id']]);
$courses = $stmt->fetchAll();

$pageTitle = 'My Courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 class="page-title">
                <i data-lucide="bookmark"></i>
                <span>My Courses</span>
            </h1>
            <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-secondary btn-sm">
                <i data-lucide="plus"></i> Browse More Courses
            </a>
        </div>

        <?php if (empty($courses)): ?>
            <div class="card">
                <p class="text-dim text-center">
                    <i data-lucide="book-open"></i> You haven't enrolled in any courses yet.
                    <br><br>
                    <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-primary">
                        Browse Courses
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($courses as $course):
                    $progress = $course['total_lessons'] > 0
                        ? round(($course['completed_lessons'] / $course['total_lessons']) * 100)
                        : 0;
                    ?>
                    <div class="card">
                        <div class="flex flex-between flex-center mb-1">
                            <span class="badge badge-primary">
                                <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <?php if ($progress === 100): ?>
                                <span class="badge badge-success">
                                    <i data-lucide="check"></i> Completed
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="course-title">
                            <?php echo e($course['title']); ?>
                        </h3>

                        <div class="flex flex-between flex-center mb-2">
                            <span class="text-dim text-xs">
                                <?php echo $course['completed_lessons']; ?> /
                                <?php echo $course['total_lessons']; ?> lessons
                            </span>
                            <span class="text-orange text-sm" data-progress-text="<?php echo $course['id']; ?>">
                                <?php echo $progress; ?>%
                            </span>
                        </div>

                        <div class="progress mb-2">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%;"
                                data-course-progress="<?php echo $course['id']; ?>"></div>
                        </div>

                        <div class="flex gap-1">
                            <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                class="btn btn-secondary btn-sm flex-1">
                                <i data-lucide="eye"></i> View
                            </a>
                            <?php
                            // Get first incomplete lesson
                            $stmt = $pdo->prepare("
                        SELECT l.id FROM lessons l
                        LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                        WHERE l.course_id = ? AND (lp.completed IS NULL OR lp.completed = 0)
                        ORDER BY l.order_num ASC, l.id ASC
                        LIMIT 1
                    ");
                            $stmt->execute([$user['id'], $course['id']]);
                            $nextLesson = $stmt->fetch();
                            ?>
                            <?php if ($nextLesson): ?>
                                <a href="<?php echo BASE_PATH; ?>/student/lesson.php?id=<?php echo $nextLesson['id']; ?>"
                                    class="btn btn-primary btn-sm flex-1">
                                    <i data-lucide="play"></i> Continue
                                </a>
                            <?php endif; ?>
                        </div>

                        <p class="text-dim text-xs mt-2">
                            Enrolled:
                            <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>