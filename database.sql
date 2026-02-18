-- ExamPro Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS exampro_db;
USE exampro_db;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    roll_number VARCHAR(50) NOT NULL,
    department VARCHAR(50) DEFAULT 'BCA',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NULL,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    semester VARCHAR(50) DEFAULT 'Semester 2',
    total_questions INT NOT NULL,
    duration_minutes INT NOT NULL,
    status ENUM('scheduled', 'active', 'completed') DEFAULT 'scheduled',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    exam_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Student exam results
CREATE TABLE IF NOT EXISTS student_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    score INT DEFAULT 0,
    total INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Student answers
CREATE TABLE IF NOT EXISTS student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_exam_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option CHAR(1),
    FOREIGN KEY (student_exam_id) REFERENCES student_exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- =====================
-- SEED DATA
-- =====================

-- Sample students (password: student123)
INSERT INTO students (name, email, roll_number, department, password) VALUES
('Rahul Sharma', 'rahul@student.com', 'BCA/2024/001', 'BCA', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Priya Patel', 'priya@student.com', 'BCA/2024/002', 'BCA', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Default teacher login (password: teacher123)
INSERT INTO teachers (name, email, password) VALUES
('Default Teacher', 'teacher@exampro.com', '$2y$10$tLg3ve.46FC5bbhiCM5TWe5DqaBHydQneJjf6zVqZBlvPB0f69Jpu');

-- Sample exams
INSERT INTO exams (teacher_id, title, subject, semester, total_questions, duration_minutes, status, is_active, exam_date) VALUES
(1, 'Mathematics - Unit Test', 'Mathematics', 'BCA Semester 2', 5, 10, 'active', 1, '2026-02-18'),
(1, 'Programming - C++', 'C++ Programming', 'BCA Semester 2', 5, 10, 'active', 1, '2026-02-22'),
(1, 'Database Management', 'DBMS', 'BCA Semester 3', 5, 10, 'scheduled', 0, '2026-02-25');

-- Math Questions (Exam 1)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(1, 'What is 15 × 12?', '170', '180', '190', '200', 'B'),
(1, 'What is the square root of 144?', '10', '11', '12', '14', 'C'),
(1, 'What is 2^10?', '512', '1024', '2048', '256', 'B'),
(1, 'What is the value of π (approx)?', '3.14', '2.14', '4.14', '1.14', 'A'),
(1, 'What is 100 ÷ 8?', '12', '12.5', '13', '11.5', 'B');

-- C++ Questions (Exam 2)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(2, 'Which of the following is the correct way to declare a variable in C++?', 'int x;', 'var x;', 'declare x;', 'x int;', 'A'),
(2, 'What does cout stand for?', 'Character Output', 'Console Output', 'Common Output', 'Central Output', 'B'),
(2, 'Which operator is used for dynamic memory allocation in C++?', 'malloc', 'new', 'alloc', 'create', 'B'),
(2, 'What is the file extension for C++ source files?', '.c', '.cp', '.cpp', '.cplusplus', 'C'),
(2, 'Which keyword is used to define a class in C++?', 'struct', 'object', 'class', 'define', 'C');

-- DBMS Questions (Exam 3)
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(3, 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'Standard Query Language', 'Sequential Query Language', 'A'),
(3, 'Which command is used to retrieve data from a database?', 'GET', 'FETCH', 'SELECT', 'RETRIEVE', 'C'),
(3, 'What is a primary key?', 'A key that can be NULL', 'A unique identifier for each record', 'A foreign reference', 'A secondary index', 'B'),
(3, 'Which normal form removes partial dependency?', '1NF', '2NF', '3NF', 'BCNF', 'B'),
(3, 'What does ACID stand for in databases?', 'Atomicity, Consistency, Isolation, Durability', 'Access, Control, Identity, Data', 'Automatic, Controlled, Indexed, Durable', 'None of the above', 'A');

-- Sample completed exam result for Rahul
INSERT INTO student_exams (student_id, exam_id, score, total, percentage, submitted_at) VALUES
(1, 1, 4, 5, 80.00, '2026-02-10 10:30:00');

INSERT INTO student_answers (student_exam_id, question_id, selected_option) VALUES
(1, 1, 'B'), (1, 2, 'C'), (1, 3, 'B'), (1, 4, 'A'), (1, 5, 'A');
