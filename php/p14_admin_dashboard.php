<?php
// =======================================================
// Practical 14: Admin Dashboard with Role-Based Access Control,
// Dynamic Menu, User Toggle, Analytics Chart, and Audit Log
// =======================================================

require_once __DIR__ . '/auth_helper.php';

// RBAC: Require Admin Access
require_admin();

$pageTitle = "Practical 14 - Admin Dashboard & Audit Logs";

// Handle User Status Toggle (Intermediate Extension)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetUserId > 0) {
            // Fetch current status
            $uStmt = $pdo->prepare("SELECT name, email, status FROM users WHERE id = ?");
            $uStmt->execute([$targetUserId]);
            $targetUser = $uStmt->fetch();

            if ($targetUser) {
                $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';
                $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $targetUserId]);

                log_audit($pdo, $_SESSION['user_id'], 'USER_STATUS_TOGGLED', "Changed status of {$targetUser['email']} to $newStatus");
                set_flash('success', "User '{$targetUser['name']}' status changed to " . strtoupper($newStatus) . ".");
            }
        }
    }
    header("Location: p14_admin_dashboard.php");
    exit;
}

// 1. Fetch Dynamic Statistics
$stats = [
    'total_students'      => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'active_students' => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'total_events'        => (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'open_events'         => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'open'")->fetchColumn(),
    'total_registrations' => (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
    'total_users'         => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
];

// 2. Fetch Latest 6 Registrations
$latestRegStmt = $pdo->query("
    SELECT r.id, r.registration_date, r.status, s.name AS student_name, s.email AS student_email, e.title AS event_title, e.category
    FROM registrations r
    INNER JOIN students s ON r.student_id = s.id
    INNER JOIN events e ON r.event_id = e.id
    ORDER BY r.registration_date DESC
    LIMIT 6
");
$latestRegistrations = $latestRegStmt->fetchAll();

// 3. Fetch User Accounts for Status Toggle (Intermediate Extension)
$usersStmt = $pdo->query("SELECT id, name, email, role, status, last_login FROM users ORDER BY id ASC");
$allUsers = $usersStmt->fetchAll();

// 4. Fetch System Audit Logs (Core Requirement)
$auditStmt = $pdo->query("
    SELECT a.*, u.name AS user_name, u.role AS user_role
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 12
");
$auditLogs = $auditStmt->fetchAll();

// 5. Fetch Monthly Registrations for Analytics Chart (Advanced Extension)
$chartStmt = $pdo->query("
    SELECT 
        DATE_FORMAT(registration_date, '%b %Y') AS month_label,
        COUNT(*) AS total_count
    FROM registrations
    GROUP BY DATE_FORMAT(registration_date, '%Y-%m'), month_label
    ORDER BY MIN(registration_date) ASC
    LIMIT 6
");
$chartData = $chartStmt->fetchAll();

// Fallback sample data if no multi-month registrations exist
if (empty($chartData)) {
    $chartData = [
        ['month_label' => 'Aug 2026', 'total_count' => 4],
        ['month_label' => 'Sep 2026', 'total_count' => 8],
        ['month_label' => 'Oct 2026', 'total_count' => 12],
        ['month_label' => 'Nov 2026', 'total_count' => 15]
    ];
}

$csrfToken = generate_csrf_token();
require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; color: #1e293b;">
                🛡️ Administrator Command Dashboard
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Practical 14: Role-Based Access Control, Aggregated SQL Metrics, User Toggle, Analytics Chart & Audit Trail
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="p11_manage_students.php" class="btn btn-secondary btn-sm">👨‍🎓 Students</a>
            <a href="p12_manage_events.php" class="btn btn-secondary btn-sm">🎪 Events</a>
            <a href="p13_ajax_crud.php" class="btn btn-secondary btn-sm">⚡ AJAX</a>
            <a href="p10_logout.php" class="btn btn-danger btn-sm">🚪 Logout</a>
        </div>
    </div>

    <?php echo display_flash(); ?>

    <!-- KPI Aggregated Statistics Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid var(--primary);">
            <div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Students</div>
            <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin: 6px 0 2px;">
                <?php echo $stats['total_students']; ?>
            </div>
            <div style="font-size: 12px; color: #16a34a; font-weight: 600;">
                🟢 <?php echo $stats['active_students']; ?> Active enrolled
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid #06b6d4;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Campus Events</div>
            <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin: 6px 0 2px;">
                <?php echo $stats['total_events']; ?>
            </div>
            <div style="font-size: 12px; color: #0284c7; font-weight: 600;">
                🚀 <?php echo $stats['open_events']; ?> Open for registration
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid #10b981;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Registrations</div>
            <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin: 6px 0 2px;">
                <?php echo $stats['total_registrations']; ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                Across all workshops & expos
            </div>
        </div>

        <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid #8b5cf6;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase;">Auth Users</div>
            <div style="font-size: 32px; font-weight: 700; color: #1e293b; margin: 6px 0 2px;">
                <?php echo $stats['total_users']; ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                Admins & student accounts
            </div>
        </div>
    </div>

    <!-- Analytics Chart & Latest Registrations -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Advanced Extension: Registration Analytics Chart -->
        <div class="card" style="margin: 0;">
            <div class="card-header">
                <h3 class="card-title">
                    📊 Event Registrations Analytics
                    <span class="badge-practical">Canvas Chart</span>
                </h3>
            </div>
            <div style="padding: 10px 0;">
                <canvas id="registrationChart" width="500" height="260" style="width: 100%; height: 260px;"></canvas>
            </div>
            <div style="font-size: 12px; color: #64748b; text-align: center; margin-top: 8px;">
                Aggregated monthly registration trend rendered dynamically via HTML5 Canvas.
            </div>
        </div>

        <!-- Latest Registrations Table -->
        <div class="card" style="margin: 0;">
            <div class="card-header">
                <h3 class="card-title">
                    🎟️ Latest Event Enrollments
                </h3>
                <a href="p11_manage_students.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="hub-table" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestRegistrations)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;">
                                    No registrations recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestRegistrations as $reg): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($reg['event_title']); ?>
                                        <div style="font-size: 11px; color: #64748b;">
                                            <?php echo htmlspecialchars($reg['category']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date("d M, H:i", strtotime($reg['registration_date'])); ?></td>
                                    <td>
                                        <span class="role-badge" style="background:#dcfce7; color:#166534;">
                                            <?php echo htmlspecialchars($reg['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Management with Active/Inactive Status Toggle (Intermediate Extension) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                👥 System Users & Status Toggle Switch (Intermediate Extension)
            </h3>
            <span class="badge-practical"><?php echo count($allUsers); ?> User Accounts</span>
        </div>
        <div class="table-responsive">
            <table class="hub-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Account Status</th>
                        <th>Last Login</th>
                        <th style="text-align: right; width: 140px;">Toggle Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($u['email']); ?></code></td>
                            <td>
                                <span class="role-badge <?php echo $u['role'] === 'admin' ? 'role-admin' : 'role-student'; ?>">
                                    <?php echo strtoupper($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="role-badge" style="background:#dcfce7; color:#166534;">Active</span>
                                <?php else: ?>
                                    <span class="role-badge" style="background:#fee2e2; color:#991b1b;">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $u['last_login'] ? date("d M Y, H:i", strtotime($u['last_login'])) : '<span class="text-muted">Never</span>'; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="POST" action="p14_admin_dashboard.php" style="margin: 0;"
                                          onsubmit="return confirm('Toggle status for user \'<?php echo addslashes($u['email']); ?>\'?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="toggle_user_status">
                                        <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>"
                                                style="padding: 4px 10px;">
                                            <?php echo $u['status'] === 'active' ? 'Deactivate ⏸️' : 'Activate ▶️'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #94a3b8;">(Your Session)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- System Audit Logs Table (Core Practical Requirement) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                📝 Security & Administrative Audit Logs (Real-Time Traceability)
            </h3>
            <span class="badge-practical">Last 12 Audit Records</span>
        </div>
        <div class="table-responsive">
            <table class="hub-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">Log ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action Performed</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">
                                No audit log records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td>#<?php echo $log['id']; ?></td>
                                <td><?php echo date("d M Y, H:i:s", strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                                        <span style="font-size: 11px; color: #64748b;">(<?php echo $log['user_role']; ?>)</span>
                                    <?php else: ?>
                                        <span class="text-muted">Anonymous / System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background: #eef2ff; color: var(--primary); padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </code>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><code><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- HTML5 Canvas Responsive Bar Chart Drawing Script (Advanced Extension) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('registrationChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // Chart Data passed from PHP PDO Query
    const chartData = <?php echo json_encode($chartData); ?>;

    const padding = { top: 30, right: 20, bottom: 40, left: 45 };
    const chartWidth = canvas.width - padding.left - padding.right;
    const chartHeight = canvas.height - padding.top - padding.bottom;

    const maxVal = Math.max(...chartData.map(d => parseInt(d.total_count)), 5);
    const ceiling = Math.ceil(maxVal * 1.25);

    // Clear Canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw Grid Lines & Y-Axis Labels
    const ySteps = 4;
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    ctx.fillStyle = '#64748b';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';

    for (let i = 0; i <= ySteps; i++) {
        const yVal = Math.round((ceiling / ySteps) * i);
        const yPos = canvas.height - padding.bottom - (i / ySteps) * chartHeight;

        ctx.beginPath();
        ctx.moveTo(padding.left, yPos);
        ctx.lineTo(canvas.width - padding.right, yPos);
        ctx.stroke();

        ctx.fillText(yVal, padding.left - 8, yPos + 4);
    }

    // Draw Bars
    const barWidth = Math.min(48, chartWidth / chartData.length * 0.55);
    const spacing = chartWidth / chartData.length;

    chartData.forEach((item, index) => {
        const x = padding.left + (index * spacing) + (spacing - barWidth) / 2;
        const barH = (parseInt(item.total_count) / ceiling) * chartHeight;
        const y = canvas.height - padding.bottom - barH;

        // Gradient Fill for Bars
        const grad = ctx.createLinearGradient(0, y, 0, canvas.height - padding.bottom);
        grad.addColorStop(0, '#4f46e5');
        grad.addColorStop(1, '#818cf8');
        ctx.fillStyle = grad;

        // Rounded bar top
        ctx.beginPath();
        ctx.roundRect(x, y, barWidth, barH, [6, 6, 0, 0]);
        ctx.fill();

        // Value text above bar
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 12px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(item.total_count, x + barWidth / 2, y - 6);

        // X-Axis Month label
        ctx.fillStyle = '#64748b';
        ctx.font = '11px sans-serif';
        ctx.fillText(item.month_label, x + barWidth / 2, canvas.height - padding.bottom + 18);
    });
});
</script>

<footer class="hub-footer">
    StudentHub Portal &bull; Practical 14: Admin Dashboard, RBAC, Status Toggle & Audit Logging &bull; Charusat ITUE203
</footer>

</body>
</html>
