<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: directing.html");
    exit(); // Always call exit() after a redirect to stop the script from running
}
// Database Connection
$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$loggedInCompanyId = $_SESSION['user_id'];
$statusMsg = "";

// --- 2. Handle Form Submission (Adding a Listing) ---
// --- 2. Handle Form Submission (Adding a Listing) ---
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_listing'])){
    // Capture all form data
    $title = htmlspecialchars($_POST['title']);
    $price = $_POST['price'];
    $location = htmlspecialchars($_POST['location']);
    $description = htmlspecialchars($_POST['description']);
    $phone_number = htmlspecialchars($_POST['phone']);
    $size = htmlspecialchars($_POST['size']);
    $lat = !empty($_POST['latitude']) ? $_POST['latitude'] : NULL;
    $lng = !empty($_POST['longitude']) ? $_POST['longitude'] : NULL;
    
    // 1. Image Upload Logic
    $targetDir = "uploads/";
    if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $fileName = basename($_FILES["landImage"]["name"]);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $uploadImageSuccess = false;

    if(!empty($_FILES["landImage"]["name"])){
        $allowTypes = array('jpg','png','jpeg','gif');
        if(in_array(strtolower($fileType), $allowTypes)){
            if(move_uploaded_file($_FILES["landImage"]["tmp_name"], $targetFilePath)){
                $uploadImageSuccess = true;
            } else { $statusMsg = "Error uploading image."; }
        } else { $statusMsg = "Only JPG, JPEG, PNG, & GIF files are allowed."; }
    } else { $statusMsg = "Image is required."; }

    // Mandatory Video Logic with Error Checking
$videoPath = ""; 
$uploadVideoSuccess = false;
$maxVideoSize = 100 * 1024 * 1024; // Increased to 100MB for longer videos

