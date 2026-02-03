<?php
/**
 * Progress API - AJAX endpoint
 */
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

// Check if AJAX request
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = getCurrentUser();
$pdo = getDBConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'complete':
        $lessonId = (int) ($input['lesson_id'] ?? 0);

        if ($lessonId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid lesson ID']);
            exit;
        }

        // Verify lesson exists and user has access
        $stmt = $pdo->prepare("
            SELECT l.*, c.id as course_id 
            FROM lessons l 
            INNER JOIN courses c ON l.course_id = c.id
            WHERE l.id = ?
        ");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();

        if (!$lesson) {
            echo json_encode(['success' => false, 'error' => 'Lesson not found']);
            exit;
        }

        // Check enrollment for students
        if ($user['role'] === 'student') {
            $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$user['id'], $lesson['course_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Not enrolled']);
                exit;
            }
        }

        // Mark as complete (upsert)
        $stmt = $pdo->prepare("
            INSERT INTO lesson_progress (user_id, lesson_id, completed, completed_at) 
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        $stmt->execute([$user['id'], $lessonId]);

        // Calculate course progress
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
        $stmt->execute([$lesson['course_id']]);
        $totalLessons = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM lesson_progress lp
            INNER JOIN lessons l ON lp.lesson_id = l.id
            WHERE lp.user_id = ? AND l.course_id = ? AND lp.completed = 1
        ");
        $stmt->execute([$user['id'], $lesson['course_id']]);
        $completedLessons = $stmt->fetchColumn();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        echo json_encode([
            'success' => true,
            'progress' => $progress,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
