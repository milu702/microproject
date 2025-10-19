<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: shop.php");
    exit;
}

// Database Class
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "nailstore";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->query("CREATE DATABASE IF NOT EXISTS $this->dbname");
        $this->conn->select_db($this->dbname);

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100),
                price DECIMAL(10,2),
                image VARCHAR(255)
            )
        ");

        $check = $this->conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc();
        if ($check['count'] == 0) {
            $this->conn->query("INSERT INTO products (name, price, image) VALUES
                ('Bold Red', 299.00, 'n1.jpg'),
                ('Pastel Pink', 249.00, 'n2.jpg'),
                ('Ocean Blue', 279.00, 'n3.jpg'),
                ('Golden Glow', 349.00, 'n4.jpg'),
                ('Mystic Purple', 319.00, 'n12.jpg'),
                ('Emerald Green', 289.00, 'n6.jpg'),
                ('Silver Sparkle', 359.00, 'n14.jpg'),
                ('Lavender Dream', 269.00, 'n7.jpg'),
                ('Coral Reef', 299.00, 'n9.jpg'),
                ('Midnight Black', 329.00, 'n10.jpg')
            ");
        }
    }
}

// Product Class
class Product {
    private $db;
    public function __construct($dbConn) {
        $this->db = $dbConn;
    }
    public function getAllProducts() {
        $products = [];
        $res = $this->db->query("SELECT * FROM products");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }
}

// Cart Class
class Cart {
    public function addToCart($product) {
        $id = $product['id'];
        $_SESSION['cart'][$id]['name'] = $product['name'];
        $_SESSION['cart'][$id]['price'] = $product['price'];
        $_SESSION['cart'][$id]['image'] = $product['image'];
        $_SESSION['cart'][$id]['quantity'] = ($_SESSION['cart'][$id]['quantity'] ?? 0) + 1;
    }
    public function getTotalItems() {
        return isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
    }
}

// Logic Execution
$db = new Database();
$productObj = new Product($db->conn);
$cartObj = new Cart();

$products = $productObj->getAllProducts();

