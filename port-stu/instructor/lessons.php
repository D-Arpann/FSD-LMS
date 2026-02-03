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

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
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

            case 'update':
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

            case 'delete':
                $lessonId = (int) ($_POST['lesson_id'] ?? 0);
                $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ? AND course_id = ?");
                $stmt->execute([$lessonId, $courseId]);
                $success = 'Lesson deleted successfully.';
                break;
        }
    }
}

// Get lessons for this course
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY order_num ASC, id ASC");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

$pageTitle = 'Manage Lessons';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <a href="<?php echo BASE_PATH; ?>/instructor/my-courses.php" class="btn btn-ghost btn-sm mb-2">
            <i data-lucide="arrow-left"></i>
            <span>Back to My Courses</span>
        </a>

        <div class="flex flex-between flex-center mb-3">
            <div>
                <h1 class="page-title">
                    <?php echo e($course['title']); ?>
                </h1>
                <p class="text-dim text-sm mt-1">Manage course lessons</p>
            </div>
            <button onclick="openModal('addLessonModal')" class="btn btn-primary">
                <i data-lucide="plus"></i>
                <span>Add Lesson</span>
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i data-lucide="x-circle"></i>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle"></i>
                <span><?php echo e($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Lessons List -->
        <?php if (empty($lessons)): ?>
            <div class="card">
                <p class="text-dim text-center">
                    <i data-lucide="layers"></i>
                    <br>No lessons yet.
                    <br>Click "Add Lesson" to create your first lesson.
                </p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Title</th>
                                <th>Video</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td>
                                        <?php echo $lesson['order_num']; ?>
                                    </td>
                                    <td>
                                        <?php echo e($lesson['title']); ?>
                                    </td>
                                    <td>
                                        <?php if ($lesson['video_url']): ?>
                                            <span class="badge badge-success">
                                                <i data-lucide="video"></i>
                                                <span>Yes</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i data-lucide="video-off"></i>
                                                <span>No</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="editLesson(<?php echo htmlspecialchars(json_encode($lesson)); ?>)"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="edit"></i>
                                        </button>
                                        <form method="POST" class="inline-form"
                                            onsubmit="return confirmDelete('Delete this lesson?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

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
            <input type="hidden" name="action" value="create">

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
            <input type="hidden" name="action" value="update">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>