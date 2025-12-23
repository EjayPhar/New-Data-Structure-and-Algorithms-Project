<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: ../login/login1.php');
require '../login/db.php';

$id = $_GET['id'];
$student = $conn->query("SELECT s.*, u.email FROM students s JOIN user u ON s.user_id = u.id WHERE s.id = $id")->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $course = trim($_POST['course']);
    $gpa = trim($_POST['gpa']);
    $password = trim($_POST['password'] ?? '');

    if(empty($name) || empty($email) || empty($student_id)){
        $error = "Name, Email, and Student ID are required.";
    } else {
        if(!empty($password)){
            if(strlen($password) < 8 || !preg_match('/[\W_]/', $password)){
                $error = "Password must be at least 8 characters with a special character.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE user SET email=?, password=? WHERE id=?");
                $stmt->bind_param("ssi", $email, $hashed, $student['user_id']);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("UPDATE user SET email=? WHERE id=?");
            $stmt->bind_param("si", $email, $student['user_id']);
            $stmt->execute();
            $stmt->close();
        }
        if(!isset($error)){
            $stmt = $conn->prepare("UPDATE students SET name=?, student_id=?, course=?, gpa=? WHERE id=?");
            $stmt->bind_param("sssdi", $name, $student_id, $course, $gpa, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: admin-dashboard.php');
            exit;
        }
    }
    }
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Student</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<button onclick="toggleDarkMode()" class="toggle" title="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
<div class="container">
<h1><i class="fas fa-edit"></i> Edit Student</h1>
<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST">
<input type="text" name="name" value="<?= $student['name'] ?>" required>
<input type="email" name="email" value="<?= $student['email'] ?>" required>
<input type="text" name="student_id" value="<?= $student['student_id'] ?>" required>
<input type="text" name="course" value="<?= $student['course'] ?>">
<input type="number" name="gpa" value="<?= $student['gpa'] ?>" step="0.01" min="0" max="4">
<input type="password" name="password" placeholder="New Password (leave blank to keep current)">
<button type="submit"><i class="fas fa-save"></i> Update Student</button>
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
