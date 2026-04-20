<?php
date_default_timezone_set('Africa/Nairobi');
session_start();

$servername="localhost";
$db_username="root";
$db_password="";
$dbname="land app";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'];
    $email = $_SESSION['reset_email'];

    // Check if OTP is correct and not expired
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND otp_code = ? AND expires_at > NOW()");
    $stmt->bind_param("si", $email, $user_otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['otp_verified'] = true;

        // Fetch username to display on the next page
        $user_stmt = $conn->prepare("SELECT username FROM registrations WHERE email = ?");
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result();
        $user_data = $user_res->fetch_assoc();
        
        $_SESSION['reset_username'] = $user_data['username']; // Save username here
        
        header("Location: reset_new_password.php");
        exit();
    } else {
        echo "Invalid or expired code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP | LandHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        <h2>Verify Identity</h2>
        <p>Enter the code sent to <b><?php echo $_SESSION['reset_email']; ?></b></p>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="number" name="otp" placeholder="Enter 6-digit code" required>
            <button type="submit">Verify Now</button>
        </form>
    </div>
</body>
</html>