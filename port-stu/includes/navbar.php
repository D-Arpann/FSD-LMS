<?php
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar">
    <div class="container">
        <a href="<?php echo BASE_PATH; ?>/public/index.php" class="navbar-brand">
            LMS
        </a>

        <ul class="navbar-nav">
            <?php if (!$user): ?>
                <li><a href="<?php echo BASE_PATH; ?>/public/index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                        Home
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/courses.php"
                        class="<?php echo $currentPage === 'courses' ? 'active' : ''; ?>">
                        Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/about.php" class="<?php echo $currentPage === 'about' ? 'active' : ''; ?>">
                        About
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/signup.php" class="btn btn-primary">
                        Sign Up
                    </a></li>

            <?php elseif ($user['role'] === 'student'): ?>
                <li><a href="<?php echo BASE_PATH; ?>/student/dashboard.php"
                        class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        Dashboard
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/courses.php"
                        class="<?php echo $currentPage === 'courses' ? 'active' : ''; ?>">
                        Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/student/my-courses.php"
                        class="<?php echo $currentPage === 'my-courses' ? 'active' : ''; ?>">
                        My Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/student/support.php"
                        class="<?php echo $currentPage === 'support' ? 'active' : ''; ?>">
                        Support
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/logout.php">
                        Logout
                    </a></li>

            <?php elseif ($user['role'] === 'instructor'): ?>
                <li><a href="<?php echo BASE_PATH; ?>/instructor/dashboard.php"
                        class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        Dashboard
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/courses.php"
                        class="<?php echo $currentPage === 'courses' ? 'active' : ''; ?>">
                        Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/instructor/my-courses.php"
                        class="<?php echo $currentPage === 'my-courses' ? 'active' : ''; ?>">
                        My Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/instructor/support.php"
                        class="<?php echo $currentPage === 'support' ? 'active' : ''; ?>">
                        Support
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/logout.php">
                        Logout
                    </a></li>

            <?php elseif ($user['role'] === 'admin'): ?>
                <li><a href="<?php echo BASE_PATH; ?>/admin/dashboard.php"
                        class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        Dashboard
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/users.php" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                        Users
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/courses.php" class="<?php echo $currentPage === 'courses' ? 'active' : ''; ?>">
                        Courses
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/categories.php"
                        class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                        Categories
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/admin/support.php" class="<?php echo $currentPage === 'support' ? 'active' : ''; ?>">
                        Support
                    </a></li>
                <li><a href="<?php echo BASE_PATH; ?>/public/logout.php">
                        Logout
                    </a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<?php
$flash = getFlashMessage();
if ($flash): ?>
    <div class="container mt-2">
        <div class="alert alert-<?php echo e($flash['type']); ?>">
            <i
                data-lucide="<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'x-circle' : 'alert-triangle'); ?>"></i>
            <?php echo e($flash['message']); ?>
        </div>
    </div>
<?php endif; ?>