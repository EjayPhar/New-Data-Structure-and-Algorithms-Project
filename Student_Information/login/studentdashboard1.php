<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login/login1.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
</head>
<body>
<h1>Welcome, <?php echo htmlspecialchars($student['name']); ?></h1>
<p>Email: <?php echo htmlspecialchars($student['email']); ?></p>
<p>Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
<p>Course: <?php echo htmlspecialchars($student['course']); ?></p>
<p>GPA: <?php echo htmlspecialchars($student['gpa']); ?></p>
<a href="logout.php">Logout</a>
</body>
</html>
