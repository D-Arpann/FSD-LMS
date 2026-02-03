<?php
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    switch ($user['role']) {
        case 'admin':
            redirect(BASE_PATH . '/admin/dashboard.php');
        case 'instructor':
            redirect(BASE_PATH . '/instructor/dashboard.php');
        default:
            redirect(BASE_PATH . '/student/dashboard.php');
    }
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check if instructor is verified
                if ($user['role'] === 'instructor' && !$user['verified']) {
                    $error = 'Your instructor account is pending approval.';
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];

                    switch ($user['role']) {
                        case 'admin':
                            redirect(BASE_PATH . '/admin/dashboard.php', 'Welcome back, ' . $user['name'] . '!');
                        case 'instructor':
                            redirect(BASE_PATH . '/instructor/dashboard.php', 'Welcome back, ' . $user['name'] . '!');
                        default:
                            redirect(BASE_PATH . '/student/dashboard.php', 'Welcome back, ' . $user['name'] . '!');
                    }
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="auth-container">
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i data-lucide="log-in"></i> Login
                    </h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i data-lucide="x-circle"></i>
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="user@example.com"
                            value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="log-in"></i> Login
                    </button>
                </form>

                <p class="text-center mt-2 text-sm text-dim">
                    Don't have an account? <a href="<?php echo BASE_PATH; ?>/public/signup.php">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>