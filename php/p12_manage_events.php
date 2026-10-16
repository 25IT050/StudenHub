<?php
// =======================================================
// Practical 12: Event Management CRUD with Poster Upload and File Validation
// Features: Full Event CRUD (Add, View, Update, Delete)
//           File Validation: MIME type, file extension, max file size
//           Intermediate: Event Category and Open/Closed Filters
//           Advanced: Safe image renaming, upload folder protection, audit log
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$pageTitle = "Practical 12 - Event Management & Poster Upload";
$errors = [];
$successMessage = "";

// Ensure DB connected
if (!isset($pdo) || !$pdo) {
    die("Database connection unavailable. Please start MySQL service in XAMPP.");
}

// Upload configuration
$uploadDir = __DIR__ . '/../uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Categories allowed
$categories = ['Workshop', 'Competition', 'Seminar', 'Exhibition'];

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('error', "Security verification failed: Invalid CSRF token.");
        header("Location: p12_manage_events.php");
        exit;
    }

    // Helper: Validate and process image upload
    function process_poster_upload($fileInput, &$errors, $uploadDir) {
        if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file uploaded
        }

        $file = $_FILES[$fileInput];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload encountered an error code: " . $file['error'];
            return null;
        }

        // 1. Max size: 2MB (2,097,152 bytes)
        $maxSizeBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxSizeBytes) {
            $errors[] = "Poster image size exceeds limit of 2MB (Uploaded: " . round($file['size'] / 1024 / 1024, 2) . "MB).";
            return null;
        }

        // 2. Validate Extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = "Invalid image extension '.$ext'. Only JPG, JPEG, PNG, and WEBP formats are permitted.";
            return null;
        }

        // 3. Validate MIME type safely using getimagesize / finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $errors[] = "Invalid MIME type ($mime). Please upload an authentic image file.";
            return null;
        }

        // 4. Advanced: Secure Image Renaming (poster_timestamp_random.ext)
        $newFilename = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $newFilename;
        } else {
            $errors[] = "Failed to save uploaded poster file to the destination directory.";
            return null;
        }
    }

    // 1. ADD EVENT
    if ($action === 'add') {
        $title       = trim(strip_tags($_POST['title'] ?? ''));
        $description = trim(strip_tags($_POST['description'] ?? ''));
        $category    = trim($_POST['category'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $venue       = trim(strip_tags($_POST['venue'] ?? ''));
        $status      = in_array($_POST['status'] ?? '', ['open', 'closed']) ? $_POST['status'] : 'open';

        // Validation
        if (empty($title) || strlen($title) < 5 || strlen($title) > 200) {
            $errors[] = "Event title must be between 5 and 200 characters.";
        }
        if (empty($description) || strlen($description) < 10) {
            $errors[] = "Please provide a detailed event description (minimum 10 characters).";
        }
        if (!in_array($category, $categories)) {
            $errors[] = "Please select a valid event category.";
        }
        if (empty($eventDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $eventDate)) {
            $errors[] = "Please provide a valid event date (YYYY-MM-DD).";
        }
        if (empty($venue)) {
            $errors[] = "Event venue/hall location is required.";
        }

        // Poster upload
        $posterFilename = process_poster_upload('poster_image', $errors, $uploadDir);

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO events (title, description, category, event_date, venue, poster_image, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $category, $eventDate, $venue, $posterFilename, $status]);
                $newId = $pdo->lastInsertId();

                log_audit($pdo, $_SESSION['user_id'] ?? null, 'EVENT_CREATED', "Created event #$newId ('$title') with poster: " . ($posterFilename ?: 'None'));
                set_flash('success', "Event '$title' successfully scheduled (Event ID: #$newId).");
                header("Location: p12_manage_events.php");
                exit;
            } catch (Exception $e) {
                $errors[] = "Database insert error: " . $e->getMessage();
            }
        }
    }

    // 2. EDIT EVENT
    elseif ($action === 'edit') {
        $eventId     = (int)($_POST['event_id'] ?? 0);
        $title       = trim(strip_tags($_POST['title'] ?? ''));
        $description = trim(strip_tags($_POST['description'] ?? ''));
        $category    = trim($_POST['category'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $venue       = trim(strip_tags($_POST['venue'] ?? ''));
        $status      = in_array($_POST['status'] ?? '', ['open', 'closed']) ? $_POST['status'] : 'open';

        // Validation
        if ($eventId <= 0) {
            $errors[] = "Invalid event ID.";
        }
        if (empty($title) || strlen($title) < 5 || strlen($title) > 200) {
            $errors[] = "Event title must be between 5 and 200 characters.";
        }
        if (empty($description) || strlen($description) < 10) {
            $errors[] = "Description must be at least 10 characters.";
        }
        if (!in_array($category, $categories)) {
            $errors[] = "Please select a valid event category.";
        }
        if (empty($eventDate)) {
            $errors[] = "Valid event date is required.";
        }
        if (empty($venue)) {
            $errors[] = "Event venue is required.";
        }

        // Handle optional poster replacement
        $newPoster = process_poster_upload('poster_image', $errors, $uploadDir);

        if (empty($errors)) {
            try {
                if ($newPoster) {
                    // Fetch and unlink old poster image
                    $oldStmt = $pdo->prepare("SELECT poster_image FROM events WHERE id = ?");
                    $oldStmt->execute([$eventId]);
                    $oldPoster = $oldStmt->fetchColumn();
                    if ($oldPoster && file_exists($uploadDir . $oldPoster)) {
                        unlink($uploadDir . $oldPoster);
                    }

                    $stmt = $pdo->prepare("
                        UPDATE events 
                        SET title = ?, description = ?, category = ?, event_date = ?, venue = ?, poster_image = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $category, $eventDate, $venue, $newPoster, $status, $eventId]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE events 
                        SET title = ?, description = ?, category = ?, event_date = ?, venue = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $category, $eventDate, $venue, $status, $eventId]);
                }

                log_audit($pdo, $_SESSION['user_id'] ?? null, 'EVENT_UPDATED', "Updated event #$eventId ('$title')");
                set_flash('success', "Event #$eventId ('$title') updated successfully.");
                header("Location: p12_manage_events.php");
                exit;
            } catch (Exception $e) {
                $errors[] = "Failed to update event: " . $e->getMessage();
            }
        }
    }

    // 3. DELETE EVENT
    elseif ($action === 'delete') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($eventId > 0) {
            try {
                // Delete poster file if present
                $oldStmt = $pdo->prepare("SELECT poster_image, title FROM events WHERE id = ?");
                $oldStmt->execute([$eventId]);
                $eventData = $oldStmt->fetch();

                if ($eventData && $eventData['poster_image'] && file_exists($uploadDir . $eventData['poster_image'])) {
                    unlink($uploadDir . $eventData['poster_image']);
                }

                $delStmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $delStmt->execute([$eventId]);

                log_audit($pdo, $_SESSION['user_id'] ?? null, 'EVENT_DELETED', "Deleted event #$eventId (" . ($eventData['title'] ?? '') . ")");
                set_flash('success', "Event #$eventId was successfully deleted.");
            } catch (Exception $e) {
                set_flash('error', "Could not delete event: " . $e->getMessage());
            }
        }
        header("Location: p12_manage_events.php");
        exit;
    }
}

