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
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #fff0f5, #ffe6f0);
            animation: fadeInBody 1s ease-in;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background: #ff6fa5;
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            animation: fadeInHeader 0.8s ease-in;
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

        .logo:hover {
            transform: scale(1.1);
        }

        .nav-bar h1 {
            font-size: 1.8em;
            margin: 0;
            display: none;
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
            cursor: pointer;
        }

        nav a:hover {
            background: #ff4d8c;
            color: #ffe6f0;
        }

        .user-btn {
            background: #d4286d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s;
        }

        .user-btn:hover {
            background: #b81e5e;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ff6fa5;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 10px;
            margin-top: 5px;
            padding: 5px 0;
            transition: opacity 0.3s ease-in-out;
            opacity: 0;
        }

        .dropdown:hover .dropdown-content {
            opacity: 1;
            display: block;
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

        .main-content {
            display: flex;
            flex: 1;
            margin-top: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .sidebar {
            width: 200px;
            padding: 20px;
            background: #fff8fc;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-left: 20px;
        }

        .sidebar h3 {
            color: #ff4d8c;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .price-range {
            margin-bottom: 20px;
        }

        .price-range label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .price-range input[type="range"] {
            width: 100%;
            margin: 10px 0;
        }

        .sort-options select {
            width: 100%;
            padding: 8px;
            border: 2px solid #ff6fa5;
            border-radius: 10px;
            font-size: 1em;
        }

        .search-container {
            text-align: right;
            margin-right: 20px;
            flex: 1;
            display: flex;
            justify-content: flex-end;
            padding-left: 20px;
        }

        .search-container input[type="text"] {
            width: 30%;
            padding: 8px;
            border: 2px solid #ff6fa5;
            border-radius: 20px;
            font-size: 1em;
        }

        .products {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .product {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            text-align: center;
            padding: 15px;
            animation: floatCard 3s ease-in-out infinite;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 3px solid transparent;
            background-clip: padding-box;
            position: relative;
        }

        .product:hover {
            transform: scale(1.05);
            animation: borderPulse 1.5s infinite;
        }

        .product img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s;
        }

        .product:hover img {
            transform: scale(1.05);
        }

        .product h3 {
            font-size: 1.3em;
            color: #ff4d8c;
            animation: glowText 2s infinite;
        }

        .product button {
            background: #ff6fa5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            box-shadow: 0 0 10px #ff6fa5;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product button:hover {
            background: #ff4d8c;
            box-shadow: 0 0 20px #ff4d8c;
            transform: scale(1.05);
        }

        .no-results {
            text-align: center;
            color: #ff4d8c;
            font-size: 1.2em;
            margin: 20px 0;
        }

        @keyframes fadeInBody {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeInHeader {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @keyframes glowText {
            0% { text-shadow: 0 0 5px #ff6fa5; }
            50% { text-shadow: 0 0 20px #ff4d8c; }
            100% { text-shadow: 0 0 5px #ff6fa5; }
        }

        @keyframes floatCard {
            0% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0); }
        }

        @keyframes borderPulse {
            0% { box-shadow: 0 0 10px #ff6fa5; }
            50% { box-shadow: 0 0 20px #ff4d8c; }
            100% { box-shadow: 0 0 10px #ff6fa5; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const button = dropdown.querySelector('.user-btn');
                const content = dropdown.querySelector('.dropdown-content');

                button.addEventListener('mouseover', () => {
                    content.style.display = 'block';
                    content.style.opacity = '1';
                });

                dropdown.addEventListener('mouseleave', () => {
                    setTimeout(() => {
                        if (!dropdown.matches(':hover')) {
                            content.style.opacity = '0';
                            setTimeout(() => {
                                content.style.display = 'none';
                            }, 300); // Match the CSS transition duration
                        }
                    }, 200); // Delay to allow mouse movement to content
                });

                content.addEventListener('mouseover', () => {
                    content.style.display = 'block';
                    content.style.opacity = '1';
                });

                content.addEventListener('mouseleave', () => {
                    content.style.opacity = '0';
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
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="cart.php">Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="user-btn"><?= strtoupper(htmlspecialchars($_SESSION['username'])) ?></button>
                        <div class="dropdown-content">
                            <a href="order_history.php">Order History</a>
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

    <div class="main-content">
        <div class="sidebar">
            <div class="price-range">
                <h3>Price Range</h3>
                <label>Min: <span id="minPriceValue">0</span> ₹</label>
                <input type="range" id="minPrice" min="0" max="400" value="0" oninput="updatePriceRange()">
                <label>Max: <span id="maxPriceValue">400</span> ₹</label>
                <input type="range" id="maxPrice" min="0" max="400" value="400" oninput="updatePriceRange()">
            </div>
            <div class="sort-options">
                <h3>Sort By</h3>
                <select id="sortSelect" onchange="filterProducts()">
                    <option value="default">Default</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="name-asc">Name: A to Z</option>
                    <option value="name-desc">Name: Z to A</option>
                </select>
            </div>
        </div>

        <div style="flex: 1;">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search..." onkeyup="filterProducts()">
            </div>
            <section class="products" id="productList">
                <?php foreach ($products as $p): ?>
                    <div class="product" data-price="<?= $p['price'] ?>" data-name="<?= $p['name'] ?>">
                        <img src="<?= $p['image'] ?>" alt="<?= $p['name'] ?>">
                        <h3><?= $p['name'] ?></h3>
                        <p>₹<?= $p['price'] ?></p>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button name="add_to_cart">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>

    <p class="no-results" id="noResults" style="display: none;">No products found</p>

    <script>
        function updatePriceRange() {
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            document.getElementById('minPriceValue').textContent = minPrice;
            document.getElementById('maxPriceValue').textContent = maxPrice;
            filterProducts();
        }

        function filterProducts() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || 400;
            const sortOption = document.getElementById('sortSelect').value;
            const products = Array.from(document.getElementById('productList').getElementsByClassName('product'));
            let visible = 0;

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

            products.forEach(product => {
                const name = product.getElementsByTagName('h3')[0].textContent.toLowerCase();
                const price = parseFloat(product.getAttribute('data-price'));
                if (name.includes(searchInput) && price >= minPrice && price <= maxPrice) {
                    product.style.display = "";
                    visible++;
                } else {
                    product.style.display = "none";
                }
            });

            const productList = document.getElementById('productList');
            productList.innerHTML = '';
            products.forEach(product => productList.appendChild(product));

            document.getElementById('noResults').style.display = visible === 0 ? "block" : "none";
        }
    </script>
</body>
</html>