<?php
session_start();
// Security: Ensure only logged in users can update
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "land app";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $listing_id = $_POST['id'];
    $new_status = $_POST['status']; // This will be 'sold' or 'available'
    $company_id = $_SESSION['user_id'];

    // Update query - ensures the user can only update THEIR OWN listing
    $stmt = $conn->prepare("UPDATE addlistings SET status = ? WHERE id = ? AND company_id = ?");
    $stmt->bind_param("sii", $new_status, $listing_id, $company_id);

    if ($stmt->execute()) {
        // Redirect back to the dashboard with a success message
        // After the $stmt->execute() success:
        header("Location: view_listings.php?id=" . $listing_id . "&status=updated");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>