// Search and Filter GET Parameters (Intermediate Extension)
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR description LIKE ? OR venue LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($category) && in_array($category, $categories)) {
    $where .= " AND category = ?";
    $params[] = $category;
}

if (!empty($status) && in_array($status, ['open', 'closed'])) {
    $where .= " AND status = ?";
    $params[] = $status;
}

$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count
    FROM events e 
    $where 
    ORDER BY event_date ASC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

// Check if an event edit is requested
$editEvent = null;
if (isset($_GET['edit_id'])) {
    $eId = (int)$_GET['edit_id'];
    $editStmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
    $editStmt->execute([$eId]);
    $editEvent = $editStmt->fetch();
}

$csrfToken = generate_csrf_token();
require_once __DIR__ . '/navbar.php';
?>

<div class="hub-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; color: #1e293b;">
                🎪 Event Management CRUD & Poster Upload
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 14px;">
                Practical 12: Poster Upload Validation (MIME & Size), Safe Renaming, Category & Status Filters, Audit Logging
            </p>
        </div>
        <button onclick="document.getElementById('eventFormCard').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary">
            ➕ Schedule New Event
        </button>
    </div>

    <?php echo display_flash(); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please resolve the following upload/validation errors:</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar (Intermediate Extension) -->
    <div class="card" style="padding: 18px 24px;">
        <form method="GET" action="p12_manage_events.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 12px; align-items: end;">
            <div class="form-field" style="margin: 0;">
                <label>🔍 Search Events</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Title, Venue, Description...">
            </div>

            <div class="form-field" style="margin: 0;">
                <label>🏷️ Category Filter</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field" style="margin: 0;">
                <label>📌 Status Filter</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open for Registration</option>
                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed / Completed</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
            </div>

            <div>
                <a href="p12_manage_events.php" class="btn btn-secondary" style="height: 38px;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Events Cards Grid -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                📅 Scheduled University Events 
                <span class="badge-practical"><?php echo count($events); ?> Listed</span>
            </h2>
        </div>

        <?php if (empty($events)): ?>
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                No university events match the selected criteria.
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php foreach ($events as $ev): ?>
                    <div style="border: 1px solid var(--card-border); border-radius: 8px; overflow: hidden; background: #ffffff; display: flex; flex-direction: column;">
                        <!-- Poster Preview with Validation details -->
                        <div style="height: 180px; background: #f1f5f9; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <?php if ($ev['poster_image'] && file_exists($uploadDir . $ev['poster_image'])): ?>
                                <img src="../uploads/posters/<?php echo htmlspecialchars($ev['poster_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($ev['title']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="text-align: center; color: #94a3b8; padding: 20px;">
                                    <div style="font-size: 32px;">🖼️</div>
                                    <div style="font-size: 12px; margin-top: 4px;">No Poster Uploaded</div>
                                </div>
                            <?php endif; ?>

                            <div style="position: absolute; top: 10px; left: 10px;">
                                <span class="role-badge" style="background: rgba(255,255,255,0.95); color: var(--primary);">
                                    <?php echo htmlspecialchars($ev['category']); ?>
                                </span>
                            </div>

                            <div style="position: absolute; top: 10px; right: 10px;">
                                <span class="role-badge" style="background: <?php echo $ev['status'] === 'open' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $ev['status'] === 'open' ? '#166534' : '#991b1b'; ?>;">
                                    <?php echo strtoupper($ev['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div style="padding: 16px; flex-grow: 1; display: flex; flex-direction: column;">
                            <h3 style="margin: 0 0 8px; font-size: 16px; color: #1e293b;">
                                <?php echo htmlspecialchars($ev['title']); ?>
                            </h3>
                            <p style="margin: 0 0 12px; font-size: 13px; color: #64748b; line-height: 1.5; flex-grow: 1;">
                                <?php echo htmlspecialchars(mb_strimwidth($ev['description'], 0, 110, "...")); ?>
                            </p>

                            <div style="font-size: 12px; color: #475569; display: flex; flex-direction: column; gap: 4px; margin-bottom: 14px;">
                                <div>📅 <strong>Date:</strong> <?php echo date("D, d M Y", strtotime($ev['event_date'])); ?></div>
                                <div>📍 <strong>Venue:</strong> <?php echo htmlspecialchars($ev['venue']); ?></div>
                                <div>👥 <strong>Registrations:</strong> <?php echo $ev['registration_count']; ?> students</div>
                            </div>

                            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--card-border); padding-top: 12px;">
                                <a href="p12_manage_events.php?edit_id=<?php echo $ev['id']; ?>#eventFormCard" class="btn btn-secondary btn-sm">
                                    ✏️ Edit
                                </a>

                                <form method="POST" action="p12_manage_events.php" style="margin: 0;"
                                      onsubmit="return confirm('Delete event \'<?php echo addslashes($ev['title']); ?>\'? This will also remove the poster file and related registrations.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Event Add / Edit Form Card -->
    <div class="card" id="eventFormCard">
        <div class="card-header">
            <h2 class="card-title">
                <?php echo $editEvent ? "✏️ Update Event (#{$editEvent['id']})" : "➕ Schedule New University Event"; ?>
            </h2>
            <?php if ($editEvent): ?>
                <a href="p12_manage_events.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="p12_manage_events.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="<?php echo $editEvent ? 'edit' : 'add'; ?>">
            <?php if ($editEvent): ?>
                <input type="hidden" name="event_id" value="<?php echo $editEvent['id']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Artificial Intelligence & Agentic AI Masterclass"
                           value="<?php echo htmlspecialchars($editEvent['title'] ?? $_POST['title'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>Event Category *</label>
                    <select name="category" required>
                        <option value="">-- Choose Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php 
                                $selected = false;
                                if ($editEvent && $editEvent['category'] === $cat) $selected = true;
                                elseif (!$editEvent && isset($_POST['category']) && $_POST['category'] === $cat) $selected = true;
                            ?>
                            <option value="<?php echo $cat; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" required
                           value="<?php echo htmlspecialchars($editEvent['event_date'] ?? $_POST['event_date'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>Venue / Location *</label>
                    <input type="text" name="venue" required placeholder="e.g. Central Auditorium / Lab 204"
                           value="<?php echo htmlspecialchars($editEvent['venue'] ?? $_POST['venue'] ?? ''); ?>">
                </div>

                <div class="form-field">
                    <label>Registration Status *</label>
                    <select name="status" required>
                        <option value="open" <?php echo ($editEvent['status'] ?? 'open') === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="closed" <?php echo ($editEvent['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Description *</label>
                    <textarea name="description" rows="3" required placeholder="Detailed information, prerequisites, and learning outcomes..."><?php echo htmlspecialchars($editEvent['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>
                        🖼️ Event Poster Image (File Validation: Max 2MB, JPG/PNG/WEBP only)
                    </label>
                    <input type="file" name="poster_image" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">
                        <?php if ($editEvent && $editEvent['poster_image']): ?>
                            Current file: <strong><?php echo htmlspecialchars($editEvent['poster_image']); ?></strong>. Uploading a new file will safely replace and unlink the old image.
                        <?php else: ?>
                            Optional. Files will be scanned for MIME authenticity and renamed safely on server.
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    💾 <?php echo $editEvent ? 'Update Event Record' : 'Publish & Save Event'; ?>
                </button>
                <?php if ($editEvent): ?>
                    <a href="p12_manage_events.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<footer class="hub-footer">
    StudentHub Portal &bull; Practical 12: Event Management CRUD with Poster Upload & File Validation &bull; Charusat ITUE203
</footer>

</body>
</html>
