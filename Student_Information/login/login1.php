<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "SELECT id, username, email, password, role FROM user WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $db_username, $db_email, $hashed_password, $db_role);

    if ($stmt->fetch() && password_verify($password, $hashed_password) && $role === $db_role) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $db_username;
        $_SESSION['email'] = $db_email;
        $_SESSION['role'] = $db_role;

        if ($role === 'student') {
            $_SESSION['student_id'] = $id;
            header("Location: ../Student/student-dashboard.php");
            exit();
        } elseif ($role === 'admin') {
            $_SESSION['admin_id'] = $id;
            header("Location: ../admin/admin-dashboard.php");
            exit();
        }
    } else {
        echo "Invalid email, password, or role.";
    }
}
?>