if (isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['user_id'])) {
        $id = $_POST['id'];
        foreach ($products as $p) {
            if ($p['id'] == $id) {
                $cartObj->addToCart($p);
                break;
            }
        }
        header("Location: shop.php");
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

$totalItems = $cartObj->getTotalItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Sparkle Nails</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #fff0f5, #ffe6f0, #f0e6ff);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite, fadeInBody 1s ease-in;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
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
            max-width: 1400px;
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
        
        .logo-text {
            font-size: 1.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
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
        .dropdown:hover .dropdown-content {
            display: block;
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

        .dropdown:hover .dropdown-content {
            opacity: 1;
            display: block;
            transform: translateY(0) scale(1);
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

        /* Main Content Layout */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            animation: slideInUp 1s ease-out;
            gap: 30px;
        }

        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            animation: slideInDown 1s ease-out;
        }

        .shop-title {
            font-size: 2.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            animation: titleGlow 3s ease-in-out infinite;
        }

        .search-container {
            position: relative;
            width: 350px;
        }

        .search-container input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #ff6fa5;
            border-radius: 30px;
            font-size: 1em;
            background: linear-gradient(135deg, #fff, #fff8fc);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 8px 25px rgba(255, 111, 165, 0.15);
            font-weight: 500;
        }
        
        .search-container input:focus {
            outline: none;
            border-color: #ff4d8c;
            box-shadow: 0 0 30px rgba(255, 77, 140, 0.3);
            transform: translateY(-2px);
        }
        
        .search-container input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff6fa5;
            font-size: 1.2em;
        }

        .shop-content {
            display: flex;
            gap: 30px;
            min-height: 600px;
        }

        /* Enhanced Sidebar */
        .sidebar {
            width: 280px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(255, 248, 252, 0.95), rgba(255, 240, 245, 0.95));
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(255, 111, 165, 0.15);
            border: 1px solid rgba(255, 111, 165, 0.2);
            animation: slideInLeft 1s ease-out;
            position: relative;
            overflow: hidden;
            height: fit-content;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 111, 165, 0.05), transparent);
            animation: sidebarShimmer 6s linear infinite;
            pointer-events: none;
        }

        .filter-section {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .filter-title {
            color: #ff4d8c;
            font-size: 1.4em;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(255, 77, 140, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Fixed Price Range */
        .price-range-container {
            background: linear-gradient(135deg, #fff, #fff8fc);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.1);
            border: 1px solid rgba(255, 111, 165, 0.1);
        }

        .price-inputs {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .price-input {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .price-input label {
            font-size: 0.9em;
            color: #666;
            font-weight: 500;
        }

        .price-value {
            font-size: 1.1em;
            font-weight: 700;
            color: #ff4d8c;
            background: rgba(255, 111, 165, 0.1);
            padding: 10px 15px;
            border-radius: 10px;
            width: 100%;
            text-align: center;
            border: 2px solid rgba(255, 111, 165, 0.2);
        }

        .price-slider-container {
            position: relative;
            height: 40px;
            display: flex;
            align-items: center;
        }

        .price-slider {
            position: relative;
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 5px;
        }

        .price-slider .track {
            position: absolute;
            left: 25%;
            right: 25%;
            top: 0;
            bottom: 0;
            background: linear-gradient(90deg, #ff6fa5, #ff4d8c);
            border-radius: 5px;
        }

        .price-slider input[type="range"] {
            position: absolute;
            width: 100%;
            height: 6px;
            background: transparent;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
            top: 0;
            left: 0;
        }

        .price-slider input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff, #ff6fa5);
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(255, 111, 165, 0.4);
            transition: all 0.3s ease;
            pointer-events: auto;
            border: 2px solid #fff;
        }
        
        .price-slider input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 6px 20px rgba(255, 111, 165, 0.6);
        }

        /* Sort Options */
        .sort-options select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #ff6fa5;
            border-radius: 15px;
            font-size: 1em;
            background: linear-gradient(135deg, #fff, #fff8fc);
            color: #555;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.1);
        }
        
        .sort-options select:focus {
            outline: none;
            border-color: #ff4d8c;
            box-shadow: 0 0 25px rgba(255, 77, 140, 0.3);
            transform: translateY(-2px);
        }

        /* Products Grid - Fixed 4 products per row */
        .products-container {
            flex: 1;
            animation: fadeInGrid 1.2s ease-out;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            width: 100%;
        }

        .product {
            background: linear-gradient(135deg, #fff, #fff8fc);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(255, 111, 165, 0.15);
            text-align: center;
            padding: 25px;
            animation: productFloat 4s ease-in-out infinite, productSlideIn 0.8s ease-out;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 111, 165, 0.1), transparent);
            animation: productShimmer 6s linear infinite;
            pointer-events: none;
        }

        .product:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(255, 111, 165, 0.25);
            border-color: rgba(255, 111, 165, 0.3);
        }
        
        .product:hover::before {
            animation-duration: 2s;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            z-index: 1;
            filter: brightness(1.05) contrast(1.1);
            margin-bottom: 20px;
        }

        .product:hover .product-image {
            transform: scale(1.08) rotate(2deg);
            filter: brightness(1.1) contrast(1.2) saturate(1.2);
        }

        .product-name {
            font-size: 1.4em;
            color: #ff4d8c;
            font-weight: 700;
            margin: 0 0 10px 0;
            animation: textGlow 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(255, 77, 140, 0.2);
            flex-grow: 1;
        }
        
        .product-price {
            font-size: 1.3em;
            color: #666;
            font-weight: 700;
            margin: 10px 0 20px 0;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .product button {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1em;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.3);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            z-index: 1;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }
        
        .product button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .product button:hover {
            background: linear-gradient(135deg, #ff4d8c, #d4286d);
            box-shadow: 0 12px 30px rgba(255, 77, 140, 0.5);
            transform: translateY(-3px) scale(1.05);
        }
        
        .product button:hover::before {
            left: 100%;
        }
        
        .product button:active {
            transform: translateY(-1px) scale(1.02);
        }

        .no-results {
            text-align: center;
            color: #ff4d8c;
            font-size: 1.4em;
            font-weight: 600;
            margin: 40px 0;
            animation: pulseText 2s ease-in-out infinite;
            text-shadow: 0 2px 4px rgba(255, 77, 140, 0.2);
            grid-column: 1 / -1;
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

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
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

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes sidebarShimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        @keyframes fadeInGrid {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes productFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes productSlideIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes productShimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        @keyframes textGlow {
            0%, 100% { text-shadow: 0 0 5px rgba(255, 77, 140, 0.3); }
            50% { text-shadow: 0 0 20px rgba(255, 77, 140, 0.6), 0 0 30px rgba(255, 77, 140, 0.4); }
        }

        @keyframes titleGlow {
            0%, 100% { text-shadow: 0 0 5px rgba(255, 77, 140, 0.3); }
            50% { text-shadow: 0 0 20px rgba(255, 77, 140, 0.6), 0 0 30px rgba(255, 77, 140, 0.4); }
        }

        @keyframes pulseText {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        /* Staggered animation for product cards */
        .product:nth-child(1) { animation-delay: 0.1s; }
        .product:nth-child(2) { animation-delay: 0.2s; }
        .product:nth-child(3) { animation-delay: 0.3s; }
        .product:nth-child(4) { animation-delay: 0.4s; }
        .product:nth-child(5) { animation-delay: 0.5s; }
        .product:nth-child(6) { animation-delay: 0.6s; }
        .product:nth-child(7) { animation-delay: 0.7s; }
        .product:nth-child(8) { animation-delay: 0.8s; }
        .product:nth-child(9) { animation-delay: 0.9s; }
        .product:nth-child(10) { animation-delay: 1.0s; }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .shop-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                margin-bottom: 30px;
            }
            
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
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
            
            .shop-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .search-container {
                width: 100%;
                max-width: 400px;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
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
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product {
                padding: 20px;
            }
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

            // Initialize price range slider
            initializePriceRange();

            // Add loading animation for product cards
            const products = document.querySelectorAll('.product');
            products.forEach((product, index) => {
                product.style.animationDelay = `${index * 0.1}s`;
                
                // Add click animation
                product.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'BUTTON') {
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });

            // Add scroll-triggered animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                    }
                });
            }, observerOptions);

            // Observe all animated elements
            document.querySelectorAll('.product, .sidebar, .search-container').forEach(el => {
                observer.observe(el);
            });

            // Add ripple effect to buttons
            document.querySelectorAll('button').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add CSS for ripple effect
            const style = document.createElement('style');
            style.textContent = `
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });

        function initializePriceRange() {
            const minPriceSlider = document.getElementById('minPrice');
            const maxPriceSlider = document.getElementById('maxPrice');
            const minPriceValue = document.getElementById('minPriceValue');
            const maxPriceValue = document.getElementById('maxPriceValue');

            // Set initial values
            minPriceValue.textContent = minPriceSlider.value;
            maxPriceValue.textContent = maxPriceSlider.value;

            // Update values when sliders change
            minPriceSlider.addEventListener('input', function() {
                if (parseInt(this.value) > parseInt(maxPriceSlider.value)) {
                    this.value = maxPriceSlider.value;
                }
                minPriceValue.textContent = this.value;
                updateTrack();
                filterProducts();
            });

            maxPriceSlider.addEventListener('input', function() {
                if (parseInt(this.value) < parseInt(minPriceSlider.value)) {
                    this.value = minPriceSlider.value;
                }
                maxPriceValue.textContent = this.value;
                updateTrack();
                filterProducts();
            });

            function updateTrack() {
                const min = parseInt(minPriceSlider.value);
                const max = parseInt(maxPriceSlider.value);
                const track = document.querySelector('.price-slider .track');
                track.style.left = (min / 400 * 100) + '%';
                track.style.right = (100 - (max / 400 * 100)) + '%';
            }

            // Initialize track position
            updateTrack();
        }

        function filterProducts() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || 400;
            const sortOption = document.getElementById('sortSelect').value;
            const products = Array.from(document.getElementById('productList').getElementsByClassName('product'));
            let visible = 0;

            // Filter products
            products.forEach(product => {
                const name = product.getElementsByClassName('product-name')[0].textContent.toLowerCase();
                const price = parseFloat(product.getAttribute('data-price'));
                if (name.includes(searchInput) && price >= minPrice && price <= maxPrice) {
                    product.style.display = "";
                    visible++;
                } else {
                    product.style.display = "none";
                }
            });

            // Sort products
            products.sort((a, b) => {
                const aPrice = parseFloat(a.getAttribute('data-price'));
                const bPrice = parseFloat(b.getAttribute('data-price'));
                const aName = a.getAttribute('data-name').toLowerCase();
                const bName = b.getAttribute('data-name').toLowerCase();

                switch (sortOption) {
                    case 'price-asc':
                        return aPrice - bPrice;
                    case 'price-desc':
                        return bPrice - aPrice;
                    case 'name-asc':
                        return aName.localeCompare(bName);
                    case 'name-desc':
                        return bName.localeCompare(aName);
                    default:
                        return 0;
                }
            });

            // Reorder products in DOM
            const productList = document.getElementById('productList');
            productList.innerHTML = '';
            products.forEach(product => productList.appendChild(product));

            // Show/hide no results message
            document.getElementById('noResults').style.display = visible === 0 ? "block" : "none";
        }
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

    <div class="main-content">
        <div class="shop-header">
            <h1 class="shop-title">✨ Nail Polish Collection</h1>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="filterProducts()">
                <div class="search-icon">🔍</div>
            </div>
        </div>

        <div class="shop-content">
            <div class="sidebar">
                <div class="filter-section">
                    <h3 class="filter-title">💰 Price Range</h3>
                    <div class="price-range-container">
                        <div class="price-inputs">
                            <div class="price-input">
                                <label>Min Price</label>
                                <div class="price-value" id="minPriceValue">0</div>
                            </div>
                            <div class="price-input">
                                <label>Max Price</label>
                                <div class="price-value" id="maxPriceValue">400</div>
                            </div>
                        </div>
                        <div class="price-slider-container">
                            <div class="price-slider">
                                <div class="track"></div>
                                <input type="range" id="minPrice" min="0" max="400" value="0">
                                <input type="range" id="maxPrice" min="0" max="400" value="400">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">📊 Sort By</h3>
                    <div class="sort-options">
                        <select id="sortSelect" onchange="filterProducts()">
                            <option value="default">Default Sorting</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="name-asc">Name: A to Z</option>
                            <option value="name-desc">Name: Z to A</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="products-container">
                <div class="products-grid" id="productList">
                    <?php foreach ($products as $p): ?>
                        <div class="product" data-price="<?= $p['price'] ?>" data-name="<?= $p['name'] ?>">
                            <img src="<?= $p['image'] ?>" alt="<?= $p['name'] ?>" class="product-image">
                            <h3 class="product-name"><?= $p['name'] ?></h3>
                            <p class="product-price">₹<?= $p['price'] ?></p>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button name="add_to_cart">Add to Cart</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="no-results" id="noResults" style="display: none;">No products found matching your criteria</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            filterProducts();
        });
    </script>
</body>
</html>