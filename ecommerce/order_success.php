<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'ecommerce';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", $user, $password);
    // Set the PDO error mode to exception  
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set the error mode to exception for better error handling

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get order ID from URL parameter or session
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!$order_id || !$user_id) {
    header('Location: index.php');
    exit();
}

// Fetch order details with user information
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: index.php');
        exit();
    }
} catch(PDOException $e) {
    die("Error fetching order: " . $e->getMessage());
}

// Helper function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Helper function to format date
function formatDate($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'confirmed': return 'bg-blue-100 text-blue-800';
        case 'preparing': return 'bg-orange-100 text-orange-800';
        case 'ready': return 'bg-purple-100 text-purple-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getPaymentStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'failed': return 'bg-red-100 text-red-800';
        case 'refunded': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Thank You!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-animation {
            animation: bounce 0.6s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0,-15px,0);
            }
            70% {
                transform: translate3d(0,-7px,0);
            }
            90% {
                transform: translate3d(0,-2px,0);
            }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Your Store</h1>
                <nav class="hidden md:flex space-x-6">
                    <a href="index.php" class="hover:text-gray-200 transition">Home</a>
                    <a href="orders.php" class="hover:text-gray-200 transition">My Orders</a>
                    <a href="profile.php" class="hover:text-gray-200 transition">Profile</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Success Message -->
        <div class="text-center mb-8">
            <div class="success-animation inline-block">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-3xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600 text-lg">Thank you for your purchase. Your order has been successfully placed.</p>
        </div>

        <!-- Order Details Card -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Order Header -->
                <div class="gradient-bg text-white p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold mb-1">Order #<?php echo $order['id']; ?></h2>
                            <p class="text-blue-100">Placed on <?php echo formatDate($order['created_at']); ?></p>
                        </div>
                        <div class="mt-4 md:mt-0 text-right">
                            <p class="text-2xl font-bold"><?php echo formatCurrency($order['total_amount']); ?></p>
                            <p class="text-blue-100">Total Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Order Status</h3>
                            <div class="flex items-center space-x-3">
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="text-gray-500">•</span>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>">
                                    Payment <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Estimated Delivery</h3>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-truck mr-2"></i>
                                <?php if ($order['delivery_date']): ?>
                                    <span><?php echo formatDate($order['delivery_date']); ?></span>
                                <?php else: ?>
                                    <span>Within 2-3 business days</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium"><?php echo formatCurrency($order['total_amount'] - $order['delivery_fee'] - ($order['tax_amount'] ?? 0)); ?></span>
                        </div>
                        <?php if ($order['tax_amount']): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-medium"><?php echo formatCurrency($order['tax_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Delivery Fee</span>
                            <span class="font-medium"><?php echo formatCurrency($order['delivery_fee']); ?></span>
                        </div>
                        <div class="border-t pt-3 flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-800">Total</span>
                            <span class="text-lg font-bold text-gray-800"><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Delivery Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Delivery Address</h4>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Contact Information</h4>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-phone mr-2"></i>
                                <?php echo htmlspecialchars($order['delivery_phone']); ?>
                            </p>
                            <p class="text-gray-600">
                                <i class="fas fa-envelope mr-2"></i>
                                <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                            <?php if ($order['delivery_notes']): ?>
                                <div class="mt-3">
                                    <h4 class="font-medium text-gray-700 mb-1">Delivery Notes</h4>
                                    <p class="text-gray-600 text-sm"><?php echo nl2br(htmlspecialchars($order['delivery_notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($order['payment_date']): ?>
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Information</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600">Payment completed on</p>
                            <p class="font-medium"><?php echo formatDate($order['payment_date']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>
                                Paid
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="orders.php" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-md shadow-sm bg-white text-gray-700 hover:bg-gray-50 font-medium transition">
                    <i class="fas fa-list mr-2"></i>
                    View All Orders
                </a>
                <a href="index.php" class="inline-flex items-center justify-center px-6 py-3 gradient-bg text-white rounded-md shadow-sm hover:opacity-90 font-medium transition">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Continue Shopping
                </a>
                <button onclick="window.print()" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-md shadow-sm bg-white text-gray-700 hover:bg-gray-50 font-medium transition">
                    <i class="fas fa-print mr-2"></i>
                    Print Receipt
                </button>
            </div>

            <!-- Additional Information -->
            <div class="mt-8 bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">What's Next?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                    <div class="flex items-start">
                        <i class="fas fa-envelope text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <p class="font-medium">Order Confirmation Email</p>
                            <p>We'll send you a confirmation email with your order details shortly.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-truck text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <p class="font-medium">Order Tracking</p>
                            <p>You'll receive tracking information once your order ships.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-headset text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <p class="font-medium">Customer Support</p>
                            <p>Contact us if you have any questions about your order.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-undo text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <p class="font-medium">Returns & Exchanges</p>
                            <p>Easy returns within 30 days of delivery.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <p class="text-gray-300 mb-2">Email: support@yourstore.com</p>
                    <p class="text-gray-300 mb-2">Phone: +91 1234567890</p>
                    <p class="text-gray-300">Hours: Mon-Fri 9AM-6PM</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-white transition">Track Your Order</a></li>
                        <li><a href="#" class="hover:text-white transition">Returns & Exchanges</a></li>
                        <li><a href="#" class="hover:text-white transition">Shipping Info</a></li>
                        <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Your Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Auto-refresh order status every 30 seconds
        setInterval(function() {
            // You can implement AJAX call here to refresh order status
            // without reloading the entire page
        }, 30000);

        // Print functionality
        function printReceipt() {
            window.print();
        }

        // Success animation trigger
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelector('.success-animation').classList.add('animate-bounce');
            }, 500);
        });
    </script>
</body>
</html>