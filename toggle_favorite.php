<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "land app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// 1. Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. Validate Listing ID
if ($listing_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid listing ID']);
    exit();
}

// 3. Check if it already exists
$check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
$check->bind_param("ii", $user_id, $listing_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // 4. Already a favorite? REMOVE it.
    // Note: Changed "id = ?" to "user_id = ?" to match your table logic
    $delete = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?");
    $delete->bind_param("ii", $user_id, $listing_id);
    
    if ($delete->execute()) {
        echo json_encode(['status' => 'removed', 'message' => 'Removed from favorites']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
    }
    $delete->close();
} else {
    // 5. Not a favorite? ADD it.
    $insert = $conn->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $listing_id);
    
    if ($insert->execute()) {
        echo json_encode(['status' => 'added', 'message' => 'Added to favorites']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insert failed']);
    }
    $insert->close();
}

$check->close();
$conn->close();
?>