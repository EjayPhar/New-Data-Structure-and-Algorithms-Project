<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: ../login/login1.php');
require '../login/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $course = trim($_POST['course']);
    $gpa = trim($_POST['gpa']);
    $password = trim($_POST['password']);

    if(empty($name) || empty($email) || empty($student_id) || empty($password)){
        $error = "All fields are required.";
    } elseif(strlen($password) < 8 || !preg_match('/[\W_]/', $password)){
        $error = "Password must be at least 8 characters with a special character.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user (username, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->bind_param("sss", $name, $email, $hashed);
        if($stmt->execute()){
            $user_id = $conn->insert_id;
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO students (user_id, name, student_id, course, gpa) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $user_id, $name, $student_id, $course, $gpa);
            $stmt->execute();
            $stmt->close();
            header('Location: admin-dashboard.php?success=1');
            exit;
        } else {
            $error = "Error adding student.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Student</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<button onclick="toggleDarkMode()" class="toggle" title="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
<div class="container">
<h1><i class="fas fa-plus"></i> Add Student</h1>
<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
<input type="text" name="name" placeholder="Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="student_id" placeholder="Student ID" required>
<input type="text" name="course" placeholder="Course">
<input type="number" name="gpa" placeholder="GPA" step="0.01" min="0" max="4">
<input type="password" name="password" placeholder="Password" required>
<button type="submit"><i class="fas fa-save"></i> Add Student</button>
</form>
<a href="admin-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script>
function toggleDarkMode(){
document.body.classList.toggle('dark');
}
</script>
</body>
</html>
