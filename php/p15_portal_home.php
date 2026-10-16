<?php
// =======================================================
// Practical 15: Final Integration, Testing, Documentation,
// and Local Deployment Portal
// Integrates all 15 Practicals into a single unified hub
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 15 - Portal Integration & Deployment";

// Live System Diagnostic Status
$dbConnected = false;
$tablesCount = 0;
$dbMessage = "Disconnected";

if (isset($pdo) && $pdo) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll();
        $tablesCount = count($tables);
        $dbConnected = true;
        $dbMessage = "Connected (MySQL PDO active & healthy - $tablesCount tables found)";
    } catch (Exception $e) {
        $dbMessage = "Error: " . $e->getMessage();
    }
}

// Complete Matrix of All 15 Practicals (ITUE203 Syllabus)
$practicalsMatrix = [
    [
        'id' => 1,
        'title' => 'Project Initiation & Wireframe',
        'tech' => 'Requirements, Wireframes, Sitemap, Git',
        'co' => 'CO1',
        'file' => '../README.md',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 2,
        'title' => 'Semantic HTML5 Pages',
        'tech' => 'HTML5 Semantic tags, Accessibility',
        'co' => 'CO1, CO2',
        'file' => '../pages/index.html',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 3,
        'title' => 'Responsive UI Design',
        'tech' => 'CSS Grid, Flexbox, Media Queries',
        'co' => 'CO2',
        'file' => '../pages/events.html',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 4,
        'title' => 'JavaScript DOM & UI Interactivity',
        'tech' => 'DOM Selection, Event Handling, localStorage',
        'co' => 'CO3, CO4',
        'file' => '../pages/faq.html',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 5,
        'title' => 'Registration Form Validation',
        'tech' => 'Regex Validation, Error UX, Forms',
        'co' => 'CO1, CO3',
        'file' => '../pages/register.html',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 6,
        'title' => 'External JSON Data & Fetch API',
        'tech' => 'JSON, Fetch API, Search/Filter',
        'co' => 'CO3, CO4',
        'file' => '../pages/events.html',
        'isBackend' => false,
        'status' => 'Completed'
    ],
    [
        'id' => 7,
        'title' => 'PHP Form Processing & CSV/JSON Storage',
        'tech' => 'POST Handling, Sanitization, CSV/JSON Files',
        'co' => 'CO1, CO5',
        'file' => 'p7_form_processing.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 8,
        'title' => 'MySQL Schema & PDO Prepared Statements',
        'tech' => 'Normalized Schema, PDO, Stored Procedure',
        'co' => 'CO1, CO5',
        'file' => 'p8_db_connectivity.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 9,
        'title' => 'Secure User Registration & Password Hashing',
        'tech' => 'password_hash(), Duplicate Check, CSRF',
        'co' => 'CO1, CO3, CO5',
        'file' => 'p9_secure_registration.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 10,
        'title' => 'Secure Login/Logout & Session RBAC',
        'tech' => 'Sessions, password_verify(), Timeout, Remember-Me',
        'co' => 'CO1, CO5',
        'file' => 'p10_login.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 11,
        'title' => 'Student Management CRUD with Search & Filter',
        'tech' => 'CRUD, Search, Filters, Soft Delete, CSV Export',
        'co' => 'CO1, CO4, CO5',
        'file' => 'p11_manage_students.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 12,
        'title' => 'Event Management CRUD & Poster Upload',
        'tech' => 'File Upload Validation, Safe Renaming, Audit Log',
        'co' => 'CO1, CO4, CO5',
        'file' => 'p12_manage_events.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 13,
        'title' => 'PHP JSON API & AJAX/Fetch-Based CRUD',
        'tech' => 'RESTful API, 300ms Debounce, Zero Reload',
        'co' => 'CO3, CO4, CO5',
        'file' => 'p13_ajax_crud.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 14,
        'title' => 'Admin Dashboard & Dynamic Menus',
        'tech' => 'RBAC, Metrics Aggregation, Canvas Chart, Logs',
        'co' => 'CO1, CO4, CO5',
        'file' => 'p14_admin_dashboard.php',
        'isBackend' => true,
        'status' => 'Completed'
    ],
    [
        'id' => 15,
        'title' => 'Final Integration, Testing & Deployment',
        'tech' => 'Full-Stack Integration, Automated Test Suite, Docs',
        'co' => 'CO2, CO4, CO5',
        'file' => 'p15_portal_home.php',
        'isBackend' => true,
        'status' => 'Completed'
    ]
];

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <!-- Hero Banner -->
    <div class="card" style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); color: #ffffff; border: none; padding: 36px 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <span class="role-badge" style="background: rgba(255,255,255,0.2); color: #ffffff; margin-bottom: 8px; display: inline-block;">
                    CHARUSAT &bull; CSPIT &bull; ITUE203
                </span>
                <h1 style="margin: 0 0 10px; font-size: 30px; font-weight: 800; color: #ffffff;">
                    🎓 StudentHub Enterprise Web Portal
                </h1>
                <p style="margin: 0; font-size: 15px; color: #c7d2fe; max-width: 650px; line-height: 1.6;">
                    Comprehensive semester-long project implementing all 15 laboratory practicals spanning Semantic HTML5, CSS Grid/Flexbox, JavaScript ES6+, PHP 8, MySQL PDO, RBAC, File Uploads, and Asynchronous AJAX Fetch APIs.
                </p>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="test_suite.php" class="btn btn-success" style="padding: 10px 20px; font-size: 15px; justify-content: center;">
                    🧪 Run System Test Suite
                </a>
                <a href="seed_database.php" class="btn btn-secondary" style="padding: 8px 16px; justify-content: center;"
                   onclick="return confirm('Run database seeder? This will verify and seed 20+ students, default users, courses, and events.');">
                    🌱 1-Click Database Seeder
                </a>
            </div>
        </div>
    </div>

    <?php echo display_flash(); ?>

    <!-- System Status & Quick Auth Card -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Server Diagnostics Card -->
        <div class="card" style="margin: 0;">
            <div class="card-header">
                <h3 class="card-title">
                    🖥️ Server & Database Health
                </h3>
                <span class="role-badge" style="background: <?php echo $dbConnected ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $dbConnected ? '#166534' : '#991b1b'; ?>;">
                    <?php echo $dbConnected ? 'ONLINE' : 'OFFLINE'; ?>
                </span>
            </div>
            <div style="font-size: 14px; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <strong>MySQL Database Status:</strong> 
                    <span style="color: <?php echo $dbConnected ? '#16a34a' : '#dc2626'; ?>;">
                        <?php echo htmlspecialchars($dbMessage); ?>
                    </span>
                </div>
                <div>
                    <strong>PHP Version:</strong> <code><?php echo PHP_VERSION; ?></code>
                </div>
                <div>
                    <strong>Web Architecture:</strong> Apache / XAMPP Virtual Host Ready
                </div>
                <div>
                    <strong>Protected Uploads Directory:</strong> 
                    <code>uploads/posters/</code> (Secured against direct script execution)
                </div>
            </div>
        </div>

        <!-- Default Credentials Quick Access -->
        <div class="card" style="margin: 0;">
            <div class="card-header">
                <h3 class="card-title">
                    🔑 Default Evaluation Credentials
                </h3>
                <span class="badge-practical">Pre-Seeded</span>
            </div>
            <div style="font-size: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div style="background: #f8fafc; border: 1px solid var(--card-border); padding: 12px; border-radius: 8px;">
                    <div style="font-weight: 700; color: #991b1b; margin-bottom: 6px;">👑 Administrator Account</div>
                    <div style="font-size: 13px;"><strong>Email:</strong> <code>admin@studenthub.com</code></div>
                    <div style="font-size: 13px;"><strong>Password:</strong> <code>Admin@123</code></div>
                    <div style="margin-top: 8px;">
                        <a href="p10_login.php" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">Login as Admin</a>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--card-border); padding: 12px; border-radius: 8px;">
                    <div style="font-weight: 700; color: #1e40af; margin-bottom: 6px;">👨‍🎓 Student Account</div>
                    <div style="font-size: 13px;"><strong>Email:</strong> <code>student@studenthub.com</code></div>
                    <div style="font-size: 13px;"><strong>Password:</strong> <code>Student@123</code></div>
                    <div style="margin-top: 8px;">
                        <a href="p10_login.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">Login as Student</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Practicals Matrix Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                📚 Full Laboratory Practical Deliverables Matrix (1 to 15)
            </h2>
            <span class="badge-practical">15 / 15 Practicals Available</span>
        </div>

        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Prac #</th>
                        <th>Practical Title & Focus Area</th>
                        <th>Core Technology & Concepts</th>
                        <th>CO Mapping</th>
                        <th>Module Type</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 140px;">Launch / View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($practicalsMatrix as $p): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 15px; color: var(--primary);">
                                    P<?php echo $p['id']; ?>
                                </strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #475569;">
                                    <?php echo htmlspecialchars($p['tech']); ?>
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 12px; color: #6366f1;"><?php echo htmlspecialchars($p['co']); ?></code>
                            </td>
                            <td>
                                <?php if ($p['isBackend']): ?>
                                    <span class="role-badge" style="background: #e0e7ff; color: var(--primary);">Backend / DB</span>
                                <?php else: ?>
                                    <span class="role-badge" style="background: #f1f5f9; color: #475569;">Frontend / Static</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="role-badge" style="background: #dcfce7; color: #166534;">
                                    ✅ <?php echo $p['status']; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo htmlspecialchars($p['file']); ?>" 
                                   class="btn btn-sm <?php echo $p['isBackend'] ? 'btn-primary' : 'btn-secondary'; ?>" 
                                   target="_blank">
                                    Open &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security & Architecture Compliance Checklist -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                🛡️ Security & Enterprise Architecture Checklist
            </h3>
            <span class="badge-practical">ITUE203 Standards Compliant</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; font-size: 13px;">
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>🔒 CSRF Protection:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">Cryptographic 32-byte tokens on all POST/PUT/DELETE forms with timing attack-resistant verification.</p>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>🔑 Bcrypt Password Hashing:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">Industry standard <code>password_hash()</code> and <code>password_verify()</code> preventing credential leakage.</p>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>⏱️ Inactivity Timeout:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">Enforced 15-minute inactivity auto-logout protection in <code>auth_helper.php</code>.</p>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>🛡️ SQL Injection Immunity:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">100% Parameterized Prepared Statements via PHP PDO; strict data type binding.</p>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>📁 File Upload Security:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">MIME validation, 2MB size cap, randomized safe renaming, and disabled script execution in upload dir.</p>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--card-border);">
                <strong>📝 Central Audit Trail:</strong>
                <p style="margin: 4px 0 0; color: #64748b;">Full activity traceability logging IP address, user ID, timestamp, and details for administrative oversight.</p>
            </div>
        </div>
    </div>
</div>

<footer class="hub-footer">
    StudentHub Portal &bull; Practical 15: Full-Stack Integration, Testing & Deployment &bull; Charusat ITUE203 &bull; 2026-27
</footer>

</body>
</html>
