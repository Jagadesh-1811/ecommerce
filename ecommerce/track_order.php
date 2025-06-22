<?php
// track_order.php
session_start();
include 'includes/db.php';

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
$order = null;
$order_items = [];
$tracking_history = [];
$error_message = '';

// If user is logged in, verify order belongs to them
$user_id = $_SESSION['user_id'] ?? null;

if ($order_id) {
    try {
        // Fetch order details
        if ($user_id) {
            // Logged in user - verify ownership
            $stmt = $conn->prepare("SELECT o.*, u.username, u.email 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.id 
                                   WHERE o.id = ? AND o.user_id = ?");
            $stmt->execute([$order_id, $user_id]);
        } else {
            // Guest tracking - anyone can track with order ID
            $stmt = $conn->prepare("SELECT o.*, u.username, u.email 
                                   FROM orders o 
                                   JOIN users u ON o.user_id = u.id 
                                   WHERE o.id = ?");
            $stmt->execute([$order_id]);
        }
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Fetch order items
            $stmt = $conn->prepare("SELECT oi.*, p.name, p.description 
                                   FROM order_items oi 
                                   JOIN products p ON oi.product_id = p.id 
                                   WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create tracking history based on order status
            $tracking_history = generateTrackingHistory($order);
        } else {
            $error_message = "Order not found or access denied.";
        }
    } catch (Exception $e) {
        $error_message = "Error fetching order details: " . $e->getMessage();
    }
}

// Function to generate tracking history based on order status and dates
function generateTrackingHistory($order) {
    $history = [];
    $order_date = new DateTime($order['created_at']);
    $current_date = new DateTime();
    
    // Order placed
    $history[] = [
        'status' => 'Order Placed',
        'description' => 'Your order has been successfully placed and payment is being processed.',
        'timestamp' => $order_date->format('Y-m-d H:i:s'),
        'completed' => true,
        'icon' => '📝'
    ];
    
    // Order confirmed
    $confirmed_date = clone $order_date;
    $confirmed_date->add(new DateInterval('PT30M')); // 30 minutes after order
    $history[] = [
        'status' => 'Order Confirmed',
        'description' => 'Order confirmed and being prepared for processing.',
        'timestamp' => $confirmed_date->format('Y-m-d H:i:s'),
        'completed' => in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered']),
        'icon' => '✅'
    ];
    
    // Processing
    $processing_date = clone $order_date;
    $processing_date->add(new DateInterval('P1D')); // 1 day after order
    $history[] = [
        'status' => 'Processing',
        'description' => 'Your order is being processed and packaged.',
        'timestamp' => $processing_date->format('Y-m-d H:i:s'),
        'completed' => in_array($order['status'], ['processing', 'shipped', 'delivered']),
        'icon' => '📦'
    ];
    
    // Shipped
    $shipped_date = clone $order_date;
    $shipped_date->add(new DateInterval('P2D')); // 2 days after order
    $history[] = [
        'status' => 'Shipped',
        'description' => 'Your order has been shipped and is on its way to you.',
        'timestamp' => $shipped_date->format('Y-m-d H:i:s'),
        'completed' => in_array($order['status'], ['shipped', 'delivered']),
        'icon' => '🚚'
    ];
    
    // Out for delivery
    $delivery_date = clone $order_date;
    $delivery_date->add(new DateInterval('P4D')); // 4 days after order
    $history[] = [
        'status' => 'Out for Delivery',
        'description' => 'Your order is out for delivery and will reach you soon.',
        'timestamp' => $delivery_date->format('Y-m-d H:i:s'),
        'completed' => $order['status'] === 'delivered',
        'icon' => '🛵'
    ];
    
    // Delivered
    $final_delivery_date = clone $order_date;
    $final_delivery_date->add(new DateInterval('P5D')); // 5 days after order
    $history[] = [
        'status' => 'Delivered',
        'description' => 'Your order has been successfully delivered.',
        'timestamp' => $final_delivery_date->format('Y-m-d H:i:s'),
        'completed' => $order['status'] === 'delivered',
        'icon' => '🎉'
    ];
    
    return $history;
}

// Function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Function to get status color
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending': return '#ff9800';
        case 'confirmed': return '#2196f3';
        case 'processing': return '#9c27b0';
        case 'shipped': return '#4caf50';
        case 'delivered': return '#4caf50';
        case 'cancelled': return '#f44336';
        default: return '#757575';
    }
}

