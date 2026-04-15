<?php
session_start();

// Clear all session data
$_SESSION = array();

// If you want to kill the session cookie too (Highly Recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_unset();
session_destroy();

// Prevent the browser from going "Back" to a logged-in page
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

header("Location: index.php");
exit;
?>
