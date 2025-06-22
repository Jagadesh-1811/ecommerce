<?php
session_start();

// Admin access password - CHANGE THIS TO YOUR SECURE PASSWORD
$ADMIN_ACCESS_PASSWORD = 'Admin@123'; // Change this password

// Database configuration
$host = 'localhost';
$dbname = 'ecommerce';
$db_username = 'root';
$db_password = '';

$error_message = '';
$success_message = '';
$access_granted = false;

// Check if admin access password is provided
if (isset($_POST['access_password'])) {
    if ($_POST['access_password'] === $ADMIN_ACCESS_PASSWORD) {
        $_SESSION['admin_access'] = true;
        $access_granted = true;
    } else {
        $error_message = "Invalid access password!";
    }
}

// Check if admin access is already granted
if (isset($_SESSION['admin_access']) && $_SESSION['admin_access'] === true) {
    $access_granted = true;
}

// Handle admin registration
if ($access_granted && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $role = 'admin';
    
    // Validation
    if (empty($user) || empty($email) || empty($pass) || empty($confirm_pass)) {
        $error_message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } elseif (strlen($pass) < 8) {
        $error_message = "Admin password must be at least 8 characters long!";
    } elseif ($pass !== $confirm_pass) {
        $error_message = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $pass)) {
        $error_message = "Admin password must contain at least one uppercase letter, one lowercase letter, one number, and one special character!";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Email already exists!";
            } else {
                // Hash password and insert admin
                $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user, $email, $hashed_password, $role]);
                
                $success_message = "Admin account created successfully!";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $error_message = "Database connection failed. Please check your database credentials.";
            } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                $error_message = "Database not found. Please create the database first.";
            } else {
                $error_message = "Database error: Please contact system administrator.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Restricted Access</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #6f42c1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
            border: 2px solid rgba(220, 53, 69, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #6f42c1, #dc3545);
            animation: shimmer 2s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #666;
            font-size: 1rem;
        }

        .warning-badge {
            background: linear-gradient(135deg, #dc3545, #6f42c1);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #dc3545;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #dc3545;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #dc3545 0%, #6f42c1 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .back-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .back-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #6f42c1;
        }

        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .password-requirements h4 {
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            color: #6c757d;
            margin-bottom: 4px;
            position: relative;
            padding-left: 20px;
        }

        .password-requirements li::before {
            content: '•';
            color: #dc3545;
            position: absolute;
            left: 0;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 25px;
                margin: 10px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .form-group input {
                padding: 12px 15px 12px 45px;
            }

            .input-icon {
                left: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles">
        <?php for ($i = 0; $i < 40; $i++): ?>
            <div class="particle" style="
                width: <?= rand(3, 10) ?>px;
                height: <?= rand(3, 10) ?>px;
                left: <?= rand(0, 100) ?>%;
                top: <?= rand(0, 100) ?>%;
                animation-delay: <?= rand(0, 8) ?>s;
                animation-duration: <?= rand(6, 10) ?>s;
            "></div>
        <?php endfor; ?>
    </div>

    <div class="register-container">
        <?php if (!$access_granted): ?>
            <!-- Access Password Form -->
            <div class="header">
                <h1><i class="fas fa-shield-alt"></i> Restricted Access</h1>
                <p>Admin Registration - Authorization Required</p>
            </div>

            <div class="warning-badge">
                <i class="fas fa-exclamation-triangle"></i> Admin Access Only
            </div>

            <?php if ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group
">
                    <label for="access_password">Enter Admin Access Password</label>
                    <input type="password" id="access_password" name="access_password" required placeholder="Enter password">
                </div>
                <button type="submit" class="submit-btn">Grant Access</button>
            </form>
        <?php else: ?>
            <!-- Admin Registration Form -->
            <div class="header">
                <h1><i class="fas fa-user-plus"></i> Create Admin Account</h1>
                <p>Register a new admin user</p>
            </div>

            <?php if ($success_message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php elseif ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group
">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" required placeholder="Enter username">
                    </div>
                </div>
                <div class="form-group
">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" required placeholder="Enter email">
                    </div>
                </div>
                <div class="form-group
">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" required placeholder="Enter password">
                        <span class="password-toggle" onclick="togglePasswordVisibility()"><i class="fas fa-eye
"></i></span>
                    </div>
                </div>
                <div class="form-group
">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                        <span class="password-toggle" onclick="togglePasswordVisibility()"><i class="fas fa-eye
"></i></span>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Create Admin Account</button>
            </form>

            <div class="password-requirements">
                <h4>Password Requirements:</h4>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one lowercase letter</li>
                    <li>At least one number</li>
                    <li>At least one special character (e.g., @, #, $, etc.)</li>
                </ul>
            </div>

            <div class="back-link">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordFields = document.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                if (field.type === 'password') {
                    field.type = 'text';
                } else {
                    field.type = 'password';
                }
            });
        }
    </script>
</body>
</html>
