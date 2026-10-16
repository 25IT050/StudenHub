<?php
// =======================================================
// Practical 13: RESTful PHP JSON API for Events
// Supports: GET (List/Read), POST (Create), PUT (Update), DELETE (Remove)
// Features: Valid JSON responses, Proper HTTP Status Codes,
//           Input validation, Centralized Error Handling
// =======================================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/db_connect.php';

// Helper: Standardized JSON Response
function send_json($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    echo json_encode([
        'success'    => $success,
        'statusCode' => $statusCode,
        'message'    => $message,
        'data'       => $data,
        'timestamp'  => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Ensure DB connected
if (!isset($pdo) || !$pdo) {
    send_json(500, false, "Database service is offline or unreachable.");
}

// Detect HTTP Method (support method spoofing via _method or header)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
} elseif ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
}

// Parse Raw Input for PUT / DELETE / JSON POST
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
if (is_array($jsonInput)) {
    $requestData = $jsonInput;
} else {
    $requestData = $_POST;
}

// Categories list
$validCategories = ['Workshop', 'Competition', 'Seminar', 'Exhibition'];

// -------------------------------------------------------
// 1. GET - List or Retrieve Events
// -------------------------------------------------------
if ($method === 'GET') {
    try {
        // Single Event Fetch
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $event = $stmt->fetch();

            if ($event) {
                send_json(200, true, "Event #$id retrieved successfully.", $event);
            } else {
                send_json(404, false, "Event with ID #$id was not found.");
            }
        }

        // List Events with Optional Search & Filters
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status   = trim($_GET['status'] ?? '');

        $sql = "
            SELECT e.*, 
                   (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registration_count 
            FROM events e 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($category) && in_array($category, $validCategories)) {
            $sql .= " AND e.category = ?";
            $params[] = $category;
        }

        if (!empty($status) && in_array($status, ['open', 'closed'])) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY e.event_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        send_json(200, true, "Retrieved " . count($events) . " event(s).", $events);
    } catch (Exception $e) {
        send_json(500, false, "Failed to retrieve events: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// 2. POST - Create New Event
// -------------------------------------------------------
elseif ($method === 'POST') {
    $title       = trim(strip_tags($requestData['title'] ?? ''));
    $description = trim(strip_tags($requestData['description'] ?? ''));
    $category    = trim($requestData['category'] ?? '');
    $eventDate   = trim($requestData['event_date'] ?? '');
    $venue       = trim(strip_tags($requestData['venue'] ?? ''));
    $status      = in_array($requestData['status'] ?? '', ['open', 'closed']) ? $requestData['status'] : 'open';

    // Validation
    $errors = [];
    if (empty($title) || strlen($title) < 5 || strlen($title) > 200) {
        $errors[] = "Title must be between 5 and 200 characters.";
    }
    if (empty($description) || strlen($description) < 10) {
        $errors[] = "Description must be at least 10 characters.";
    }
    if (!in_array($category, $validCategories)) {
        $errors[] = "Category must be one of: " . implode(', ', $validCategories);
    }
    if (empty($eventDate) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $eventDate)) {
        $errors[] = "Valid event_date (YYYY-MM-DD) is required.";
    }
    if (empty($venue)) {
        $errors[] = "Venue location is required.";
    }

    if (!empty($errors)) {
        send_json(400, false, "Validation errors occurred.", $errors);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, category, event_date, venue, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $category, $eventDate, $venue, $status]);
        $newId = $pdo->lastInsertId();

        // Fetch newly created record
        $getStmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $getStmt->execute([$newId]);
        $created = $getStmt->fetch();

        send_json(201, true, "Event scheduled successfully via JSON API.", $created);
    } catch (Exception $e) {
        send_json(500, false, "Database error: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// 3. PUT - Update Existing Event
// -------------------------------------------------------
elseif ($method === 'PUT') {
    $id          = (int)($requestData['id'] ?? 0);
    $title       = trim(strip_tags($requestData['title'] ?? ''));
    $description = trim(strip_tags($requestData['description'] ?? ''));
    $category    = trim($requestData['category'] ?? '');
    $eventDate   = trim($requestData['event_date'] ?? '');
    $venue       = trim(strip_tags($requestData['venue'] ?? ''));
    $status      = in_array($requestData['status'] ?? '', ['open', 'closed']) ? $requestData['status'] : 'open';

    if ($id <= 0) {
        send_json(400, false, "Event 'id' is required for update.");
    }

    // Verify record exists
    $check = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        send_json(404, false, "Event with ID #$id does not exist.");
    }

    // Validation
    $errors = [];
    if (empty($title) || strlen($title) < 5) {
        $errors[] = "Title must be at least 5 characters.";
    }
    if (empty($description) || strlen($description) < 10) {
        $errors[] = "Description must be at least 10 characters.";
    }
    if (!in_array($category, $validCategories)) {
        $errors[] = "Invalid category selection.";
    }
    if (empty($eventDate)) {
        $errors[] = "Event date is required.";
    }
    if (empty($venue)) {
        $errors[] = "Venue is required.";
    }

    if (!empty($errors)) {
        send_json(400, false, "Validation errors occurred.", $errors);
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE events 
            SET title = ?, description = ?, category = ?, event_date = ?, venue = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $category, $eventDate, $venue, $status, $id]);

        $getStmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $getStmt->execute([$id]);
        $updated = $getStmt->fetch();

        send_json(200, true, "Event #$id updated successfully.", $updated);
    } catch (Exception $e) {
        send_json(500, false, "Update failed: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// 4. DELETE - Remove Event
// -------------------------------------------------------
elseif ($method === 'DELETE') {
    $id = 0;
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
    } elseif (isset($requestData['id'])) {
        $id = (int)$requestData['id'];
    }

    if ($id <= 0) {
        send_json(400, false, "Event ID must be supplied in request query or payload.");
    }

    // Verify record exists
    $check = $pdo->prepare("SELECT title FROM events WHERE id = ?");
    $check->execute([$id]);
    $existing = $check->fetch();
    if (!$existing) {
        send_json(404, false, "Event #$id not found in system.");
    }

    try {
        $del = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $del->execute([$id]);

        send_json(200, true, "Event #$id ('{$existing['title']}') was deleted successfully.", ['deleted_id' => $id]);
    } catch (Exception $e) {
        send_json(500, false, "Delete failed: " . $e->getMessage());
    }
}

// -------------------------------------------------------
// 5. Unsupported Method
// -------------------------------------------------------
else {
    send_json(405, false, "Method $method Not Allowed. Use GET, POST, PUT, or DELETE.");
}
?>
