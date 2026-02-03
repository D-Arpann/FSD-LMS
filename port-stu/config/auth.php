<?php
session_start();

require_once __DIR__ . '/database.php';

// Set BASE_PATH based on your server setup:
// - For local development: '/port-stu'
// - For server root deployment: ''
// - For subdirectory deployment: '/subdirectory-name'
define('BASE_PATH', '');

function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, role, verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function hasRole($role)
{
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/public/login.php');
        exit;
    }
}

function requireRole($role)
{
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_PATH . '/public/index.php');
        exit;
    }
}

function requireVerifiedInstructor()
{
    requireRole('instructor');
    $user = getCurrentUser();
    if (!$user['verified']) {
        header('Location: ' . BASE_PATH . '/instructor/pending.php');
        exit;
    }
}

function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url, $message = '', $type = 'success')
{
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
