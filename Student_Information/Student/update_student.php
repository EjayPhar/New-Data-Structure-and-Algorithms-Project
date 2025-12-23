<?php
session_start();
include "../login/db.php";

if(!isset($_SESSION['student_id'])) {
    header('Location: ../login/login1.html');
    exit;
}

$student_id = $_SESSION['student_id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$student_id_field = trim($_POST['student_id']);
$password = trim($_POST['password'] ?? '');

// Update user table if password or email changed
if(!empty($password)){
    if(strlen($password) < 8 || !preg_match('/[\W_]/', $password)){
        die('Password must be at least 8 characters with a special char.');
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET email=?, password=? WHERE id=?");
    $stmt->bind_param("ssi", $email, $hashed, $student_id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("UPDATE user SET email=? WHERE id=?");
    $stmt->bind_param("si", $email, $student_id);
    $stmt->execute();
    $stmt->close();
}

// Update students table
$stmt = $conn->prepare("UPDATE students SET name=?, email=?, student_id=? WHERE user_id=?");
$stmt->bind_param("sssi", $name, $email, $student_id_field, $student_id);
$stmt->execute();
$stmt->close();

header('Location: profile.php');
exit;
