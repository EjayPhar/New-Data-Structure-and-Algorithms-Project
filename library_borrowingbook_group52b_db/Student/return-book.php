<?php
session_start();
include '../login/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrowing_id'])) {
    $borrowing_id = $_POST['borrowing_id'];
    $user_id = $_SESSION['user_id'];
    
    // First, get the borrowing details to calculate penalty
    $check_query = "SELECT due_date, status FROM borrowings WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $borrowing_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $borrowing = $result->fetch_assoc();
    
    if ($borrowing) {
        $penalty_amount = 0;
        
        // Calculate penalty if overdue
        if ($borrowing['status'] === 'overdue') {
            $due_date = new DateTime($borrowing['due_date']);
            $current_date = new DateTime();
            $days_overdue = max(0, $current_date->diff($due_date)->days);
            $weeks_overdue = ceil($days_overdue / 7);
            $penalty_amount = $weeks_overdue * 10;
        }
        
        // Update the borrowing record
        $update_query = "UPDATE borrowings 
                        SET status = 'returned', 
                            return_date = NOW(), 
                            penalty_amount = ? 
                        WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("dii", $penalty_amount, $borrowing_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Book returned successfully!" . 
                ($penalty_amount > 0 ? " Penalty: ₱" . number_format($penalty_amount, 2) : "");
        }
    }
    
    header("Location: borrowing.php");
    exit();
}
?>