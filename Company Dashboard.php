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
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        
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
        .sidebar-inner { width: 210px; padding: 25px; }
        .main-content { margin-left: 0;text-align: center; padding: 40px; width: calc(100% - 260px);transition: margin-left 0.5s; }
        
        .card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        input, textarea { width: 100%; margin: 10px 0; padding: 12px; border: 1px solid #ddd; border-radius: 6px; }
        
        #map { height: 450px; width: 100%; border-radius: 8px; margin: 20px 0; border: 2px solid #ccc; position: relative; background: #e0e0e0;} /* Grey fallback color */border-radius: 8px;  margin: 20px 0; border: 2px solid #ccc;position: relative; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #777; }
        td { padding: 15px; border-top: 1px solid #eee; }
        
        .btn-action { padding: 8px 15px; border-radius: 5px; text-decoration: none; color: white; font-size: 13px; font-weight: bold; display: inline-block; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-available { background: #e8f5e9; color: var(--success); }
        .status-sold { background: #ffebee; color: var(--danger); }
        .seller-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #2c3e50; /* Dark professional blue */
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid #34495e;
        }

        .seller-profile-btn:hover {
            background-color: #3498db; /* Brightens to your accent blue */
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            transform: translateY(-1px);
        }

        .seller-profile-btn i {
            font-size: 1rem;
        }
        .btn:hover {
            color: white;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            transform: translateY(-1px);
        }
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
        /* This applies to screens smaller than 768px (most phones) */
    @media (max-width: 768px) {
        .sidebar {
            position: relative; /* Stops it from floating over content */
            width: 100%;        /* Takes full width */
            height: auto;
        }
    
        .main-content {
            margin-left: 0;    /* Removes the gap meant for the desktop sidebar */
            width: 100%;
            padding: 10px;
        }
    
        /* Make your input boxes and dashboard cards stack vertically */
        .stats-container, .form-group {
            display: block;
            width: 100%;
        }
    }

    </style>
</head>
<body>

<button id="toggleBtn" onclick="toggleNav()"> > </button>

<div id="mySidebar" class="sidebar">
    <div class="sidebar">
        <h2 style="color: var(--success);"><i class="fa fa-mountain"></i> LandHub</h2>
        <div style="margin-top: 30px;">
            <small style="color: #95a5a6;">SELLER PROFILE</small>
            <p><strong>NAME:<?= htmlspecialchars($companyInfo['username'] ?? 'User') ?></strong></p>
            <p style="font-size: 0.8rem; opacity: 0.8;">EMAIL:<?= htmlspecialchars($companyInfo['email'] ?? '') ?></p>
            <a href="profile.php" class="seller-profile-btn">
                <i class="fa-solid fa-address-card"></i> Manage Profile
            </a>
        </div>
            <hr style="opacity: 0.1; margin: 20px 0;">
            <a href="index.php" class="btn" style="background: #18d81b; text-decoration: none; text-align: center; display: block; margin-bottom: 15px;">🏠 Home</a>
            <a href="private sellers upload.html" class="btn" style="background: #cb9d1f; text-decoration: none; text-align: center;display: block; margin-bottom: 15px;">⬅ Back</a>
            <a href="View Listings.php" class="btn" style="color: white; background: #5711e3; text-decoration: none; text-align: center; display: block; margin-bottom: 15px;"><i class="fa fa-eye"></i> View Public Site</a>
            <a href="Logout.php" class="btn" style="color: black; background: #ef0505;  text-decoration: none; text-align: center; display: block; margin-bottom: 15px;"><i class="fa fa-sign-out"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div>
                <h2 style="margin: 0; color: #2c3e50;">Seller Dashboard</h2>
                <p style="margin: 5px 0 0; color: #777;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            </div>
        </div>
        <p>Manage your properties and track buyer interest.</p>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <h3 style="margin:0; font-size: 0.9rem; color: #7f8c8d;">YOUR LISTINGS</h3>
            <p style="font-size: 2rem; margin: 10px 0 0;"><?= $listings_result->num_rows ?></p>
        </div>
        <div class="stat-card">
            <h3 style="margin:0; font-size: 0.9rem; color: #7f8c8d;">TOTAL INTEREST</h3>
            <p style="font-size: 2rem; margin: 10px 0 0; color: var(--danger);">
                <i class="fa fa-heart"></i> 
                <?=$totalLikes ?>
            </p>
        </div>
        
    </div>

    <!-- Upload Form -->
    <div class="card">
        <h3><i class="fa fa-plus-circle"></i> List New Property</h3>
        <?php if($statusMsg) echo "<p style='color:var(--success); font-weight:bold;'>$statusMsg</p>"; ?>
        
        <form action="" method="POST" enctype="multipart/form-data">
           
            <label><strong>Title:</strong></label>
            <input type="text" name="title" placeholder="Title (e.g. Plot in Kitengela)" required>
        
            <label><strong>Land price:</strong></label>
            <input type="number" name="price" min="1000" max="1000000000" placeholder="Price" required>    
        
            <label><strong>Land size:</strong></label>
            <input type="text" name="size" placeholder="Land Size(e.g. 50x100) " required>
        
            <label><strong>Location:</strong></label>
            <input type="text" name="location" placeholder="Location (e.g. Nairobi, Juja)" required>
        
            <label><strong>Land description:</strong></label>
            <textarea name="description" rows="3" placeholder="Description..."></textarea>
            
            <label><strong>Phone numer:</strong></label>
            <input type="text" name="phone" pattern="^(07|01)\d{8}$" title="Please enter a valid 10-digit Kenyan number starting with 07 or 01" placeholder="Phone number" required>

            <label><i class="fa fa-image"></i><strong>Property Image:</strong></label>
            <input type="file" name="landImage" accept="image/*" required>

            <label><i class="fa fa-video"></i> <strong>Upload Property Video (Required):</strong></label>
            <input type="file" name="landVideo" accept="video/mp4,video/webm,video/ogg" required>

            <label><strong>Select Location on Map:</strong></label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="map-search" placeholder="Search Town in Kenya...">
                <button type="button" onclick="searchLocation()" style="background:var(--accent); color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">Search</button>
            </div>

            <div id="map"></div>
            
            <input type="hidden" name="latitude" id="lat">
            <input type="hidden" name="longitude" id="lng">

            <button type="submit" name="submit_listing" style="background: var(--success); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer;">
                🚀 POST LISTING
            </button>
        </form>
    </div>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div id="success-alert" style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #c3e6cb; font-weight: bold; text-align: center;">
            ✅ Changes saved successfully!
        </div>
    <?php endif; ?>
    <!--Listings table-->
    <div class="card" style="padding: 0;">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $row): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['title']) ?></strong><br>
                        <small><?= htmlspecialchars($row['location']) ?></small>
                        <br><small><?= htmlspecialchars($row['size']) ?></small>
                    </td>
                    <td>Ksh <?= number_format($row['price']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $row['status'] ?>"><?= $row['status'] ?></span>
                    </td>
                    <td>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="status" value="<?= $row['status'] == 'available' ? 'sold' : 'available' ?>">
                            <button type="submit" name="change_status" class="btn-action" style="background: var(--primary); border:none; cursor:pointer;">
                                Mark <?= $row['status'] == 'available' ? 'Sold' : 'Available' ?>
                            </button>
                        </form>
                        <!-- 1. VIEW (Green like Post Land) -->
                        <a href="view_my_lands.php" class="btn-action" 
                        style="background: #25d366; color: white; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold; font-size: 14px; border: none;">
                            View
                        </a>

                        <!-- 2. EDIT (Blue) -->
                        <a href="edit_listing.php?id=<?php echo $row['id']; ?>" class="btn-action" 
                        style="background: #3498db; color: white; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold; font-size: 14px; border: none;">
                            Edit
                        </a>

                        <!-- 3. DELETE (Red) -->
                        <a href="delete_listing.php?id=<?php echo $row['id']; ?>" class="btn-action" 
                        style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold; font-size: 14px; border: none;"
                        onclick="return confirm('Are you sure?')">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
    // 1. Initialize Map
    var map = L.map('map').setView([-1.286389, 36.817223], 13); // Nairobi Center

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker;

    // 2. Click to Pin
    map.on('click', function(e) {
        if (marker) { map.removeLayer(marker); }
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById('lat').value = e.latlng.lat;
        document.getElementById('lng').value = e.latlng.lng;
    });

    // 3. Search Location
    function searchLocation() {
        const query = document.getElementById('map-search').value;
        if (query.length < 3) return alert("Type a town name.");

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ke&limit=1`;

        fetch(url, {
            headers: { "User-Agent": "LandHub-User-Agent" }
        })
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                const lat = data[0].lat;
                const lon = data[0].lon;
                map.setView([lat, lon], 14);
                if (marker) { map.removeLayer(marker); }
                marker = L.marker([lat, lon]).addTo(map);
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lon;
            } else {
                alert("Location not found. Try clicking on the map manually.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Search blocked by browser security. \n\nFIX: Please just click the correct spot on the map to pin it!");
        });
    }

    // 4. THE FIX FOR THE GREY BOX:
    // This forces the map to redraw itself after the page finishes loading.
    window.addEventListener('load', function() {
        setTimeout(function() {
            map.invalidateSize();
            // Trigger a fake window resize to wake up the map tiles
            window.dispatchEvent(new Event('resize'));
        }, 800);
    });

    window.addEventListener('load', function() {
        // 1. Find the alert element
        const alert = document.getElementById('success-alert');
        
        if (alert) {
            // 2. Wait 3 seconds, then hide the message
            setTimeout(function() {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                
                // Remove from layout after fade
                setTimeout(() => alert.style.display = "none", 500);
                
                // 3. CLEAN THE URL: This removes "?status=success" from the browser bar
                // This prevents the message from coming back on a manual refresh
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                
            }, 7000); // 3000ms = 3 seconds
        }
    });
</script>

</body>
</html>

</html>
