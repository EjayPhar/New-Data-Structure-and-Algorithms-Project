<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: login.php');
require 'db.php';
$id=$_GET['id']??0;

$stmt=$conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$student=$stmt->get_result()->fetch_assoc();
$stmt->close();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=$_POST['name']; $student_id=$_POST['student_id']; $email=$_POST['email'];
    $course=$_POST['course']; $gpa=$_POST['gpa'];
    $stmt=$conn->prepare("UPDATE students SET student_id=?,name=?,email=?,course=?,gpa=? WHERE id=?");
    $stmt->bind_param("ssssdi",$student_id,$name,$email,$course,$gpa,$id);
    $stmt->execute(); $stmt->close();
    header('Location: admin_dashboard.php'); exit;
}
?>
<form method="POST">
<input name="name" value="<?= $student['name'] ?>" required>
<input name="student_id" value="<?= $student['student_id'] ?>" required>
<input name="email" value="<?= $student['email'] ?>" required>
<input name="course" value="<?= $student['course'] ?>" required>
<input name="gpa" type="number" step="0.01" min="0" max="4" value="<?= $student['gpa'] ?>" required>
<button type="submit">Update Student</button>
</form>
