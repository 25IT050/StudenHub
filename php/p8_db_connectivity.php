<?php
// =======================================================
// Practical 8: MySQL Schema Design, ER Model, 
// PDO Connectivity, and Prepared Statements
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 8 - Database Design & PDO Prepared Statements";

// Test Live Connection
$connectionStatus = false;
$serverInfo = "";
$dbError = null;

try {
    $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    $connectionStatus = true;
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// Fetch schema table statistics
$tablesSummary = [];
if ($connectionStatus) {
    $tables = ['courses', 'users', 'students', 'events', 'registrations', 'audit_logs'];
    foreach ($tables as $t) {
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $tablesSummary[$t] = $count;
        } catch (Exception $e) {
            $tablesSummary[$t] = "Not Created";
        }
    }
}

// Advanced Extension: Execute Stored Procedure
$procedureResults = [];
$procedureError = null;
if ($connectionStatus) {
    try {
        $procStmt = $pdo->query("CALL GetEventRegistrationStats()");
        $procedureResults = $procStmt->fetchAll();
        $procStmt->closeCursor(); // Allow subsequent queries
    } catch (Exception $e) {
        $procedureError = $e->getMessage();
    }
}

// Intermediate Extension: Prepared Statement Demo
$courseFilter = $_GET['course_code'] ?? 'IT';
$filteredStudents = [];

if ($connectionStatus) {
    $prepStmt = $pdo->prepare("
        SELECT s.id, s.name, s.email, s.mobile,
               c.course_name, s.year, s.gender
        FROM students s
        INNER JOIN courses c ON s.course_id = c.course_id
        ORDER BY s.id ASC
    ");

    $prepStmt->execute();
    $filteredStudents = $prepStmt->fetchAll();
}

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <!-- Header Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <span class="badge-practical">Practical 8</span>
                <h1 class="card-title">MySQL Schema Design, ER Model & PDO Prepared Statements</h1>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    3NF Normalized relational schema, secure PDO driver connection, parameterized query defense, and stored procedure integration.
                </p>
            </div>
            <div>
                <a href="seed_database.php" target="_blank" class="btn btn-primary btn-sm">⚡ Re-run Seed Script</a>
            </div>
        </div>

        <!-- Connection Status Banner -->
        <?php if ($connectionStatus): ?>
            <div class="alert alert-success">
                <strong>✓ Database Connected Successfully!</strong> Connected to MySQL Host via PDO driver. 
                <br><small class="text-muted">Driver: <code><?php echo htmlspecialchars($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)); ?></code> | Server Info: <?php echo htmlspecialchars($serverInfo); ?></small>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>✕ Connection Failure:</strong> <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>

        <!-- Database Table Architecture Overview -->
        <h3 style="margin-top: 10px; font-size: 17px; color: #1e293b;">Normalized Relational Schema Entities (3NF)</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-top: 12px;">
            <?php foreach ($tablesSummary as $tbl => $cnt): ?>
                <div style="background: #f8fafc; border: 1px solid var(--card-border); border-radius: 8px; padding: 14px; text-align: center;">
                    <div style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase;">
                        <?php echo htmlspecialchars($tbl); ?>
                    </div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--primary); margin: 6px 0;">
                        <?php echo htmlspecialchars($cnt); ?>
                    </div>
                    <div style="font-size: 11px; color: #94a3b8;">Active Records</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Intermediate Extension: Prepared Statement Interactive Demo -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Intermediate Extension: Parameterized Prepared Statement</h2>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Demonstrating SQL Injection immunity using PDO prepared statements with positional parameters (<code>?</code>).
                </p>
            </div>
            <div>
                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <label for="course_code" style="font-size: 13px; font-weight: 600;">Course:</label>
                    <select name="course_code" id="course_code" onchange="this.form.submit()" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
                        <option value="IT" <?php echo $courseFilter === 'IT' ? 'selected' : ''; ?>>IT</option>
                        <option value="CE" <?php echo $courseFilter === 'CE' ? 'selected' : ''; ?>>CE</option>
                        <option value="CSE" <?php echo $courseFilter === 'CSE' ? 'selected' : ''; ?>>CSE</option>
                        <option value="EC" <?php echo $courseFilter === 'EC' ? 'selected' : ''; ?>>EC</option>
                    </select>
                </form>
            </div>
        </div>

        <div style="background: #0f172a; color: #e2e8f0; font-family: monospace; font-size: 12px; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
            <span style="color: #94a3b8;">// Prepared Statement Query:</span><br>
            <span style="color: #38bdf8;">$stmt = $pdo->prepare("SELECT ... FROM students s INNER JOIN courses c ON s.course_id = c.course_id WHERE c.course_code = ? AND s.status = 'active'");</span><br>
            <span style="color: #4ade80;">$stmt->execute(['<?php echo htmlspecialchars($courseFilter); ?>']);</span>
        </div>

        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Enrolled Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredStudents)): ?>
                        <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 16px;">No students found for course code <?php echo htmlspecialchars($courseFilter); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($filteredStudents as $s): ?>
                            <tr>
                                <td>#<?php echo $s['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['mobile']); ?></td>
                                <td><span class="role-badge role-student"><?php echo htmlspecialchars($s['course_name']); ?></span></td>
                                <td>Year <?php echo htmlspecialchars($s['year']); ?></td>
                                <td><?php echo htmlspecialchars($s['gender']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Advanced Extension: Stored Procedure Execution -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Advanced Extension: MySQL Stored Procedure Execution</h2>
                <p class="text-muted" style="margin: 4px 0 0 0;">
                    Executing <code>CALL GetEventRegistrationStats()</code> via PDO to fetch aggregated registration counts per campus event.
                </p>
            </div>
        </div>

        <?php if ($procedureError): ?>
            <div class="alert alert-warning">
                Could not execute stored procedure: <?php echo htmlspecialchars($procedureError); ?>. 
                <a href="seed_database.php" target="_blank">Click here to re-seed and install it.</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="hub-table">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Event Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Event Status</th>
                            <th>Total Registered Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($procedureResults)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8;">No event statistics returned by procedure.</td></tr>
                        <?php else: ?>
                            <?php foreach ($procedureResults as $row): ?>
                                <tr>
                                    <td>#<?php echo $row['event_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                    <td><span class="role-badge role-student"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $row['event_status'] === 'open' ? 'role-student' : 'role-admin'; ?>">
                                            <?php echo strtoupper(htmlspecialchars($row['event_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--primary); font-size: 15px;"><?php echo (int)$row['total_registrations']; ?></strong> Students
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="hub-footer">
    StudentHub &copy; 2026 - Practical 8 Implemented with PDO Prepared Statements and Stored Procedures.
</footer>
</body>
</html>
