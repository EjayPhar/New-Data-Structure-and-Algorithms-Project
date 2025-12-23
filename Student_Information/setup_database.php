<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "student_information_system";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS student_information_system";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select database
$conn->select_db($dbname);

// Create user table
$sql = "CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "User table created successfully\n";
} else {
    echo "Error creating user table: " . $conn->error . "\n";
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(255),
    gpa DECIMAL(3,2),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Students table created successfully\n";
} else {
    echo "Error creating students table: " . $conn->error . "\n";
}

// Create schedule table
$sql = "CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    time VARCHAR(50) NOT NULL,
    classroom VARCHAR(50) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Schedule table created successfully\n";
} else {
    echo "Error creating schedule table: " . $conn->error . "\n";
}

// Insert sample data
$hashed_password = password_hash('password123', PASSWORD_DEFAULT);

// Insert user
$sql = "INSERT INTO user (username, email, password, role) VALUES ('john_doe', 'student@example.com', '$hashed_password', 'student')";
if ($conn->query($sql) === TRUE) {
    $user_id = $conn->insert_id;
    echo "Sample user inserted with ID: $user_id\n";

    // Insert student
    $sql = "INSERT INTO students (user_id, name, student_id, course, gpa) VALUES ($user_id, 'John Doe', 'S001', 'Computer Science', 3.5)";
    if ($conn->query($sql) === TRUE) {
        echo "Sample student inserted successfully\n";
    } else {
        echo "Error inserting student: " . $conn->error . "\n";
    }
} else {
    echo "Error inserting user: " . $conn->error . "\n";
}

$conn->close();
?>
