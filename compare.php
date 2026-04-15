<?php
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
if (empty($ids)) { 
    header("Location: View Listings.php");
    exit(); 
}

$idArray = explode(',', $ids);
$placeholders = implode(',', array_fill(0, count($idArray), '?'));

$servername = "sql112.infinityfree.com";
$username = "if0_41669716";
$password = "v625mgR7min";
$dbname = "if0_41669716_landapp";
$conn = new mysqli($servername, $username, $password, $dbname);

$stmt = $conn->prepare("SELECT * FROM addlistings WHERE id IN ($placeholders)");
$stmt->bind_param(str_repeat('i', count($idArray)), ...$idArray);
$stmt->execute();
$result = $stmt->get_result();

$plots = [];
while($row = $result->fetch_assoc()) {
    $plots[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Plots | LandHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { 
            --primary: #3498db; 
            --success: #27ae60; 
            --danger: #e74c3c;
            --dark: #2c3e50; 
            --bg: #f8f9fa; 
        }

        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--bg); 
            margin: 0; 
            padding: 40px 20px; 
            color: var(--dark);
        }

        .header-section { text-align: center; margin-bottom: 40px; }

        .compare-container {
            max-width: 1100px;
            margin: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .compare-table { width: 100%; border-collapse: collapse; min-width: 600px; }

        .feature-label {
            background: #fdfdfd;
            font-weight: bold;
            color: #7f8c8d;
            text-align: left;
            padding: 20px;
            width: 180px;
            border-bottom: 1px solid #eee;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }

        .plot-data { padding: 20px; text-align: center; border-bottom: 1px solid #eee; border-left: 1px solid #f9f9f9; vertical-align: top; }

        .plot-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        .plot-img:hover { transform: scale(1.02); filter: brightness(0.9); }

        /* Description Box Styling */
        .description-box {
            text-align: left;
            font-size: 0.88rem;
            color: #555;
            line-height: 1.5;
            max-height: 120px;
            overflow-y: auto;
            padding-right: 5px;
        }
        /* Custom Scrollbar for Description */
        .description-box::-webkit-scrollbar { width: 4px; }
        .description-box::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-available { background: #e8f5e9; color: var(--success); }
        .status-sold { background: #ffebee; color: var(--danger); }

        .price-tag { font-size: 1.3rem; color: var(--success); font-weight: bold; }

        .btn-action {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-disabled { background: #ccc; cursor: not-allowed; }

        .image-modal { 
            display: none; 
            position: fixed; 
            z-index: 9999; 
            padding-top: 50px; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.9); 
            backdrop-filter: blur(5px); 
        }
        .modal-content { 
            margin: auto; 
            display: block; 
            max-width: 90%; 
            max-height: 85vh; 
            border-radius: 8px; 
            animation: zoom 0.3s; 
        }
        @keyframes zoom { from {transform:scale(0.5)} to {transform:scale(1)} }
        .close-modal { 
            position: absolute; 
            top: 20px; right: 35px; 
            color: white; 
            font-size: 40px; 
            font-weight: bold; 
            cursor: pointer; 
        }
    </style>
</head>
<body>

    <div id="imageModal" class="image-modal" onclick="this.style.display='none'">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="fullImg">
    </div>

    <div class="header-section">
        <h1><i class="fa-solid fa-code-compare"></i> Compare Your Picks</h1>
        <p>A detailed side-by-side look at your selected land plots</p>
    </div>

    <div class="compare-container">
        <table class="compare-table">
            <tr>
                <td class="feature-label">Preview</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data">
                        <img src="<?= htmlspecialchars($p['image_path']) ?>" 
                             class="plot-img" 
                             onclick="openModal(this.src)">
                    </td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td class="feature-label">Availability</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data">
                        <span class="status-badge <?= ($p['status'] === 'sold') ? 'status-sold' : 'status-available' ?>">
                            <?= ($p['status'] === 'sold') ? '● Sold' : '● Available' ?>
                        </span>
                    </td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td class="feature-label">Title</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data"><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td class="feature-label">Description</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data">
                        <div class="description-box">
                            <?= nl2br(htmlspecialchars($p['description'])) ?>
                        </div>
                    </td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td class="feature-label">Price</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data"><span class="price-tag">Ksh <?= number_format($p['price']) ?></span></td>
                <?php endforeach; ?>
            </tr>

            <tr>
                <td class="feature-label">Contact</td>
                <?php foreach($plots as $p): ?>
                    <td class="plot-data">
                        <?php if($p['status'] === 'sold'): ?>
                            <span class="btn-action btn-disabled">Closed</span>
                        <?php else: ?>
                            <a href="tel:<?= $p['phone_number'] ?>" class="btn-action">Call Seller</a>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </table>
    </div>

    <center style="margin-top: 30px;">
        <a href="View Listings.php" style="text-decoration: none; color: #95a5a6; font-weight: bold;">← Back to Listings</a>
    </center>

    <script>
        function openModal(src) {
            document.getElementById("imageModal").style.display = "block";
            document.getElementById("fullImg").src = src;
        }
    </script>

</body>
</html>