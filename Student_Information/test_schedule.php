<?php
session_start();
include "login/db.php";

// Simulate a student session - assuming there's a student with user_id = 1
$_SESSION['student_id'] = 1; // This should be a valid user_id from the user table

$student_id = $_SESSION['student_id'];

$sql = "SELECT * FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "No student found with user_id = $student_id\n";
    echo "Checking if user exists...\n";
    $sql_user = "SELECT id, email FROM user WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $student_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        echo "User exists, but no corresponding student record.\n";
    } else {
        echo "User does not exist.\n";
    }
    $stmt_user->close();
} else {
    echo "Student found: " . $student['name'] . "\n";
    echo "Student ID: " . $student['student_id'] . "\n";
    echo "Course: " . $student['course'] . "\n";
    echo "GPA: " . $student['gpa'] . "\n";

    // Test initials generation
    $name_parts = explode(' ', $student['name']);
    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
    echo "Initials: $initials\n";
}

$stmt->close();
$conn->close();
?>
