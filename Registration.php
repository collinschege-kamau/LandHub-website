<?php
session_start();
// If someone is at the registration page, they shouldn't be logged in as anyone else
if (isset($_SESSION['user_id'])) {
    session_unset(); 
}

//Error checking
error_reporting(E_ALL);
ini_set('display_errors',1);

$servername="localhost";
$db_username="root";
$db_password="";
$dbname="land app";

$conn=new mysqli($servername,$db_username,$db_password,$dbname);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$msg = "";
$msg_class = "";

//Handle form submission
if ($_SERVER["REQUEST_METHOD"]=="POST"){
    $username=trim($_POST['username']);
    $password=$_POST['password'];
    $email=filter_var(trim($_POST['email']),FILTER_SANITIZE_EMAIL);
    $phone_number=$_POST['phone_number'];
    $role=$_POST['role'];

    $errors=[];

    if(empty($username)||empty($password)||empty($role)||empty($phone_number)){
        $errors = "All fields are required.";
    }
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if (!preg_match("/^(07|01)\d{8}$/", $phone_number)) {
        $errors[] = "Invalid phone number format. Use 07... or 01...";
    }
    // 3. If no basic errors, check for Duplicate Username
    if (empty($errors)) {
        //Check for duplicate username
        $check=$conn->prepare("SELECT id FROM registrations WHERE username = ?");
        $check->bind_param("s",$username);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $errors [] = "Username already exists. Please choose another.";  
        }
        $check->close();
    }

    // 4. Final Processing (If no errors at all)
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO registrations (username, password, email, phone_number, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashedPassword, $email, $phone_number, $role);

        if ($stmt->execute()) {
            // 1. Get the ID of the user who JUST registered
            $new_user_id = $conn->insert_id; 

            // 2. Clear any old session (like Collins's data)
            session_unset();

            //3.set the new session variables
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            // Successful redirect
            if ($role == 'seller') {
                header("Location: Company Dashboard.php");
            } else if($role == 'buyer') {
                header("Location: View Listings.php");
            }
            exit();
        } else {
            $msg = "Registration failed: " . $stmt->error;
            $msg_class = "error-message";
        }
        $stmt->close();
    } else {
        // Combine all validation errors into one message string
        $msg = implode("<br>", $errors);
        $msg_class = "error-message";
    }
}
?>
         
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LandHub Kenya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --accent: #3498db;
            --white: #ffffff;
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

        /* --- THE WHITE CARD --- */
        .register-card {
            background-color: rgba(255, 255, 255, 0.98);
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            text-align: center;
            margin: 20px;
        }

        .register-card h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .register-card p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }

        /* --- Form Styling --- */
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

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px 15px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            font-size: 0.95rem;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            background-color: #fff;
        }

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

        /* Register Button */
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background-color: var(--accent);
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
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        /* Footer Links */
        .card-links {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .card-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .card-links a:hover {
            color: var(--accent);
        }

        .error-message {
            color: #721c24;
            background: #f8d7da;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h2>Join LandHub</h2>
        <p>Create an account to get started</p>
        
        <?php if(!empty($msg)): ?>
            <div class="<?php echo $msg_class; ?>">
                <i class="fa fa-exclamation-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <label><i class="fa fa-user"></i> Username</label>
            <input type="text" name="username" placeholder="Choose a username" required>

            <label><i class="fa fa-lock"></i> Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Create a password" required>
                <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
            </div>

            <label><i class="fa fa-envelope"></i> Email Address</label>
            <input type="email" name="email" required placeholder="example@mail.com">

            <label><i class="fa fa-phone"></i> Phone Number</label>
            <input type="text" name="phone_number" pattern="^(07|01)\d{8}$" title="Please enter a valid 10-digit Kenyan number starting with 07 or 01" required>

            <label><i class="fa fa-users"></i> I am a:</label>
            <select name="role" required>
                <option value="buyer">Buyer (Looking for land)</option>
                <option value="seller">Seller (Offering land)</option>
            </select>

            <button type="submit">Create Account</button>
        </form>

        <div class="card-links">
            <a href="directing.html"><i class="fa fa-arrow-left"></i> BACK</a>
            <a href="Login.php">ALREADY REGISTERED?</a>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>