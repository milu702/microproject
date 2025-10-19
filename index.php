<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Sparkle Nails 💅</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        body {
            background: url('background.png') no-repeat center center;
            background-size: cover;
            background-color: #ffe6f0; /* Fallback color */
            animation: fadeInBody 1s ease-in;
        }

        header, footer {
            background: #ff6fa5;
            color: white;
            text-align: center;
            padding: 20px;
            animation: fadeIn 1s ease-in;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            height: 50px;
            width: auto;
            border-radius: 10px;
            transition: transform 0.3s;
        }

        .logo-fallback {
            height: 50px;
            width: auto;
            background: #ff6fa5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 5px;
            font-weight: bold;
        }

        .logo-text {
            font-size: 1.8em;
            color: white;
            font-weight: bold;
            margin: 0;
        }

        .logo:hover, .logo-fallback:hover {
            transform: scale(1.1);
        }

        .nav-bar h1 {
            font-size: 1.8em;
            margin: 0;
            display: none; /* Hide the original h1 */
        }

        nav {
            display: flex;
            gap: 10px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 1.1em;
            border-radius: 20px;
            transition: background 0.3s, color 0.3s;
        }

        nav a:hover {
            background: #ff4d8c;
            color: #ffe6f0;
        }

        .user-btn {
            background: #ff6fa5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .user-btn:hover {
            background: #ff4d8c;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ff6fa5;
            min-width: 100px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 10px;
            margin-top: 5px;
        }

        .dropdown-content a {
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            border-radius: 10px;
        }

        .dropdown-content a:hover {
            background-color: #ff4d8c;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .hero {
            padding: 80px 20px;
            text-align: center;
            animation: slideUp 1s ease-out;
        }

        .hero h1 {
            font-size: 3em;
            margin-bottom: 10px;
            color: #f3e1e7ff;
            animation: glow 2s infinite alternate;
        }

        .hero p {
            font-size: 1.2em;
            color: #555;
        }

        button {
            background: #ff6fa5;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 0 10px #ff6fa5;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px #ff4d8c;
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            padding: 40px;
            animation: fadeIn 1.5s ease-in;
        }

        .feature {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(172, 8, 144, 0.1);
            width: 280px;
            text-align: center;
            transition: transform 0.4s, box-shadow 0.4s;
        }

        .feature:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(163, 10, 112, 0.2);
        }

        .feature img {
            width: 80px;
            margin-bottom: 15px;
        }

        .image-carousel {
            overflow: hidden;
            background: #b82277ff;
            padding: 40px 0; /* Increased padding to expand the pink background */
            box-shadow: inset 0 10px 20px rgba(0,0,0,0.05);
            position: relative;
            z-index: 0;
            margin-bottom: 20px;
        }

        .carousel-track {
            display: flex;
            animation: scrollImages 25s linear infinite;
        }

        .carousel-item {
            flex: 0 0 auto;
            width: 300px;
            height: 250px; /* Increased height to accommodate text below image */
            margin-right: 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #b82277ff; /* Match carousel background */
            padding: 10px; /* Add padding inside each item */
        }

        .carousel-item img {
            width: 100%;
            height: 200px; /* Adjusted height to leave space for text */
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .carousel-item img:hover {
            transform: scale(1.05);
        }

        .carousel-item p {
            font-weight: bold;
            color: #ffffff; /* Changed to white for contrast on pink */
            font-size: 1.2em; /* Slightly larger for better readability */
            text-align: center;
            padding: 5px 10px;
            border-radius: 5px;
            background: rgba(255, 77, 140, 0.8); /* Subtle pinkish background */
            margin-top: 10px; /* Space between image and text */
            font-style: italic; /* Added for a nice touch */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3); /* Shadow for readability */
        }

        @keyframes scrollImages {
            0% { transform: translateX(0); }
            100% { transform: translateX(-1600px); }
        }

        @keyframes fadeInBody {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes slideUp {
            from {transform: translateY(50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }

        @keyframes glow {
            from {text-shadow: 0 0 10px #ff6fa5;}
            to {text-shadow: 0 0 20px #ff4d8c;}
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-bar">
            <div class="logo-container">
                <a href="index.php">
                    <img src="logo.png" alt="Sparkle Nails Logo" class="logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span class="logo-fallback" style="display:none;">Sparkle Nails</span>
                </a>
                <span class="logo-text">Sparkle Nails</span>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="cart.php">Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="user-btn"><?= strtoupper(htmlspecialchars($_SESSION['username'])) ?></button>
                        <div class="dropdown-content">
                            <a href="?logout=1">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Welcome to Sparkle Nails</h1>
        <p>Discover vibrant nail colors, try them virtually, and shop your favorites with ease!</p>
        <a href="shop.php"><button>Start Shopping</button></a>
    </section>

    <section class="image-carousel">
        <div class="carousel-track">
            <div class="carousel-item">
                <img src="img1.png" alt="Bold Red">
                <p>Bold Red – bold and unique</p>
            </div>
            <div class="carousel-item">
                <img src="img2.png" alt="Pastel Pink">
                <p>Pastel Pink – Soft and sweet</p>
            </div>
            <div class="carousel-item">
                <img src="img3.png" alt="Ocean Blue">
                <p>Ocean Blue – Dive into style</p>
            </div>
            <div class="carousel-item">
                <img src="img4.png" alt="Golden Glow">
                <p>Golden Glow – Shine bright</p>
            </div>
            <div class="carousel-item">
                <img src="img5.png" alt="Mystic Purple">
                <p>Mystic Purple – Enchant your look</p>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="feature">
            <img src="nail-polish.jpg" alt="Colors">
            <h3>Vibrant Shades</h3>
            <p>Explore a curated collection of trendy nail polish colors.</p>
        </div>
        <div class="feature">
            <img src="tryon.jpg" alt="Try-On">
            <h3>Virtual Try-On</h3>
            <p>Preview nail colors on a hand model before you buy.</p>
        </div>
        <div class="feature">
            <img src="checkout.jpg" alt="Cart">
            <h3>Easy Checkout</h3>
            <p>Add products to your cart and pay with a single click.</p>
        </div>
    </section>

    <footer>
        <p>© 2025 Sparkle Nails. All Rights Reserved.</p>
        <a href="index.php">Home</a> | <a href="shop.php">Shop</a>
    </footer>
</body>
</html>