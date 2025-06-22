<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            transform: translateY(0);
            animation: float 6s ease-in-out infinite;
        }

        .dashboard-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dashboard-title {
            text-align: center;
            margin-bottom: 40px;
            color: #fff;
            font-size: 2.8rem;
            font-weight: 300;
            letter-spacing: 3px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .dashboard-title:hover {
            color: #ffd700;
            transform: scale(1.05);
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.5);
        }

        .nav-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-button {
            text-decoration: none;
            padding: 20px 30px;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.4s ease;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .nav-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .nav-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(76, 175, 80, 0.5);
            background: linear-gradient(45deg, #45a049, #4CAF50);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .nav-button:hover::before {
            left: 100%;
        }

        .nav-button:active {
            transform: translateY(-2px) scale(1.02);
        }

        .logout-button {
            background: linear-gradient(45deg, #f44336, #e53935);
            box-shadow: 0 8px 25px rgba(244, 67, 54, 0.3);
        }

        .logout-button:hover {
            background: linear-gradient(45deg, #e53935, #f44336);
            box-shadow: 0 15px 40px rgba(244, 67, 54, 0.5);
        }

        .welcome-message {
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
            margin-bottom: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .welcome-message:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.02);
            color: #ffd700;
        }

        .dashboard-footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            letter-spacing: 1px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .dashboard-footer:hover {
            color: rgba(255, 255, 255, 0.9);
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .admin-icon {
            font-size: 4rem;
            color: #ffd700;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-button {
            animation: slideIn 0.6s ease-out forwards;
        }

        .nav-button:nth-child(1) { animation-delay: 0.1s; }
        .nav-button:nth-child(2) { animation-delay: 0.2s; }
        .nav-button:nth-child(3) { animation-delay: 0.3s; }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .dashboard-title {
                font-size: 2.2rem;
                letter-spacing: 2px;
            }
            
            .nav-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .nav-button {
                padding: 18px 25px;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-title {
                font-size: 1.8rem;
                letter-spacing: 1px;
            }
            
            .welcome-message {
                font-size: 1rem;
            }
        }

        /* Additional interactive elements */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ffd700;
            display: block;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="admin-icon">Dashboard</div>
        <h2 class="dashboard-title">Admin Dashboard</h2>
        
        <div class="welcome-message">
            Welcome back, Administrator! Manage your system with ease.
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <span class="stat-number">∞</span>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">✓</span>
                <div class="stat-label">Active Status</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"></span>
                <div class="stat-label">out</div>
            </div>
        </div>
        
        <nav class="nav-container">
            <a href="add_product.php" class="nav-button">
                Add Product
            </a>
            <a href="manage_products.php" class="nav-button">
                Manage Products
            </a>
            <a href="logout.php" class="nav-button logout-button">
                Logout
            </a>
        </nav>
        
        <div class="dashboard-footer">
            <p>&copy; <?php echo date("Y"); ?> Admin Dashboard - Powered by Innovation</p>
        </div>
    </div>
</body>
</html>