<?php
/**
 * Admin - Enrollment Management
 */
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if ($_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM enrollments WHERE id = ?")->execute([(int) $_POST['enrollment_id']]);
        $success = 'Enrollment removed.';
    }
}

$enrollments = $pdo->query("
    SELECT e.*, u.name as user_name, u.email, c.title as course_title
    FROM enrollments e
    INNER JOIN users u ON e.user_id = u.id
    INNER JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
")->fetchAll();

$pageTitle = 'Enrollments';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><i data-lucide="user-check"></i> Enrollments</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($enrollments)): ?>
                <p class="text-dim text-center">No enrollments yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $e): ?>
                            <tr>
                                <td>
                                    <?php echo e($e['user_name']); ?>
                                </td>
                                <td class="text-dim">
                                    <?php echo e($e['email']); ?>
                                </td>
                                <td>
                                    <?php echo e($e['course_title']); ?>
                                </td>
                                <td class="text-dim">
                                    <?php echo date('M j, Y', strtotime($e['enrolled_at'])); ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove enrollment?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $e['id']; ?>">
                                        <button class="btn btn-ghost btn-sm text-error"><i data-lucide="trash-2"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>