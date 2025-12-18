<?php
// Migration script to add penalty_amount column to borrowings table
include '../login/db_connect.php';

// Check if column already exists
$check_sql = "SHOW COLUMNS FROM borrowings LIKE 'penalty_amount'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Add penalty_amount column
    $alter_sql = "ALTER TABLE borrowings ADD COLUMN penalty_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER status";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "penalty_amount column added successfully to borrowings table.<br>";
        
        // Update existing overdue records with calculated penalty (10 pesos per day)
        $update_sql = "UPDATE borrowings 
                       SET penalty_amount = GREATEST(0, DATEDIFF(CURDATE(), due_date)) * 10.00 
                       WHERE status = 'overdue' AND return_date IS NULL";
        
        if ($conn->query($update_sql) === TRUE) {
            echo "Existing overdue records updated with penalty amounts.<br>";
        } else {
            echo "Error updating penalties: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "penalty_amount column already exists in borrowings table.<br>";
}

$conn->close();
echo "Migration completed.";
?>