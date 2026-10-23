<?php
require 'db_connect.php';

$admin = password_hash("Admin@123", PASSWORD_DEFAULT);
$student = password_hash("Student@123", PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "UPDATE users SET password = :password WHERE email = :email"
);

$stmt->execute([
    ':password' => $admin,
    ':email' => 'admin@studenthub.com'
]);

$stmt->execute([
    ':password' => $student,
    ':email' => 'student@studenthub.com'
]);

echo "Passwords updated successfully!";
?>