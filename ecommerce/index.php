<?php
session_start();
if (isset($_POST['logout'])) {
    session_unset(); // Remove all session variables
    session_destroy(); // Destroy the session
    header("Location: pages/login.php"); // Redirect to login page
    exit(); // Make sure no further code is executed after redirection
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: pages/login.php");
    exit();
}

include 'includes/db.php'; // Include the database connection

// Fetch products from the database
$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <!-- Include Tailwind CSS -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store</title>
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
            color: #fff;
        }

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header:hover {
            background: rgba(206, 98, 98, 0.15);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            color:rgb(52, 131, 196);
            transform: translateY(-2px);    
        }

        .header-container {
            color: rgba(0, 0, 0, 0.9);
            font-size: 1.2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-container h1 {
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .header-container h1:hover {
            color: #ffd700;
            transform: scale(1.05);
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.5);
        }

        nav {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.6s ease;
        }

        nav a:hover {
            color: #fff;
            border-color: #ffd700;
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        nav a:hover::before {
            left: 100%;
        }

        .cart-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-icon {
            width: 20px;
            height: 20px;
            transition: all 0.3s ease;
            filter: brightness(0) invert(1);
        }

        .cart-link:hover .cart-icon {
            transform: scale(1.2) rotate(10deg);
            filter: brightness(0) invert(1) sepia(1) saturate(10000%) hue-rotate(45deg);
        }

        .logout-button {
            color: rgba(255, 255, 255, 0.9);
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            border: 2px solid rgba(255, 107, 107, 0.5);
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 500;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        .logout-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.6s ease;
        }

        .logout-button:hover {
            background: linear-gradient(45deg, #ff8e8e, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
            border-color: #ff6b6b;
        }

        .logout-button:hover::before {
            left: 100%;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        main {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            animation: float 6s ease-in-out infinite;
        }

        main:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        main h2 {
            font-size: 2.2rem;
            font-weight: 300;
            letter-spacing: 1.5px;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        main h2:hover {
            color: #ffd700;
            transform: scale(1.05);
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.5);
        }
        .product-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    width: 80%; /* Take up most of the screen */
}

.product {
   background: linear-gradient(45deg,rgb(74, 140, 178),rgb(212, 209, 229));
    padding: 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    text-align: center;
    width: 23%; /* Set width for 4 products per row */
    transition: transform 0.3s ease-in-out;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    color:black;
    font-family: 'Arial', sans-serif;
    animation: float 6s ease-in-out infinite;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 20px;
    font-size: 1.1rem;
    font-weight: 500;
    line-height: 1.5;
}

.product:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    background: linear-gradient(45deg,rgb(74, 140, 178),rgb(212, 209, 229));
    transform: scale(1.05);
}


.product h3 {
    margin-bottom: 10px;
    font-size: 1.3em;
    color: #333;
}

.product p {
    font-size: 1em;
    color: #777;
    margin-bottom: 10px;
}

.product-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    margin: 10px 0;
}
        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg,rgb(240, 20, 20),rgb(57, 19, 247));
            border: none;
            border-radius: 25px;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(45deg,rgb(81, 240, 192),rgb(42, 37, 209));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
        }

        footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            text-align: center;
            padding: 30px 20px;
            margin-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        footer:hover {
            background: rgba(0, 0, 0, 0.4);
            color: #ffd700;
        }

        footer p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        footer:hover p {
            color: #ffd700;
            transform: scale(1.05);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        /* Empty state message */
        .empty-products {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            letter-spacing: 0.5px;
        }

        .empty-products:hover {
            color: #ffd700;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header-container h1 {
                font-size: 2rem;
            }

            nav {
                justify-content: center;
                gap: 10px;
            }

            nav a, .logout-button {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .main-container {
                padding: 20px 10px;
            }

            main {
                padding: 30px 20px;
            }

            main h2 {
                font-size: 1.8rem;
            }

            .product-list {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .product-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Welcome to Our Store</h1>
            <nav>
                <a href="pages/login.php">Login</a>
                <a href="pages/register.php">Register</a>
                <a href="pages/cart.php" class="cart-link">
                    <span class="cart-icon">🛒</span>
                    Cart
                </a>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-button">Logout</button>
                </form>
            </nav>
        </div>
    </header>
    
    <div class="main-container">
        <main>
            <h2>Products</h2>
            <div class="product-list">
                
                <?php if (empty($products)) : ?>
                 <p>No products available.</p>
                    <?php else : ?>
                        <?php foreach ($products as $product) : ?>
                            <div class="product">
                                <h3><?= htmlspecialchars($product['name']); ?></h3>
                                <p>Price:₹<?= number_format($product['price'], 2); ?></p>
                                <p><?= htmlspecialchars($product['description']); ?></p>
                                <?php if (!empty($product['image'])) : ?>
                                    <img src="images/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php endif; ?>
                                <form method="POST" action="pages/cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
            </div>
        </main>
    </div>
    
 
     <!-- Footer Section (Now at Bottom) -->
  <!-- Footer Section (Now at Bottom) -->
<footer class="bg-gray-900 text-gray-300">
    <div class="container mx-auto px-4 py-6 flex flex-col md:flex-row justify-between text-sm">
        <!-- Contact Info -->
        <div class="mb-4 md:mb-0">
            <h4 class="text-white font-semibold mb-2">Contact Us</h4>
            <p>Email: support@yourstore.com</p>
            <p>Phone: +91 1234567890</p>
            <p>Hours: Mon–Fri 9AM–6PM</p>
        </div>
        
        <!-- Social Media -->
        <div>
            <h4 class="text-white font-semibold mb-2">Follow Us</h4>
            <div class="flex space-x-4 text-lg">
                <a href="#" class="text-[#1877f2] hover:text-white transition">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="text-[#1da1f2] hover:text-white transition">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-[#c13584] hover:text-white transition">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="text-center text-gray-500 text-xs pb-4">
        &copy; 2025 Your Store. All rights reserved.
    </div>
</footer>


</div>

    </body>
    </html>