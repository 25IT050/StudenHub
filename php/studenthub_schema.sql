CREATE DATABASE studenthub;

USE studenthub;

CREATE TABLE courses (
    course_id INT(11) NOT NULL AUTO_INCREMENT,
    course_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (course_id)
);

INSERT INTO courses (course_name)
VALUES
('Information Technology'),
('Computer Science'),
('Computer Engineering');

CREATE TABLE students (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    course_id INT(11),
    year INT(11),
    gender VARCHAR(20),
    PRIMARY KEY (id),
    FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT