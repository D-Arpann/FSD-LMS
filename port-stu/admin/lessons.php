<?php
/**
 * Admin - Lesson Management
 */
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$error = '';
$success = '';
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $cId = (int) ($_POST['course_id'] ?? 0);
        if ($title && $cId > 0) {
            $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, video_url, learning_outcome, order_num) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cId, $title, $_POST['video_url'] ?? '', $_POST['learning_outcome'] ?? '', (int) ($_POST['order_num'] ?? 0)]);
            $success = 'Lesson created.';
        }
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE lessons SET title=?, video_url=?, learning_outcome=?, order_num=? WHERE id=?");
        $stmt->execute([$_POST['title'], $_POST['video_url'] ?? '', $_POST['learning_outcome'] ?? '', (int) $_POST['order_num'], (int) $_POST['lesson_id']]);
        $success = 'Lesson updated.';
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM lessons WHERE id=?")->execute([(int) $_POST['lesson_id']]);
        $success = 'Lesson deleted.';
    }
}

$sql = "SELECT l.*, c.title as course_title FROM lessons l INNER JOIN courses c ON l.course_id = c.id";
if ($courseId > 0) {
    $stmt = $pdo->prepare($sql . " WHERE l.course_id = ? ORDER BY l.order_num");
    $stmt->execute([$courseId]);
} else {
    $stmt = $pdo->query($sql . " ORDER BY l.course_id, l.order_num");
}
$lessons = $stmt->fetchAll();
$courses = $pdo->query("SELECT * FROM courses ORDER BY title")->fetchAll();

$pageTitle = 'Lessons';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 style="font-size: 1.5rem;"><i data-lucide="layers"></i> Lessons</h1>
            <button onclick="openModal('addLessonModal')" class="btn btn-primary"><i data-lucide="plus"></i>
                Add</button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <form method="GET" style="display:flex;gap:1rem;align-items:center;">
                <select name="course_id" class="form-input" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $courseId == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo e($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Title</th>
                        <th>Course</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $l): ?>
                        <tr>
                            <td>
                                <?php echo $l['order_num']; ?>
                            </td>
                            <td>
                                <?php echo e($l['title']); ?>
                            </td>
                            <td class="text-dim">
                                <?php echo e($l['course_title']); ?>
                            </td>
                            <td>
                                <button onclick="editLesson(<?php echo htmlspecialchars(json_encode($l)); ?>)"
                                    class="btn btn-ghost btn-sm"><i data-lucide="edit"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="lesson_id" value="<?php echo $l['id']; ?>">
                                    <button class="btn btn-ghost btn-sm text-error"><i data-lucide="trash-2"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="addLessonModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Add Lesson</h2><button class="modal-close" onclick="closeModal('addLessonModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group"><label class="form-label">Course</label>
                <select name="course_id" class="form-input" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo e($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Title</label><input type="text" name="title"
                    class="form-input" required></div>
            <div class="form-group"><label class="form-label">YouTube URL</label><input type="url" name="video_url"
                    class="form-input"></div>
            <div class="form-group"><label class="form-label">Learning Outcome</label><textarea name="learning_outcome"
                    class="form-input"></textarea></div>
            <div class="form-group"><label class="form-label">Order</label><input type="number" name="order_num"
                    class="form-input" value="0"></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Create</button>
        </form>
    </div>
</div>

<div id="editLessonModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Lesson</h2><button class="modal-close" onclick="closeModal('editLessonModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="lesson_id" id="edit_lesson_id">
            <div class="form-group"><label class="form-label">Title</label><input type="text" name="title"
                    id="edit_title" class="form-input" required></div>
            <div class="form-group"><label class="form-label">YouTube URL</label><input type="url" name="video_url"
                    id="edit_video_url" class="form-input"></div>
            <div class="form-group"><label class="form-label">Learning Outcome</label><textarea name="learning_outcome"
                    id="edit_learning_outcome" class="form-input"></textarea></div>
            <div class="form-group"><label class="form-label">Order</label><input type="number" name="order_num"
                    id="edit_order_num" class="form-input"></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Update</button>
        </form>
    </div>
</div>

<script>
    function editLesson(l) {
        document.getElementById('edit_lesson_id').value = l.id;
        document.getElementById('edit_title').value = l.title;
        document.getElementById('edit_video_url').value = l.video_url || '';
        document.getElementById('edit_learning_outcome').value = l.learning_outcome || '';
        document.getElementById('edit_order_num').value = l.order_num;
        openModal('editLessonModal');
        lucide.createIcons(); // Refresh for modal
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>