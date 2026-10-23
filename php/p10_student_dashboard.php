<?php
// =======================================================
// Practical 10: Authenticated Student Dashboard
// =======================================================

require_once __DIR__ . '/auth_helper.php';
require_login(); // Ensure user is authenticated

$pageTitle = "Student Dashboard - StudentHub";
$userId = $_SESSION['user_id'];

// Fetch student profile details

$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.role, u.last_login,
           s.mobile, s.year, s.gender, c.course_name
    FROM users u
    LEFT JOIN students s ON u.email = s.email
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE u.id = ? LIMIT 1
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Fetch enrolled events
$eventsStmt = $pdo->prepare("
    SELECT e.id, e.title, e.category, e.event_date, e.venue, r.registration_date, r.status AS reg_status
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    INNER JOIN students s ON r.student_id = s.id
    WHERE s.email = ?
    ORDER BY e.event_date ASC
");
$eventsStmt->execute([$_SESSION['user_email']]);
$enrolledEvents = $eventsStmt->fetchAll();

require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <?php echo display_flash(); ?>

    <!-- Inactivity Timer Alert Banner -->
    <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>⏱️ Secure Session Active:</strong> Auto-timeout protection is enforced after 15 minutes of inactivity.
        </div>
        <div>
            Session expires in: <span id="sessionCountdown" style="font-weight: 700; font-family: monospace; font-size: 15px;">15:00</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Student Profile Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Student Profile</h2>
            </div>
            <div style="text-align: center; padding: 12px 0;">
                <div style="width: 80px; height: 80px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 12px;">
                    <?php echo strtoupper(substr($profile['name'] ?? 'S', 0, 1)); ?>
                </div>
                <h3 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($profile['name'] ?? 'Student'); ?></h3>
                <span class="role-badge role-student"><?php echo htmlspecialchars($profile['role'] ?? 'student'); ?></span>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 16px 0;">

            <div style="font-size: 13px; line-height: 2;">
                <div><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? '-'); ?></div>
                <div><strong>Mobile:</strong> <?php echo htmlspecialchars($profile['mobile'] ?? 'N/A'); ?></div>
                <div><strong>Course:</strong> <?php echo htmlspecialchars($profile['course_name'] ?? 'Not Enrolled'); ?></div>
                <div><strong>Year:</strong> <?php echo $profile['year'] ? 'Year ' . $profile['year'] : 'N/A'; ?></div>
                <div><strong>Gender:</strong> <?php echo htmlspecialchars($profile['gender'] ?? 'N/A'); ?></div>
                <div><strong>Last Login:</strong> <small class="text-muted"><?php echo htmlspecialchars($profile['last_login'] ?? 'Just now'); ?></small></div>
            </div>

            <div style="margin-top: 20px;">
                <a href="p10_logout.php" class="btn btn-danger" style="width: 100%; justify-content: center;">Sign Out</a>
            </div>
        </div>

        <!-- Enrolled Events Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">My Registered Campus Events</h2>
                    <p class="text-muted" style="margin: 4px 0 0 0;">Events you are currently participating in.</p>
                </div>
                <div>
                    <a href="p13_ajax_crud.php" class="btn btn-primary btn-sm">Explore All Events →</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="hub-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrolledEvents)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 24px;">
                                    You have not registered for any events yet. Check out the Events list!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enrolledEvents as $ev): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                                    <td><span class="role-badge role-student"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                    <td>
                                        <span class="role-badge role-student">
                                            ✓ <?php echo ucfirst(htmlspecialchars($ev['reg_status'])); ?>
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
</div>

<script>
// Interactive Session Inactivity Countdown
let timeLeft = 900; // 15 mins
const timerDisplay = document.getElementById('sessionCountdown');

setInterval(function() {
    if (timeLeft <= 0) {
        window.location.href = "p10_login.php";
    } else {
        timeLeft--;
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        timerDisplay.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}, 1000);
</script>

<footer class="hub-footer">
    StudentHub &copy; 2026 - Practical 10 Protected Student Dashboard.
</footer>
</body>
</html>
