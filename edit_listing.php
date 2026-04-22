<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Database Connection
$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Security Check - Ensure 'seller' role matches your login system
if(!isset($_SESSION['user_id'])){
    header("Location: Login.php");
    exit();
}

$listingId = $_GET['id'] ?? null;
$companyId = $_SESSION['user_id'];
$statusMsg = "";

// 1. Fetch existing data
if ($listingId) {
    $stmt = $conn->prepare("SELECT * FROM addlistings WHERE id = ? AND company_id = ?");
    $stmt->bind_param("ii", $listingId, $companyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    if (!$data) { die("Listing not found or access denied."); }
}

// 3. Handle the Main Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_listing'])) {
    $title = htmlspecialchars($_POST['title']);
    $price = $_POST['price'];
    $location = htmlspecialchars($_POST['location']);
    $size = htmlspecialchars($_POST['size']);
    $description = htmlspecialchars($_POST['description']);
    $phone = htmlspecialchars($_POST['phone_number']);
    
    $targetFilePath = $data['image_path']; 
    $videoPath = $data['video_path'];

    // Handle Image Update
    if(!empty($_FILES["landImage"]["name"])){
        $targetDir = "uploads/";
        $newPath = $targetDir . time() . "_" . basename($_FILES["landImage"]["name"]);
        if(move_uploaded_file($_FILES["landImage"]["tmp_name"], $newPath)){
            if(!empty($data['image_path']) && file_exists($data['image_path'])) { unlink($data['image_path']); }
            $targetFilePath = $newPath; 
        }
    }

    // Handle Video Update (CRITICAL FIX HERE)
    if(!empty($_FILES["landVideo"]["name"])){
        $videoDir = "uploads/videos/";
        if(!is_dir($videoDir)) mkdir($videoDir, 0755, true);
        
        $newVid = $videoDir . time() . "_" . basename($_FILES["landVideo"]["name"]);
        $vidType = strtolower(pathinfo($newVid, PATHINFO_EXTENSION));
        
        if(in_array($vidType, ['mp4', 'webm', 'ogg'])){
            if(move_uploaded_file($_FILES["landVideo"]["tmp_name"], $newVid)){
                if(!empty($data['video_path']) && file_exists($data['video_path'])){
                    unlink($data['video_path']);
                }
                $videoPath = $newVid; // Set the correct variable
            }
        }
    }

    // --- MANDATORY VIDEO CHECK ---
    // If the video path is empty (meaning no new upload) 
    // AND the database also has no existing video...
    if (empty($videoPath)) {
        $statusMsg = "Error: This listing must have a video. Please upload one.";
    } else {
        // Update Database - Using $videoPath correctly now
        $stmt = $conn->prepare("UPDATE addlistings SET title=?, price=?, location=?, size=?, description=?, phone_number=?, image_path=?, video_path=? WHERE id=? AND company_id=?");
        $stmt->bind_param("sissssssii", $title, $price, $location, $size, $description, $phone, $targetFilePath, $videoPath, $listingId, $companyId);
        
        if($stmt->execute()){
            header("Location: Company Dashboard.php?status=success"); 
            exit();
        } else {
            $statusMsg = "Error updating: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Listing - LandHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .edit-card { max-width: 500px; margin: auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; margin: 8px 0; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
        .current-img, video { width: 100%; border-radius: 5px; margin: 10px 0; border: 1px solid #ddd; }
        .save-btn { background: #27ae60; color: white; border: none; width: 100%; padding: 14px; font-weight: bold; cursor: pointer; border-radius: 5px; margin-top: 10px; }
        .btn-delete-small { background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin-bottom: 15px; }
        label { font-weight: bold; font-size: 0.9rem; color: #34495e; display: block; margin-top: 10px; }
    </style>
</head>
<body>

<div class="edit-card">
    <h2><i class="fa-solid fa-pen-to-square"></i> Edit Property</h2>
    
    <?php if($statusMsg) echo "<p style='color:red;'>$statusMsg</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Property Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required>
        
        <label>Price (Ksh)</label>
        <input type="number" name="price" value="<?= $data['price'] ?>" required>
        
        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($data['location']) ?>" required>

        <label>Property Size</label>
        <input type="text" name="size" value="<?= htmlspecialchars($data['size']) ?>" required>
                
        <label>Description</label>
        <textarea name="description" rows="3"><?= htmlspecialchars($data['description']) ?></textarea>
        
        <label>Phone Number</label>
        <input type="number" name="phone_number" value="<?= htmlspecialchars($data['phone_number']) ?>" required>

        <label>Current Image:</label>
        <img src="<?= htmlspecialchars($data['image_path']) ?>" class="current-img">
        <input type="file" name="landImage" accept="image/*">

        <label>Current Video:</label>
        <?php if(!empty($data['video_path'])): ?>
            <video height="180" controls>
                <source src="<?= htmlspecialchars($data['video_path']) ?>" type="video/mp4">
            </video>
        <?php else: ?>
            <p style="color:gray; font-size:0.8rem;">No video uploaded.</p>
        <?php endif; ?>

        <label>Change/Upload Video (Optional):</label>
        <input type="file" name="landVideo" accept="video/mp4,video/webm">

        <button type="submit" name="update_listing" class="save-btn">SAVE CHANGES</button>
        <a href="Company Dashboard.php" style="display:block; text-align:center; margin-top:15px; text-decoration:none; color:#7f8c8d;">Cancel</a>
    </form>
</div>

</body>
</html>
