<?php
/**
 * Admin - Category Management
 */
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
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($name)) {
                    $error = 'Category name is required.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    $success = 'Category created successfully.';
                }
                break;

            case 'update':
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (empty($name)) {
                    $error = 'Category name is required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $categoryId]);
                    $success = 'Category updated successfully.';
                }
                break;

            case 'delete':
                $categoryId = (int) ($_POST['category_id'] ?? 0);
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                $success = 'Category deleted successfully.';
                break;
        }
    }
}

// Get categories with course count
$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM courses WHERE category_id = c.id) as course_count
    FROM categories c
    ORDER BY c.name
")->fetchAll();

$pageTitle = 'Categories';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 style="font-size: 1.5rem;">
                <i data-lucide="folder"></i> Category Management
            </h1>
            <button onclick="openModal('addCategoryModal')" class="btn btn-primary">
                <i data-lucide="plus"></i> Add Category
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
            <?php if (empty($categories)): ?>
                <p class="text-dim text-center">No categories yet.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Courses</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <?php echo $cat['id']; ?>
                                    </td>
                                    <td>
                                        <?php echo e($cat['name']); ?>
                                    </td>
                                    <td class="text-dim">
                                        <?php echo e(substr($cat['description'] ?? '', 0, 50)); ?>
                                    </td>
                                    <td><span class="badge badge-primary">
                                            <?php echo $cat['course_count']; ?>
                                        </span></td>
                                    <td>
                                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)"
                                            class="btn btn-ghost btn-sm">
                                            <i data-lucide="edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirmDelete('Delete this category?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
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

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i data-lucide="plus"></i> Add Category</h2>
            <button class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input"></textarea>
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('addCategoryModal')" class="btn btn-ghost"
                    style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i data-lucide="edit"></i> Edit Category</h2>
            <button class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="category_id" id="edit_category_id">

            <div class="form-group">
                <label class="form-label">Name *</label>
                <input type="text" name="name" id="edit_name" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="edit_description" class="form-input"></textarea>
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('editCategoryModal')" class="btn btn-ghost"
                    style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editCategory(cat) {
        document.getElementById('edit_category_id').value = cat.id;
        document.getElementById('edit_name').value = cat.name;
        document.getElementById('edit_description').value = cat.description || '';
        openModal('editCategoryModal');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>