<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: cart.php");
    exit;
}

// Handle item removal
if (isset($_POST['remove_from_cart'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    error_log("Attempting to remove item with ID: " . ($id ?? 'null')); // Debug log
    if ($id !== false && $id > 0 && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        error_log("Successfully removed item ID: $id from cart. New cart: " . var_export($_SESSION['cart'], true));
    } else {
        error_log("Failed to remove item ID: $id. Cart state: " . var_export($_SESSION['cart'], true));
    }
    header("Location: cart.php");
    exit;
}

// Handle quantity adjustment
if (isset($_POST['adjust_quantity'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    if ($id !== false && $id > 0 && isset($_SESSION['cart'][$id]) && $quantity >= 0) {
        $_SESSION['cart'][$id]['quantity'] = $quantity;
        if ($quantity == 0) {
            unset($_SESSION['cart'][$id]);
            if (empty($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
        }
    } else {
        error_log("Invalid quantity adjustment for ID: " . $id . ", Quantity: " . $quantity);
    }
    header("Location: cart.php");
    exit;
}

// Razorpay API Keys - Replace with your actual keys
define('RAZOR_KEY_ID', 'rzp_test_RUpQh5ubKDdpIb');
define('RAZOR_KEY_SECRET', '8AI88SVJhYSFH1Avmxk4s651');

// Connect to database
$conn = new mysqli("localhost", "root", "", "nailstore");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create orders and order_items tables if they don't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_date DATETIME,
        total DECIMAL(10,2),
        status VARCHAR(20) DEFAULT 'paid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        name VARCHAR(100),
        price DECIMAL(10,2),
        quantity INT,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )
");

// Fetch products
$products = [];
$res = $conn->query("SELECT * FROM products");
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
}

// Add to cart
if (isset($_POST['add_to_cart'])) {
    if (isset($_SESSION['user_id'])) {
        $id = $_POST['id'];
        $found = false;
        foreach ($products as $p) {
            if ($p['id'] == $id) {
                $_SESSION['cart'][$p['id']]['name'] = $p['name'] ?? 'Unknown';
                $_SESSION['cart'][$p['id']]['price'] = $p['price'] ?? 0;
                $_SESSION['cart'][$p['id']]['image'] = $p['image'] ?? 'default.png';
                $_SESSION['cart'][$p['id']]['quantity'] = ($_SESSION['cart'][$p['id']]['quantity'] ?? 0) + 1;
                $_SESSION['cart'][$p['id']]['id'] = $p['id'];
                $found = true;
                error_log("Added to cart: " . var_export($_SESSION['cart'][$p['id']], true));
            }
        }
        if (!$found) {
            error_log("Product ID $id not found in products array.");
        }
        header("Location: cart.php");
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

// Handle Razorpay payment initiation
if (isset($_POST['initiate_payment'])) {
    $cart = $_SESSION['cart'] ?? [];
    if (!empty($cart)) {
        $total_amount = totalPrice($cart) * 100; // Amount in paise (multiply by 100)
        
        // Create order with Razorpay
        $url = 'https://api.razorpay.com/v1/orders';
        $data = [
            'amount' => $total_amount,
            'currency' => 'INR',
            'receipt' => 'order_' . time(),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, RAZOR_KEY_ID . ':' . RAZOR_KEY_SECRET);

        $response = curl_exec($ch);
        curl_close($ch);

        $order_data = json_decode($response, true);
        
        if (isset($order_data['id'])) {
            $_SESSION['razor_order_id'] = $order_data['id'];
            $_SESSION['payment_initiated'] = true;
        } else {
            $payment_error = "Failed to initiate payment. Please try again.";
        }
    }
}

// Handle Razorpay verification (callback)
if (isset($_POST['razorpay_payment_id'])) {
    $razorpay_order_id = $_POST['razorpay_order_id'];
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    $razorpay_signature = $_POST['razorpay_signature'];
    
    $api_secret = RAZOR_KEY_SECRET;
    
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $api_secret);
    
    if ($generated_signature == $razorpay_signature) {
        // Payment successful
        $user_id = $_SESSION['user_id'];
        $cart = $_SESSION['cart'] ?? [];
        $total = totalPrice($cart);
        $order_date = date('Y-m-d H:i:s');

        // Save to orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_date, total) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $user_id, $order_date, $total);
        if (!$stmt->execute()) {
            error_log("Order insertion failed: " . $stmt->error);
        }
        $order_id = $conn->insert_id;
        $stmt->close();

        // Save order items
        foreach ($cart as $item) {
            $product_id = $item['id'];
            $name = $item['name'];
            $price = $item['price'];
            $quantity = $item['quantity'];
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisdi", $order_id, $product_id, $name, $price, $quantity);
            if (!$stmt->execute()) {
                error_log("Order item insertion failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // Clear cart and set success
        unset($_SESSION['cart']);
        $payment_success = true;
        $payment_id = $razorpay_payment_id;
    } else {
        $payment_error = "Payment verification failed. Please contact support.";
    }
}

// Helper function to calculate total price
function totalPrice($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Sparkle Nails</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .cart-items, .add-section {
            width: 100%;
        }
        .cart-item, .product {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cart-item img, .product img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
        }
        .cart-item-details, .product-details {
            flex-grow: 1;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-controls button {
            background: #ff6fa5;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .quantity-controls button:hover {
            background: #ff4d8c;
        }
        .cart-item button, .product button {
            background: #ff6fa5;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .cart-item button:hover, .product button:hover {
            background: #ff4d8c;
        }
        .payment-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success, .error {
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        footer {
            background: #ff6fa5;
            color: white;
            text-align: center;
            padding: 10px;
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

            // Razorpay payment initialization
            <?php if (isset($_SESSION['payment_initiated']) && $_SESSION['payment_initiated']): ?>
                var options = {
                    "key": "<?= addslashes(RAZOR_KEY_ID) ?>",
                    "amount": "<?= addslashes(totalPrice($_SESSION['cart'] ?? []) * 100) ?>",
                    "currency": "INR",
                    "name": "Sparkle Nails",
                    "description": "Purchase nail products",
                    "order_id": "<?= addslashes($_SESSION['razor_order_id']) ?>",
                    "handler": function (response) {
                        var paymentData = {
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature
                        };
                        
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'cart.php';
                        
                        for (var key in paymentData) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = paymentData[key];
                            form.appendChild(input);
                        }
                        
                        document.body.appendChild(form);
                        form.submit();
                    },
                    "prefill": {
                        "name": "Customer Name",
                        "email": "customer@example.com",
                        "contact": "9999999999"
                    },
                    "theme": {
                        "color": "#ff6fa5"
                    }
                };
                
                var rzp = new Razorpay(options);
                rzp.open();
                document.getElementById('payButton').disabled = true;
                <?php unset($_SESSION['payment_initiated']); ?>
            <?php endif; ?>
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
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="main-content">
        <div class="cart-items">
            <h2>Your Cart</h2>
            <?php
            $cart = $_SESSION['cart'] ?? [];
            if (!is_array($cart)) {
                error_log("Cart is not an array: " . var_export($cart, true));
                $cart = [];
            }
            if (empty($cart)): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <?php foreach ($cart as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($item['name'] ?? 'Unknown') ?>">
                        <div class="cart-item-details">
                            <strong><?= htmlspecialchars($item['name'] ?? 'Unknown') ?></strong><br>
                            ₹<?= htmlspecialchars($item['price'] ?? 0) ?> x 
                            <form method="POST" action="cart.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? 0) ?>">
                                <div class="quantity-controls">
                                    <button type="submit" name="adjust_quantity" value="decrement" onclick="this.form.querySelector('input[name=quantity]').value = <?= max(0, ($item['quantity'] ?? 1) - 1) ?>">-</button>
                                    <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity'] ?? 1) ?>" min="0" style="width: 50px; text-align: center;" onchange="this.form.submit()">
                                    <button type="submit" name="adjust_quantity" value="increment" onclick="this.form.querySelector('input[name=quantity]').value = <?= ($item['quantity'] ?? 1) + 1 ?>">+</button>
                                </div>
                            </form>
                        </div>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? 0) ?>">
                            <input type="hidden" name="remove_from_cart" value="1">
                            <button type="submit">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <div class="payment-section">
                    <p>Total: ₹<?= totalPrice($cart) ?></p>
                    <form method="POST">
                        <button type="submit" name="initiate_payment" id="payButton">💳 Pay Now with Razorpay</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (isset($payment_success)): ?>
                <p class="success">Payment Successful! Thank you for shopping 💖<br>Payment ID: <?= htmlspecialchars($payment_id) ?></p>
            <?php endif; ?>

            <?php if (isset($payment_error)): ?>
                <p class="error"><?= htmlspecialchars($payment_error) ?></p>
            <?php endif; ?>
        </div>

        <section class="add-section">
            <h2>➕ Add More Products</h2>
            <?php foreach ($products as $p): ?>
                <div class="product">
                    <img src="<?= htmlspecialchars($p['image'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($p['name'] ?? 'Unknown') ?>">
                    <div class="product-details">
                        <strong><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></strong><br>
                        ₹<?= htmlspecialchars($p['price'] ?? 0) ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? 0) ?>">
                        <button type="submit" name="add_to_cart">Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
    </div>

    <footer>
        <p>© 2025 Sparkle Nails. All Rights Reserved.</p>
        <a href="index.php">Home</a> | <a href="shop.php">Shop</a>
    </footer>
</body>
</html>