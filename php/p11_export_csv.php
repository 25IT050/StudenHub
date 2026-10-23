<?php
// =======================================================
// Practical 11: Student Management CRUD with Search and Filter
// Features: Create, Read, Update, Delete
//           Search (Name, Email, Mobile), Filters (Course, Year)
//           Confirmation on Delete, Flash feedback messages
//           Export to CSV
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 11 - Student Management CRUD";
$errors = [];
$successMessage = "";

// Ensure studenthub database is connected
if (!isset($pdo) || !$pdo) {
    die("Database connection unavailable. Please start MySQL service in XAMPP.");
}

// Fetch Courses for Dropdowns
$courses = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC")->fetchAll();
// Handle Form Submissions (Add, Edit, Delete, Restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('error', "Security verification failed: Invalid CSRF token.");
        header("Location: p11_manage_students.php");
        exit;
    }

    // 1. ADD STUDENT
    if ($action === 'add') {
        $name     = trim(strip_tags($_POST['name'] ?? ''));
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $mobile   = trim($_POST['mobile'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? 0);
        $year     = (int)($_POST['year'] ?? 0);
        $gender   = trim($_POST['gender'] ?? '');

        // Validation
        if (empty($name) || !preg_match("/^[a-zA-Z\s]{3,50}$/", $name)) {
            $errors[] = "Name must contain only alphabets and spaces (3-50 chars).";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid student email address is required.";
        }
        if (empty($mobile) || !preg_match("/^[6-9]\d{9}$/", $mobile)) {
            $errors[] = "Mobile number must be 10 digits starting with 6-9.";
        }
        if ($courseId <= 0) {
            $errors[] = "Please select a valid course.";
        }
        if ($year < 1 || $year > 4) {
            $errors[] = "Academic year must be between 1 and 4.";
        }
        if (!in_array($gender, ['Male', 'Female', 'Other'])) {
            $errors[] = "Please select a valid gender option.";
        }

        // Duplicate Check
        if (empty($errors)) {
            $dupStmt = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
            $dupStmt->execute([$email]);
            if ($dupStmt->fetch()) {
                $errors[] = "A student record with this email ($email) already exists.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO students (name, email, mobile, course_id, year, gender)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $mobile, $courseId, $year, $gender]);
                $newId = $pdo->lastInsertId();

                log_audit($pdo, $_SESSION['user_id'] ?? null, 'STUDENT_ADDED', "Added student ID #$newId ($name, $email)");
                set_flash('success', "Student '$name' was successfully enrolled (ID: #$newId).");
                header("Location: p11_manage_students.php");
                exit;
            } catch (Exception $e) {
                $errors[] = "Database insert error: " . $e->getMessage();
            }
        }
    }

    // 2. EDIT STUDENT
    elseif ($action === 'edit') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $name      = trim(strip_tags($_POST['name'] ?? ''));
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $mobile    = trim($_POST['mobile'] ?? '');
        $courseId  = (int)($_POST['course_id'] ?? 0);
        $year      = (int)($_POST['year'] ?? 0);
        $gender    = trim($_POST['gender'] ?? '');
        
        // Validation
        if ($studentId <= 0) {
            $errors[] = "Invalid student identifier.";
        }
        if (empty($name) || !preg_match("/^[a-zA-Z\s]{3,50}$/", $name)) {
            $errors[] = "Name must contain only alphabets and spaces (3-50 chars).";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid student email address is required.";
        }
        if (empty($mobile) || !preg_match("/^[6-9]\d{9}$/", $mobile)) {
            $errors[] = "Mobile number must be 10 digits starting with 6-9.";
        }
        if ($courseId <= 0) {
            $errors[] = "Please select a valid course.";
        }
        if ($year < 1 || $year > 4) {
            $errors[] = "Academic year must be between 1 and 4.";
        }

        // Duplicate Email check excluding current student
        if (empty($errors)) {
            $dupStmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
            $dupStmt->execute([$email, $studentId]);
            if ($dupStmt->fetch()) {
                $errors[] = "Another student with email ($email) already exists.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET name = ?, email = ?, mobile = ?, course_id = ?, year = ?, gender = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $mobile, $courseId, $year, $gender, $studentId]);

                log_audit($pdo, $_SESSION['user_id'] ?? null, 'STUDENT_UPDATED', "Updated student ID #$studentId ($name)");
                set_flash('success', "Student record #$studentId ($name) updated successfully.");
                header("Location: p11_manage_students.php");
                exit;
            } catch (Exception $e) {
                $errors[] = "Update failed: " . $e->getMessage();
            }
        }
    }

    // Permanent Delete
    elseif ($action === 'permanent_delete') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$studentId]);

            log_audit($pdo, $_SESSION['user_id'] ?? null, 'STUDENT_PERMANENT_DELETED', "Permanently purged student #$studentId");
            set_flash('warning', "Student #$studentId has been permanently removed from database.");
        }
        header("Location: p11_manage_students.php");
        exit;
    }
}

