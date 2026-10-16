<?php
// =======================================================
// Practical 10: Secure Logout Handler
// =======================================================

require_once __DIR__ . '/auth_helper.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    log_audit($pdo, $userId, 'LOGOUT', 'User signed out gracefully');

    // Clear remember token in database
    $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$userId]);
}

// Clear Remember-Me Cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Clear and Destroy Session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start fresh session for flash message
session_start();
$_SESSION['flash_info'] = "You have been safely signed out of StudentHub.";
header("Location: p10_login.php");
exit;
?>
