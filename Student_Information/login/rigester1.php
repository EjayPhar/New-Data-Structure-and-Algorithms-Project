<?php
include 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 $username = $_POST['username'];
 $email = $_POST['email'];
 $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encrypt
 $role = $_POST['role']; // Capture the role from the form
 $sql = "INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, ?)";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("ssss", $username, $email, $password, $role);
 if ($stmt->execute()) {
    $user_id = $conn->insert_id; // Get the inserted user ID
    if ($role === 'student') {
        // Insert into students table
        $student_sql = "INSERT INTO students (user_id, name, student_id) VALUES (?, ?, ?)";
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bind_param("iss", $user_id, $username, $username); // Using username as name and student_id for now
        $student_stmt->execute();
        $student_stmt->close();
    }
    echo "Registration successful! <a href='../login/login1.html'>Login here</a>";
 } else {
 echo "Error: " . $stmt->error;
 }
}
?>