// Search and Filter Parameters
$search   = trim($_GET['search'] ?? '');
$courseId = (int)($_GET['course_id'] ?? 0);
$year     = (int)($_GET['year'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 8;
$offset   = ($page - 1) * $perPage;

// Build Dynamic SQL Query
$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (s.name LIKE ? OR s.email LIKE ? OR s.mobile LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($courseId > 0) {
    $whereClause .= " AND s.course_id = ?";
    $params[] = $courseId;
}

if ($year > 0) {
    $whereClause .= " AND s.year = ?";
    $params[] = $year;
}

// Count Total Records for Pagination
$countQuery = "SELECT COUNT(*) FROM students s $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Fetch Records with Courses JOIN



$dataQuery = "
    SELECT s.id, s.name, s.email, s.mobile, s.course_id,
           c.course_name, s.year, s.gender
    FROM students s
    INNER JOIN courses c ON s.course_id = c.course_id
    $whereClause
    ORDER BY s.id DESC
    LIMIT $perPage OFFSET $offset
";

$dataStmt = $pdo->prepare($dataQuery);
$dataStmt->execute($params);
$students = $dataStmt->fetchAll();

// If Edit student requested via GET
$editStudent = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $editStmt = $pdo->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $editStmt->execute([$editId]);
    $editStudent = $editStmt->fetch();
}

$csrfToken = generate_csrf_token();
require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; color: #1e293b;">
                👨‍🎓 Student Management CRUD
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Practical 11: PDO Statements, Dynamic Search, Course/Year Filtering, and CSV Export
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="p11_export_csv.php?search=<?php echo urlencode($search); ?>&course_id=<?php echo $courseId; ?>&year=<?php echo $year; ?>" 
               class="btn btn-success" title="Export current filtered view to CSV">
                📥 Export CSV (Intermediate)
            </a>
            <button onclick="document.getElementById('addStudentCard').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary">
                ➕ Add New Student
            </button>
        </div>
    </div>

    <?php echo display_flash(); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please resolve the following errors:</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <div class="card" style="padding: 18px 24px;">
        <form method="GET" action="p11_manage_students.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 12px; align-items: end;">
            <div class="form-field" style="margin: 0;">
                <label>🔍 Search Keyword</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Name, Email, or Mobile...">
            </div>

            <div class="form-field" style="margin: 0;">
                <label>📚 Course</label>
                <select name="course_id">
                    <option value="0">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo $courseId === (int)$c['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field" style="margin: 0;">
                <label>📅 Year</label>
                <select name="year">
                    <option value="0">All Years</option>
                    <option value="1" <?php echo $year === 1 ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2" <?php echo $year === 2 ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3" <?php echo $year === 3 ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4" <?php echo $year === 4 ? 'selected' : ''; ?>>4th Year</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
            </div>

            <div>
                <a href="p11_manage_students.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Student List Table Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                📋 Enrolled Students 
                <span class="badge-practical"><?php echo $totalRecords; ?> Total Records Found</span>
            </h2>
            <div style="font-size: 13px; color: #64748b;">
                Showing Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th style="text-align: right; width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 36px; color: #94a3b8;">
                                No students match your search/filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><strong>#<?php echo $s['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($s['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><code><?php echo htmlspecialchars($s['mobile']); ?></code></td>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary);">
                                        <?php echo htmlspecialchars($s['course_name']); ?>
                                    </span>
                                </td>
                                <td>Year <?php echo $s['year']; ?></td>
                                <td><?php echo htmlspecialchars($s['gender']); ?></td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <!-- Edit Button -->
                                        <a href="p11_manage_students.php?edit_id=<?php echo $s['id']; ?>#addStudentCard" 
                                           class="btn btn-secondary btn-sm" title="Edit Student">
                                            ✏️ Edit
                                        </a>

                                        <form method="POST" action="p11_manage_students.php" style="margin: 0;"
                                              onsubmit="return confirm('Permanently delete student #<?php echo $s['id']; ?>? This cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="permanent_delete">
                                            <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Student">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 6px; margin-top: 20px;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="p11_manage_students.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&course_id=<?php echo $courseId; ?>&year=<?php echo $year; ?>"
                       class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add / Edit Student Form Card -->
    <div class="card" id="addStudentCard">
        <div class="card-header">
            <h2 class="card-title">
                <?php echo $editStudent ? "✏️ Edit Student Record (#{$editStudent['id']})" : "➕ Enroll New Student (INSERT Operation)"; ?>
            </h2>
            <?php if ($editStudent): ?>
                <a href="p11_manage_students.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="p11_manage_students.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editStudent ? 'edit' : 'add'; ?>">
            <?php if ($editStudent): ?>
                <input type="hidden" name="student_id" value="<?php echo $editStudent['id']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-field">
                    <label>Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Bhargavi Patel"
                           value="<?php echo htmlspecialchars($editStudent['name'] ?? $_POST['name'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>University Email *</label>
                    <input type="email" name="email" required placeholder="student@studenthub.com"
                           value="<?php echo htmlspecialchars($editStudent['email'] ?? $_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>10-Digit Mobile *</label>
                    <input type="text" name="mobile" required placeholder="9825000001" maxlength="10"
                           value="<?php echo htmlspecialchars($editStudent['mobile'] ?? $_POST['mobile'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>Enrolled Course *</label>
                    <select name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <?php 
                                $selected = false;
                                if ($editStudent && (int)$editStudent['course_id'] === (int)$c['course_id']) $selected = true;
                                elseif (!$editStudent && isset($_POST['course_id']) && (int)$_POST['course_id'] === (int)$c['course_id']) $selected = true;
                            ?>
                            <option value="<?php echo $c['course_id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Academic Year *</label>
                    <select name="year" required>
                        <option value="">-- Choose Year --</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?>
                            <?php 
                                $selected = false;
                                if ($editStudent && (int)$editStudent['year'] === $y) $selected = true;
                                elseif (!$editStudent && isset($_POST['year']) && (int)$_POST['year'] === $y) $selected = true;
                            ?>
                            <option value="<?php echo $y; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                Year <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <?php 
                            $currGender = $editStudent['gender'] ?? $_POST['gender'] ?? 'Female'; 
                        ?>
                        <option value="Female" <?php echo $currGender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Male" <?php echo $currGender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Other" <?php echo $currGender === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

            </div>

            <div style="margin-top: 16px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    💾 <?php echo $editStudent ? 'Update Student Record' : 'Save & Enroll Student'; ?>
                </button>
                <?php if ($editStudent): ?>
                    <a href="p11_manage_students.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<footer class="hub-footer">
    StudentHub Portal &bull; Practical 11: Student Management CRUD with Search, Filter & CSV Export &bull; Charusat ITUE203
</footer>

</body>
</html>