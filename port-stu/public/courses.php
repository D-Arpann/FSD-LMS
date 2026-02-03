<?php
require_once __DIR__ . '/../config/auth.php';

$pdo = getDBConnection();

// Get filter parameters
$categoryFilter = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$levelFilter = isset($_GET['level']) ? $_GET['level'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
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
$courses = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get enrolled course IDs for current student
$enrolledCourseIds = [];
$user = getCurrentUser();
if ($user && $user['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $enrolledCourseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="flex flex-between flex-center mb-3">
            <h1 class="page-title">
                <i data-lucide="book-open"></i>
                <span>Browse Courses</span>
            </h1>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <form method="GET" action="" class="filter-form">
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
                        <option value="beginner" <?php echo $levelFilter === 'beginner' ? 'selected' : ''; ?>>Beginner
                        </option>
                        <option value="intermediate" <?php echo $levelFilter === 'intermediate' ? 'selected' : ''; ?>>
                            Intermediate</option>
                        <option value="advanced" <?php echo $levelFilter === 'advanced' ? 'selected' : ''; ?>>Advanced
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="search"></i> Filter
                </button>

                <?php if ($categoryFilter || $levelFilter || $search): ?>
                    <a href="<?php echo BASE_PATH; ?>/public/courses.php" class="btn btn-ghost">
                        <i data-lucide="x"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Course Grid -->
        <?php if (empty($courses)): ?>
            <div class="card">
                <p class="text-dim text-center">
                    <i data-lucide="search"></i> No courses found.
                </p>
            </div>
        <?php else: ?>
            <p class="text-dim text-sm mb-2">
                <?php echo count($courses); ?> course(s) found
            </p>
            <div class="grid grid-3">
                <?php foreach ($courses as $course): 
                    $isEnrolled = in_array($course['id'], $enrolledCourseIds);
                ?>
                    <div class="card course-card">
                        <div class="flex flex-between flex-center">
                            <span class="badge badge-primary">
                                <?php echo e($course['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <?php if ($isEnrolled): ?>
                                <span class="badge badge-success">
                                    <i data-lucide="check"></i>
                                    <span>Enrolled</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="course-title">
                            <?php echo e($course['title']); ?>
                        </h3>
                        <p class="text-sm text-dim course-desc">
                            <?php echo e(substr($course['description'] ?? '', 0, 100)); ?>...
                        </p>
                        <div class="card-meta">
                            <span><i data-lucide="user"></i>
                                <?php echo e($course['instructor_name'] ?? 'TBA'); ?>
                            </span>
                            <span><i data-lucide="play-circle"></i>
                                <?php echo $course['lesson_count']; ?>
                            </span>
                            <span><i data-lucide="signal"></i>
                                <?php echo ucfirst(e($course['level'])); ?>
                            </span>
                        </div>
                        <?php if (!$isEnrolled): ?>
                            <?php if ($course['price'] > 0): ?>
                                <p class="text-orange mt-1 price-text">$
                                    <?php echo number_format($course['price'], 2); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-success mt-1 price-text">Free</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($isEnrolled): ?>
                            <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                class="btn btn-primary btn-sm mt-2 w-full">
                                <i data-lucide="play"></i> Continue Learning
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $course['id']; ?>"
                                class="btn btn-secondary btn-sm mt-2 w-full">
                                <i data-lucide="eye"></i> View Course
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>