-- =======================================================
-- StudentHub Complete Normalized Database Schema
-- Charusat University - ITUE203 Web Development Frameworks
-- Covers Practicals 8, 9, 10, 11, 12, 13, 14, 15
-- =======================================================

CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- 1. Courses Table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT(11) NOT NULL AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (Authentication, RBAC, Password Hashing, Remember-Me)
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

-- 3. Students Table (Student Management CRUD, Soft Delete)
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

-- 4. Events Table (Event Management CRUD, Poster Upload, Status)
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

-- 5. Registrations Table (Event Participation)
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

-- 6. Audit Logs Table (Admin Tracking & Security Logging)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Stored Procedure (Practical 8 Advanced Requirement)
DROP PROCEDURE IF EXISTS GetEventRegistrationStats;
DELIMITER //
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
END //
DELIMITER ;