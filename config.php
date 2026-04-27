<?php
// Securely load LandHub configuration
$db_host = "sql112.infinityfree.com";
$db_user = "if0_41669716";
$db_pass = "v625mgR7min";
$db_name = "if0_41669716_landapp";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Server error. Please try again later.");
}

// Set charset to handle any special characters in property descriptions
$conn->set_charset("utf8mb4");
?>
