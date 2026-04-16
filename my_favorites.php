<?php
session_start();

$servername = "sql112.infinityfree.com";
$username = "if0_41669716";
$password = "v625mgR7min";
$dbname = "if0_41669716_landapp";

$conn = new mysqli($servername, $username, $password, $dbname);

if (!isset($_SESSION['user_id'])) {
    header("Location: directing.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Join query to get listing details for favorites
$sql = "SELECT l.* FROM addlistings l 
        JOIN favorites f ON l.id = f.listing_id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LandHub | My Saved Plots</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root { --sold-red: #e74c3c; --available-green: #27ae60; --primary-blue: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .back-link { text-decoration: none; color: var(--available-green); font-weight: bold; margin-bottom: 20px; display: inline-block; }
        
        .fav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .fav-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .fav-header { display: flex; border-bottom: 1px solid #eee; }
        .fav-img { width: 120px; height: 120px; object-fit: cover; }
        .fav-info { padding: 15px; flex-grow: 1; position: relative; }
        
        .fav-title { margin: 0; font-size: 1.1rem; color: #333; }
        .fav-price { color: var(--available-green); font-weight: bold; margin: 5px 0; }
        
        /* Buttons Styling */
        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px; background: #fafafa; }
        .action-btn { text-align: center; padding: 10px; border-radius: 6px; text-decoration: none; color: white; font-weight: bold; font-size: 0.85rem; }
        .btn-wa { background: #25d366; }
        .btn-call { background: var(--primary-blue); }
        .btn-disabled { background: #ccc; cursor: not-allowed; border: none; }
        
        .remove-link { display: block; text-align: center; padding: 10px; color: #e74c3c; text-decoration: none; font-size: 0.8rem; border-top: 1px solid #eee; }
        .is-sold { filter: grayscale(0.5); opacity: 0.8; }
        .empty-state { grid-column: 1 / -1; /* Spans across the grid */text-align: center;padding: 60px 20px;background: white;border-radius: 15px;border: 2px dashed #ddd; /* Gives it a "placeholder" feel */margin-top: 20px; }

        .empty-icon { font-size: 4rem;color: #ffdada; /* Very light red */margin-bottom: 20px;}

        .empty-state h2 { color: #333;margin-bottom: 10px;}

        .empty-state p {color: #777;max-width: 400px;margin: 0 auto 25px;line-height: 1.6;}

        .btn-explore {
            display: inline-block;
            padding: 12px 30px;
            background: var(--available-green);
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 30px;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.2);
        }

        .btn-explore:hover {
            background: #219150;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <a href="View Listings.php" class="back-link">← Back to Listings</a>
    <h1>❤️ My Saved Plots</h1>

    <div class="fav-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $isSold = ($row['status'] === 'sold');
            ?>
                <div class="fav-card <?php echo $isSold ? 'is-sold' : ''; ?>" id="card-<?php echo $row['id']; ?>">
                    <div class="fav-header">
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="fav-img">
                        <div class="fav-info">
                            <h3 class="fav-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="fav-price">Ksh <?php echo number_format($row['price']); ?></p>
                            <p class="fav-size"><?php echo htmlspecialchars($row['size']); ?></p>
                            <p style="font-size: 0.8rem; color: #777;">📍 <?php echo htmlspecialchars($row['location']); ?></p>
                        </div>
                    </div>

                    <div class="btn-group">
                        <?php if($isSold): ?>
                            <button class="action-btn btn-disabled" style="grid-column: span 2; width: 100%;">
                                <i class="fa-solid fa-lock"></i> Listing Closed
                            </button>
                        <?php else: ?>
                            <a href="tel:<?php echo $row['phone_number']; ?>" class="action-btn btn-call">
                                <i class="fa-solid fa-phone"></i> Call
                            </a>
                            <a href="https://wa.me/<?php echo $row['phone_number']; ?>?text=Inquiry:<?php echo urlencode($row['title']); ?>" 
                               target="_blank" class="action-btn btn-wa">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>

                    <a href="javascript:void(0)" class="remove-link" onclick="removeFav(<?php echo $row['id']; ?>)">
                        <i class="fa-solid fa-trash"></i> Remove from Favorites
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h2>No saved plots yet</h2>
                <p>When you find a piece of land you love, click the heart icon to save it here for later.</p>
                <a href="View Listings.php" class="btn-explore">Start Exploring</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function removeFav(listingId) {
    if(confirm("Are you sure?")) {
        fetch('toggle_favorite.php?id=' + listingId)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'removed') {
                document.getElementById('card-' + listingId).remove();
            }
        });
    }
}
</script>

</body>
</html>
