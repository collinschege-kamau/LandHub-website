<?php
session_start();

$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

$error_message="";

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $username_input=$_POST['username'];
    $password_input=$_POST['password'];
    $role_input=$_POST['role'];

    // Prepare statement to prevent SQL Injection
    $stmt=$conn->prepare("SELECT id, password, role FROM registrations WHERE username=? AND role=?");
    $stmt->bind_param("ss", $username_input, $role_input);
    $stmt->execute();
    $result=$stmt->get_result();

    if($row = $result->fetch_assoc()){
        // Verify the password against the hashed version in the database
        if(password_verify($password_input, $row['password'])){
            // SUCCESSFUL LOGIN: Set session variables
            $_SESSION['user_id']=$row['id'];
            $_SESSION['username']=$username_input;
            $_SESSION['role']=$role_input;
            
            // Redirect based on selected role
            if($role_input === 'seller'){
                header("Location: Company Dashboard.php");
            } else {
                header("Location: View Listings.php");
            }
            exit();

        } else {
            $error_message="Invalid username, password or role.";
        }
    } else {
        $error_message="Invalid username, password or role.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LandHub Kenya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --accent: #3498db;
            --white: #ffffff;
            --danger: #e74c3c;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('pexels-altaf-shah-3143825-8314513.jpg');
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

        .login-card {
            background-color: rgba(255, 255, 255, 0.98);
            width: 90%;
            max-width: 420px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            text-align: center;
        }

        .login-card h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-card p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 30px;
        }

        form {
            text-align: left;
        }

        label { 
            display: block; 
            margin-top: 15px; 
            font-weight: 600; 
            color: var(--primary);
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="password"],
        select {
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

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            background-color: #fff;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        #togglePassword {
            position: absolute;
            right: 15px;
            top: 22px; /* Centered inside password input */
            cursor: pointer;
            color: #777;
            z-index: 5;
        }

        .forgot-container {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        input[type="submit"] {
            width: 100%;
            padding: 15px;
            background-color: var(--success);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 25px;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        input[type="submit"]:hover {
            background-color: #219150;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .error-message {
            color: #721c24;
            background: #f8d7da;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .card-links {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .card-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .card-links a:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>Welcome Back</h2>
        <p>Login to your LandHub account</p>

        <?php if(!empty($error_message)): ?>
            <div class="error-message">
                <i class="fa fa-circle-exclamation"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;">
                <i class="fa fa-check-circle"></i> Success! Your password has been updated.
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <label><i class="fa fa-user"></i> Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>

            <label><i class="fa fa-lock"></i> Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="••••••••" required>
                <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
                
                <div class="forgot-container">
                    <a href="forget_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </div>

            <label><i class="fa fa-users-gear"></i> Select Role</label>
            <select name="role" required>
                <option value="seller">Seller / Company</option>
                <option value="buyer">Buyer / Client</option>
            </select>

            <input type="submit" value="Login to LandHub">
        </form>

        <div class="card-links">
            <a href="index.php"><i class="fa fa-arrow-left"></i> BACK HOME</a>
            <a href="directing.html">EXIT <i class="fa fa-sign-out"></i></a>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle the eye icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>