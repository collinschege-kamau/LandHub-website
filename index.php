<?php
    // 1. Database Connection
    $servername = "sql112.infinityfree.com";
    $username = "if0_41669716";
    $password = "v625mgR7min";
    $dbname = "if0_41669716_landapp";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // 2. Fetch 3 random available listings for the preview
    $featured_sql = "SELECT title, price, location, image_path FROM addlistings WHERE status != 'sold' ORDER BY RAND() LIMIT 3";
    $featured_result = $conn->query($featured_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LandHub Kenya | Home</title>
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
            /* PRESERVING YOUR BACKGROUND */
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('pexels-altaf-shah-3143825-8314513.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* --- Navigation --- */
        nav {
            background-color: rgba(44, 62, 80, 0.95);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .nav-links {
            text-align: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: var(--success);
        }

        /* --- Layout Containers --- */
        .container {
            max-width: 1100px;
            margin: auto;
            padding: 20px;
        }

        .white-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            margin-bottom: 40px;
            text-align: center;
        }

        /* --- Header & Logo --- */
        .main-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 5px;
            letter-spacing: 2px;
        }

        .logo img {
            width: 150px;
            margin: 20px 0;
            border-radius: 10px;
        }

        /* --- Hero Section --- */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            text-align: left;
        }

        .hero-text h1 {
            font-size: 3rem;
            line-height: 1.2;
            color: var(--success);
            margin: 0;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(5px 5px 15px rgba(0,0,0,0.1));
        }

        /* --- Featured Grid --- */
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .preview-item {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: 0.3s;
        }

        .preview-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .preview-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .preview-content {
            padding: 15px;
            text-align: left;
        }

        .feat-price {
            color: var(--success);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 5px 0;
        }

        /* --- Buttons --- */
        .btn-main {
            background: var(--accent);
            color: white !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
            transition: 0.3s;
            border: none;
            margin: 10px;
        }

        .btn-main:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        /* --- Notice Box --- */
        .notice-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            border-left: 5px solid #ffeeba;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* --- Team & Contact --- */
        .team-avatars {
            font-size: 50px;
            margin-bottom: 10px;
        }

        .contact-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: white;
            background: rgba(44, 62, 80, 0.8);
            font-size: 0.9rem;
        }/* --- Footer --- */
        
        @media (max-width: 768px) {
            .hero { flex-direction: column; text-align: center; }
            .hero-text h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-links">
            <a href="index.php"><i class="fa fa-home"></i> Home</a>
            <a href="About Us.html"><i class="fa fa-info-circle"></i> About LandHub</a>
            <a href="How it works.html"><i class="fa fa-gear"></i> How it Works</a>
        </div>
    </nav>

    <div class="container">
        <div class="white-card">
            <header class="main-header">
                <h1>LANDHUB KENYA</h1>
                <p>Connecting people to their dream property with transparency.</p>
                <div class="logo">
                    <img src="landhub logo.png" alt="LandHub Logo">
                </div>
            </header>
        </div>

        <div class="white-card">
            <section class="hero">
                <div class="hero-text">
                    <h1>Find your<br>perfect land<br>in your dream<br>area</h1>
                    <p>Verified Listings. Direct Contacts. Safe Deals.</p>
                    
                </div> 
                <div class="hero-image">
                    <img src="data:image/png;base64,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" alt="Search Illustration">
            </section>
        </div>

        <div class="white-card">
            <h2 style="color: var(--primary); border-bottom: 2px solid var(--success); display: inline-block; padding-bottom: 5px;">Featured Listings</h2>
            <div class="featured-grid">
                <?php if ($featured_result && $featured_result->num_rows > 0): ?>
                    <?php while($row = $featured_result->fetch_assoc()): ?>
                        <div class="preview-item">
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Land">
                            <div class="preview-content">
                                <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                                <p class="feat-price">Ksh <?php echo number_format($row['price']); ?></p>
                                <p class="feat-loc"><i class="fa fa-map-marker-alt" style="color: red;"></i> <?php echo htmlspecialchars($row['location']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>New listings coming soon!</p>
                <?php endif; ?>
            </div>
            <a href="View Listings.php" class="btn-main">Browse All Listings</a>
        </div>

        <div class="white-card">
            <div class="notice-box">
                <i class="fa fa-exclamation-triangle"></i> MAKE SURE YOU HAVE READ "HOW IT WORKS" BEFORE CONTINUING
            </div>
            <div class="action-buttons">
                <a href="private sellers upload.html" class="btn-main"><i class="fa fa-user"></i> Private Seller</a>
                <a href="companies.html" class="btn-main"><i class="fa fa-building"></i>Companies</a>
            </div>
        </div>

        <div class="white-card">
            <section id="team">
                <h2>Meet the Developer</h2>
                <div class="team-avatars">👤</div>
                <p><strong>Kamau Collins Chege</strong></p>
                <p style="color: #666; max-width: 600px; margin: auto;">Built by a passionate developer from Karatina University. My goal is to modernize and simplify land advertisement for everyone in Kenya.</p>
            </section>

            <hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

            <section id="contact">
                <h2>Contact Support</h2>
                <div class="contact-container">
                    <div class="contact-item">
                        <i class="fa fa-envelope" style="color: var(--accent);"></i> collokrymboy@gmail.com
                    </div>
                    <div class="contact-item">
                        <i class="fa fa-phone" style="color: var(--success);"></i> +254 715 185922
                    </div>
                </div>
            </section>
        </div>
    </div>

    <footer>
        &copy; 2026 LandHub Kenya. All rights reserved. <br>
        <small>Empowering Kenyans through transparent land deals.</small>
    </footer>

</body>
</html>
