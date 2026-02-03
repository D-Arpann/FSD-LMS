<?php
require_once __DIR__ . '/../config/auth.php';
requireVerifiedInstructor();

$user = getCurrentUser();
$pdo = getDBConnection();

// Get filter parameters
$categoryFilter = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$levelFilter = isset($_GET['level']) ? $_GET['level'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'assigned';

// Get assigned course IDs for this instructor
$stmt = $pdo->prepare("SELECT id FROM courses WHERE instructor_id = ?");
$stmt->execute([$user['id']]);
$assignedCourseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get assigned courses with full details
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, u.name as instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.instructor_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user['id']]);
$assignedCourses = $stmt->fetchAll();

// Build query for all courses (browse)
$sql = "
    SELECT c.*, cat.name as category_name, u.name as instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE 1=1
";
$params = [];

if ($categoryFilter > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $categoryFilter;
}

if ($levelFilter && in_array($levelFilter, ['beginner', 'intermediate', 'advanced'])) {
    $sql .= " AND c.level = ?";
    $params[] = $levelFilter;
}

if ($search) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allCourses = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'My Courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 class="page-title">
                <i data-lucide="book-open"></i>
                <span>My Courses</span>
            </h1>
        </div>

        <!-- View Toggle Tabs -->
        <div class="tabs mb-3">
            <a href="?view=assigned" class="tab <?php echo $view === 'assigned' ? 'active' : ''; ?>">
                <i data-lucide="briefcase"></i>
                <span>Assigned Courses (<?php echo count($assignedCourses); ?>)</span>
            </a>
            <a href="?view=browse" class="tab <?php echo $view === 'browse' ? 'active' : ''; ?>">
                <i data-lucide="compass"></i>
                <span>Browse All Courses</span>
            </a>
        </div>

        <?php if ($view === 'assigned'): ?>
            <!-- Assigned Courses View -->
            <?php if (empty($assignedCourses)): ?>
                <div class="card">
                    <p class="text-dim text-center">
                        <i data-lucide="inbox"></i>
                        <br>No courses assigned yet.
                        <br>Contact admin for course assignment.
                    </p>
                </div>
            <?php else: ?>
                <div class="grid grid-2">
                    <?php foreach ($assignedCourses as $course): ?>
                        <div class="card course-card">
                            <div class="flex flex-between flex-center mb-1">
                                <span class="badge badge-primary">
                                    <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                                <span class="badge badge-success">
                                    <i data-lucide="check"></i>
                                    <span>Assigned</span>
                                </span>
                            </div>

                            <h3 class="course-title">
                                <?php echo e($course['title']); ?>
                            </h3>

                            <p class="text-sm text-dim mb-2 leading-relaxed">
                                <?php echo e(substr($course['description'] ?? '', 0, 100)); ?><?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                            </p>

                            <div class="course-meta mb-2">
                                <span class="meta-item">
                                    <i data-lucide="play-circle"></i>
                                    <span><?php echo $course['lesson_count']; ?></span>
                                </span>
                                <span class="meta-item">
                                    <i data-lucide="users"></i>
                                    <span><?php echo $course['student_count']; ?></span>
                                </span>
                                <span class="meta-item">
                                    <i data-lucide="signal"></i>
                                    <span><?php echo ucfirst(e($course['level'])); ?></span>
                                </span>
                            </div>

                            <div class="flex gap-1">
                                <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                    class="btn btn-primary btn-sm flex-1">
                                    <i data-lucide="layers"></i>
                                    <span>Manage Lessons</span>
                                </a>
                                <a href="<?php echo BASE_PATH; ?>/instructor/students.php?course_id=<?php echo $course['id']; ?>"
                                    class="btn btn-secondary btn-sm flex-1">
                                    <i data-lucide="users"></i>
                                    <span>Students</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Browse All Courses View -->
            <div class="card mb-3">
                <form method="GET" action="" class="filter-form">
                    <input type="hidden" name="view" value="browse">
                    <div class="form-group form-group-flex">
                        <label class="form-label" for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-input" placeholder="Search courses..."
                            value="<?php echo e($search); ?>">
                    </div>

                    <div class="form-group form-group-inline">
                        <label class="form-label" for="category">Category</label>
                        <select id="category" name="category" class="form-input">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group form-group-inline">
                        <label class="form-label" for="level">Level</label>
                        <select id="level" name="level" class="form-input">
                            <option value="">All Levels</option>
                            <option value="beginner" <?php echo $levelFilter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo $levelFilter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo $levelFilter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i>
                        <span>Filter</span>
                    </button>

                    <?php if ($categoryFilter || $levelFilter || $search): ?>
                        <a href="?view=browse" class="btn btn-ghost">
                            <i data-lucide="x"></i>
                            <span>Clear</span>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($allCourses)): ?>
                <div class="card">
                    <p class="text-dim text-center">
                        <i data-lucide="search"></i>
                        <br>No courses found.
                    </p>
                </div>
            <?php else: ?>
                <p class="text-dim text-sm mb-2">
                    <?php echo count($allCourses); ?> course(s) found
                </p>
                <div class="grid grid-3">
                    <?php foreach ($allCourses as $course): 
                        $isAssigned = in_array($course['id'], $assignedCourseIds);
                    ?>
                        <div class="card course-card">
                            <div class="flex flex-between flex-center mb-1">
                                <span class="badge badge-primary">
                                    <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                                <?php if ($isAssigned): ?>
                                    <span class="badge badge-success">
                                        <i data-lucide="check"></i>
                                        <span>Assigned</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="course-title">
                                <?php echo e($course['title']); ?>
                            </h3>

                            <p class="text-sm text-dim course-desc">
                                <?php echo e(substr($course['description'] ?? '', 0, 100)); ?>...
                            </p>

                            <div class="course-meta">
                                <span class="meta-item">
                                    <i data-lucide="user"></i>
                                    <span><?php echo e($course['instructor_name'] ?? 'TBA'); ?></span>
                                </span>
                                <span class="meta-item">
                                    <i data-lucide="play-circle"></i>
                                    <span><?php echo $course['lesson_count']; ?></span>
                                </span>
                                <span class="meta-item">
                                    <i data-lucide="signal"></i>
                                    <span><?php echo ucfirst(e($course['level'])); ?></span>
                                </span>
                            </div>

                            <?php if ($course['price'] > 0): ?>
                                <p class="text-orange mt-1 price-text">$<?php echo number_format($course['price'], 2); ?></p>
                            <?php else: ?>
                                <p class="text-success mt-1 price-text">Free</p>
                            <?php endif; ?>

                            <?php if ($isAssigned): ?>
                                <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                    class="btn btn-primary btn-sm mt-2 w-full">
                                    <i data-lucide="layers"></i>
                                    <span>Manage Course</span>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                    class="btn btn-secondary btn-sm mt-2 w-full">
                                    <i data-lucide="eye"></i>
                                    <span>View Course</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>