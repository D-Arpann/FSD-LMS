<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$user = getCurrentUser();
$pdo = getDBConnection();

$lessonId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($lessonId <= 0) {
    redirect(BASE_PATH . '/student/my-courses.php', 'Lesson not found.', 'error');
}

// Get lesson details
$stmt = $pdo->prepare("
    SELECT l.*, c.id as course_id, c.title as course_title, c.instructor_id
    FROM lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.id = ?
");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    redirect(BASE_PATH . '/student/my-courses.php', 'Lesson not found.', 'error');
}

// Check access - must be enrolled, admin, or assigned instructor
$hasAccess = false;
if ($user['role'] === 'admin') {
    $hasAccess = true;
} elseif ($user['role'] === 'instructor' && $lesson['instructor_id'] == $user['id']) {
    $hasAccess = true;
} elseif ($user['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user['id'], $lesson['course_id']]);
    $hasAccess = (bool) $stmt->fetch();
}

if (!$hasAccess) {
    redirect(BASE_PATH . '/public/course-details.php?id=' . $lesson['course_id'], 'Please enroll to access lessons.', 'warning');
}

// Get all lessons in this course
$stmt = $pdo->prepare("
    SELECT l.*, 
           (SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = l.id) as is_completed
    FROM lessons l
    WHERE l.course_id = ?
    ORDER BY l.order_num ASC, l.id ASC
");
$stmt->execute([$user['id'], $lesson['course_id']]);
$allLessons = $stmt->fetchAll();

// Find current position and next lesson
$currentIndex = 0;
$nextLesson = null;
foreach ($allLessons as $index => $l) {
    if ($l['id'] == $lessonId) {
        $currentIndex = $index;
        if (isset($allLessons[$index + 1])) {
            $nextLesson = $allLessons[$index + 1];
        }
        break;
    }
}

// Check if current lesson is completed
$stmt = $pdo->prepare("SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?");
$stmt->execute([$user['id'], $lessonId]);
$progressRow = $stmt->fetch();
$isCompleted = $progressRow && $progressRow['completed'];

// Extract YouTube video ID
$videoId = '';
if ($lesson['video_url']) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $lesson['video_url'], $matches);
    $videoId = $matches[1] ?? '';
}

