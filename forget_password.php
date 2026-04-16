<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

date_default_timezone_set('Africa/Nairobi');

$servername = "sql112.infinityfree.com";
$db_username = "if0_41669716";
$db_password = "v625mgR7min";
$dbname = "if0_41669716_landapp";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Cleanup: Delete expired tokens
$conn->query("UPDATE registrations SET reset_token = NULL, reset_expiry = NULL WHERE reset_expiry < NOW()");

$message = "";
$message_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']); // Get the username from the form

    // 1. Check if both exists
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE email = ? AND username= ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        $update = $conn->prepare("UPDATE registrations SET reset_token=?, reset_expiry=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        
        if ($update->execute()) {
            $mail = new PHPMailer(true);
            try {
                // Connection Settings
                $mail->isSMTP();                                            
                $mail->Host       = 'smtp.gmail.com';                     
                $mail->SMTPAuth   = true;                                   
                $mail->Username   = 'your-email@gmail.com'; 
                $mail->Password   = 'eakj fsyh rrvg smli'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
                $mail->Port       = 587;                                    

                $mail->setFrom('no-reply@landhub.co.ke', 'LandHub');
                $mail->addAddress($email); 

                $mail->isHTML(true);                                  
                $mail->Subject = 'Password Reset Request - LandHub';
                
                $reset_url = "http://localhost/land_app/reset_password.php?token=" . $token;
                $mail->Body = "Hello, <br><br> Click the link below to reset your password: <br> 
                               <a href='$reset_url'>$reset_url</a> <br><br> Link expires in 30 mins.";

                $mail->send();
                $message = "A reset link has been sent to your email.";
                $message_class = "success-message";

            } catch (Exception $e) {
                // If Email fails, we show the simulation link as a backup
                $message = "Instructions sent! <br> <a href='reset_password.php?token=" . $token . "' style='color:var(--accent); font-weight:bold;'>Click here to reset (Simulation)</a>";
                $message_class = "success-message"; 
            }
        }
    } else {
        // This runs ONLY if the email is NOT found in the database
        $message = "Account details do not match our records.";
        $message_class = "error-message";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | LandHub Kenya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2c3e50; --success: #27ae60; --accent: #3498db; --white: #ffffff; }
        
        .reset-card {
            background-color: rgba(255, 255, 255, 0.98); width: 90%; max-width: 400px; padding: 40px;
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.4); text-align: center;
        }
        h2 { color: var(--primary); margin-bottom: 10px; }
        p { font-size: 0.85rem; color: #666; margin-bottom: 25px; }
        label { display: block; text-align: left; font-weight: 600; font-size: 0.8rem; margin-top: 15px; color: var(--primary); }
        input[type="email"] {
            width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box;
        }
        button {
            width: 100%; padding: 14px; background-color: var(--success); color: white; border: none;
            border-radius: 30px; font-weight: 600; margin-top: 25px; cursor: pointer; transition: 0.3s;
        }
        button:hover { background-color: #219150; transform: translateY(-2px); }
        .card-links { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .card-links a { color: var(--accent); text-decoration: none; font-size: 0.8rem; font-weight: 600; }
        .error-message { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.8rem; }
        .success-message { color: #155724; background: #d4edda; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2>Reset Password</h2>
        <p>Enter your email and we'll send you a link to get back into your account.</p>

        <?php if($message): ?>
            <div class="<?php echo $message_class; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label><i class="fa fa-user"></i> Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>

            <label><i class="fa fa-envelope"></i> Registered Email</label>
            <input type="email" name="email" placeholder="e.g. name@mail.com" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <div class="card-links">
            <a href="Login.php"><i class="fa fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
