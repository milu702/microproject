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

    public function register($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        return $stmt->execute();
    }
}

// Input Validation Class (Server-side)
class Validator {
    public static function validateRegister($username, $email, $password, $confirmPassword) {
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters.";
        } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }

        return $errors;
    }
}

// Logic Execution
$db = new Database();
$user = new User($db->conn);

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = Validator::validateRegister($username, $email, $password, $confirmPassword);
    
    if (empty($errors)) {
        if ($user->register($username, $email, $password)) {
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed! Username or email already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sparkle Nails</title>
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

        .register-section {
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

        .login-link {
            margin-top: 15px;
            color: #ff4d8c;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link:hover {
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

    <section class="register-section">
        <h2>✨ Register</h2>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="error show"><?php echo $error; ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <form method="POST" id="registerForm" onsubmit="return validateRegisterForm()">
            <div>
                <input type="text" name="username" id="username" placeholder="Username" required>
                <p class="error" id="usernameError"></p>
            </div>
            <div>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <p class="error" id="emailError"></p>
            </div>
            <div>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <p class="error" id="passwordError"></p>
            </div>
            <div>
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required>
                <p class="error" id="confirmPasswordError"></p>
            </div>
            <button type="submit" name="register">Register</button>
        </form>
        <a href="login.php" class="login-link">Already have an account? Login here</a>
    </section>

    <script>
        function validateRegisterForm() {
            let isValid = true;
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const usernameRegex = /^[a-zA-Z0-9_]+$/;

            // Reset error messages
            document.getElementById('usernameError').textContent = '';
            document.getElementById('emailError').textContent = '';
            document.getElementById('passwordError').textContent = '';
            document.getElementById('confirmPasswordError').textContent = '';
            document.getElementById('usernameError').classList.remove('show');
            document.getElementById('emailError').classList.remove('show');
            document.getElementById('passwordError').classList.remove('show');
            document.getElementById('confirmPasswordError').classList.remove('show');

            // Validate username
            if (!username) {
                document.getElementById('usernameError').textContent = 'Username is required.';
                document.getElementById('usernameError').classList.add('show');
                isValid = false;
            } else if (username.length < 3 || username.length > 50) {
                document.getElementById('usernameError').textContent = 'Username must be between 3 and 50 characters.';
                document.getElementById('usernameError').classList.add('show');
                isValid = false;
            } else if (!usernameRegex.test(username)) {
                document.getElementById('usernameError').textContent = 'Username can only contain letters, numbers, and underscores.';
                document.getElementById('usernameError').classList.add('show');
                isValid = false;
            }

            // Validate email
            if (!email) {
                document.getElementById('emailError').textContent = 'Email is required.';
                document.getElementById('emailError').classList.add('show');
                isValid = false;
            } else if (!emailRegex.test(email)) {
                document.getElementById('emailError').textContent = 'Invalid email format.';
                document.getElementById('emailError').classList.add('show');
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

            // Validate confirm password
            if (password !== confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
                document.getElementById('confirmPasswordError').classList.add('show');
                isValid = false;
            }

            return isValid;
        }

        // Real-time validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value.trim();
            const errorElement = document.getElementById('usernameError');
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!username) {
                errorElement.textContent = 'Username is required.';
                errorElement.classList.add('show');
            } else if (username.length < 3 || username.length > 50) {
                errorElement.textContent = 'Username must be between 3 and 50 characters.';
                errorElement.classList.add('show');
            } else if (!usernameRegex.test(username)) {
                errorElement.textContent = 'Username can only contain letters, numbers, and underscores.';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        });

        document.getElementById('email').addEventListener('input', function() {
            const email = this.value.trim();
            const errorElement = document.getElementById('emailError');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                errorElement.textContent = 'Email is required.';
                errorElement.classList.add('show');
            } else if (!emailRegex.test(email)) {
                errorElement.textContent = 'Invalid email format.';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const errorElement = document.getElementById('passwordError');
            const confirmPassword = document.getElementById('confirmPassword').value;
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
            // Re-validate confirm password on password change
            if (confirmPassword && password !== confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
                document.getElementById('confirmPasswordError').classList.add('show');
            } else if (confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = '';
                document.getElementById('confirmPasswordError').classList.remove('show');
            }
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const confirmPassword = this.value;
            const password = document.getElementById('password').value;
            const errorElement = document.getElementById('confirmPasswordError');
            if (password !== confirmPassword) {
                errorElement.textContent = 'Passwords do not match.';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        });
    </script>
</body>
</html>