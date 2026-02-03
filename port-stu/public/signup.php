<?php
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    redirect(BASE_PATH . '/public/index.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        $bio = trim($_POST['bio'] ?? '');
        $experience = trim($_POST['experience'] ?? '');

        // Validate role
        if (!in_array($role, ['student', 'instructor'])) {
            $role = 'student';
        }

        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif ($role === 'instructor' && (empty($bio) || empty($experience))) {
            $error = 'Bio and experience are required for instructor registration.';
        } else {
            try {
                $pdo = getDBConnection();

                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $error = 'Email already registered.';
                } else {
                    // Create user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $verified = ($role === 'student') ? 1 : 0; // Instructors need approval

                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, bio, experience, verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $hashedPassword, $role, $bio ?: null, $experience ?: null, $verified]);

                    if ($role === 'instructor') {
                        $success = 'Registration successful! Your instructor account is pending admin approval.';
                    } else {
                        // Auto-login for students
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        redirect(BASE_PATH . '/student/dashboard.php', 'Welcome to LMS, ' . $name . '!');
                    }
                }
            } catch (PDOException $e) {
                // Log error for debugging (in production, log to file, here show friendly message)
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Sign Up';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div style="max-width: 500px; margin: 2rem auto;">
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">
                        Sign Up
                    </h1>
                </div>

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
                <?php else: ?>

                    <form method="POST" action="" id="signupForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="form-group">
                            <label class="form-label" for="name">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-input" placeholder="John Doe"
                                value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="user@example.com"
                                value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-input"
                                placeholder="Min. 6 characters" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                                placeholder="Confirm password" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Register as *</label>
                            <div style="display: flex; gap: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="radio" name="role" value="student" id="role_student" 
                                        <?php echo ($_POST['role'] ?? 'student') === 'student' ? 'checked' : ''; ?>>
                                    Student
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="radio" name="role" value="instructor" id="role_instructor"
                                        <?php echo ($_POST['role'] ?? '') === 'instructor' ? 'checked' : ''; ?>>
                                    Instructor
                                </label>
                            </div>
                        </div>

                        <!-- Instructor Fields (hidden by default) -->
                        <div id="instructorFields" style="display: none;">
                            <div class="form-group">
                                <label class="form-label" for="bio">Personal Bio *</label>
                                <textarea id="bio" name="bio" class="form-input" 
                                    placeholder="Tell us about yourself, your expertise, and what you teach..."><?php echo e($_POST['bio'] ?? ''); ?></textarea>
                                <small class="text-dim">Describe who you are and what areas you specialize in.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="experience">Experience & Qualifications *</label>
                                <textarea id="experience" name="experience" class="form-input" 
                                    placeholder="Your educational background, certifications, years of experience..."><?php echo e($_POST['experience'] ?? ''); ?></textarea>
                                <small class="text-dim">Include your education, certifications, and relevant work experience.</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i data-lucide="user-plus"></i> Create Account
                        </button>
                    </form>

                    <p class="text-center mt-2 text-sm text-dim">
                        Already have an account? <a href="<?php echo BASE_PATH; ?>/public/login.php">Login</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Toggle instructor fields based on role selection
document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const instructorFields = document.getElementById('instructorFields');
        if (this.value === 'instructor') {
            instructorFields.style.display = 'block';
        } else {
            instructorFields.style.display = 'none';
        }
    });
});

// Show instructor fields if already selected (on page reload with errors)
if (document.getElementById('role_instructor').checked) {
    document.getElementById('instructorFields').style.display = 'block';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>