<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $instructorId = (int) ($_POST['instructor_id'] ?? 0);
                $price = (float) ($_POST['price'] ?? 0);
                $duration = trim($_POST['duration'] ?? '');
                $level = $_POST['level'] ?? 'beginner';

                if (empty($title)) {
                    $error = 'Course title is required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO courses (title, description, category_id, instructor_id, price, duration, level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $categoryId ?: null, $instructorId ?: null, $price, $duration, $level]);
                    $success = 'Course created successfully.';
                }
                break;

            case 'update':
                $courseId = (int) ($_POST['course_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $instructorId = (int) ($_POST['instructor_id'] ?? 0);
                $price = (float) ($_POST['price'] ?? 0);
                $duration = trim($_POST['duration'] ?? '');
                $level = $_POST['level'] ?? 'beginner';

                if (empty($title)) {
                    $error = 'Course title is required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, category_id = ?, instructor_id = ?, price = ?, duration = ?, level = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $categoryId ?: null, $instructorId ?: null, $price, $duration, $level, $courseId]);
                    $success = 'Course updated successfully.';
                }
                break;

            case 'delete':
                $courseId = (int) ($_POST['course_id'] ?? 0);
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$courseId]);
                $success = 'Course deleted successfully.';
                break;
        }
    }
}

// Get courses
$courses = $pdo->query("
    SELECT c.*, cat.name as category_name, u.name as instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.created_at DESC
")->fetchAll();

// Get categories and instructors for forms
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$instructors = $pdo->query("SELECT * FROM users WHERE role = 'instructor' AND verified = 1 ORDER BY name")->fetchAll();

$pageTitle = 'Courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 style="font-size: 1.5rem;">
                <i data-lucide="book-open"></i> Course Management
            </h1>
            <button onclick="openModal('addCourseModal')" class="btn btn-primary">
                <i data-lucide="plus"></i> Add Course
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i data-lucide="x-circle"></i>
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i data-lucide="check-circle"></i>
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($courses)): ?>
                <p class="text-dim text-center">No courses yet.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Instructor</th>
                                <th>Lessons</th>
                                <th>Students</th>
                                <th>Price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>
                                        <?php echo e($course['title']); ?>
                                    </td>
                                    <td class="text-dim">
                                        <?php echo e($course['category_name'] ?? 'None'); ?>
                                    </td>
                                    <td class="text-dim">
                                        <?php echo e($course['instructor_name'] ?? 'Unassigned'); ?>
                                    </td>
                                    <td><span class="badge badge-primary">
                                            <?php echo $course['lesson_count']; ?>
                                        </span></td>
                                    <td><span class="badge badge-success">
                                            <?php echo $course['student_count']; ?>
                                        </span></td>
                                    <td>
                                        <?php echo $course['price'] > 0 ? '$' . number_format($course['price'], 2) : 'Free'; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_PATH; ?>/admin/lessons.php?course_id=<?php echo $course['id']; ?>"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="layers"></i>
                                        </a>
                                        <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirmDelete('Delete this course?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
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
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i data-lucide="plus"></i> Add Course</h2>
            <button class="modal-close" onclick="closeModal('addCourseModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input"></textarea>
            </div>

            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <select name="instructor_id" class="form-input">
                        <option value="">Assign Later</option>
                        <?php foreach ($instructors as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>">
                                <?php echo e($inst['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-3" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Price ($)</label>
                    <input type="number" name="price" class="form-input" value="0" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" class="form-input" placeholder="e.g., 4 weeks">
                </div>

                <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-input">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('addCourseModal')" class="btn btn-ghost"
                    style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i data-lucide="edit"></i> Edit Course</h2>
            <button class="modal-close" onclick="closeModal('editCourseModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="course_id" id="edit_course_id">

            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" id="edit_title" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="edit_description" class="form-input"></textarea>
            </div>

            <div class="grid grid-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" id="edit_category_id" class="form-input">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Instructor</label>
                    <select name="instructor_id" id="edit_instructor_id" class="form-input">
                        <option value="">Assign Later</option>
                        <?php foreach ($instructors as $inst): ?>
                            <option value="<?php echo $inst['id']; ?>">
                                <?php echo e($inst['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-3" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Price ($)</label>
                    <input type="number" name="price" id="edit_price" class="form-input" value="0" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" id="edit_duration" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" id="edit_level" class="form-input">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('editCourseModal')" class="btn btn-ghost"
                    style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editCourse(course) {
        document.getElementById('edit_course_id').value = course.id;
        document.getElementById('edit_title').value = course.title;
        document.getElementById('edit_description').value = course.description || '';
        document.getElementById('edit_category_id').value = course.category_id || '';
        document.getElementById('edit_instructor_id').value = course.instructor_id || '';
        document.getElementById('edit_price').value = course.price;
        document.getElementById('edit_duration').value = course.duration || '';
        document.getElementById('edit_level').value = course.level;
        openModal('editCourseModal');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>