// Function to calculate delivery progress
function getDeliveryProgress($status) {
    switch (strtolower($status)) {
        case 'pending': return 20;
        case 'confirmed': return 40;
        case 'processing': return 60;
        case 'shipped': return 80;
        case 'delivered': return 100;
        default: return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <!-- Font Awesome CDN (Place in <head>) -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order</title>
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .search-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
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

        .track-btn {
            padding: 12px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: end;
        }

        .track-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            background: linear-gradient(45deg, #f44336, #ff6b6b);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .order-details, .delivery-info {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }

        .progress-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .progress-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #4caf50, #66bb6a);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .tracking-timeline {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding: 20px 0;
            margin-left: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -37px;
            top: 25px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e0e0e0;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .timeline-item.completed::before {
            background: #4caf50;
        }

        .timeline-item.current::before {
            background: #2196f3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(33, 150, 243, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); }
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #e0e0e0;
        }

        .timeline-item.completed .timeline-content {
            border-left-color: #4caf50;
            background: #f1f8e9;
        }

        .timeline-item.current .timeline-content {
            border-left-color: #2196f3;
            background: #e3f2fd;
        }

        .timeline-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .timeline-title {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        .timeline-time {
            font-size: 0.9rem;
            color: #666;
            margin-left: auto;
        }

        .timeline-description {
            color: #555;
            line-height: 1.5;
        }

        .order-items {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .item-price {
            font-weight: 600;
            color: #4caf50;
            font-size: 1.1rem;
        }

        .back-link {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .content {
                padding: 20px;
            }

            .order-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .search-form {
                flex-direction: column;
            }

            .form-group {
                min-width: auto;
            }

            .timeline-item {
                margin-left: 20px;
            }

            .timeline-item::before {
                left: -27px;
            }
        }

        .live-tracking {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            border: 1px solid #4caf50;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2e7d32;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Track Your Order</h1>
            <p>Enter your order ID to get real-time tracking information</p>
        </div>

        <div class="content">
            <?php if ($user_id): ?>
                <a href="index.php" class="back-link">← Back to Home</a>
            <?php endif; ?>

            <!-- Search Section -->
            <div class="search-section">
                <form method="POST" class="search-form">
                    <div class="form-group">
                        <label for="order_id">Order ID</label>
                        <input type="text" id="order_id" name="order_id" class="form-control" 
                               placeholder="Enter your order ID" value="<?php echo htmlspecialchars($order_id ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="track-btn">🔍 Track Order</button>
                </form>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($order): ?>
                <!-- Live Tracking Indicator -->
                <div class="live-tracking">
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        Live Tracking - Last updated: <?php echo date('M j, Y g:i A'); ?>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="order-info">
                    <div class="order-details">
                        <h3 class="section-title">📋 Order Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['id']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value"><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge" style="background-color: <?php echo getStatusColor($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment:</span>
                            <span class="detail-value"><?php echo ucfirst($order['payment_status']); ?></span>
                        </div>
                    </div>

                    <div class="delivery-info">
                        <h3 class="section-title">🚚 Delivery Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Customer:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['delivery_phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Est. Delivery:</span>
                            <span class="detail-value"><?php echo date('M j, Y', strtotime($order['created_at'] . ' +5 days')); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-section">
                    <h3 class="section-title">📊 Delivery Progress</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo getDeliveryProgress($order['status']); ?>%"></div>
                    </div>
                    <p style="text-align: center; margin-top: 10px; color: #666;">
                        <?php echo getDeliveryProgress($order['status']); ?>% Complete
                    </p>
                </div>

                <!-- Tracking Timeline -->
                <div class="tracking-timeline">
                    <h3 class="section-title">🕐 Tracking History</h3>
                    <div class="timeline">
                        <?php 
                        $current_status_found = false;
                        foreach ($tracking_history as $index => $event): 
                            $is_current = !$current_status_found && !$event['completed'];
                            if ($is_current) $current_status_found = true;
                        ?>
                            <div class="timeline-item <?php echo $event['completed'] ? 'completed' : ($is_current ? 'current' : ''); ?>">
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span><?php echo $event['icon']; ?></span>
                                        <span class="timeline-title"><?php echo $event['status']; ?></span>
                                        <span class="timeline-time">
                                            <?php 
                                            if ($event['completed'] || $is_current) {
                                                echo date('M j, g:i A', strtotime($event['timestamp']));
                                            } else {
                                                echo 'Pending';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="timeline-description">
                                        <?php echo $event['description']; ?>
                                        <?php if ($is_current): ?>
                                            <strong> (Current Status)</strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-items">
                    <h3 class="section-title">🛍️ Order Items</h3>
                    <?php foreach ($order_items as $item): ?>
                        <?php $item_total = $item['price'] * $item['quantity']; ?>
                        <div class="item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-quantity">Qty: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?></div>
                            </div>
                            <div class="item-price"><?php echo formatCurrency($item_total); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br><br>
    <br><br><br>
<br>
<br>
<br>


   <footer class="bg-blue-900 text-black-100">
    <div class="container mx-auto px-4 py-6 flex flex-col md:flex-row justify-between text-sm">
        <!-- Contact Info -->
        <div class="mb-4 md:mb-0">
            <h4 class="text-white-500 font-bold mb-2">Contact Us</h4>
            <p>Email: support@yourstore.com</p>
            <p>Phone: +91 1234567890</p>
            <p>Hours: Mon–Fri 9AM–6PM</p>
        </div>

        <!-- Social Media -->
        <div>
            <h4 class="text-blue-50 font-semibold mb-2">Follow Us</h4>
            <div class="flex space-x-4 text-lg">
                <a href="#" class="text-blue-400 hover:text-black transition">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="text-blue-400 hover:text-black transition">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-blue-400 hover:text-black transition">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="text-center text-blue-200 text-xs pb-4">
        &copy; 2025 Your Store. All rights reserved.
    </div>
</footer>

   


    <script>
        // Auto-refresh page every 30 seconds if tracking an order
        <?php if ($order): ?>
        setInterval(function() {
            if (document.querySelector('.live-tracking')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>

        // Format order ID input (remove spaces and convert to uppercase)
        document.getElementById('order_id').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').toUpperCase();
            e.target.value = value;
        });

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>