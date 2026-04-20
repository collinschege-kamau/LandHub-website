<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

date_default_timezone_set('Africa/Nairobi');

session_start();
$servername="localhost";
$db_username="root";
$db_password="";
$dbname="land app";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username=trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $otp = rand(100000, 999999);
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // 2. Save OTP to password_resets table
        $stmt = $conn->prepare("REPLACE INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $email, $otp, $expires);
        $stmt->execute();

        // 3. Store email in session for the next step
        $_SESSION['reset_email'] = $email;
        
        // For now, we echo it so you can test without an email server
        echo "<script>alert('Your reset code is: $otp');</script>"; 
        
        header("Location: verify_otp.php");
        exit();
    } else {
        $error = "Username and Email combination not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | LandHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Reuse your Registration CSS here */
        :root { --primary: #2c3e50; --accent: #3498db; }
        body { background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('pexels-altaf-shah-3143825-8314513.jpg'); background-size: cover; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: var(--accent); color: white; border: none; border-radius: 30px; cursor: pointer; font-weight: 600; margin-top: 20px; }
        .error { color: #e74c3c; font-size: 0.8rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Forgot Password?</h2>
        <p>Enter your details to receive a 6-digit code.</p>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit">Send Code</button>
        </form>
    </div>
</body>
</html>