<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Database Connection
$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
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
