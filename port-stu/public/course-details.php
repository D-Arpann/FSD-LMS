<?php
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();

// Get course ID
$courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($courseId <= 0) {
    redirect(BASE_PATH . '/public/courses.php', 'Course not found.', 'error');
}

// Get course details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.name as instructor_name
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    redirect(BASE_PATH . '/public/courses.php', 'Course not found.', 'error');
}

// Check enrollment status and instructor assignment
$isEnrolled = false;
$user = getCurrentUser();
$isAssignedInstructor = false;

if ($user) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user['id'], $courseId]);
    $isEnrolled = (bool) $stmt->fetch();
    
    // Check if current user is the assigned instructor
    if ($user['role'] === 'instructor' && $course['instructor_id'] == $user['id']) {
        $isAssignedInstructor = true;
    }
}

$error = '';
$success = '';

// Handle lesson CRUD for assigned instructors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAssignedInstructor) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_lesson':
                $title = trim($_POST['title'] ?? '');
                $videoUrl = trim($_POST['video_url'] ?? '');
                $learningOutcome = trim($_POST['learning_outcome'] ?? '');
                $orderNum = (int) ($_POST['order_num'] ?? 0);

                if (empty($title)) {
                    $error = 'Lesson title is required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, video_url, learning_outcome, order_num) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$courseId, $title, $videoUrl, $learningOutcome, $orderNum]);
                    $success = 'Lesson created successfully.';
                }
                break;

            case 'update_lesson':
                $lessonId = (int) ($_POST['lesson_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $videoUrl = trim($_POST['video_url'] ?? '');
                $learningOutcome = trim($_POST['learning_outcome'] ?? '');
                $orderNum = (int) ($_POST['order_num'] ?? 0);

                if (empty($title)) {
                    $error = 'Lesson title is required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE lessons SET title = ?, video_url = ?, learning_outcome = ?, order_num = ? WHERE id = ? AND course_id = ?");
                    $stmt->execute([$title, $videoUrl, $learningOutcome, $orderNum, $lessonId, $courseId]);
                    $success = 'Lesson updated successfully.';
                }
                break;

            case 'delete_lesson':
                $lessonId = (int) ($_POST['lesson_id'] ?? 0);
                $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ? AND course_id = ?");
                $stmt->execute([$lessonId, $courseId]);
                $success = 'Lesson deleted successfully.';
                break;
        }
    }
}

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    if (!$user) {
        redirect(BASE_PATH . '/public/login.php', 'Please login to enroll.', 'warning');
    }

    if ($user['role'] !== 'student') {
        redirect(BASE_PATH . '/public/course-details.php?id=' . $courseId, 'Only students can enroll in courses.', 'error');
    }

    if (!$isEnrolled) {
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $courseId]);
        redirect(BASE_PATH . '/public/course-details.php?id=' . $courseId, 'Successfully enrolled!', 'success');
    }
}

// Get lessons (after potential CRUD operations)
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_num ASC, id ASC");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

// Calculate progress for enrolled students
$completedLessons = 0;
$totalLessons = count($lessons);
$progress = 0;
$isCompleted = false;
$completedLessonIds = [];