$pageTitle = $lesson['title'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <a href="<?php echo BASE_PATH; ?>/public/course-details.php?id=<?php echo $lesson['course_id']; ?>"
            class="btn btn-ghost btn-sm mb-2">
            <i data-lucide="arrow-left"></i> Back to
            <?php echo e($lesson['course_title']); ?>
        </a>

        <div class="grid" style="grid-template-columns: 1fr 300px; gap: 2rem;">
            <!-- Main Content -->
            <div>
                <div class="flex flex-between flex-center mb-2">
                    <h1 style="font-size: 1.25rem;">
                        <?php if ($isCompleted): ?>
                            <i data-lucide="check-circle" class="text-success"></i>
                        <?php else: ?>
                            <i data-lucide="play-circle" class="text-orange"></i>
                        <?php endif; ?>
                        <?php echo e($lesson['title']); ?>
                    </h1>
                    <span class="text-dim text-sm">
                        Lesson
                        <?php echo $currentIndex + 1; ?> of
                        <?php echo count($allLessons); ?>
                    </span>
                </div>

                <!-- Video -->
                <?php if ($videoId): ?>
                    <div class="video-container mb-2">
                        <iframe src="https://www.youtube.com/embed/<?php echo e($videoId); ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div class="card mb-2" style="padding: 3rem; text-align: center;">
                        <i data-lucide="video-off" style="font-size: 3rem; color: var(--text-dim);"></i>
                        <p class="text-dim mt-1">No video available for this lesson.</p>
                    </div>
                <?php endif; ?>

                <!-- Learning Outcome -->
                <?php if ($lesson['learning_outcome']): ?>
                    <div class="card">
                        <h3 class="text-sm text-dim mb-1 flex flex-center gap-1">
                            <i data-lucide="target"></i> <span style="transform: translateY(1px);">What you should
                                understand after this lesson</span>
                        </h3>
                        <p>
                            <?php echo nl2br(e($lesson['learning_outcome'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Navigation -->
                <div class="flex flex-between mt-3">
                    <?php if ($currentIndex > 0): ?>
                        <a href="<?php echo BASE_PATH; ?>/student/lesson.php?id=<?php echo $allLessons[$currentIndex - 1]['id']; ?>"
                            class="btn btn-ghost">
                            <i data-lucide="arrow-left"></i> Previous Lesson
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <?php if ($nextLesson): ?>
                        <button id="nextLessonBtn"
                            onclick="completeAndNext(<?php echo $lessonId; ?>, <?php echo $nextLesson['id']; ?>)"
                            class="btn btn-primary">
                            <?php if ($isCompleted): ?>
                                Next Lesson <i data-lucide="arrow-right"></i>
                            <?php else: ?>
                                Complete & Next <i data-lucide="arrow-right"></i>
                            <?php endif; ?>
                        </button>
                    <?php elseif (!$isCompleted): ?>
                        <button id="completeBtn" onclick="markComplete(<?php echo $lessonId; ?>)" class="btn btn-primary">
                            <i data-lucide="check"></i> Mark as Complete
                        </button>
                    <?php else: ?>
                        <span class="btn btn-ghost" style="pointer-events: none;">
                            <i data-lucide="check-circle" class="text-success"></i> Course Completed
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar - Lesson List -->
            <div>
                <div class="card" style="position: sticky; top: 100px;">
                    <h3 class="text-sm text-dim mb-1 flex flex-center gap-1">
                        <i data-lucide="list"></i> <span style="transform: translateY(1px);">Lesson List</span>
                    </h3>
                    <ul class="lesson-list" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($allLessons as $index => $l): ?>
                            <li class="lesson-item <?php echo $l['is_completed'] ? 'completed' : ''; ?> <?php echo $l['id'] == $lessonId ? 'active' : ''; ?>"
                                data-lesson-id="<?php echo $l['id']; ?>"
                                style="<?php echo $l['id'] == $lessonId ? 'background-color: var(--bg-secondary); border-left: 3px solid var(--orange-primary);' : ''; ?>">
                                <span class="lesson-icon">
                                    <?php if ($l['is_completed']): ?>
                                        <i data-lucide="check-circle" class="text-success"></i>
                                    <?php elseif ($l['id'] == $lessonId): ?>
                                        <i data-lucide="play-circle" class="text-orange"></i>
                                    <?php else: ?>
                                        <i data-lucide="circle" class="text-dim"></i>
                                    <?php endif; ?>
                                </span>
                                <a href="<?php echo BASE_PATH; ?>/student/lesson.php?id=<?php echo $l['id']; ?>" class="lesson-title"
                                    style="color: inherit;">
                                    <?php echo ($index + 1) . '. ' . e($l['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    async function completeAndNext(lessonId, nextLessonId) {
        const btn = document.getElementById('nextLessonBtn');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader"></i> Loading...';
        lucide.createIcons(); // Refresh for loader

        try {
            await ajax.post('<?php echo BASE_PATH; ?>/api/progress.php', {
                action: 'complete',
                lesson_id: lessonId
            });
            window.location.href = '<?php echo BASE_PATH; ?>/student/lesson.php?id=' + nextLessonId;
        } catch (error) {
            console.error('Error:', error);
            window.location.href = '<?php echo BASE_PATH; ?>/student/lesson.php?id=' + nextLessonId;
        }
    }

    async function markComplete(lessonId) {
        const btn = document.getElementById('completeBtn');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader"></i> Saving...';
        lucide.createIcons(); // Refresh for loader

        try {
            await ajax.post('<?php echo BASE_PATH; ?>/api/progress.php', {
                action: 'complete',
                lesson_id: lessonId
            });
            location.reload();
        } catch (error) {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check"></i> Mark as Complete';
            lucide.createIcons();
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>