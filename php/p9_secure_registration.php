<?php
// =======================================================
// Practical 9: Secure User Registration with Database Insert,
// Duplicate Email Check, and Password Hashing
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 9 - Secure Registration & Password Hashing";
$errors = [];
$successMessage = "";

// Fetch available courses for dropdown
$courses = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Advanced Extension: CSRF Verification
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "CSRF Token validation failed. Please refresh and try again.";
    }

    // 2. Sanitization
    $name     = trim(strip_tags($_POST['name'] ?? ''));
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile   = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $courseId = (int)($_POST['course_id'] ?? 0);
    $year     = (int)($_POST['year'] ?? 0);
    $gender   = trim($_POST['gender'] ?? '');

    // 3. Robust Backend Validation
    if (empty($name) || !preg_match("/^[a-zA-Z\s]{3,50}$/", $name)) {
        $errors[] = "Name must contain only alphabets and spaces (3-50 characters).";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid university email address is required.";
    }

    if (empty($mobile) || !preg_match("/^[6-9]\d{9}$/", $mobile)) {
        $errors[] = "Mobile number must be a valid 10-digit number.";
    }

    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[@$!%*?&]/", $password)) {
        $errors[] = "Password must be at least 8 characters long, contain an uppercase letter, a number, and a special character (@$!%*?&).";
    }

    if ($password !== $confirm) {
        $errors[] = "Password and Confirmation Password do not match.";
    }

    if ($courseId <= 0) {
        $errors[] = "Please select a valid academic course.";
    }

    if ($year < 1 || $year > 4) {
        $errors[] = "Please select a valid year (1-4).";
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Please select a valid gender option.";
    }

    // 4. Duplicate Email Verification (Intermediate: Prepared Statement)
    if (empty($errors)) {
        $dupStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $dupStmt->execute([$email]);
        if ($dupStmt->fetch()) {
            $errors[] = "An account with email '$email' already exists. Please login or use another email.";
        }
    }

    // 5. Secure Insert with Password Hashing & Transaction
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Hash password securely with BCRYPT
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert into users table
            $userInsert = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status)
                VALUES (?, ?, ?, 'student', 'active')
            ");
            $userInsert->execute([$name, $email, $hashedPassword]);
            $newUserId = $pdo->lastInsertId();

            // Insert into students table
            $studentInsert = $pdo->prepare("
                INSERT INTO students (user_id, name, email, mobile, course_id, year, gender, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $studentInsert->execute([$newUserId, $name, $email, $mobile, $courseId, $year, $gender]);

            // Advanced Extension: Audit Log Entry
            log_audit($pdo, $newUserId, 'USER_REGISTERED', "Student account registered successfully: $email");

            $pdo->commit();

            $successMessage = "Account successfully registered! Your password was securely hashed with bcrypt. You can now login.";
            $name = $email = $mobile = $password = $confirm = $courseId = $year = $gender = "";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Registration database transaction failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
            <div>
                <span class="badge-practical">Practical 9</span>
                <h1 class="card-title">Secure User Registration with Duplicate Checks & Password Hashing</h1>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Client & server-side validation, duplicate email detection, bcrypt cryptographic hashing, and automated audit logging.
                </p>
            </div>
            <div>
                <a href="p10_login.php" class="btn btn-secondary btn-sm">Go to Login</a>
            </div>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <strong>✓ Registered!</strong> <?php echo htmlspecialchars($successMessage); ?>
                <div style="margin-top: 8px;">
                    <a href="p10_login.php" class="btn btn-primary btn-sm">Proceed to Login →</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Registration Errors:</strong>
                <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="p9_secure_registration.php" method="POST" id="regForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

            <div class="form-grid">
                <div class="form-field">
                    <label for="name">Student Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="Bhargavi Patel"
                           value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="email">Campus Email Address * (Duplicate Check)</label>
                    <input type="email" id="email" name="email" required placeholder="bhargavi@studenthub.com"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" required placeholder="9825000001"
                           value="<?php echo htmlspecialchars($mobile ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="course_id">Enrolled Course *</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo ($courseId ?? 0) == $c['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name']); ?> (<?php echo htmlspecialchars($c['course_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="year">Year of Study *</label>
                    <select id="year" name="year" required>
                        <option value="">-- Choose Year --</option>
                        <option value="1" <?php echo ($year ?? 0) == 1 ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo ($year ?? 0) == 2 ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo ($year ?? 0) == 3 ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo ($year ?? 0) == 4 ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="gender">Gender *</label>
                    <select id="gender" name="gender" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Female" <?php echo ($gender ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Male" <?php echo ($gender ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Other" <?php echo ($gender ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="password">Create Password * (Bcrypt Hashed)</label>
                    <input type="password" id="password" name="password" required placeholder="Min 8 chars, 1 Upper, 1 Special">
                    <div id="meterContainer" style="height: 6px; background: #e2e8f0; border-radius: 4px; margin-top: 6px; overflow: hidden;">
                        <div id="meterBar" style="height: 100%; width: 0%; transition: all 0.3s ease;"></div>
                    </div>
                    <small id="meterText" class="text-muted" style="font-size: 11px;">Password strength: Not entered</small>
                </div>

                <div class="form-field">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat exact password">
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">Register Account</button>
                <span class="text-muted" style="font-size: 13px;">🔒 Stored via <code>password_hash(..., PASSWORD_BCRYPT)</code></span>
            </div>
        </form>
    </div>
</div>

<script>
// Interactive Client-Side Password Strength Meter
const passInput = document.getElementById('password');
const bar = document.getElementById('meterBar');
const text = document.getElementById('meterText');

passInput.addEventListener('input', function() {
    const val = this.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[@$!%*?&]/.test(val)) score++;

    if (val.length === 0) {
        bar.style.width = '0%';
        text.textContent = 'Password strength: Not entered';
    } else if (score <= 1) {
        bar.style.width = '25%';
        bar.style.backgroundColor = '#ef4444';
        text.textContent = 'Password strength: Weak ❌ (Requires uppercase, number, symbol)';
    } else if (score === 2 || score === 3) {
        bar.style.width = '65%';
        bar.style.backgroundColor = '#f59e0b';
        text.textContent = 'Password strength: Medium ⚠️';
    } else {
        bar.style.width = '100%';
        bar.style.backgroundColor = '#16a34a';
        text.textContent = 'Password strength: Strong ✅';
    }
});
</script>

<footer class="hub-footer">
    StudentHub &copy; 2026 - Practical 9 Implemented with Bcrypt Hashing, Duplicate Checks & Audit Logging.
</footer>
</body>
</html>
