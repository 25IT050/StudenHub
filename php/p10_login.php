<?php
// =======================================================
// Practical 10: Secure Login with Sessions, Role-Based Access,
// Timeout, and Remember-Me Token
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 10 - Secure Authentication & RBAC";
$errors = [];

// If already logged in, redirect based on role
if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: p14_admin_dashboard.php");
    } else {
        header("Location: p10_student_dashboard.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security Token validation failed. Please refresh the page.";
    }

    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password   = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $errors[] = "Please provide both email address and password.";
    }

    if (empty($errors)) {
        // Query user with prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check user existence and status
        if (!$user) {
            $errors[] = "Invalid credentials. No account found for that email.";
            log_audit($pdo, null, 'LOGIN_FAILED', "Unknown email attempt: $email");
        } elseif ($user['status'] !== 'active') {
            $errors[] = "Your account has been deactivated. Please contact an administrator.";
            log_audit($pdo, $user['id'], 'LOGIN_BLOCKED', "Inactive account tried to login: $email");
        } else {
            // 2. Verify Password using password_verify()
            if (password_verify($password, $user['password'])) {
                // Prevent Session Fixation
                session_regenerate_id(true);

                // Populate Session
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['last_activity'] = time();

                // Intermediate Extension: Update last_login timestamp
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Advanced Extension: Secure Remember-Me Token
                if ($rememberMe) {
                    $randomToken = bin2hex(random_bytes(32));
                    $hashedToken = hash('sha256', $randomToken);
                    
                    // Store hashed token in DB
                    $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$hashedToken, $user['id']]);
                    
                    // Set HttpOnly cookie for 30 days
                    setcookie('remember_token', $randomToken, [
                        'expires'  => time() + (86400 * 30),
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                log_audit($pdo, $user['id'], 'LOGIN_SUCCESS', "User successfully authenticated with role: " . $user['role']);

                // Role-Based Redirection
                if ($user['role'] === 'admin') {
                    set_flash('success', "Welcome back, Administrator " . $user['name'] . "!");
                    header("Location: p14_admin_dashboard.php");
                } else {
                    set_flash('success', "Welcome back, " . $user['name'] . "!");
                    header("Location: p10_student_dashboard.php");
                }
                exit;

            } else {
                $errors[] = "Invalid password. Please check your credentials.";
                log_audit($pdo, $user['id'], 'LOGIN_FAILED', "Incorrect password attempt for user: $email");
            }
        }
    }
}

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div class="card" style="max-width: 500px; margin: 40px auto;">
        <div class="card-header">
            <div>
                <span class="badge-practical">Practical 10</span>
                <h1 class="card-title">StudentHub Secure Authentication</h1>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Session management, password_verify(), RBAC redirection & Remember-Me tokens.
                </p>
            </div>
        </div>

        <?php echo display_flash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Authentication Error:</strong>
                <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Quick Demo Credentials Box -->
        <div style="background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 13px;">
            <strong style="color: var(--primary);">🔑 Quick Test Accounts (Pre-seeded):</strong>
            <div style="margin-top: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <strong>Admin Role:</strong><br>
                    <code>admin@studenthub.com</code><br>
                    Pass: <code>Admin@123</code>
                </div>
                <div>
                    <strong>Student Role:</strong><br>
                    <code>student@studenthub.com</code><br>
                    Pass: <code>Student@123</code>
                </div>
            </div>
        </div>

        <form action="p10_login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

            <div class="form-field">
                <label for="email">Campus Email</label>
                <input type="email" id="email" name="email" required placeholder="admin@studenthub.com or student@studenthub.com"
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>

            <div class="form-field" style="display: flex; align-items: center; justify-content: space-between;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer;">
                    <input type="checkbox" name="remember_me" style="width: auto; margin: 0;">
                    <span>Remember me on this browser (30 Days)</span>
                </label>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
                    Secure Sign In →
                </button>
            </div>

            <div style="text-align: center; margin-top: 16px; font-size: 13px;">
                Need a new student account? <a href="p9_secure_registration.php" style="color: var(--primary); font-weight: 600;">Register here</a>
            </div>
        </form>
    </div>
</div>

<footer class="hub-footer">
    StudentHub &copy; 2026 - Practical 10 Implemented with password_verify, Session Hijack Prevention & Hashed Remember-Me Tokens.
</footer>
</body>
</html>
