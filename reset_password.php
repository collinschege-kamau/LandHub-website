<?php
date_default_timezone_set('Africa/Nairobi');
session_start();

$servername="sql112.infinityfree.com"; 
$db_username="if0_41669716"; 
$db_password="v625mgR7min"; 
$dbname="if0_41669716_landapp";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';
$valid_token = false;

// Verify Token
if ($token) {
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { $valid_token = true; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $typed_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Prepare the update
    $update = $conn->prepare("UPDATE registrations SET password=?, reset_token=NULL, reset_expiry=NULL WHERE reset_token= ? < NOW() AND username= ? AND email= ?");
    $update->bind_param("ssss", $new_pass, $token, $typed_username, $email);
    
    if ($update->execute()) {
        // --- SECURITY HARDENING ---
        session_unset();
        session_destroy(); // Clears any old logged-in sessions
        
        // Redirect to login with the success flag
        header("Location: Login.php?msg=updated");
        exit();
    }else {
        // If the username typed doesn't match the one tied to the token
        $message = "Username does not match this reset request.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | LandHub Kenya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --accent: #3498db;
            --danger: #e74c3c;
            --white: #ffffff;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('pexels-altaf-shah-3143825-8314513.jpg'); /* Using your land background */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* --- THE WHITE CARD --- */
        .reset-card {
            background-color: rgba(255, 255, 255, 0.98);
            width: 90%;
            max-width: 400px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            text-align: center;
        }

        h2 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* --- Form Elements --- */
        form {
            text-align: left;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.85rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
            font-size: 1rem;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: 0.3s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
            background-color: #fff;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.2);
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 30px;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button[type="submit"]:hover {
            background-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* --- Error/Expired Styling --- */
        .error-container i {
            font-size: 3rem;
            color: var(--danger);
            margin-bottom: 15px;
        }

        .btn-outline {
            display: inline-block;
            margin-top: 20px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 2px solid var(--accent);
            padding: 10px 20px;
            border-radius: 25px;
            transition: 0.3s;
        }

        .btn-outline:hover {
            background-color: var(--accent);
            color: white;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <?php if ($valid_token): ?>
            <h2>New Password</h2>
            <p>Please enter your new secure password.</p>
            <form method="POST" autocomplete="off">
                <label>Confirm Username</label>
                <input type="text" name="username" placeholder="Your username" required>
                
                <label><i class="fa fa-envelope"></i> Registered Email</label>
                <input type="email" name="email" placeholder="e.g. name@mail.com" required>

                <label>New Password</label>
                <input type="password" name="password" placeholder="********" autocomplete="new-password" required>
                
                <button type="submit">Update Password</button>
            </form>
        <?php else: ?>
            <div class="error-container">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h2 style="color:var(--danger)">Link Expired</h2>
                <p>This reset link is no longer valid. Please request a new one.</p>
                <a href="forgot_password.php" class="btn-outline">Request New Link</a>
            </div>
             <?php endif; ?>
    </div>
</body>
</html>