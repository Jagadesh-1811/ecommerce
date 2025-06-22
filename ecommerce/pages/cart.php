<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Handle adding product to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($quantity > 0) {
        // Check if product already exists in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Update existing item quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Handle cart updates
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $user_id]);
    } else {
        // If quantity is 0, remove the item
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    }
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Handle item removal
if (isset($_POST['remove_from_cart'])) {
    $cart_id = $_POST['cart_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    
    // Redirect to prevent form resubmission
    header("Location: cart.php");
    exit();
}

// Handle clear cart
if (isset($_POST['clear_cart'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    header("Location: cart.php");
    exit();
}

// Fetch the user's cart items
$stmt = $conn->prepare("SELECT cart.id AS cart_id, cart.product_id, products.name, products.price, cart.quantity, products.description
                        FROM cart 
                        JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_cost = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
            padding: 20px;
        }

        .cart-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .cart-header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .cart-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navigation {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 12px 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cart-table tr:hover {
            background-color: #f8f9ff;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: border-color 0.3s ease;
        }

        .quantity-input:focus {
            border-color: #667eea;
            outline: none;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-update {
            background: linear-gradient(45deg, #2196F3, #21CBF3);
            color: white;
            margin-left: 8px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }

        .btn-remove {
            background: linear-gradient(45deg, #f44336, #ff6b6b);
            color: white;
        }

        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
        }

        .btn-clear {
            background: linear-gradient(45deg, #ff9800, #ffb74d);
            color: white;
            margin-right: 10px;
        }

        .btn-clear:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
        }

        .btn-checkout {
            background: linear-gradient(45deg, #4CAF50, #66bb6a);
            color: white;
            padding: 15px 30px;
            font-size: 16px;
            min-width: 200px;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .total-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .total-amount {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-cart h2 {
            font-size: 2em;
            margin-bottom: 15px;
            color: #333;
        }

        .empty-cart p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }

        .continue-shopping {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .continue-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .product-name {
            font-weight: bold;
            color: #333;
        }

        .price {
            font-weight: bold;
            color: #4CAF50;
            font-size: 1.1em;
        }

        .subtotal {
            font-weight: bold;
            color: #2196F3;
            font-size: 1.1em;
        }

        .cart-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .cart-container {
                padding: 20px;
                margin: 10px;
            }
            
            .cart-table {
                font-size: 14px;
            }
            
            .cart-table th,
            .cart-table td {
                padding: 10px 5px;
            }
            
            .navigation {
                flex-direction: column;
                align-items: center;
            }
            
            .cart-actions {
                flex-direction: column;
                align-items: center;
            }
        }

        .success-message {
            background: linear-gradient(45deg, #4CAF50, #66bb6a);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <!-- Navigation -->
        <div class="navigation">
            <a href="../index.php" class="nav-link">🏠 Home</a>
            <a href="../index.php" class="nav-link">🛍️ Add Products</a>
        </div>

        <div class="cart-header">
            <h1>🛒 Your Shopping Cart</h1>
            <p>Review your items and proceed to checkout</p>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>🛒 Your cart is empty!</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <p>Start shopping and add some amazing products!</p>
                <a href="products.php" class="continue-shopping">🛍️ Start Shopping</a>
                <a href="../index.php" class="continue-shopping">🏠 Go Home</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total_cost += $subtotal;
                        ?>
                        <tr>
                            <td class="product-name"><?= htmlspecialchars($item['name']); ?></td>
                            <td class="price">₹<?= number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                                    <input type="number" name="quantity" value="<?= $item['quantity']; ?>" 
                                           min="1" max="100" class="quantity-input">
                                    <button type="submit" name="update_quantity" class="btn btn-update">Update</button>
                                </form>
                            </td>
                            <td class="subtotal"><?= number_format($subtotal, 2); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id']; ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-remove" 
                                            onclick="return confirm('Are you sure you want to remove this item from your cart?')">
                                        🗑️ Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-amount">
                    💰 Total: ₹<?= number_format($total_cost, 2); ?>
                </div>
                
                <div class="cart-actions">
                    <a href="../index.php" class="continue-shopping">🛍️ Continue Shopping</a>
                    
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="clear_cart" class="btn btn-clear" 
                                onclick="return confirm('Are you sure you want to clear your entire cart?')">
                            🗑️ Clear Cart
                        </button>
                    </form>
                    
                    <button class="btn btn-checkout" onclick="location.href='../checkout.php';">
                        💳 Proceed to Checkout
                        
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
<footer class="bg-blue-900 text-blue-100">
    <div class="container mx-auto px-4 py-6 flex flex-col md:flex-row justify-between text-sm">
        <!-- Contact Info -->
        <div class="mb-4 md:mb-0">
            <h4 class="text-blue-50 font-semibold mb-2">Contact Us</h4>
            <p>Email: support@yourstore.com</p>
            <p>Phone: +91 1234567890</p>
            <p>Hours: Mon–Fri 9AM–6PM</p>
        </div>

        <!-- Social Media -->
        <div>
            <h4 class="text-blue-50 font-semibold mb-2">Follow Us</h4>
            <div class="flex space-x-4 text-lg">
                <a href="#" class="text-blue-400 hover:text-white transition">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="text-blue-400 hover:text-white transition">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-blue-400 hover:text-white transition">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="text-center text-blue-200 text-xs pb-4">
        &copy; 2025 Your Store. All rights reserved.
    </div>
</footer>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
   
</body>
</html>