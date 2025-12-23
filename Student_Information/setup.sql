-- Create database
CREATE DATABASE IF NOT EXISTS student_information_system;

-- Use the database
USE student_information_system;

-- Create user table
CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(255),
    gpa DECIMAL(3,2),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Create schedule table
CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    time VARCHAR(50) NOT NULL,
    classroom VARCHAR(50) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert sample data (optional, for testing)
-- INSERT INTO user (username, email, password, role) VALUES ('john_doe', 'student@example.com', '$2y$10$examplehashedpassword', 'student');
-- INSERT INTO students (user_id, name, student_id, course, gpa) VALUES (1, 'John Doe', 'S001', 'Computer Science', 3.5);
-- INSERT INTO schedule (student_id, day, course_name, instructor, time, classroom) VALUES
-- (1, 'Monday', 'CS101 - Introduction to Computer Science', 'Dr. Sarah Johnson', '9:00 AM - 10:30 AM', 'Room 205'),
-- (1, 'Tuesday', 'MATH201 - Calculus II', 'Prof. Mike Wilson', '10:00 AM - 11:30 AM', 'Room 101'),
-- (1, 'Wednesday', 'PHYS301 - Physics', 'Dr. Lisa Brown', '1:00 PM - 2:30 PM', 'Lab 1'),
-- (1, 'Thursday', 'CHEM101 - Chemistry', 'Dr. Emily Davis', '11:00 AM - 12:30 PM', 'Lab 2'),
-- (1, 'Friday', 'ENG101 - English Literature', 'Prof. John Smith', '2:00 PM - 3:30 PM', 'Room 303');
