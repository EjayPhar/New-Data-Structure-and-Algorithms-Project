<?php
include 'login/db.php';

echo "Checking database content...\n\n";

// Check user table
$sql = "SELECT * FROM user";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "Users:\n";
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - Email: " . $row["email"]. " - Role: " . $row["role"]. "\n";
    }
} else {
    echo "No users found\n";
}
echo "\n";

// Check students table
$sql = "SELECT * FROM students";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "Students:\n";
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - User ID: " . $row["user_id"]. " - Name: " . $row["name"]. " - Student ID: " . $row["student_id"]. "\n";
    }
} else {
    echo "No students found\n";
}

$conn->close();
?>
