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
        
        /* New vertical layout styles */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            animation: slideInUp 1s ease-out;
            width: 100%;
        }
        
        .cart-section {
            width: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 248, 252, 0.95));
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(255, 111, 165, 0.15);
            border: 1px solid rgba(255, 111, 165, 0.2);
            animation: cartSectionSlideIn 1s ease-out;
        }
        
        .cart-items {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .vertical-heading {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 111, 165, 0.2);
        }
        
        .vertical-heading h2 {
            font-size: 2.2em;
            color: #1a1a2e;
            margin: 0;
            font-weight: 800;
            writing-mode: vertical-lr;
            transform: rotate(180deg);
            padding: 15px 10px;
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            border-right: 3px solid #ff6fa5;
            margin-right: 20px;
            animation: titleGlow 3s ease-in-out infinite;
        }
        
        .cart-content {
            flex-grow: 1;
        }
        
        .cart-item {
            background: linear-gradient(135deg, #fff, #fff8fc);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.12);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(255, 111, 165, 0.1);
            position: relative;
            overflow: hidden;
            animation: cartItemSlideIn 0.8s ease-out;
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 111, 165, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .cart-item:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 12px 25px rgba(255, 111, 165, 0.2);
            border-color: rgba(255, 111, 165, 0.3);
        }
        
        .cart-item:hover::before {
            left: 100%;
        }
        
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 15px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            filter: brightness(1.05) contrast(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .cart-item:hover img {
            transform: scale(1.1) rotate(3deg);
            filter: brightness(1.1) contrast(1.2) saturate(1.2);
        }
        
        .cart-item-details {
            flex-grow: 1;
            position: relative;
            z-index: 1;
        }
        
        .cart-item-details strong {
            font-size: 1.1em;
            color: #1a1a2e;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        
        .cart-item-details .price {
            font-size: 1em;
            color: #ff4d8c;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }
        
        .quantity-controls button {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-weight: 700;
            font-size: 0.9em;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(255, 111, 165, 0.3);
        }
        
        .quantity-controls button:hover {
            background: linear-gradient(135deg, #ff4d8c, #d4286d);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 111, 165, 0.5);
        }
        
        .quantity-controls input {
            width: 50px;
            text-align: center;
            padding: 6px;
            border: 1px solid #ff6fa5;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9em;
            background: linear-gradient(135deg, #fff, #fff8fc);
            transition: all 0.3s ease;
        }
        
        .quantity-controls input:focus {
            outline: none;
            border-color: #ff4d8c;
            box-shadow: 0 0 15px rgba(255, 77, 140, 0.3);
        }
        
        .cart-item button {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-weight: 600;
            font-size: 0.85em;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 111, 165, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cart-item button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .cart-item button:hover {
            background: linear-gradient(135deg, #ff4d8c, #d4286d);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 111, 165, 0.5);
        }
        
        .cart-item button:hover::before {
            left: 100%;
        }
        
        .payment-section {
            background: linear-gradient(135deg, #fff, #fff8fc);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.12);
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 111, 165, 0.1);
            position: relative;
            overflow: hidden;
            animation: paymentSlideIn 1s ease-out;
        }
        
        .payment-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 111, 165, 0.05), transparent);
            animation: paymentShimmer 4s linear infinite;
            pointer-events: none;
        }
        
        .payment-section h3 {
            font-size: 1.3em;
            color: #1a1a2e;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        
        .total-price {
            font-size: 1.5em;
            color: #ff4d8c;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            animation: pricePulse 2s ease-in-out infinite;
        }
        
        .pay-button {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c, #d4286d);
            background-size: 200% 200%;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(255, 111, 165, 0.4);
            width: 100%;
            animation: payButtonFloat 3s ease-in-out infinite, payButtonGradient 4s ease infinite;
        }
        
        .pay-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }
        
        .pay-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 111, 165, 0.6);
            animation-play-state: paused;
        }
        
        .pay-button:hover::before {
            left: 100%;
        }
        
        .pay-button:active {
            transform: translateY(-2px) scale(1.02);
        }
        
        .add-section {
            width: 100%;
            background: linear-gradient(135deg, rgba(255, 248, 252, 0.95), rgba(255, 240, 245, 0.95));
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(255, 111, 165, 0.15);
            border: 1px solid rgba(255, 111, 165, 0.2);
            animation: sidebarSlideIn 1s ease-out;
        }
        
        .add-section h2 {
            font-size: 1.8em;
            color: #ff4d8c;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(255, 77, 140, 0.2);
            animation: titleGlow 3s ease-in-out infinite;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 15px;
        }
        
        .product {
            background: linear-gradient(135deg, #fff, #fff8fc);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(255, 111, 165, 0.12);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(255, 111, 165, 0.1);
            position: relative;
            overflow: hidden;
            animation: productSlideIn 0.8s ease-out;
            text-align: center;
        }
        
        .product::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 111, 165, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .product:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 25px rgba(255, 111, 165, 0.2);
            border-color: rgba(255, 111, 165, 0.3);
        }
        
        .product:hover::before {
            left: 100%;
        }
        
        .product img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            filter: brightness(1.05) contrast(1.1);
        }
        
        .product:hover img {
            transform: scale(1.1) rotate(2deg);
            filter: brightness(1.1) contrast(1.2) saturate(1.2);
        }
        
        .product-details {
            flex-grow: 1;
            position: relative;
            z-index: 1;
            width: 100%;
        }
        
        .product-details strong {
            font-size: 1.1em;
            color: #1a1a2e;
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
        }
        
        .product-details .price {
            font-size: 1.1em;
            color: #ff4d8c;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .product button {
            background: linear-gradient(135deg, #ff6fa5, #ff4d8c);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-weight: 600;
            font-size: 0.9em;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 111, 165, 0.3);
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
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 111, 165, 0.5);
        }
        
        .product button:hover::before {
            left: 100%;
        }
        
        .success, .error {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 1.1em;
            animation: messageSlideIn 0.8s ease-out;
        }
        
        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #c3e6cb;
            box-shadow: 0 8px 25px rgba(21, 87, 36, 0.2);
        }
        
        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #f5c6cb;
            box-shadow: 0 8px 25px rgba(114, 28, 36, 0.2);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.3em;
            font-weight: 500;
            animation: emptyCartPulse 2s ease-in-out infinite;
        }
        
        footer {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: auto;
        }
        
        footer a {
            color: #ff6fa5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: #ff4d8c;
        }
        
        /* Enhanced Animations */
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes cartItemSlideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes productSlideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes paymentSlideIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        @keyframes sidebarSlideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes cartSectionSlideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes messageSlideIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes titleGlow {
            0%, 100% { text-shadow: 0 0 5px rgba(255, 111, 165, 0.3); }
            50% { text-shadow: 0 0 20px rgba(255, 111, 165, 0.6), 0 0 30px rgba(255, 111, 165, 0.4); }
        }
        
        @keyframes pricePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes payButtonFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }
        
        @keyframes payButtonGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes paymentShimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        @keyframes emptyCartPulse {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }
        
        /* Staggered animations for cart items */
        .cart-item:nth-child(1) { animation-delay: 0.1s; }
        .cart-item:nth-child(2) { animation-delay: 0.2s; }
        .cart-item:nth-child(3) { animation-delay: 0.3s; }
        .cart-item:nth-child(4) { animation-delay: 0.4s; }
        .cart-item:nth-child(5) { animation-delay: 0.5s; }
        
        .product:nth-child(1) { animation-delay: 0.1s; }
        .product:nth-child(2) { animation-delay: 0.2s; }
        .product:nth-child(3) { animation-delay: 0.3s; }
        .product:nth-child(4) { animation-delay: 0.4s; }
        .product:nth-child(5) { animation-delay: 0.5s; }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .cart-item img {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .vertical-heading {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .vertical-heading h2 {
                writing-mode: horizontal-tb;
                transform: none;
                border-right: none;
                border-bottom: 3px solid #ff6fa5;
                margin-right: 0;
                margin-bottom: 15px;
                padding: 10px 0;
            }
            
            .add-section {
                padding: 20px;
            }
            
            .cart-section {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product {
                padding: 15px;
            }
            
            .product img {
                width: 100px;
                height: 100px;
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
        <div class="cart-section">
            <div class="vertical-heading">
                <h2>🛒 Your Cart</h2>
                <div class="cart-content">
                    <?php
                    $cart = $_SESSION['cart'] ?? [];
                    if (!is_array($cart)) {
                        error_log("Cart is not an array: " . var_export($cart, true));
                        $cart = [];
                    }
                    if (empty($cart)): ?>
                        <div class="empty-cart">
                            <h3>🛒 Your cart is empty</h3>
                            <p>Add some beautiful nail products to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart as $item): ?>
                            <div class="cart-item">
                                <img src="<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($item['name'] ?? 'Unknown') ?>">
                                <div class="cart-item-details">
                                    <strong><?= htmlspecialchars($item['name'] ?? 'Unknown') ?></strong>
                                    <div class="price">₹<?= htmlspecialchars($item['price'] ?? 0) ?></div>
                                    <form method="POST" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? 0) ?>">
                                        <div class="quantity-controls">
                                            <button type="submit" name="adjust_quantity" value="decrement" onclick="this.form.querySelector('input[name=quantity]').value = <?= max(0, ($item['quantity'] ?? 1) - 1) ?>">-</button>
                                            <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity'] ?? 1) ?>" min="0" onchange="this.form.submit()">
                                            <button type="submit" name="adjust_quantity" value="increment" onclick="this.form.querySelector('input[name=quantity]').value = <?= ($item['quantity'] ?? 1) + 1 ?>">+</button>
                                        </div>
                                    </form>
                                </div>
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($item['id'] ?? 0) ?>">
                                    <input type="hidden" name="remove_from_cart" value="1">
                                    <button type="submit">🗑️ Remove</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (isset($payment_success)): ?>
                        <div class="success">Payment Successful! Thank you for shopping 💖<br>Payment ID: <?= htmlspecialchars($payment_id) ?></div>
                    <?php endif; ?>

                    <?php if (isset($payment_error)): ?>
                        <div class="error"><?= htmlspecialchars($payment_error) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($cart)): ?>
                <div class="payment-section">
                    <h3>💳 Order Summary</h3>
                    <div class="total-price">Total: ₹<?= totalPrice($cart) ?></div>
                    <form method="POST">
                        <button type="submit" name="initiate_payment" id="payButton" class="pay-button">💳 Pay Now with Razorpay</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="add-section">
            <h2>➕ Add More Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product">
                        <img src="<?= htmlspecialchars($p['image'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($p['name'] ?? 'Unknown') ?>">
                        <div class="product-details">
                            <strong><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></strong>
                            <div class="price">₹<?= htmlspecialchars($p['price'] ?? 0) ?></div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? 0) ?>">
                            <button type="submit" name="add_to_cart">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 Sparkle Nails. All Rights Reserved.</p>
        <a href="index.php">Home</a> | <a href="shop.php">Shop</a>
    </footer>
</body>
</html>