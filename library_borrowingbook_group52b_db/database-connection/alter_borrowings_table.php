<?php
include 'login/db_connect.php';

$sql = "ALTER TABLE borrowings ADD COLUMN IF NOT EXISTS penalty DECIMAL(10,2) DEFAULT 0.00";
if ($conn->query($sql) === TRUE) {
    echo "Penalty column added successfully.";
} else {
    echo "Error adding penalty column: " . $conn->error;
}
$conn->close();
?>