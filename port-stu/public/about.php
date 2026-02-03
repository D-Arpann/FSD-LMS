<?php
/**
 * About / Contact Page
 */
require_once __DIR__ . '/../config/auth.php';

$success = '';
$error = '';

// Handle contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // If user is logged in, create a support ticket
            $user = getCurrentUser();
            if ($user) {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $subject, $message]);
            }
            // For guests, we just show success (in real app, would send email)
            $success = 'Thank you for your message! We will get back to you soon.';
        }
    }
}

$user = getCurrentUser();
$pageTitle = 'About';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="grid grid-2" style="gap: 3rem;">
            <!-- About Section -->
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 1rem;">
                    <i class="lucide-info"></i> About LMS
                </h1>

                <div class="card mb-2">
                    <h3 class="text-orange mb-1">Our Mission</h3>
                    <p class="text-sm">
                        We provide a comprehensive learning platform designed to help students
                        acquire new skills and knowledge through structured courses taught by
                        expert instructors.
                    </p>
                </div>

                <div class="card mb-2">
                    <h3 class="text-orange mb-1">Features</h3>
                    <ul style="list-style: none; font-size: 0.875rem;">
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                            <i class="lucide-check text-success"></i> Browse and enroll in courses
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                            <i class="lucide-check text-success"></i> Track your learning progress
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                            <i class="lucide-check text-success"></i> Video-based lessons
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                            <i class="lucide-check text-success"></i> Personal dashboard
                        </li>
                        <li style="padding: 0.5rem 0;">
                            <i class="lucide-check text-success"></i> Support system
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <h3 class="text-orange mb-1">Contact Info</h3>
                    <div style="font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <span><i class="lucide-mail"></i> support@lms.example.com</span>
                        <span><i class="lucide-phone"></i> +1 (555) 123-4567</span>
                        <span><i class="lucide-map-pin"></i> 123 Learning Street, Education City</span>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="lucide-message-square"></i> Contact Us
                </h2>

                <div class="card">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="lucide-x-circle"></i>
                            <?php echo e($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="lucide-check-circle"></i>
                            <?php echo e($success); ?>
                        </div>
                    <?php else: ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                            <div class="form-group">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" id="name" name="name" class="form-input" placeholder="Your name"
                                    value="<?php echo e($user['name'] ?? ($_POST['name'] ?? '')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input" placeholder="your@email.com"
                                    value="<?php echo e($user['email'] ?? ($_POST['email'] ?? '')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" class="form-input"
                                    placeholder="How can we help?" value="<?php echo e($_POST['subject'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="message">Message</label>
                                <textarea id="message" name="message" class="form-input" placeholder="Your message..."
                                    required><?php echo e($_POST['message'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="lucide-send"></i> Send Message
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>