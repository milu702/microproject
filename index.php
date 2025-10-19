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
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
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
            background-color: #ffe6f0;
            animation: fadeInBody 1s ease-in;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(255, 111, 165, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 77, 140, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 80%, rgba(255, 182, 193, 0.1) 0%, transparent 50%);
            animation: floatingBubbles 20s ease-in-out infinite;
            pointer-events: none;
            z-index: -1;
        }

        header {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            background-size: 200% 200%;
            animation: headerGradient 8s ease infinite, fadeInHeader 0.8s ease-in;
            color: white;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 111, 165, 0.1), rgba(255, 77, 140, 0.05));
            pointer-events: none;
        }
        
        header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 111, 165, 0.5), transparent);
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 30px;
            position: relative;
            z-index: 2;
            min-height: 80px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }
        
        .logo-container a {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        
        .logo-container a:hover {
            transform: translateY(-2px);
        }

        .logo {
            height: 60px;
            width: auto;
            border-radius: 12px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
            animation: logoFloat 6s ease-in-out infinite;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .logo:hover {
            transform: scale(1.1) rotate(3deg);
            filter: drop-shadow(0 8px 20px rgba(255, 111, 165, 0.4));
            border-color: rgba(255, 111, 165, 0.3);
        }

        .logo-fallback {
            height: 60px;
            width: auto;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            padding: 5px;
            font-weight: bold;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .logo-text {
            font-size: 1.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
            margin: 0;
        }

        .nav-bar h1 {
            font-size: 1.8em;
            margin: 0;
            display: none;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 12px 20px;
            font-weight: 500;
            font-size: 1em;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
        
        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 111, 165, 0.2), transparent);
            transition: left 0.5s;
        }

        nav a:hover {
            background: linear-gradient(135deg, rgba(255, 111, 165, 0.2), rgba(255, 77, 140, 0.1));
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 111, 165, 0.3);
            border-color: rgba(255, 111, 165, 0.3);
        }
        
        nav a:hover::before {
            left: 100%;
        }
        
        nav a.active {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: #fff;
            box-shadow: 0 4px 15px rgba(255, 111, 165, 0.4);
        }

        .user-btn {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 111, 165, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .user-btn:hover {
            background: linear-gradient(135deg, #ff4d8c, #d4286d);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 111, 165, 0.5);
        }
        
        .user-btn:hover::before {
            left: 100%;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }
        /* Fix for dropdown visibility */
header {
    overflow: visible !important; /* Changed from hidden */
    z-index: 1000;
}

.nav-bar {
    z-index: 1001;
}

.dropdown {
    position: relative;
    z-index: 1002;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: linear-gradient(135deg, rgba(26, 26, 46, 0.98), rgba(22, 33, 62, 0.98));
    backdrop-filter: blur(20px);
    min-width: 200px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    z-index: 1003; /* Highest z-index */
    border-radius: 12px;
    margin-top: 8px;
    padding: 8px 0;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dropdown:hover .dropdown-content {
    opacity: 1;
    display: block;
    transform: translateY(0) scale(1);
}

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95), rgba(22, 33, 62, 0.95));
            backdrop-filter: blur(20px);
            min-width: 200px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            border-radius: 12px;
            margin-top: 8px;
            padding: 8px 0;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dropdown-content a {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }
        
        .dropdown-content a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 111, 165, 0.2), transparent);
            transition: left 0.3s;
        }

        .dropdown-content a:hover {
            background: linear-gradient(135deg, rgba(255, 111, 165, 0.2), rgba(255, 77, 140, 0.1));
            color: #fff;
            transform: translateX(5px);
        }
        
        .dropdown-content a:hover::before {
            left: 100%;
        }

        .dropdown:hover .dropdown-content {
            opacity: 1;
            display: block;
            transform: translateY(0) scale(1);
        }

        /* Enhanced Keyframe Animations */
        @keyframes fadeInBody {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeInHeader {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @keyframes floatingBubbles {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(90deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
            75% { transform: translateY(-30px) rotate(270deg); }
        }

        @keyframes headerGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(2deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-bar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                min-height: auto;
            }
            
            .logo-container {
                justify-content: center;
            }
            
            .logo-text {
                font-size: 1.5em;
            }
            
            nav {
                justify-content: center;
                gap: 6px;
            }
            
            nav a {
                padding: 10px 16px;
                font-size: 0.9em;
            }
            
            .user-btn {
                padding: 10px 20px;
                font-size: 0.9em;
            }
            
            .dropdown-content {
                right: auto;
                left: 50%;
                transform: translateX(-50%) translateY(-10px) scale(0.95);
            }
            
            .dropdown:hover .dropdown-content {
                transform: translateX(-50%) translateY(0) scale(1);
            }
        }
        
        @media (max-width: 480px) {
            .nav-bar {
                padding: 12px 15px;
            }
            
            .logo {
                height: 50px;
            }
            
            .logo-text {
                font-size: 1.3em;
            }
            
            nav {
                flex-wrap: wrap;
                gap: 4px;
            }
            
            nav a {
                padding: 8px 12px;
                font-size: 0.85em;
            }
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced dropdown functionality
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const button = dropdown.querySelector('.user-btn');
                const content = dropdown.querySelector('.dropdown-content');

                button.addEventListener('mouseover', () => {
                    content.style.display = 'block';
                    content.style.opacity = '1';
                    content.style.transform = 'translateY(0) scale(1)';
                });

                dropdown.addEventListener('mouseleave', () => {
                    setTimeout(() => {
                        if (!dropdown.matches(':hover')) {
                            content.style.opacity = '0';
                            content.style.transform = 'translateY(-10px) scale(0.95)';
                            setTimeout(() => {
                                content.style.display = 'none';
                            }, 300);
                        }
                    }, 200);
                });

                content.addEventListener('mouseover', () => {
                    content.style.display = 'block';
                    content.style.opacity = '1';
                    content.style.transform = 'translateY(0) scale(1)';
                });

                content.addEventListener('mouseleave', () => {
                    content.style.opacity = '0';
                    content.style.transform = 'translateY(-10px) scale(0.95)';
                    setTimeout(() => {
                        content.style.display = 'none';
                    }, 300);
                });
            });
        });
    </script>
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
                <a href="index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Home</a>
                <a href="shop.php" <?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'class="active"' : '' ?>>Shop</a>
                <a href="cart.php" <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'class="active"' : '' ?>>Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="user-btn">
                            <span><?= strtoupper(htmlspecialchars($_SESSION['username'])) ?></span>
                        </button>
                        <div class="dropdown-content">
                            <a href="order_history.php">📋 Order History</a>
                            <a href="?logout=1">🚪 Logout</a>
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