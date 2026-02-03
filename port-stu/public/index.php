<?php
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();

// Get featured courses (latest 6)
$stmt = $pdo->query("
    SELECT c.*, cat.name as category_name, u.name as instructor_name
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 6
");
$featuredCourses = $stmt->fetchAll();

// Get stats
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalLessons = $pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn();

$pageTitle = 'Home';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">
            Learn. <span>Code.</span> Grow.
        </h1>
        <p class="hero-subtitle">
            Welcome to the Learning Management System. Browse courses, expand your skills, and track your progress.
        </p>
        <div class="flex gap-2 justify-center">
            <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-primary btn-lg">
                <i data-lucide="book-open"></i> Browse Courses
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo BASE_PATH; ?>/public/signup.php" class="btn btn-secondary btn-lg">
                    <i data-lucide="user-plus"></i> Get Started
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<main class="main-content">
    <div class="container">
        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalCourses; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="book-open"></i> Courses
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalStudents; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="users"></i> Students
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-value">
                    <?php echo $totalLessons; ?>
                </div>
                <div class="stat-label">
                    <i data-lucide="play-circle"></i> Lessons
                </div>
            </div>
        </div>

        <!-- Featured Courses -->
        <div class="mb-4">
            <div class="flex flex-between flex-center mb-2">
                <h2 class="section-title">
                    <i data-lucide="star"></i> Featured Courses
                </h2>
                <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-ghost btn-sm">
                    View All <i data-lucide="arrow-right"></i>
                </a>
            </div>

            <?php if (empty($featuredCourses)): ?>
                <div class="card">
                    <p class="text-dim text-center">No courses available yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($featuredCourses as $course): ?>
                        <div class="card course-card">
                            <span class="badge badge-primary">
                                <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <h3 class="course-title">
                                <?php echo e($course['title']); ?>
                            </h3>
                            <p class="text-sm text-dim course-desc">
                                <?php echo e(substr($course['description'] ?? '', 0, 80)); ?>...
                            </p>
                            <div class="card-meta">
                                <span><i data-lucide="user"></i>
                                    <?php echo e($course['instructor_name'] ?? 'TBA'); ?>
                                </span>
                                <span><i data-lucide="signal"></i>
                                    <?php echo ucfirst(e($course['level'])); ?>
                                </span>
                            </div>
                            <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                class="btn btn-secondary btn-sm mt-2 w-full">
                                <i data-lucide="eye"></i> View Course
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>