if ($isEnrolled && $user) {
    $stmt = $pdo->prepare("
        SELECT lp.lesson_id FROM lesson_progress lp
        INNER JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.user_id = ? AND l.course_id = ? AND lp.completed = 1
    ");
    $stmt->execute([$user['id'], $courseId]);
    $completedLessonIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $completedLessons = count($completedLessonIds);
    
    if ($totalLessons > 0) {
        $progress = round(($completedLessons / $totalLessons) * 100);
        $isCompleted = ($completedLessons >= $totalLessons);
    }
}

$pageTitle = $course['title'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-ghost btn-sm mb-2">
            <i data-lucide="arrow-left"></i>
            <span>Back to Courses</span>
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error mb-2">
                <i data-lucide="x-circle"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-2">
                <i data-lucide="check-circle"></i>
                <span><?php echo e($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($isAssignedInstructor): ?>
            <!-- Instructor CRUD Banner -->
            <div class="alert alert-success mb-2 instructor-banner">
                <div class="flex flex-center gap-1">
                    <i data-lucide="shield-check"></i>
                    <span>You are the assigned instructor for this course</span>
                </div>
                <a href="<?php echo BASE_PATH; ?>/instructor/students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-secondary btn-sm">
                    <i data-lucide="users"></i>
                    <span>View Students</span>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($isEnrolled && $isCompleted): ?>
            <!-- Course Completed Banner -->
            <div class="alert alert-success mb-2">
                <i data-lucide="trophy"></i>
                <span>Congratulations! You have completed this course.</span>
            </div>
        <?php endif; ?>

        <div class="grid grid-course-details">
            <!-- Course Info -->
            <div>
                <span class="badge badge-primary mb-1">
                    <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                </span>
                <h1 class="course-page-title">
                    <?php echo e($course['title']); ?>
                </h1>

                <div class="card mb-2">
                    <h3 class="text-sm text-dim mb-1">Description</h3>
                    <p>
                        <?php echo nl2br(e($course['description'] ?? 'No description available.')); ?>
                    </p>
                </div>

                <!-- Lesson List -->
                <div class="card">
                    <div class="flex flex-between flex-center mb-1">
                        <h3 class="text-sm text-dim meta-item">
                            <i data-lucide="list"></i>
                            <span>Lessons (<?php echo count($lessons); ?>)</span>
                        </h3>
                        <?php if ($isAssignedInstructor): ?>
                            <button onclick="openModal('addLessonModal')" class="btn btn-primary btn-sm">
                                <i data-lucide="plus"></i>
                                <span>Add Lesson</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($lessons)): ?>
                        <p class="text-dim">No lessons available yet.</p>
                    <?php else: ?>
                        <ul class="lesson-list">
                            <?php foreach ($lessons as $index => $lesson): 
                                $lessonCompleted = in_array($lesson['id'], $completedLessonIds);
                            ?>
                                <li class="lesson-item<?php echo $lessonCompleted ? ' completed' : ''; ?>">
                                    <span class="lesson-icon">
                                        <?php if ($lessonCompleted): ?>
                                            <i data-lucide="check-circle"></i>
                                        <?php elseif ($isEnrolled || ($user && in_array($user['role'], ['admin', 'instructor']))): ?>
                                            <i data-lucide="play-circle"></i>
                                        <?php else: ?>
                                            <i data-lucide="lock"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="lesson-title<?php echo $lessonCompleted ? ' text-orange' : ''; ?>">
                                        <?php echo ($index + 1) . '. ' . e($lesson['title']); ?>
                                    </span>
                                    <div class="flex gap-1">
                                        <?php if ($isEnrolled || ($user && in_array($user['role'], ['admin', 'instructor']))): ?>
                                            <a href="<?php echo BASE_PATH; ?>/student/lesson.php?id=<?php echo $lesson['id']; ?>"
                                                class="btn btn-ghost btn-sm">
                                                <i data-lucide="play"></i>
                                                <span>Watch</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isAssignedInstructor): ?>
                                            <button onclick="editLesson(<?php echo htmlspecialchars(json_encode($lesson)); ?>)"
                                                class="btn btn-ghost btn-sm">
                                                <i data-lucide="edit"></i>
                                            </button>
                                            <form method="POST" class="inline-form"
                                                onsubmit="return confirmDelete('Delete this lesson?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="delete_lesson">
                                                <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!$isEnrolled && $user && $user['role'] === 'student'): ?>
                        <p class="text-dim text-sm mt-2 meta-item">
                            <i data-lucide="info"></i>
                            <span>Enroll to access lessons</span>
                        </p>
                    <?php elseif (!$user): ?>
                        <p class="text-dim text-sm mt-2 meta-item">
                            <i data-lucide="info"></i>
                            <span><a href="<?php echo BASE_PATH; ?>/public/login.php">Login</a> or <a
                                    href="<?php echo BASE_PATH; ?>/public/signup.php">sign up</a> to access lessons</span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="card">
                    <?php if ($course['price'] > 0): ?>
                        <div class="stat-value">$<?php echo number_format($course['price'], 2); ?></div>
                    <?php else: ?>
                        <div class="stat-value text-success">Free</div>
                    <?php endif; ?>

                    <div class="sidebar-info">
                        <div class="meta-item text-sm">
                            <i data-lucide="user"></i>
                            <span>
                                <span class="text-dim">Instructor:</span>
                                <?php echo e($course['instructor_name'] ?? 'TBA'); ?>
                            </span>
                        </div>
                        <div class="meta-item text-sm">
                            <i data-lucide="signal"></i>
                            <span>
                                <span class="text-dim">Level:</span>
                                <?php echo ucfirst(e($course['level'])); ?>
                            </span>
                        </div>
                        <div class="meta-item text-sm">
                            <i data-lucide="clock"></i>
                            <span>
                                <span class="text-dim">Duration:</span>
                                <?php echo e($course['duration'] ?? 'Self-paced'); ?>
                            </span>
                        </div>
                        <div class="meta-item text-sm">
                            <i data-lucide="play-circle"></i>
                            <span>
                                <span class="text-dim">Lessons:</span>
                                <?php echo count($lessons); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($isEnrolled): ?>
                        <div class="mb-1">
                            <div class="flex flex-between flex-center mb-1">
                                <span class="text-sm text-dim">Progress</span>
                                <span class="text-orange text-sm"><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                            </div>
                            <p class="text-xs text-dim mt-1"><?php echo $completedLessons; ?>/<?php echo $totalLessons; ?> lessons completed</p>
                        </div>
                    <?php elseif ($user && $user['role'] === 'student'): ?>
                        <form method="POST">
                            <button type="submit" name="enroll" class="btn btn-primary w-full">
                                <i data-lucide="plus"></i>
                                <span>Enroll Now</span>
                            </button>
                        </form>
                    <?php elseif ($isAssignedInstructor): ?>
                        <a href="<?php echo BASE_PATH; ?>/instructor/students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary w-full">
                            <i data-lucide="users"></i>
                            <span>View Enrolled Students</span>
                        </a>
                    <?php elseif ($user && $user['role'] === 'instructor'): ?>
                        <div class="text-dim text-sm text-center empty-state">
                            <i data-lucide="info"></i>
                            <span>View only - Not assigned</span>
                        </div>
                    <?php elseif (!$user): ?>
                        <a href="<?php echo BASE_PATH; ?>/public/login.php" class="btn btn-primary w-full">
                            <i data-lucide="log-in"></i>
                            <span>Login to Enroll</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if ($isAssignedInstructor): ?>
