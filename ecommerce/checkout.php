<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch cart items - removed image_url from SELECT
$stmt = $conn->prepare("SELECT cart.id AS cart_id, cart.product_id, products.name, products.price, cart.quantity, products.description
                        FROM cart 
                        JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: cart/cart.php");
    exit();
}

// Calculate costs
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = $subtotal >= 1000 ? 0 : 50.00; // Free delivery on orders above ₹1000
$tax_rate = 0.18; // 18% GST
$tax_amount = $subtotal * $tax_rate;
$total_cost = $subtotal + $delivery_fee + $tax_amount;

// Handle order placement
if (isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    $delivery_notes = trim($_POST['delivery_notes'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($shipping_address)) $errors[] = "Shipping address is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = "Please enter a valid 10-digit phone number.";
    if (!in_array($payment_method, ['online', 'cod'])) $errors[] = "Please select a payment method.";
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Create order with all required fields from schema
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, delivery_phone, delivery_notes, delivery_fee, tax_amount, status, payment_status, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW(), NOW())");
            $stmt->execute([$user_id, $total_cost, $shipping_address, $phone, $delivery_notes, $delivery_fee, $tax_amount]);
            $order_id = $conn->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            
            // Redirect based on payment method
            if ($payment_method === 'online') {
                header("Location: payment.php?order_id=" . $order_id);
            } else {
                // Update order status for COD
                $stmt = $conn->prepare("UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$order_id]);
                header("Location: order_success.php?order_id=" . $order_id);
            }
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Error placing order: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
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
    <title>Checkout - Complete Your Order</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .checkout-header h1 {
            font-size: 2.5em;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .checkout-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control[readonly] {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .payment-option:hover {
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
        }

        .payment-option input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
        }

        .payment-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .order-summary {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9em;
        }

        .item-price {
            font-weight: 600;
            color: #4CAF50;
            font-size: 1.1em;
        }

        .cost-breakdown {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .cost-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .cost-row.total {
            color: #333;
            font-weight: bold;
            font-size: 1.3em;
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 15px;
        }

        .savings-badge {
            background: linear-gradient(45deg, #4CAF50, #66bb6a);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 10px;
        }

        .place-order-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, #4CAF50, #66bb6a);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(76, 175, 80, 0.4);
        }

        .place-order-btn:active {
            transform: translateY(0);
        }

        .back-to-cart {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            background: linear-gradient(45deg, #f44336, #ff6b6b);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: #e8f5e8;
            border-radius: 10px;
            margin-top: 20px;
            color: #2e7d32;
        }

        .delivery-info {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            color: #856404;
        }

        .delivery-info strong {
            color: #533f03;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .checkout-container {
                padding: 20px;
                margin: 10px;
            }
            
            .checkout-header h1 {
                font-size: 2em;
            }
        }

        .required {
            color: #f44336;
        }

        .field-error {
            color: #f44336;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .form-control.error {
            border-color: #f44336;
            background: #ffebee;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <a href="cart/cart.php" class="back-to-cart">← Back to Cart</a>
        
        <div class="checkout-header">
            <h1>🛒 Checkout</h1>
            <p>Complete your order and choose your payment method</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="loading" id="loadingDiv">
            <div class="spinner"></div>
            <p>Processing your order...</p>
        </div>

        <form method="POST" id="checkoutForm">
            <div class="checkout-content">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h2 class="section-title">📦 Shipping Information</h2>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" class="form-control" 
                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="Enter your 10-digit phone number" required maxlength="10">
                        <div class="field-error" id="phoneError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address <span class="required">*</span></label>
                        <textarea id="shipping_address" name="shipping_address" class="form-control" 
                                  rows="4" placeholder="Enter your complete shipping address including city, state, and PIN code" required></textarea>
                        <div class="field-error" id="addressError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_notes">Delivery Notes (Optional)</label>
                        <textarea id="delivery_notes" name="delivery_notes" class="form-control" 
                                  rows="2" placeholder="Any special delivery instructions..."></textarea>
                    </div>
                    
                    <div class="delivery-info">
                        <strong>📍 Delivery Information:</strong><br>
                        • Standard delivery: 2-5 business days<br>
                        • Delivery fee: ₹<?= number_format($delivery_fee, 2) ?><?= $delivery_fee == 0 ? ' <span class="savings-badge">FREE!</span>' : '' ?><br>
                        <?php if ($delivery_fee > 0): ?>
                        • Free delivery on orders above ₹1000<br>
                        <?php endif; ?>
                        • Cash on delivery available
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section">
                    <h2 class="section-title">💳 Payment Method</h2>
                    
                    <div class="payment-methods">
                        <div class="payment-option" onclick="selectPayment('online')">
                            <input type="radio" name="payment_method" value="online" id="online_payment">
                            <label for="online_payment">
                                <strong>💳 Online Payment</strong><br>
                                <small>Credit/Debit Card, UPI, Net Banking, Wallets</small>
                            </label>
                        </div>
                        
                        <div class="payment-option selected" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" value="cod" id="cod_payment" checked>
                            <label for="cod_payment">
                                <strong>💰 Cash on Delivery</strong><br>
                                <small>Pay when you receive your order</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="security-badge">
                        <span>🔒</span>
                        <span>Your payment information is secure and encrypted</span>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h2 class="section-title">📋 Order Summary (<?= count($cart_items) ?> items)</h2>
                
                <?php foreach ($cart_items as $item): ?>
                    <?php $item_subtotal = $item['price'] * $item['quantity']; ?>
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-quantity">Quantity: <?= $item['quantity'] ?> × ₹<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <div class="item-price">₹<?= number_format($item_subtotal, 2) ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="cost-breakdown">
                    <div class="cost-row">
                        <span>Subtotal:</span>
                        <span>₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="cost-row">
                        <span>Delivery Fee:</span>
                        <span>
                            <?php if ($delivery_fee == 0): ?>
                                <strike>₹50.00</strike> <span class="savings-badge">FREE!</span>
                            <?php else: ?>
                                ₹<?= number_format($delivery_fee, 2) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="cost-row">
                        <span>Tax (GST 18%):</span>
                        <span>₹<?= number_format($tax_amount, 2) ?></span>
                    </div>
                    <div class="cost-row total">
                        <span>Total Amount:</span>
                        <span>₹<?= number_format($total_cost, 2) ?></span>
                    </div>
                </div>
            </div>

            <button type="submit" name="place_order" class="place-order-btn">
                🛒 Place Order - ₹<?= number_format($total_cost, 2) ?>
            </button>
        </form>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script>
        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(method + '_payment').checked = true;
        }

        // Real-time validation
        function validateField(fieldId, value, validationFn, errorMsg) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + 'Error');
            
            if (!validationFn(value)) {
                field.classList.add('error');
                errorDiv.textContent = errorMsg;
                return false;
            } else {
                field.classList.remove('error');
                errorDiv.textContent = '';
                return true;
            }
        }

        // Phone validation
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            e.target.value = value;
            
            validateField('phone', value, 
                val => /^[6-9]\d{9}$/.test(val), 
                'Please enter a valid 10-digit phone number starting with 6-9'
            );
        });

        // Address validation
        document.getElementById('shipping_address').addEventListener('blur', function(e) {
            validateField('shipping_address', e.target.value, 
                val => val.trim().length >= 10, 
                'Please enter a complete address (minimum 10 characters)'
            );
        });

        // Form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const address = document.getElementById('shipping_address').value.trim();
            
            let isValid = true;
            
            // Validate phone
            if (!validateField('phone', phone, 
                val => /^[6-9]\d{9}$/.test(val), 
                'Please enter a valid 10-digit phone number starting with 6-9')) {
                isValid = false;
            }
            
            // Validate address
            if (!validateField('shipping_address', address, 
                val => val.length >= 10, 
                'Please enter a complete address (minimum 10 characters)')) {
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            document.getElementById('loadingDiv').style.display = 'block';
            document.getElementById('checkoutForm').style.display = 'none';
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on first empty required field
            const phone = document.getElementById('phone');
            const address = document.getElementById('shipping_address');
            
            if (!phone.value) {
                phone.focus();
            } else if (!address.value) {
                address.focus();
            }
        });
    </script>
</body>
</html>
