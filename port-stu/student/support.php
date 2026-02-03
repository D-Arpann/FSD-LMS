<?php
/**
 * Student Support
 */
require_once __DIR__ . '/../config/auth.php';
requireRole('student');

$user = getCurrentUser();
$pdo = getDBConnection();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $subject, $message]);
            $success = 'Your support request has been submitted.';
        }
    }
}

// Get user's tickets
$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem;">
            <i data-lucide="help-circle"></i> Support
        </h1>

        <div class="grid grid-2" style="gap: 2rem;">
            <!-- New Ticket Form -->
            <div>
                <div class="card">
                    <h2 class="card-title mb-2">
                        <i data-lucide="plus"></i> New Support Request
                    </h2>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i data-lucide="x-circle"></i>
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i data-lucide="check-circle"></i>
                            <?php echo e($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="form-group">
                            <label class="form-label" for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-input"
                                placeholder="Brief description of your issue" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="message">Message</label>
                            <textarea id="message" name="message" class="form-input"
                                placeholder="Describe your issue in detail..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i data-lucide="send"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ticket History -->
            <div>
                <div class="card">
                    <h2 class="card-title mb-2">
                        <i data-lucide="history"></i> My Tickets
                    </h2>

                    <?php if (empty($tickets)): ?>
                        <p class="text-dim text-center">No support tickets yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($tickets as $ticket): ?>
                                <div style="border: 1px solid var(--border-color); border-radius: 4px; padding: 1rem;">
                                    <div class="flex flex-between flex-center mb-1">
                                        <span class="text-sm">
                                            <?php echo e($ticket['subject']); ?>
                                        </span>
                                        <span
                                            class="badge badge-<?php echo $ticket['status'] === 'closed' ? 'success' : ($ticket['status'] === 'in_progress' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-dim text-xs mb-1">
                                        <?php echo e(substr($ticket['message'], 0, 100)); ?>...
                                    </p>
                                    <p class="text-dim text-xs">
                                        <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                                    </p>

                                    <?php if ($ticket['admin_reply']): ?>
                                        <div
                                            style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                            <p class="text-xs text-orange mb-1"><i data-lucide="reply"></i> Admin Response:</p>
                                            <p class="text-sm">
                                                <?php echo nl2br(e($ticket['admin_reply'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>