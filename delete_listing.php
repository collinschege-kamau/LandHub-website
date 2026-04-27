<?php
session_start();

// Database Connection
require_once 'config.php';

// Ensure only the logged-in company can delete
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller'){
    exit("Unauthorized");
}

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $company_id = $_SESSION['user_id'];

    // Secure delete: matches ID AND Company ID so users can't delete others' posts
    $stmt = $conn->prepare("DELETE FROM addlistings WHERE id = ? AND company_id = ?");
    $stmt->bind_param("ii", $id, $company_id);
    
    if($stmt->execute()){
        header("Location: Company Dashboard.php?msg=DeletedSuccessfully");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