if(isset($_FILES["landVideo"]) && $_FILES["landVideo"]["error"] === 0){
    $videoTargetDir = "uploads/";
    if(!is_dir($videoTargetDir)) mkdir($videoTargetDir, 0755, true);

    $videoFileName = time() . "_" . basename($_FILES["landVideo"]["name"]);
    $videoTargetFilePath = $videoTargetDir . $videoFileName;
    $videoFileType = strtolower(pathinfo($videoTargetFilePath, PATHINFO_EXTENSION));

    if ($_FILES["landVideo"]["size"] > $maxVideoSize) {
        $statusMsg = "Video is too large! Max limit is 100MB.";
    } else {
        $allowVideoTypes = array('mp4', 'webm', 'ogg');
        if(in_array($videoFileType, $allowVideoTypes)){
            if(move_uploaded_file($_FILES["landVideo"]["tmp_name"], $videoTargetFilePath)){
                $videoPath = $videoTargetFilePath;
                $uploadVideoSuccess = true;
            } else { $statusMsg = "Server failed to move the video."; }
        } else { $statusMsg = "Invalid video format."; }
    }
} else {
    // This catches if the file was too big for the server to even process
    $error_code = $_FILES["landVideo"]["error"];
    if($error_code === 1 || $error_code === 2) {
        $statusMsg = "Video failed: File exceeds server limit (php.ini).";
    } else {
        $statusMsg = "Please upload a property video. It is mandatory.";
    }
}
    // 3. Final Database Check (Both must be true)
    if ($uploadImageSuccess && $uploadVideoSuccess) {
        $stmt = $conn->prepare("INSERT INTO addlistings (company_id, title, price, location, size, description, phone_number, image_path, video_path, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssdd", $loggedInCompanyId, $title, $price, $location, $size, $description, $phone_number, $targetFilePath, $videoPath, $lat, $lng);
        
        if($stmt->execute()){
            $statusMsg = "Listing added successfully!";
        } else {
            $statusMsg = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
// After the INSERT into addlistings is successful:
$stmt_notif = $conn->prepare("SELECT id FROM saved_searches WHERE location = ? AND (max_price >= ? OR max_price IS NULL)");
$stmt_notif->bind_param("sd", $location, $price);
$stmt_notif->execute();
$users_to_notify = $stmt_notif->get_result();

while($u = $users_to_notify->fetch_assoc()){
    $msg = "New land listed in " . $location . " for Ksh " . number_format($price);
    $ins = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $ins->bind_param("is", $u['user_id'], $msg);
    $ins->execute();
}

// --- 3. Handle Status Update (Mark Sold/Available) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $listing_id = intval($_POST['id']);
    $new_status = $_POST['status']; // 'sold' or 'available'
    
    // Security: Only update if the listing belongs to the logged-in seller
    $stmt = $conn->prepare("UPDATE addlistings SET status = ? WHERE id = ? AND company_id = ?");
    $stmt->bind_param("sii", $new_status, $listing_id, $loggedInCompanyId);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
        exit();
    } else {
        $statusMsg = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// --- 3. Combined Fetch Data (Profile & Refresh Listings) ---
$companyInfo = null;
$rows = []; // Initialize the array for the table
$totalLikes = 0;

if($loggedInCompanyId){
    // 1. Fetch Profile
    $stmt_c = $conn->prepare("SELECT username, email FROM registrations WHERE id = ?");
    $stmt_c->bind_param("i", $loggedInCompanyId);
    $stmt_c->execute();
    $companyInfo = $stmt_c->get_result()->fetch_assoc();
    $stmt_c->close();

    // 2. FETCH ALL LISTINGS + THEIR LIKES IN ONE QUERY
    // This connects addlistings to favorites and filters by YOUR ID
    $sql = "SELECT l.*, COUNT(f.id) AS total_likes 
            FROM addlistings l 
            LEFT JOIN favorites f ON l.id = f.listing_id 
            WHERE l.company_id = ? 
            GROUP BY l.id 
            ORDER BY l.id DESC";

    $stmt_l = $conn->prepare($sql);
    $stmt_l->bind_param("i", $loggedInCompanyId); // Use loggedInCompanyId, NOT $id
    $stmt_l->execute();
    $listings_result = $stmt_l->get_result();

    // 3. Process results for the Stats and the Table
    while($r = $listings_result->fetch_assoc()) {
        $totalLikes += $r['total_likes'];
        $rows[] = $r;
    }
    $stmt_l->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Dashboard | LandHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root { --primary: #2c3e50; --success: #27ae60; --danger: #e74c3c; --accent: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; overflow-x: hidden; }
        
        /* 1. UPDATED SIDEBAR: Hidden by default (width: 0) */
        .sidebar { 
            width: 0; 
            height: 100vh; 
            background: var(--primary); 
            color: white; 
            padding: 0; /* Changed from 25px to 0 */
            position: fixed; 
            overflow-x: hidden; 
            transition: 0.5s; 
            z-index: 2000;
        }

        /* Container inside sidebar to keep content from squishing */
        .sidebar-inner { width: 210px; padding: 25px; }
        
        /* 2. UPDATED MAIN CONTENT: No margin-left by default */
        .main-content { 
            margin-left: 0; 
            text-align: center; 
            padding: 40px; 
            width: 100%; 
            transition: margin-left 0.5s; 
        }
        
        /* 3. NEW TOGGLE BUTTON STYLE */
        #toggleBtn {
            position: fixed;
            left: 15px;
            top: 15px;
            z-index: 2100;
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.5s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* ... Keep your existing Card, Map, and Table styles ... */
        .card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        #map { height: 450px; width: 100%; border-radius: 8px; margin: 20px 0; border: 2px solid #ccc; position: relative; }
        /* (Add back the rest of your table and badge styles here) */
    </style>
</head>
<body>

<button id="toggleBtn" onclick="toggleNav()"> > </button>

<div id="mySidebar" class="sidebar">
    <div class="sidebar-inner">
        <h2 style="color: var(--success);"><i class="fa fa-mountain"></i> LandHub</h2>
        <div style="margin-top: 30px;">
            <small style="color: #95a5a6;">SELLER PROFILE</small>
            <p><strong>NAME: <?= htmlspecialchars($companyInfo['username'] ?? 'User') ?></strong></p>
            <p style="font-size: 0.8rem; opacity: 0.8;">EMAIL: <?= htmlspecialchars($companyInfo['email'] ?? '') ?></p>
            <a href="profile.php" class="seller-profile-btn" style="color:white; text-decoration:none;">
                <i class="fa-solid fa-address-card"></i> Manage Profile
            </a>
        </div>
        <hr style="opacity: 0.1; margin: 20px 0;">
        <a href="index.php" class="btn" style="background: #18d81b; text-decoration: none; text-align: center; display: block; margin-bottom: 15px; color:white; padding:10px; border-radius:5px;">🏠 Home</a>
        <a href="Logout.php" class="btn" style="color: white; background: #ef0505; text-decoration: none; text-align: center; display: block; margin-bottom: 15px; padding:10px; border-radius:5px;"><i class="fa fa-sign-out"></i> Logout</a>
    </div>
</div>

<div id="mainContent" class="main-content">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div>
                <h2 style="margin: 0; color: #2c3e50;">Seller Dashboard</h2>
                <p style="margin: 5px 0 0; color: #777;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            </div>
        </div>
    </header>

    <div class="stats-grid">
        </div>
</div>

<script>
    // NEW JAVASCRIPT FOR TOGGLE
    let sidebarOpen = false;

    function toggleNav() {
        const sidebar = document.getElementById("mySidebar");
        const main = document.getElementById("mainContent");
        const btn = document.getElementById("toggleBtn");

        if (!sidebarOpen) {
            sidebar.style.width = "260px";
            main.style.marginLeft = "260px";
            btn.style.left = "275px"; // Move button with menu
            btn.innerHTML = " < ";   // Change icon
            sidebarOpen = true;
            
            // Fix Leaflet map grey box after resizing
            setTimeout(() => { map.invalidateSize(); }, 500);
        } else {
            sidebar.style.width = "0";
            main.style.marginLeft = "0";
            btn.style.left = "15px";  // Reset button
            btn.innerHTML = " > ";   // Change back icon
            sidebarOpen = false;
            
            setTimeout(() => { map.invalidateSize(); }, 500);
        }
    }

    // ... Keep your existing Leaflet map and alert scripts below ...
</script>

</body>
</html>
