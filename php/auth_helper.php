<?php
// =======================================================
// StudentHub - Security & Authentication Helper
// Covers Practicals 9, 10, 11, 12, 14, 15
// Features: Sessions, CSRF, Inactivity Timeout, RBAC,
//           Remember-Me Tokens, and Audit Logging
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

// Inactivity timeout duration (15 minutes = 900 seconds)
define('SESSION_TIMEOUT_SECONDS', 900);

// 1. Session Inactivity Timeout Checker
function check_session_timeout() {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
            // Session expired due to inactivity
            $expiredEmail = $_SESSION['user_email'] ?? 'unknown';
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['flash_error'] = "Session timed out due to 15 minutes of inactivity. Please login again.";
            header("Location: p10_login.php");
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
check_session_timeout();

// 2. Remember-Me Auto Login Check
function check_remember_me($pdo) {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $hashedToken = hash('sha256', $token);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$hashedToken]);
        $user = $stmt->fetch();
        
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            log_audit($pdo, $user['id'], 'AUTO_LOGIN_REMEMBER_ME', 'Auto logged in via remember-me token');
        }
    }
}
check_remember_me($pdo);

// 3. CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 4. Role-Based Access Control Guards
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Access denied. Please login to access this page.";
        header("Location: p10_login.php");
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['flash_error'] = "Unauthorized: Admin privileges required.";
        header("Location: p10_student_dashboard.php");
        exit;
    }
}

function require_student() {
    require_login();
    if ($_SESSION['user_role'] !== 'student' && $_SESSION['user_role'] !== 'admin') {
        $_SESSION['flash_error'] = "Unauthorized access.";
        header("Location: p10_login.php");
        exit;
    }
}

// 5. Centralized Audit Logger
function log_audit($pdo, $userId, $action, $details = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Suppress audit log failures from breaking main request flow
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// 6. Flash Message Helpers
function set_flash($type, $msg) {
    $_SESSION['flash_' . $type] = $msg;
}

function display_flash() {
    $html = '';
    if (!empty($_SESSION['flash_success'])) {
        $html .= '<div class="alert alert-success">' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
        unset($_SESSION['flash_success']);
    }
    if (!empty($_SESSION['flash_error'])) {
        $html .= '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
        unset($_SESSION['flash_error']);
    }
    if (!empty($_SESSION['flash_info'])) {
        $html .= '<div class="alert alert-info">' . htmlspecialchars($_SESSION['flash_info']) . '</div>';
        unset($_SESSION['flash_info']);
    }
    return $html;
}
?>
