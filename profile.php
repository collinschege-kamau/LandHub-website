<?php
session_start();
date_default_timezone_set('Africa/Nairobi');

// 1. Connection
$servername="localhost"; 
$db_username="root"; 
$db_password=""; 
$dbname="land app";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// 2. Access Control - Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    // Ensure this matches the 'name' attribute in your HTML
    $new_phone = trim($_POST['phone_number'] ?? ''); 
    $new_password = $_POST['new_password'];

    // Update Basic Info
    $update = $conn->prepare("UPDATE registrations SET username = ?, phone_number = ? WHERE id = ?");
    $update->bind_param("ssi", $new_username, $new_phone, $user_id);
    
    if ($update->execute()) {
        $_SESSION['username'] = $new_username; 
        $success_msg = "Profile updated successfully!";
    }
    
    // Optional: Update Password if they typed something
    if (!empty($new_password)) {
        $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
        $pass_stmt = $conn->prepare("UPDATE registrations SET password = ? WHERE id = ?");
        $pass_stmt->bind_param("si", $hashed_pass, $user_id);
        $pass_stmt->execute();
        $success_msg = "Profile and password updated successfully!";
    }
}

// 4. Fetch Current Data to display in fields
$stmt = $conn->prepare("SELECT username, email, phone_number FROM registrations WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | LandHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --success: #27ae60; }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h2 { color: var(--primary); text-align: center; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: #555; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font-family: 'Poppins'; }
        
        .btn-update { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-update:hover { background: var(--accent); transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        
        .back-link { display: block; text-align: center; margin-top: 25px; color: var(--accent); text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fa fa-user-circle"></i> Profile Settings</h2>

    <?php if($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label>Email Address (Locked)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f9f9f9; color: #999;">
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <div class="form-group">
            <label>New Password (Leave blank to keep current)</label>
            <input type="password" name="new_password" placeholder="Enter new password">
        </div>

        <button type="submit" class="btn-update">SAVE CHANGES</button>
    </form>

    <a href="View Listings.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Listings Dashboard</a>
    <a href="Company Dashboard.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Seller Dashboard</a>
</div>

</body>
</html>