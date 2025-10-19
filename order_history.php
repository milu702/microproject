<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: order_history.php");
    exit;
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "nailstore");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simulate order history (replace with actual database query for paid orders)
$orders = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Assuming a table 'orders' with columns: id, user_id, order_date, total, status
    $result = $conn->query("SELECT * FROM orders WHERE user_id = '$user_id' AND status = 'paid'");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $order_id = $row['id'];
            $order_date = $row['order_date'];
            $total = $row['total'];
            // Fetch order items (simulated)
            $items_result = $conn->query("SELECT * FROM order_items WHERE order_id = '$order_id'");
            $items = [];
            if ($items_result && $items_result->num_rows > 0) {
                while ($item = $items_result->fetch_assoc()) {
                    $items[] = $item;
                }
            }
            $orders[] = ['id' => $order_id, 'date' => $order_date, 'total' => $total, 'items' => $items];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Sparkle Nails</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #fff0f5, #ffe6f0);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background: #ff6fa5;
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            transition: opacity 0.2s ease-in-out;
            opacity: 0;
        }
        .dropdown:hover .dropdown-content {
            opacity: 1;
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
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .orders {
            max-width: 800px;
            width: 100%;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .order-card h3 {
            color: #ff4d8c;
            margin-top: 0;
        }
        .order-card p {
            margin: 5px 0;
        }
        .order-item {
            border: 1px solid #ff6fa5;
            border-radius: 10px;
            padding: 10px;
            margin: 5px 0;
        }
        footer {
            background: #ff6fa5;
            color: white;
            text-align: center;
            padding: 10px;
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
        <div class="orders">
            <h2>Your Order History</h2>
            <?php if (empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h3>Order #<?= $order['id'] ?></h3>
                        <p>Date: <?= $order['date'] ?></p>
                        <p>Total: ₹<?= $order['total'] ?></p>
                        <h4>Items:</h4>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <p><?= $item['name'] ?> - ₹<?= $item['price'] ?> (Qty: <?= $item['quantity'] ?>)</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>© 2025 Sparkle Nails. All Rights Reserved.</p>
        <a href="index.php">Home</a> | <a href="shop.php">Shop</a>
    </footer>
</body>
</html>