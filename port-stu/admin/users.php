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
        $userId = (int) ($_POST['user_id'] ?? 0);

        switch ($action) {
            case 'verify':
                $stmt = $pdo->prepare("UPDATE users SET verified = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                $success = 'Instructor verified successfully.';
                break;

            case 'delete':
                if ($userId != getCurrentUser()['id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $success = 'User deleted successfully.';
                } else {
                    $error = 'Cannot delete your own account.';
                }
                break;

            case 'update':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? '';

                if (!in_array($role, ['admin', 'instructor', 'student'])) {
                    $error = 'Invalid role.';
                } elseif (empty($name) || empty($email)) {
                    $error = 'Name and email are required.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $userId]);
                    $success = 'User updated successfully.';
                }
                break;
        }
    }
}

// Filter
$roleFilter = $_GET['role'] ?? '';
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($roleFilter && in_array($roleFilter, ['admin', 'instructor', 'student'])) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 style="font-size: 1.5rem;">
                <i data-lucide="users"></i> User Management
            </h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i data-lucide="x-circle"></i> <?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i data-lucide="check-circle"></i> <?php echo e($success); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-3">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                <label class="form-label" style="margin: 0;">Filter by Role:</label>
                <select name="role" class="form-input" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="instructor" <?php echo $roleFilter === 'instructor' ? 'selected' : ''; ?>>Instructor
                    </option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                </select>
                <?php if ($roleFilter): ?>
                    <a href="<?php echo BASE_PATH; ?>/admin/users.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <p class="text-dim text-sm mb-2"><?php echo count($users); ?> user(s) found</p>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo e($u['name']); ?></td>
                                <td class="text-dim"><?php echo e($u['email']); ?></td>
                                <td><span class="badge badge-primary"><?php echo ucfirst($u['role']); ?></span></td>
                                <td>
                                    <?php if ($u['role'] === 'instructor' && !$u['verified']): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-dim text-sm"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'instructor' && !$u['verified']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i data-lucide="check"></i> Verify
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                                        class="btn btn-ghost btn-sm">
                                        <i data-lucide="edit"></i>
                                    </button>

                                    <?php if ($u['id'] != getCurrentUser()['id']): ?>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Delete this user?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i data-lucide="edit"></i> Edit User</h2>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="edit_name" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" id="edit_role" class="form-input">
                    <option value="student">Student</option>
                    <option value="instructor">Instructor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="flex gap-1">
                <button type="button" onclick="closeModal('editUserModal')" class="btn btn-ghost"
                    style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        openModal('editUserModal');
        lucide.createIcons();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>