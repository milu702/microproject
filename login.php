<?php
session_start();

// Database Class
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "nailstore";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        // Create users table if it doesn't exist
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE,
                password VARCHAR(255),
                email VARCHAR(100) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
}

// User Class
class User {
    private $db;

    public function __construct($dbConn) {
        $this->db = $dbConn;
    }

    public function login($identifier, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Input Validation Class (Server-side)
class Validator {
    public static function validateLogin($identifier, $password) {
        $errors = [];
        
        if (empty($identifier)) {
            $errors[] = "Username or Email is required.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        return $errors;
    }
}

// Logic Execution
$db = new Database();
$user = new User($db->conn);

if (isset($_POST['login'])) {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];
    
    $errors = Validator::validateLogin($identifier, $password);
    
    if (empty($errors)) {
        if ($user->login($identifier, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid username/email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sparkle Nails</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #ffe6f0, #fff0f5);
            animation: fadeInBody 1s ease-in;
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

        .login-section {
            background: white;
            margin: 30px auto;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            border-radius: 25px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
            animation: fadeInCard 1s ease-in;
            text-align: center;
        }

        h2 {
            color: #ff4d8c;
            font-size: 2em;
            margin-bottom: 20px;
            animation: glowText 2s infinite;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #ff6fa5;
            border-radius: 10px;
            font-size: 1em;
            box-sizing: border-box;
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

        .error {
            color: red;
            margin: 5px 0;
            font-size: 0.9em;
            display: none;
            background: #ffe6f0;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ff4d8c;
        }

        .error.show {
            display: block;
            animation: fadeInError 0.5s ease-in, shakeError 0.5s ease-in-out;
        }

        .register-link {
            margin-top: 15px;
            color: #ff4d8c;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        @keyframes fadeInBody {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes fadeInHeader {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @keyframes fadeInCard {
            from {opacity: 0; transform: scale(0.95);}
            to {opacity: 1; transform: scale(1);}
        }

        @keyframes glowText {
            0% { text-shadow: 0 0 5px #ff6fa5; }
            50% { text-shadow: 0 0 20px #ff4d8c; }
            100% { text-shadow: 0 0 5px #ff6fa5; }
        }

        @keyframes fadeInError {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @keyframes shakeError {
            0%, 100% {transform: translateX(0);}
            20%, 60% {transform: translateX(-5px);}
            40%, 80% {transform: translateX(5px);}
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-bar">
            <div class="logo-container">
                <a href="index.php">
                    <img src="logo.png" alt="Sparkle Nails Logo" class="logo">
                </a>
                <h1><a href="index.php" style="color:white; text-decoration:none;">Sparkle Nails</a></h1>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="cart.php">Cart</a>
            </nav>
        </div>
    </header>

    <section class="login-section">
        <h2>🔒 Login</h2>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="error show"><?php echo $error; ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <form method="POST" id="loginForm" onsubmit="return validateLoginForm()">
            <div>
                <input type="text" name="identifier" id="identifier" placeholder="Username or Email" required>
                <p class="error" id="identifierError"></p>
            </div>
            <div>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <p class="error" id="passwordError"></p>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <a href="register.php" class="register-link">Don't have an account? Register here</a>
    </section>

    <script>
        function validateLoginForm() {
            let isValid = true;
            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value;
            
            // Reset error messages
            document.getElementById('identifierError').textContent = '';
            document.getElementById('passwordError').textContent = '';
            document.getElementById('identifierError').classList.remove('show');
            document.getElementById('passwordError').classList.remove('show');

            // Validate identifier
            if (!identifier) {
                document.getElementById('identifierError').textContent = 'Username or Email is required.';
                document.getElementById('identifierError').classList.add('show');
                isValid = false;
            }

            // Validate password
            if (!password) {
                document.getElementById('passwordError').textContent = 'Password is required.';
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters long.';
                document.getElementById('passwordError').classList.add('show');
                isValid = false;
            }

            return isValid;
        }

        // Real-time validation
        document.getElementById('identifier').addEventListener('input', function() {
            const identifier = this.value.trim();
            const errorElement = document.getElementById('identifierError');
            if (!identifier) {
                errorElement.textContent = 'Username or Email is required.';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const errorElement = document.getElementById('passwordError');
            if (!password) {
                errorElement.textContent = 'Password is required.';
                errorElement.classList.add('show');
            } else if (password.length < 6) {
                errorElement.textContent = 'Password must be at least 6 characters long.';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        });
    </script>
</body>
</html>