<?php
// =======================================================
// StudentHub - Database Seeder & Schema Initializer
// Run via CLI or browser: php php/seed_database.php
// =======================================================

require_once __DIR__ . '/db_connect.php';

echo "<pre style='font-family:monospace; background:#0f172a; color:#38bdf8; padding:20px; border-radius:8px;'>";
echo "=========================================================\n";
echo "  StudentHub Database Initialization & Seeder\n";
echo "=========================================================\n\n";

try {
    // 1. Create Tables
    echo "[1/7] Creating / Verifying database tables...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            course_id INT(11) NOT NULL AUTO_INCREMENT,
            course_code VARCHAR(20) NOT NULL UNIQUE,
            course_name VARCHAR(100) NOT NULL,
            PRIMARY KEY (course_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            remember_token VARCHAR(255) DEFAULT NULL,
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            mobile VARCHAR(15) NOT NULL,
            course_id INT(11) NOT NULL,
            year INT(11) NOT NULL,
            gender VARCHAR(20) NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_students_course FOREIGN KEY (course_id)
                REFERENCES courses(course_id) ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_students_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT(11) NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            category ENUM('Workshop', 'Competition', 'Seminar', 'Exhibition') NOT NULL DEFAULT 'Workshop',
            event_date DATE NOT NULL,
            venue VARCHAR(150) NOT NULL,
            poster_image VARCHAR(255) DEFAULT NULL,
            status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registrations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            student_id INT(11) NOT NULL,
            event_id INT(11) NOT NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('registered', 'attended', 'cancelled') NOT NULL DEFAULT 'registered',
            PRIMARY KEY (id),
            UNIQUE KEY uq_student_event (student_id, event_id),
            CONSTRAINT fk_reg_student FOREIGN KEY (student_id)
                REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_reg_event FOREIGN KEY (event_id)
                REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "  -> Tables verified successfully.\n\n";

    // 2. Seed Courses
    echo "[2/7] Seeding courses...\n";
    $courses = [
        ['IT', 'Information Technology'],
        ['CE', 'Computer Engineering'],
        ['CSE', 'Computer Science and Engineering'],
        ['EC', 'Electronics and Communication']
    ];
    $courseStmt = $pdo->prepare("INSERT INTO courses (course_code, course_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE course_name = VALUES(course_name)");
    foreach ($courses as $c) {
        $courseStmt->execute($c);
    }
    echo "  -> 4 courses available.\n\n";

    // 3. Seed Users (with bcrypt password hashes)
    echo "[3/7] Seeding default authentication accounts...\n";
    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role)");
    
    // Admin user: Admin@123
    $adminPass = password_hash('Admin@123', PASSWORD_BCRYPT);
    $userStmt->execute(['System Administrator', 'admin@studenthub.com', $adminPass, 'admin', 'active']);

    // Demo student: Student@123
    $studentPass = password_hash('Student@123', PASSWORD_BCRYPT);
    $userStmt->execute(['Bhargavi Patel', 'student@studenthub.com', $studentPass, 'student', 'active']);
    echo "  -> Default accounts created:\n";
    echo "     * Admin:   admin@studenthub.com   / Admin@123\n";
    echo "     * Student: student@studenthub.com / Student@123\n\n";

    // 4. Seed 20+ Students
    echo "[4/7] Seeding students (at least 20 records for Practical 11)...\n";
    $studentsData = [
        ['Aarav Shah', 'aarav.shah@studenthub.com', '9825011111', 1, 3, 'Male'],
        ['Diya Sharma', 'diya.sharma@studenthub.com', '9825022222', 2, 2, 'Female'],
        ['Rohan Mehta', 'rohan.mehta@studenthub.com', '9825033333', 3, 4, 'Male'],
        ['Ananya Joshi', 'ananya.joshi@studenthub.com', '9825044444', 1, 1, 'Female'],
        ['Kabir Patel', 'kabir.patel@studenthub.com', '9825055555', 2, 3, 'Male'],
        ['Ishita Dave', 'ishita.dave@studenthub.com', '9825066666', 3, 2, 'Female'],
        ['Vivaan Trivedi', 'vivaan.trivedi@studenthub.com', '9825077777', 1, 4, 'Male'],
        ['Riya Bhatt', 'riya.bhatt@studenthub.com', '9825088888', 4, 2, 'Female'],
        ['Devansh Soni', 'devansh.soni@studenthub.com', '9825099999', 2, 1, 'Male'],
        ['Pooja Prajapati', 'pooja.p@studenthub.com', '9825100000', 3, 3, 'Female'],
        ['Yash Varma', 'yash.varma@studenthub.com', '9825111111', 1, 2, 'Male'],
        ['Khushi Shah', 'khushi.shah@studenthub.com', '9825122222', 2, 4, 'Female'],
        ['Manan Desai', 'manan.desai@studenthub.com', '9825133333', 3, 1, 'Male'],
        ['Tanvi Pandya', 'tanvi.pandya@studenthub.com', '9825144444', 4, 3, 'Female'],
        ['Harsh Panchal', 'harsh.p@studenthub.com', '9825155555', 1, 3, 'Male'],
        ['Bhavya Patel', 'bhavya.patel@studenthub.com', '9825166666', 2, 2, 'Female'],
        ['Siddharth Raval', 'siddharth.r@studenthub.com', '9825177777', 3, 4, 'Male'],
        ['Neha Solanki', 'neha.solanki@studenthub.com', '9825188888', 1, 1, 'Female'],
        ['Aditya Chawla', 'aditya.c@studenthub.com', '9825199999', 2, 3, 'Male'],
        ['Sneha Parmar', 'sneha.parmar@studenthub.com', '9825200000', 3, 2, 'Female'],
        ['Bhargavi Patel', 'student@studenthub.com', '9825000001', 1, 3, 'Female']
    ];

    $studentInsertStmt = $pdo->prepare("
        INSERT INTO students (name, email, mobile, course_id, year, gender, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE name = VALUES(name), mobile = VALUES(mobile), course_id = VALUES(course_id), year = VALUES(year)
    ");
    foreach ($studentsData as $st) {
        $studentInsertStmt->execute($st);
    }
    echo "  -> 21 student records seeded successfully.\n\n";

    // 5. Seed Events
    echo "[5/7] Seeding events...\n";
    $eventsData = [
        ['Full-Stack Web Bootcamp', 'Hands-on intensive masterclass covering modern HTML5, responsive CSS, Vanilla JS, and PHP-MySQL stack.', 'Workshop', '2026-11-05', 'IT Lab 102', 'event_web_bootcamp.jpg', 'open'],
        ['HackNova 2026 Hackathon', '36-hour flagship hackathon solving real-world campus and social problems. Prizes worth 50,000 INR.', 'Competition', '2026-11-18', 'Central Auditorium', 'event_hacknova.jpg', 'open'],
        ['Cloud Architecture & DevOps', 'Industry talk by cloud architects on containerization, microservices, and continuous delivery.', 'Seminar', '2026-11-25', 'Seminar Hall B', 'event_cloud.jpg', 'open'],
        ['Cyber Security & Ethical Hacking', 'Deep dive into web application security, SQL injection defenses, and ethical vulnerability disclosure.', 'Seminar', '2026-12-02', 'Seminar Hall A', 'event_security.jpg', 'open'],
        ['Annual Tech Innovators Expo', 'Showcase of student capstone projects, robotics prototypes, and AI applications.', 'Exhibition', '2026-10-01', 'University Grounds', 'event_expo.jpg', 'closed'],
        ['UI/UX Design Masterclass', 'Design principles, wireframing, typography, and interactive prototyping using Figma and design systems.', 'Workshop', '2026-12-10', 'Design Studio Lab', 'event_uiux.jpg', 'open']
    ];

    $eventInsertStmt = $pdo->prepare("
        INSERT INTO events (title, description, category, event_date, venue, poster_image, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    // Check if events exist first to avoid duplicate multiplication
    $evCount = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    if ($evCount === 0) {
        foreach ($eventsData as $ev) {
            $eventInsertStmt->execute($ev);
        }
    }
    echo "  -> Events verified (Total: " . max($evCount, count($eventsData)) . ").\n\n";

    // 6. Seed Registrations
    echo "[6/7] Seeding event registrations...\n";
    $studentIds = $pdo->query("SELECT id FROM students LIMIT 15")->fetchAll(PDO::FETCH_COLUMN);
    $eventIds = $pdo->query("SELECT id FROM events LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    $regInsertStmt = $pdo->prepare("INSERT IGNORE INTO registrations (student_id, event_id, status) VALUES (?, ?, ?)");

    if (!empty($studentIds) && !empty($eventIds)) {
        foreach ($studentIds as $idx => $sId) {
            $eId = $eventIds[$idx % count($eventIds)];
            $regInsertStmt->execute([$sId, $eId, 'registered']);
            if ($idx % 3 === 0 && isset($eventIds[($idx + 1) % count($eventIds)])) {
                $regInsertStmt->execute([$sId, $eventIds[($idx + 1) % count($eventIds)], 'registered']);
            }
        }
    }
    $totalReg = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    echo "  -> Total registrations seeded: $totalReg.\n\n";

    // 7. Seed Stored Procedure & Initial Audit Logs
    echo "[7/7] Installing Stored Procedure and Audit Logs...\n";
    $pdo->exec("DROP PROCEDURE IF EXISTS GetEventRegistrationStats");
    $pdo->exec("
        CREATE PROCEDURE GetEventRegistrationStats()
        BEGIN
            SELECT 
                e.id AS event_id,
                e.title AS event_title,
                e.category,
                e.event_date,
                e.status AS event_status,
                COUNT(r.id) AS total_registrations
            FROM events e
            LEFT JOIN registrations r ON e.id = r.event_id
            GROUP BY e.id, e.title, e.category, e.event_date, e.status
            ORDER BY total_registrations DESC;
        END
    ");

    $adminId = $pdo->query("SELECT id FROM users WHERE email='admin@studenthub.com'")->fetchColumn();
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$adminId, 'SYSTEM_INITIALIZATION', 'Database schema seeded with default users, courses, students, and events', '127.0.0.1']);
    $logStmt->execute([$adminId, 'USER_LOGIN', 'Administrator logged in from local workstation', '127.0.0.1']);
    echo "  -> Stored Procedure 'GetEventRegistrationStats' active.\n";
    echo "  -> Initial audit logs recorded.\n\n";

    echo "=========================================================\n";
    echo "  SUCCESS: StudentHub Database is completely initialized!\n";
    echo "=========================================================\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
}
?>
