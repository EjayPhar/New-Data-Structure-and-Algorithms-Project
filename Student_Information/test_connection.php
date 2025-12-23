<?php
include 'login/db.php';
if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error;
} else {
    echo 'Connected successfully';
    $conn->close();
}
?>