<!-- Add Lesson Modal -->
<div id="addLessonModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i data-lucide="plus"></i>
                <span>Add Lesson</span>
            </h2>
            <button class="modal-close" onclick="closeModal('addLessonModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create_lesson">

            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">YouTube URL</label>
                <input type="url" name="video_url" class="form-input" placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div class="form-group">
                <label class="form-label">Learning Outcome</label>
                <textarea name="learning_outcome" class="form-input"
                    placeholder="What students should understand..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Order</label>
                <input type="number" name="order_num" class="form-input" value="<?php echo count($lessons) + 1; ?>"
                    min="0">
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('addLessonModal')" class="btn btn-ghost flex-1">Cancel</button>
                <button type="submit" class="btn btn-primary flex-1">
                    <i data-lucide="plus"></i>
                    <span>Add Lesson</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Lesson Modal -->
<div id="editLessonModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i data-lucide="edit"></i>
                <span>Edit Lesson</span>
            </h2>
            <button class="modal-close" onclick="closeModal('editLessonModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_lesson">
            <input type="hidden" name="lesson_id" id="edit_lesson_id">

            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" id="edit_title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">YouTube URL</label>
                <input type="url" name="video_url" id="edit_video_url" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Learning Outcome</label>
                <textarea name="learning_outcome" id="edit_learning_outcome" class="form-input"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Order</label>
                <input type="number" name="order_num" id="edit_order_num" class="form-input" min="0">
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('editLessonModal')" class="btn btn-ghost flex-1">Cancel</button>
                <button type="submit" class="btn btn-primary flex-1">
                    <i data-lucide="check"></i>
                    <span>Update</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editLesson(lesson) {
        document.getElementById('edit_lesson_id').value = lesson.id;
        document.getElementById('edit_title').value = lesson.title;
        document.getElementById('edit_video_url').value = lesson.video_url || '';
        document.getElementById('edit_learning_outcome').value = lesson.learning_outcome || '';
        document.getElementById('edit_order_num').value = lesson.order_num;
        openModal('editLessonModal');
    }
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>