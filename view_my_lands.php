<?php
session_start();

$servername="sql112.infinityfree.com";
$db_username="if0_41669716";
$db_password="v625mgR7min";
$dbname="if0_41669716_landapp";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if(!isset($_SESSION['user_id']) ?? $session['company_id']) { header("Location: Login.php"); exit(); }

// Fetch only THIS company's lands, newest first
$query = "SELECT * FROM addlistings WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Published Lands</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com" />
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com"></script>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .land-card { background: white; margin-bottom: 20px; display: flex; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .land-img { width: 250px; height: 180px; object-fit: cover; }
        .land-details { padding: 20px; flex: 1; }
        .price { color: #27ae60; font-size: 1.4rem; font-weight: bold; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #34495e; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <a href="Company Dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> ← Back to Dashboard</a>
        <h1>My Land Listings</h1>

        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="land-card">
                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="land-img">
                    <div class="land-details">
                        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                        <p class="price">Ksh <?php echo number_format($row['price']); ?></p>
                        <p class="size"> <?php echo htmlspecialchars($row['size']); ?></p>
                        <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['location']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['phone_number']); ?></p>
                    </div>
                </div>
                <!-- Inside your while loop in view_my_lands.php -->
                <?php if(!empty($row['latitude']) && !empty($row['longitude'])): ?>
                    <div id="displayMap_<?php echo $row['id']; ?>" style="height: 200px; margin-top: 10px; border-radius: 5px;"></div>
                    <script>
                        var displayMap = L.map('displayMap_<?php echo $row['id']; ?>').setView([<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(displayMap);
                        L.marker([<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>]).addTo(displayMap)
                            .bindPopup("Exact Location").openPopup();
                    </script>
                <?php endif; ?>

            <?php endwhile; ?>

        <?php else: ?>
            <p>You haven't posted any lands yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
