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

// 1. DATA PREPARATION: Fetch Favorites
$userFavorites = [];
$user_id = $_SESSION['user_id'] ?? null;

if($user_id) {
    $fav_query = $conn->prepare("SELECT listing_id FROM favorites WHERE user_id = ?"); 
    $fav_query->bind_param("i", $user_id);
    $fav_query->execute();
    $fav_res = $fav_query->get_result();
    while($f = $fav_res->fetch_assoc()) { 
        $userFavorites[] = $f['listing_id']; 
    }
    $fav_query->close();
}

// FETCH USER INFO
$userInfo = null;
if($user_id) {
    $user_stmt = $conn->prepare("SELECT username FROM registrations WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $userInfo = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
}

// 2. SEARCH LOGIC
$location = $_GET['location'] ?? '';
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? intval($_GET['min_price']) : 0;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? intval($_GET['max_price']) : 999999999;

$sql = "SELECT l.*, COUNT(f.id) AS total_likes 
        FROM addlistings l 
        LEFT JOIN favorites f ON l.id = f.listing_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($location)) {
    $sql .= " AND l.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}
if ($min_price > 0) {
    $sql .= " AND l.price >= ?";
    $params[] = $min_price;
    $types .= "i";
}
if ($max_price < 999999999) {
    $sql .= " AND l.price <= ?";
    $params[] = $max_price;
    $types .= "i";
}

$sql .= " GROUP BY l.id ORDER BY l.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LandHub | Find Your Plot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root { --sold-red: #e74c3c; --available-green: #27ae60; --primary-blue: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; background: #b8b8b8; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: auto; }
        .header-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }
               
        .filter-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
        .filter-form input { padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 100%; box-sizing: border-box; }
        .btn-filter { background: var(--available-green); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; }

        .listing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-bottom:20px; }
        .card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: 0.3s; position: relative; display: flex; flex-direction: column; }
        
        .card-img { width: 100%; height: 200px; object-fit: cover; cursor: zoom-in; transition: 0.3s; }
        .card-img:hover { filter: brightness(0.9); }
        
        /* Video Styling */
        .card-video { width: 100%; height: 500px;margin-top: 10px; border-radius: 8px; background: #000; outline: none; }
        
        .sold-ribbon { position: absolute; top: 15px; left: -35px; background: var(--sold-red); color: white; padding: 5px 40px; transform: rotate(-45deg); font-weight: bold; z-index: 5; font-size: 0.8rem; }

        .card-body { padding: 20px; }
        .price { color: var(--available-green); font-size: 1.4rem; font-weight: bold; }
        
        .land-description { font-size: 0.88rem; color: #555; line-height: 1.5; margin: 10px 0; }
        .desc-full { display: none; }
        .read-more-btn { color: var(--primary-blue); cursor: pointer; font-weight: bold; border: none; background: none; padding: 0; font-size: 0.85rem; }

        .interaction { display: flex; align-items: center; gap: 10px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .heart-btn { background: none; border-color: none; border-style: solid; font-size: 1.5rem; cursor: pointer; transition: 0.2s; }
        
        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 0 20px 20px; }
        .action-btn { text-align: center; padding: 11px; border-radius: 6px; text-decoration: none; color: white; font-weight: bold; font-size: 0.9rem; }
        .btn-wa { background: #25d366; }
        .btn-call { background: var(--primary-blue); }
        .btn-disabled { background: #ccc; cursor: not-allowed; grid-column: span 2; }

        .status-label { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .status-available { background: #e8f5e9; color: #27ae60; }
        .status-sold-label { background: #ffebee; color: #e74c3c; }
        .card.is-sold { filter: grayscale(0.8); opacity: 0.8; }

        #compare-bar { display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 15px 30px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 2500; align-items: center; gap: 20px; cursor: pointer; }

        .image-modal { display: none; position: fixed; z-index: 2000; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(5px); }
        .modal-content { margin: auto; display: block; max-width: 90%; max-height: 85vh; border-radius: 8px; animation: zoom 0.3s; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
        .profile-btn {
            display: inline-block;
            padding: 8px 18px;
            background-color: var(--primary-blue);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .profile-btn:hover {
            background-color: white;
            color: var(--primary-blue);
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .profile-btn i {
            margin-right: 5px;
        }
    /* This targets mobile phones */
        @media (max-width: 600px) {
            .header-container { /* Replace with your actual class name */
                display: flex;
                flex-direction: column; /* Stacks items vertically */
                align-items: center;    /* Centers them */
                text-align: center;
                gap: 15px;              /* Adds space between the stacked items */
                width: 100%;
                overflow: hidden;       /* Prevents anything from sticking out */
            }

            /* Make the white card fit the screen width */
            .card {
                width: 95% !important;
                margin: 10px auto;
                box-sizing: border-box; /* Important: keeps padding inside the width */
            }
        }
        .header-wrapper {
            display: flex;
            flex-wrap: wrap;       /* This is the secret to the second row */
            justify-content: space-around; 
            align-items: center;
            gap: 10px;             /* Space between items */
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Force the buttons to stay together or take their own row if needed */
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            width: 100%;           /* This forces the buttons to a new line */
            margin-top: 5px;
        }

    </style>
</head>
<body>

<div id="imageModal" class="image-modal" onclick="this.style.display='none'">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="fullImg">
</div>

<div id="compare-bar">
    <span><strong id="compare-count">0</strong> plots selected</span>
    <button onclick="goToCompare()" style="background:var(--available-green); color:white; border:none; padding:8px 20px; border-radius:20px; cursor:pointer; font-weight:bold;">Compare Now</button>
    <button onclick="clearCompare()" style="background:transparent; color:#bbb; border:none; cursor:pointer; font-size:1.5rem;">&times;</button>
</div>

<div class="container">
    <div class="header-container">
        <div class="header-wrapper">
            <header>
                <div>
                    <h1 style="margin:0;">🌍 LandHub <span style="font-weight: normal; font-size: 0.9rem; color: #777;">(Kenya)</span></h1>
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <a href="my_favorites.php" style="text-decoration: none; color: var(--sold-red); font-weight: bold;">
                            <i class="fa-solid fa-heart"></i> Saved Plots
                        </a>
                    
                    <?php if($user_id): ?>
                        <div class="action-buttons">
                            <div style="padding: 10px; background: #eef2f3; border-radius: 8px; font-size: 0.9rem;">
                                Welcome, <strong><?php echo htmlspecialchars($userInfo['username']); ?></strong>
                            </div>
                            <a href="profile.php" class="profile-btn">
                                <i class="fa-solid fa-user-gear"></i> Edit Profile
                            </a>

                            <a href="Logout.php" style="color: #e74c3c; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
        </div>

        <div>
           <label style="font-size: 1.85rem; text-align= centre; font-weight: bold;">Search for your perfect land</label> 
        </div>
        <section>
            <form class="filter-form" method="GET">
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Location</label>
                    <input type="text" name="location" placeholder="e.g. Ngong'" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Min Price</label>
                    <input type="number" name="min_price" value="<?php echo $min_price ?: ''; ?>">
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: bold;">Max Price</label>
                    <input type="number" name="max_price" value="<?php echo $max_price < 999999999 ? $max_price : ''; ?>">
                </div>
                <button type="submit" class="btn-filter">Search</button>
            </form>
        </section>
    </div>

    <div id="mainMap" style="height: 400px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #ddd; z-index: 1;"></div>
    
    <script>
        var mainMap = L.map('mainMap').setView([-1.286389, 36.817223], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mainMap);

        <?php 
        $result->data_seek(0); 
        while($row = $result->fetch_assoc()): 
            if(!empty($row['latitude']) && !empty($row['longitude'])): 
        ?>
            L.marker([<?= $row['latitude'] ?>, <?= $row['longitude'] ?>])
                .addTo(mainMap)
                .bindPopup(`<strong><?= htmlspecialchars($row['title']) ?></strong><br>Ksh <?= number_format($row['price']) ?>`);
        <?php 
            endif;
        endwhile; 
        $result->data_seek(0); 
        ?>
    </script>

    <div class="listing-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $isSold = ($row['status'] === 'sold');
            ?>
                <div class="card <?php echo $isSold ? 'is-sold' : ''; ?>">
                    <?php if($isSold): ?><div class="sold-ribbon">SOLD</div><?php endif; ?>

                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                         class="card-img" 
                         onclick="openModal(this.src)">

                    <div class="card-body">
                        <div class="status-label <?php echo $isSold ? 'status-sold-label' : 'status-available'; ?>">
                            <?php echo $isSold ? '● Sold' : '● Available'; ?>
                        </div>

                        <div class="price">💰Ksh <?php echo number_format($row['price']); ?></div>
                        <h3 style="margin: 5px 0; font-size: 1.15rem;"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p style="color: #666; font-size: 1.00rem; margin: 0;">
                            <strong><i class="fa-solid fa-expand"></i> <?php echo htmlspecialchars($row['size']); ?></strong>
                            <br><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['location']); ?>
                        </p>

                        <div class="land-description">
                            <span class="desc-short"><?php echo substr(htmlspecialchars($row['description']), 0, 75); ?>...</span>
                            <span class="desc-full"><?php echo nl2br(htmlspecialchars($row['description'])); ?></span>
                            <button class="read-more-btn" onclick="toggleText(this)">Read More</button>
                        </div>

                        <?php if (!empty($row['video_path'])): ?>
                        <div class="video-container">
                            <video class="card-video" controls>
                                <source src="<?php echo htmlspecialchars($row['video_path']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <?php endif; ?>

                        <div class="interaction">
                            <?php 
                                $isFav = in_array($row['id'], $userFavorites);
                                $heartIcon = $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                                $heartColor = $isFav ? '#e74c3c' : '#ccc';
                            ?>
                            <button class="heart-btn" 
                                    style="color: <?php echo $heartColor; ?>;" 
                                    onclick="toggleFavorite(this, <?php echo $row['id']; ?>)">
                                <i class="<?php echo $heartIcon; ?>"></i>
                            </button>
                            <span style="font-size: 0.85rem; color: #000;"><?php echo $row['total_likes']; ?> likes</span>

                            <label style="margin-left: auto; font-size: 0.8rem; cursor: pointer; color: var(--primary-blue); font-weight: bold;">
                                <input type="checkbox" class="compare-checkbox" 
                                       data-id="<?php echo $row['id']; ?>" 
                                       onclick="toggleCompare(this)"> Compare
                            </label>
                        </div>
                    </div>

                    <div class="btn-group">
                        <?php if($isSold): ?>
                            <button class="action-btn btn-disabled"><i class="fa-solid fa-lock"></i> Closed</button>
                        <?php else: ?>
                            <a href="tel:<?php echo $row['phone_number']; ?>" class="action-btn btn-call"><i class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?php echo $row['phone_number']; ?>" target="_blank" class="action-btn btn-wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Logic remains same...
function toggleText(btn) {
    const parent = btn.parentElement;
    const short = parent.querySelector('.desc-short');
    const full = parent.querySelector('.desc-full');
    
    if (full.style.display === "inline" || full.style.display === "block") {
        full.style.display = "none";
        short.style.display = "inline";
        btn.innerText = "Read More";
    } else {
        full.style.display = "inline";
        short.style.display = "none";
        btn.innerText = "Show Less";
    }
}

function openModal(src) {
    document.getElementById("imageModal").style.display = "block";
    document.getElementById("fullImg").src = src;
}

// Compare and Favorite logic continues...
let compareList = JSON.parse(localStorage.getItem('comparePlots')) || [];

function toggleCompare(checkbox) {
    const id = checkbox.getAttribute('data-id');
    if (checkbox.checked) {
        if (compareList.length >= 3) {
            alert("You can compare up to 3 plots at a time.");
            checkbox.checked = false;
            return;
        }
        compareList.push(id);
    } else {
        compareList = compareList.filter(item => item !== id);
    }
    localStorage.setItem('comparePlots', JSON.stringify(compareList));
    updateCompareBar();
}

function updateCompareBar() {
    const bar = document.getElementById('compare-bar');
    const count = document.getElementById('compare-count');
    bar.style.display = compareList.length > 0 ? 'flex' : 'none';
    count.innerText = compareList.length;
}

function clearCompare() {
    compareList = [];
    localStorage.removeItem('comparePlots');
    document.querySelectorAll('.compare-checkbox').forEach(cb => cb.checked = false);
    updateCompareBar();
}

function goToCompare() {
    if (compareList.length < 2) {
        alert("Please select at least 2 plots to compare.");
        return;
    }
    window.location.href = 'compare.php?ids=' + compareList.join(',');
}

function toggleFavorite(btn, listingId) {
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) { window.location.href = 'directing.html'; return; }

    fetch('toggle_favorite.php?id=' + listingId)
    .then(res => res.json())
    .then(data => {
        const icon = btn.querySelector('i');
        const countSpan = btn.nextElementSibling;
        let currentNum = parseInt(countSpan.innerText) || 0;
        if(data.status === 'added') {
            icon.className = 'fa-solid fa-heart';
            btn.style.color = '#e74c3c';
            countSpan.innerText = (currentNum + 1) + " likes";
        } else {
            icon.className = 'fa-regular fa-heart';
            btn.style.color = '#ccc';
            countSpan.innerText = (currentNum - 1) + " likes";
        }
    });
}

window.onload = function() {
    compareList.forEach(id => {
        const cb = document.querySelector(`.compare-checkbox[data-id="${id}"]`);
        if (cb) cb.checked = true;
    });
    updateCompareBar();
};
</script>
<a href="directing.html" style="display:block; text-align:center; margin-top:15px; text-decoration:none; color:black;">EXIT</a>
<a href="Company Dashboard.php" style="display:block; text-align:center; margin-top:15px; text-decoration:none; color:black;">SELLERS DASHBOARD</a>
</body>
</html>