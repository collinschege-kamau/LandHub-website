<?php
date_default_timezone_set('Africa/Nairobi');
session_start();

// Database Connection
$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Security check
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$display_username = $_SESSION['reset_username']; // Grab the name we saved

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("UPDATE registrations SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_pass, $email);

    if ($stmt->execute()) {
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();

        session_unset();
        session_destroy();
        
        echo "<script>alert('Password updated for $display_username! Please login.'); window.location='Login.php';</script>";
    } else {
        echo "Error updating password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password | LandHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; }
        body { background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('pexels-altaf-shah-3143825-8314513.jpg'); background-size: cover; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: var(--accent); color: white; border: none; border-radius: 30px; cursor: pointer; font-weight: 600; margin-top: 20px; }
        .error { color: #e74c3c; font-size: 0.8rem; margin-bottom: 10px; }
        /* Password Wrapper */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        #togglePassword {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-20%);
            cursor: pointer;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Reset Password</h2>
        <p>Username: <strong><?php echo $_SESSION['reset_username']; ?></strong></p>
        <form method="POST" autocomplete="off">
            <label style="display: block; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--primary); margin-top: 15px;">
                <i class="fa fa-lock"></i> New Password
            </label>
            
            <div class="password-wrapper" style="position: relative; width: 100%;">
                <input type="password" name="password" id="password" placeholder="Enter New Password" required minlength="6" 
                    style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
                
                <i class="fa-solid fa-eye-slash" id="togglePassword" 
                style="position: absolute; right: 15px; top: 50%; transform: translateY(-20%); cursor: pointer; color: #777;"></i>
            </div>

            <button type="submit">Update Password</button>
        </form>
    </div>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle the eye / eye-slash icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
