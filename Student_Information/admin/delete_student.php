<?php
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: ../login/login1.php');
require '../login/db.php';

$id = $_GET['id'];
$conn->query("DELETE FROM students WHERE id = $id");
header('Location: admin-dashboard.php');
exit;
?>
