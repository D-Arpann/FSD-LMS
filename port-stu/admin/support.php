<?php
/**
 * Admin - Support Tickets
 */
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');

$pdo = getDBConnection();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

    if ($action === 'reply') {
        $reply = trim($_POST['admin_reply'] ?? '');
        $status = $_POST['status'] ?? 'in_progress';
        $stmt = $pdo->prepare("UPDATE support_tickets SET admin_reply = ?, status = ? WHERE id = ?");
        $stmt->execute([$reply, $status, $ticketId]);
        $success = 'Response sent.';
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM support_tickets WHERE id = ?")->execute([$ticketId]);
        $success = 'Ticket deleted.';
    }
}

$tickets = $pdo->query("
    SELECT t.*, u.name as user_name, u.email, u.role as user_role
    FROM support_tickets t
    INNER JOIN users u ON t.user_id = u.id
    ORDER BY t.status ASC, t.created_at DESC
")->fetchAll();

$pageTitle = 'Support';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><i data-lucide="message-square"></i> Support Tickets</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="card">
                <p class="text-dim text-center">No support tickets.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($tickets as $t): ?>
                    <div class="card">
                        <div class="flex flex-between flex-center mb-1">
                            <div>
                                <strong>
                                    <?php echo e($t['subject']); ?>
                                </strong>
                                <span class="text-dim text-sm"> -
                                    <?php echo e($t['user_name']); ?> (
                                    <?php echo ucfirst($t['user_role']); ?>)
                                </span>
                            </div>
                            <span
                                class="badge badge-<?php echo $t['status'] === 'closed' ? 'success' : ($t['status'] === 'in_progress' ? 'warning' : 'primary'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                            </span>
                        </div>

                        <p class="text-sm mb-1">
                            <?php echo nl2br(e($t['message'])); ?>
                        </p>
                        <p class="text-dim text-xs mb-2">
                            <?php echo date('M j, Y g:i A', strtotime($t['created_at'])); ?>
                        </p>

                        <?php if ($t['admin_reply']): ?>
                            <div
                                style="background: var(--bg-secondary); padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                                <p class="text-xs text-orange mb-1"><i data-lucide="reply"></i> Your Response:</p>
                                <p class="text-sm">
                                    <?php echo nl2br(e($t['admin_reply'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                            <div style="flex: 1;">
                                <textarea name="admin_reply" class="form-input" style="min-height: 60px;"
                                    placeholder="Type response..."><?php echo e($t['admin_reply'] ?? ''); ?></textarea>
                            </div>
                            <select name="status" class="form-input" style="width: auto;">
                                <option value="in_progress" <?php echo $t['status'] === 'in_progress' ? 'selected' : ''; ?>>In
                                    Progress</option>
                                <option value="closed" <?php echo $t['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <button type="submit" class="btn btn-primary"><i data-lucide="send"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>