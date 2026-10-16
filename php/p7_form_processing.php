<?php
// =======================================================
// Practical 7: PHP Form Processing with Server-Side
// Validation and CSV/JSON File Storage
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 7 - Form Processing & File Storage";
$errors = [];
$successMessage = "";

$csvFile = __DIR__ . '/../data/registrations.csv';
$jsonFile = __DIR__ . '/../data/registrations.json';

// Initialize files/directories if needed
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}
if (!file_exists($csvFile)) {
    // Write CSV header
    file_put_contents($csvFile, "Timestamp,Full Name,Email,Mobile,Course,Year,Gender,Inquiry\n");
}
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([], JSON_PRETTY_PRINT));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Advanced Extension: CSRF Validation
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedToken)) {
        $errors[] = "Security check failed: Invalid or expired CSRF token.";
    }

    // 2. Server-Side Sanitization
    $fullName = trim(strip_tags($_POST['fullName'] ?? ''));
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile   = trim($_POST['mobile'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $inquiry  = trim(strip_tags($_POST['inquiry'] ?? ''));

    // 3. Server-Side Validation
    if (empty($fullName) || !preg_match("/^[a-zA-Z\s]{3,50}$/", $fullName)) {
        $errors[] = "Full Name must be alphabetic characters only (3-50 chars).";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    if (empty($mobile) || !preg_match("/^[6-9]\d{9}$/", $mobile)) {
        $errors[] = "Mobile number must be a valid 10-digit Indian phone number starting with 6-9.";
    }

    if (empty($course)) {
        $errors[] = "Please select an academic course.";
    }

    if (empty($year) || !in_array($year, ['1', '2', '3', '4'])) {
        $errors[] = "Please select a valid study year (1-4).";
    }

    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Please select your gender.";
    }

    // 4. Safe File Storage if validation passed
    if (empty($errors)) {
        $timestamp = date("Y-m-d H:i:s");
        $recordData = [
            'timestamp' => $timestamp,
            'fullName'  => $fullName,
            'email'     => $email,
            'mobile'    => $mobile,
            'course'    => $course,
            'year'      => $year,
            'gender'    => $gender,
            'inquiry'   => $inquiry
        ];

        // A. Safe CSV Writing with file locking (LOCK_EX)
        $csvLine = [
            $timestamp,
            $fullName,
            $email,
            $mobile,
            $course,
            $year,
            $gender,
            str_replace(["\r", "\n", ","], " ", $inquiry)
        ];
        
        $fp = fopen($csvFile, 'a');
        if ($fp && flock($fp, LOCK_EX)) {
            fputcsv($fp, $csvLine);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // B. Safe JSON Writing with file locking
        $existingJson = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        if (!is_array($existingJson)) {
            $existingJson = [];
        }
        $existingJson[] = $recordData;
        file_put_contents($jsonFile, json_encode($existingJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        $successMessage = "Record successfully validated and stored into CSV & JSON file formats!";
        
        // Reset inputs after successful submission
        $fullName = $email = $mobile = $course = $year = $gender = $inquiry = "";
    }
}

// Intermediate Extension: Read back stored records for display
$viewMode = $_GET['view'] ?? 'json';
$storedRecords = [];
if ($viewMode === 'csv' && file_exists($csvFile)) {
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 7) {
                $storedRecords[] = [
                    'timestamp' => $data[0] ?? '',
                    'fullName'  => $data[1] ?? '',
                    'email'     => $data[2] ?? '',
                    'mobile'    => $data[3] ?? '',
                    'course'    => $data[4] ?? '',
                    'year'      => $data[5] ?? '',
                    'gender'    => $data[6] ?? '',
                    'inquiry'   => $data[7] ?? ''
                ];
            }
        }
        fclose($handle);
    }
} else {
    // Default JSON view
    if (file_exists($jsonFile)) {
        $decoded = json_decode(file_get_contents($jsonFile), true);
        if (is_array($decoded)) {
            $storedRecords = array_reverse($decoded); // latest first
        }
    }
}

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div class="card">
        <div class="card-header">
            <div>
                <span class="badge-practical">Practical 7</span>
                <h1 class="card-title">PHP Form Processing with Server-Side Validation & File Storage</h1>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Accepts student inquiries, enforces server sanitization & regex rules, and writes concurrently to CSV and JSON storage with CSRF protection.
                </p>
            </div>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Validation Errors:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form Submission Section -->
        <form action="p7_form_processing.php" method="POST" novalidate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

            <div class="form-grid">
                <div class="form-field">
                    <label for="fullName">Full Name *</label>
                    <input type="text" id="fullName" name="fullName" required 
                           placeholder="e.g. Bhargavi Patel"
                           value="<?php echo htmlspecialchars($fullName ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="e.g. student@studenthub.com"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="mobile">Mobile Number * (10 Digits)</label>
                    <input type="tel" id="mobile" name="mobile" required 
                           placeholder="e.g. 9825000001"
                           value="<?php echo htmlspecialchars($mobile ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label for="course">Academic Course *</label>
                    <select id="course" name="course" required>
                        <option value="">-- Select Course --</option>
                        <option value="Information Technology" <?php echo ($course ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Computer Engineering" <?php echo ($course ?? '') === 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engineering</option>
                        <option value="Computer Science" <?php echo ($course ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Electronics & Comm" <?php echo ($course ?? '') === 'Electronics & Comm' ? 'selected' : ''; ?>>Electronics & Comm</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="year">Year of Study *</label>
                    <select id="year" name="year" required>
                        <option value="">-- Select Year --</option>
                        <option value="1" <?php echo ($year ?? '') === '1' ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo ($year ?? '') === '2' ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo ($year ?? '') === '3' ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo ($year ?? '') === '4' ? 'selected' : ''; ?>>4th Year</option>
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
            </div>

            <div class="form-field" style="margin-top: 10px;">
                <label for="inquiry">Student Query / Campus Feedback</label>
                <textarea id="inquiry" name="inquiry" rows="3" placeholder="Enter inquiry or comments..."><?php echo htmlspecialchars($inquiry ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Submit to Server (POST)</button>
                <span class="text-muted">🔒 Protected by Server-Side CSRF Token</span>
            </div>
        </form>
    </div>

    <!-- Intermediate Extension: Display Stored Records -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Intermediate Extension: Stored File Records Viewer</h2>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Live data read from disk storage: <code>data/registrations.json</code> and <code>data/registrations.csv</code>
                </p>
            </div>
            <div>
                <a href="?view=json" class="btn <?php echo $viewMode === 'json' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">View from JSON</a>
                <a href="?view=csv" class="btn <?php echo $viewMode === 'csv' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">View from CSV</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Timestamp</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Notes / Query</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($storedRecords)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 24px;">
                                No records stored yet. Fill and submit the form above to record entries!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($storedRecords as $idx => $r): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($r['timestamp'] ?? '-'); ?></small></td>
                                <td><strong><?php echo htmlspecialchars($r['fullName'] ?? '-'); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['mobile'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['course'] ?? '-'); ?></td>
                                <td>Year <?php echo htmlspecialchars($r['year'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['gender'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['inquiry'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="hub-footer">
    StudentHub &copy; 2026 - Practical 7 Implemented with CSRF Token and Concurrent File Storage.
</footer>
</body>
</html>
