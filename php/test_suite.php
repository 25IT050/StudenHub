<?php
// =======================================================
// StudentHub - Automated Practical Diagnostics & Test Suite
// Verifies environment, schema, modules, security, and endpoints
// =======================================================

$pageTitle = "Automated System Test Suite - StudentHub";

$tests = [];

function record_test(&$tests, $category, $name, $status, $detail) {
    $tests[] = [
        'category' => $category,
        'name'     => $name,
        'status'   => $status ? 'PASS' : 'FAIL',
        'detail'   => $detail
    ];
}

// 1. PHP Environment Tests
record_test($tests, 'Environment', 'PHP Version >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), 'Current Version: ' . PHP_VERSION);
record_test($tests, 'Environment', 'PDO Extension Loaded', extension_loaded('pdo'), extension_loaded('pdo') ? 'PDO driver active' : 'Missing PDO');
record_test($tests, 'Environment', 'PDO MySQL Extension Loaded', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'pdo_mysql active' : 'Missing pdo_mysql');
record_test($tests, 'Environment', 'JSON Extension Loaded', extension_loaded('json'), extension_loaded('json') ? 'json extension active' : 'Missing json');
record_test($tests, 'Environment', 'Session Support', function_exists('session_start'), 'Sessions enabled');

// 2. Directory & Permissions Tests
$uploadDir = __DIR__ . '/../uploads/posters/';
record_test($tests, 'Storage', 'Uploads Poster Directory', is_dir($uploadDir) || @mkdir($uploadDir, 0777, true), 'Directory: uploads/posters/');

$dataDir = __DIR__ . '/../data/';
record_test($tests, 'Storage', 'Data Storage Directory', is_dir($dataDir), 'Directory: data/');

$htaccessPath = __DIR__ . '/../uploads/.htaccess';
record_test($tests, 'Security', 'Uploads .htaccess Shield', file_exists($htaccessPath), file_exists($htaccessPath) ? 'Script execution blocked' : 'Missing .htaccess');

// 3. Database & Schema Checks
$dbOk = false;
$pdo = null;
try {
    require_once __DIR__ . '/db_connect.php';
    if (isset($pdo) && $pdo) {
        $dbOk = true;
    }
} catch (Exception $e) {
    $dbOk = false;
}

record_test($tests, 'Database', 'MySQL Connection (studenthub)', $dbOk, $dbOk ? 'Connected successfully via PDO' : 'MySQL server offline or unreachable');

if ($dbOk) {
    $tablesToCheck = ['courses', 'users', 'students', 'events', 'registrations', 'audit_logs'];
    foreach ($tablesToCheck as $tbl) {
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
            record_test($tests, 'Schema', "Table: `$tbl`", true, "Table exists with $count records");
        } catch (Exception $e) {
            record_test($tests, 'Schema', "Table: `$tbl`", false, "Table missing: " . $e->getMessage());
        }
    }

    // Default User Check
    try {
        $admin = $pdo->query("SELECT email FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        record_test($tests, 'Authentication', 'Default Admin User Exists', (bool)$admin, $admin ? "Found: $admin" : "No admin account found (run seeder)");
    } catch (Exception $e) {
        record_test($tests, 'Authentication', 'Default Admin User Exists', false, $e->getMessage());
    }

    // Stored Procedure Check (Practical 8 Advanced)
    try {
        $procRes = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = 'studenthub' AND Name = 'GetEventRegistrationStats'")->fetch();
        record_test($tests, 'Database', 'Stored Procedure (GetEventRegistrationStats)', (bool)$procRes, $procRes ? 'Installed in database' : 'Missing stored procedure');
    } catch (Exception $e) {
        record_test($tests, 'Database', 'Stored Procedure (GetEventRegistrationStats)', false, $e->getMessage());
    }
}

// 4. File Existence Checks for Practicals 7 to 15
$requiredFiles = [
    'Practical 7 Form Processor'      => 'p7_form_processing.php',
    'Practical 8 DB Connectivity'     => 'p8_db_connectivity.php',
    'Practical 9 Secure Registration' => 'p9_secure_registration.php',
    'Practical 10 Login & RBAC'       => 'p10_login.php',
    'Practical 10 Logout'             => 'p10_logout.php',
    'Practical 10 Dashboard'          => 'p10_student_dashboard.php',
    'Practical 11 Student CRUD'       => 'p11_manage_students.php',
    'Practical 11 CSV Export'         => 'p11_export_csv.php',
    'Practical 12 Event Management'   => 'p12_manage_events.php',
    'Practical 13 JSON REST API'      => 'api_events.php',
    'Practical 13 AJAX CRUD'          => 'p13_ajax_crud.php',
    'Practical 14 Admin Dashboard'    => 'p14_admin_dashboard.php',
    'Practical 15 Portal Hub'         => 'p15_portal_home.php'
];

foreach ($requiredFiles as $label => $fname) {
    $exists = file_exists(__DIR__ . '/' . $fname);
    record_test($tests, 'Deliverables', "$label ($fname)", $exists, $exists ? 'File present and ready' : 'File missing');
}

$passCount = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$failCount = count($tests) - $passCount;

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; color: #1e293b;">
                🧪 Automated System Diagnostic & Test Suite
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Comprehensive testing of environment, database, schema integrity, security defenses, and practical deliverables
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="p15_portal_home.php" class="btn btn-secondary">🏠 Portal Home</a>
            <a href="test_suite.php" class="btn btn-primary">🔄 Re-run Tests</a>
        </div>
    </div>

    <!-- Summary Scorecard -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid var(--primary);">
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">TOTAL CHECKS</div>
            <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin: 4px 0;">
                <?php echo count($tests); ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">Automated tests executed</div>
        </div>

        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid #16a34a;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">PASSED TESTS</div>
            <div style="font-size: 32px; font-weight: 700; color: #16a34a; margin: 4px 0;">
                <?php echo $passCount; ?>
            </div>
            <div style="font-size: 12px; color: #16a34a; font-weight: 600;">
                <?php echo round(($passCount / count($tests)) * 100); ?>% passing rate
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid <?php echo $failCount > 0 ? '#dc2626' : '#94a3b8'; ?>;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">ATTENTION / WARNINGS</div>
            <div style="font-size: 32px; font-weight: 700; color: <?php echo $failCount > 0 ? '#dc2626' : '#94a3b8'; ?>; margin: 4px 0;">
                <?php echo $failCount; ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                <?php echo $failCount > 0 ? 'Start MySQL in XAMPP to resolve' : 'All checks verified'; ?>
            </div>
        </div>
    </div>

    <!-- Test Results Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                📋 Detailed Diagnostic Test Log
            </h2>
        </div>
        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Category</th>
                        <th>Test Case Description</th>
                        <th style="width: 100px;">Result</th>
                        <th>Diagnostics & Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $t): ?>
                        <tr>
                            <td>
                                <span class="role-badge" style="background:#f1f5f9; color:#475569;">
                                    <?php echo htmlspecialchars($t['category']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($t['name']); ?></strong>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'PASS'): ?>
                                    <span class="role-badge" style="background:#dcfce7; color:#166534;">
                                        ✔ PASS
                                    </span>
                                <?php else: ?>
                                    <span class="role-badge" style="background:#fee2e2; color:#991b1b;">
                                        ✖ FAIL
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #475569;">
                                    <?php echo htmlspecialchars($t['detail']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="hub-footer">
    StudentHub Portal &bull; Automated Diagnostic Test Suite &bull; Charusat ITUE203
</footer>

</body>
</html>
