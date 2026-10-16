<?php
// =======================================================
// Practical 11 Intermediate Extension: Export Students to CSV
// =======================================================

require_once __DIR__ . '/auth_helper.php';

$search = trim($_GET['search'] ?? '');
$courseId = (int)($_GET['course_id'] ?? 0);
$year = (int)($_GET['year'] ?? 0);
$status = trim($_GET['status'] ?? 'active');

$query = "
    SELECT s.id, s.name, s.email, s.mobile, c.course_name, s.year, s.gender, s.status, s.created_at
    FROM students s
    INNER JOIN courses c ON s.course_id = c.course_id
    WHERE 1=1
";
$params = [];

if ($status !== 'all') {
    $query .= " AND s.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.email LIKE ? OR s.mobile LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($courseId > 0) {
    $query .= " AND s.course_id = ?";
    $params[] = $courseId;
}

if ($year > 0) {
    $query .= " AND s.year = ?";
    $params[] = $year;
}

$query .= " ORDER BY s.id ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Set HTTP headers for file download
$filename = "studenthub_students_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Student Name', 'Email Address', 'Mobile Number', 'Course', 'Year', 'Gender', 'Status', 'Registered At']);

foreach ($students as $row) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['mobile'],
        $row['course_name'],
        $row['year'],
        $row['gender'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
?>
