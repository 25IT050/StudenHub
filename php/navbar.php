<?php
// =======================================================
// StudentHub - Unified Navigation Component
// =======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$activePage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - StudentHub' : 'StudentHub Backend'; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #e0e7ff;
            --accent: #06b6d4;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #dc2626;
            --dark: #0f172a;
            --gray-bg: #f8fafc;
            --card-border: #e2e8f0;
        }
        body {
            background-color: var(--gray-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .hub-navbar {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .hub-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 24px;
            border-bottom: 1px solid var(--card-border);
            max-width: 1300px;
            margin: 0 auto;
        }
        .hub-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            font-size: 20px;
        }
        .hub-brand img {
            height: 38px;
            width: auto;
        }
        .hub-user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .role-badge {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-student { background: #dbeafe; color: #1e40af; }
        .hub-navlinks {
            display: flex;
            gap: 4px;
            padding: 8px 24px;
            max-width: 1300px;
            margin: 0 auto;
            overflow-x: auto;
            white-space: nowrap;
        }
        .hub-navlinks a {
            text-decoration: none;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .hub-navlinks a:hover {
            background: var(--primary-light);
            color: var(--primary);
        }
        .hub-navlinks a.active {
            background: var(--primary);
            color: #ffffff;
        }
        .hub-container {
            max-width: 1200px;
            margin: 28px auto;
            padding: 0 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 12px;
        }
        .card-title {
            margin: 0;
            color: #1e293b;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge-practical {
            background: #eef2ff;
            color: var(--primary);
            border: 1px solid #c7d2fe;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #15803d; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        .table-responsive {
            overflow-x: auto;
        }
        .hub-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }
        .hub-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 2px solid var(--card-border);
        }
        .hub-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--card-border);
            vertical-align: middle;
        }
        .hub-table tr:hover {
            background: #f8fafc;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }
        .form-field {
            margin-bottom: 14px;
        }
        .form-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: #334155;
        }
        .form-field input, .form-field select, .form-field textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            background: #ffffff;
            transition: border-color 0.15s ease;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .text-muted { color: #64748b; font-size: 13px; }
        .hub-footer {
            text-align: center;
            padding: 24px;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid var(--card-border);
            margin-top: 40px;
        }
    </style>
</head>
<body>

<header class="hub-navbar">
    <div class="hub-topbar">
        <a href="p15_portal_home.php" class="hub-brand">
            <img src="../images/logo.png" alt="StudentHub" onerror="this.style.display='none'">
            <span>StudentHub <span style="font-size: 13px; font-weight: 500; color: #64748b;">Backend Portal</span></span>
        </a>
        <div class="hub-user-pill">
            <?php if ($isLoggedIn): ?>
                <span>Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                <span class="role-badge <?php echo $userRole === 'admin' ? 'role-admin' : 'role-student'; ?>">
                    <?php echo htmlspecialchars($userRole); ?>
                </span>
                <a href="p10_logout.php" class="btn btn-secondary btn-sm">Logout</a>
            <?php else: ?>
                <a href="p10_login.php" class="btn btn-primary btn-sm">Login</a>
                <a href="p9_secure_registration.php" class="btn btn-secondary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Dynamic Navigation across all Practicals 7 through 15 -->
    <nav class="hub-navlinks">
        <a href="p15_portal_home.php" class="<?php echo $activePage === 'p15_portal_home.php' ? 'active' : ''; ?>">🏠 Portal Home</a>
        <a href="p7_form_processing.php" class="<?php echo $activePage === 'p7_form_processing.php' ? 'active' : ''; ?>">P7: CSV/JSON Form</a>
        <a href="p8_db_connectivity.php" class="<?php echo $activePage === 'p8_db_connectivity.php' ? 'active' : ''; ?>">P8: DB & PDO</a>
        <a href="p9_secure_registration.php" class="<?php echo $activePage === 'p9_secure_registration.php' ? 'active' : ''; ?>">P9: Secure Register</a>
        <a href="p10_login.php" class="<?php echo in_array($activePage, ['p10_login.php', 'p10_student_dashboard.php']) ? 'active' : ''; ?>">P10: Auth & Sessions</a>
        <a href="p11_manage_students.php" class="<?php echo $activePage === 'p11_manage_students.php' ? 'active' : ''; ?>">P11: Student CRUD</a>
        <a href="p12_manage_events.php" class="<?php echo $activePage === 'p12_manage_events.php' ? 'active' : ''; ?>">P12: Event Poster Upload</a>
        <a href="p13_ajax_crud.php" class="<?php echo in_array($activePage, ['p13_ajax_crud.php', 'api_events.php']) ? 'active' : ''; ?>">P13: AJAX JSON API</a>
        <a href="p14_admin_dashboard.php" class="<?php echo $activePage === 'p14_admin_dashboard.php' ? 'active' : ''; ?>">P14: Admin Dashboard</a>
    </nav>
</header>
