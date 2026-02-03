<?php
require_once __DIR__ . '/../config/auth.php';
requireVerifiedInstructor();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get course ID
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

// Verify course belongs to instructor
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$courseId, $user['id']]);
$course = $stmt->fetch();

if (!$course) {
    redirect(BASE_PATH . '/instructor/my-courses.php', 'Course not found or access denied.', 'error');
}

// Get total lessons for progress calculation
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
$stmt->execute([$courseId]);
$totalLessons = $stmt->fetchColumn();

// Get enrolled students with progress
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, e.enrolled_at,
           (SELECT COUNT(*) FROM lesson_progress lp 
            INNER JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.user_id = u.id AND l.course_id = ? AND lp.completed = 1) as completed_lessons
    FROM enrollments e
    INNER JOIN users u ON e.user_id = u.id
    WHERE e.course_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$courseId, $courseId]);
$students = $stmt->fetchAll();

$pageTitle = 'Students';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-ghost btn-sm mb-2">
            <i data-lucide="arrow-left"></i>
            <span>Back to Course</span>
        </a>

        <div class="mb-3">
            <h1 class="page-title">
                <i data-lucide="users"></i>
                <span>Enrolled Students</span>
            </h1>
            <p class="text-dim text-sm mt-1"><?php echo e($course['title']); ?></p>
        </div>

        <div class="card">
            <?php if (empty($students)): ?>
                <p class="text-dim text-center empty-state">
                    <i data-lucide="users"></i>
                    No students enrolled in this course yet.
                </p>
            <?php else: ?>
                <p class="text-dim text-sm mb-2">
                    <span class="meta-item">
                        <i data-lucide="users"></i>
                        <span><?php echo count($students); ?> student(s) enrolled</span>
                    </span>
                </p>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Progress</th>
                                <th>Enrolled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student):
                                $progress = $totalLessons > 0
                                    ? round(($student['completed_lessons'] / $totalLessons) * 100)
                                    : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="meta-item">
                                            <i data-lucide="user"></i>
                                            <span><?php echo e($student['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-dim">
                                        <?php echo e($student['email']); ?>
                                    </td>
                                    <td style="min-width: 180px;">
                                        <div class="progress-cell">
                                            <div class="progress-row">
                                                <div class="progress" style="flex: 1;">
                                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                                                </div>
                                                <span class="text-xs text-orange" style="min-width: 35px; text-align: right;">
                                                    <?php echo $progress; ?>%
                                                </span>
                                            </div>
                                            <span class="text-xs text-dim">
                                                <?php echo $student['completed_lessons']; ?>/<?php echo $totalLessons; ?> lessons
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-dim text-sm">
                                        <?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?>
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