<?php
// Database connection details
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "student_systemm_information";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start Transaction: Ensures both INSERTs succeed or fail together.
$conn->begin_transaction();

try {
    if (isset($_POST['add_student'])) {
        
        // 1. Retrieve and Process Form Data
        $name = $conn->real_escape_string($_POST['name']);
        $student_id = $conn->real_escape_string($_POST['student_id']);
        $email = $conn->real_escape_string($_POST['email']);
        $course = $conn->real_escape_string($_POST['course']);
        $gpa = $conn->real_escape_string($_POST['gpa']);
        $raw_password = $_POST['password'];
        $role = 'student'; // Define the user's role

        // Security: Hashing the Password
        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
        
        
        // --- STEP A: INSERT INTO USERS TABLE (Authentication/Login Data) ---
        
        // Assuming the users table has columns: id (Auto-Increment), email, password, role, created_at, updated_at
        $sql_users = "INSERT INTO users (email, password, role, created_at, updated_at) 
                      VALUES (?, ?, ?, NOW(), NOW())";
        
        $stmt_users = $conn->prepare($sql_users);
        $stmt_users->bind_param("sss", $email, $hashed_password, $role);
        
        if (!$stmt_users->execute()) {
            throw new Exception("Users table insertion failed: " . $stmt_users->error);
        }
        
        // Get the ID of the new row inserted into the users table.
        // This is the link (Foreign Key) for the students table.
        $user_id = $conn->insert_id;
        $stmt_users->close();


        // --- STEP B: INSERT INTO STUDENTS TABLE (Academic Data) ---
        
        // Assuming the students table has columns: 
        // user_id (Foreign Key), name, student_id, course, gpa
        $sql_students = "INSERT INTO students (user_id, name, student_id, course, gpa) 
                         VALUES (?, ?, ?, ?, ?)";
        
        $stmt_students = $conn->prepare($sql_students);
        
        // i = integer (user_id), s = name, s = student_id, s = course, d = gpa (double/decimal)
        $stmt_students->bind_param("isssd", $user_id, $name, $student_id, $course, $gpa);
        
        if (!$stmt_students->execute()) {
            throw new Exception("Students table insertion failed: " . $stmt_students->error);
        }

        $stmt_students->close();

        // If both steps succeeded, commit the transaction
        $conn->commit();
        echo "✅ New student record and user account created successfully! User ID: " . $user_id;
        
    } else {
        echo "Access denied."; 
    }

} catch (Exception $e) {
    // If any error occurred, rollback the changes
    $conn->rollback();
    echo "❌ Transaction failed. No records were saved in the database. Error: " . $e->getMessage();
}

// Close the main connection
$conn->close();

?>