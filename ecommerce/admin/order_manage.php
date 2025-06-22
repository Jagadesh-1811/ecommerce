<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'ecommerce';
$user = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to safely get array value
function safeGet($array, $key, $default = 'N/A') {
    return isset($array[$key]) && $array[$key] !== null ? $array[$key] : $default;
}

// Updated SQL query to get complete order information from both table structures
$sql = "SELECT 
    o.id,
    o.user_id,
    o.status,
    o.total_amount,
    o.created_at,
    o.delivery_address,
    o.delivery_phone,
    o.payment_status,
    o.delivery_fee,
    o.tax_amount,
    u.username as customer_name,
    u.email as customer_email,
    GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ')') SEPARATOR ', ') as items
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
GROUP BY o.id
ORDER BY o.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Count orders by status for statistics
$status_counts = ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0];
$total_revenue = 0;

$count_sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as revenue FROM orders GROUP BY status";
$count_result = $conn->query($count_sql);
if ($count_result) {
    while($row = $count_result->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
        }
        $total_revenue += $row['revenue'];
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully!";
        // Refresh the page to show updated data
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = "Error updating order status: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Order Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .admin-title {
            color: #333;
            font-weight: 700;
            margin: 0;
        }

        .admin-info {
            color: #666;
            font-weight: 500;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
        }

        .stat-icon.pending { background: linear-gradient(45deg, #ff9800, #ff5722); }
        .stat-icon.processing { background: linear-gradient(45deg, #2196f3, #03a9f4); }
        .stat-icon.shipped { background: linear-gradient(45deg, #9c27b0, #e91e63); }
        .stat-icon.delivered { background: linear-gradient(45deg, #4caf50, #8bc34a); }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            color: #666;
            font-weight: 500;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .table-header h3 {
            color: #333;
            font-weight: 700;
            margin: 0;
        }

        .table-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            min-width: 200px;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background: linear-gradient(45deg, #333, #555);
            color: white;
            font-weight: 600;
            padding: 20px 15px;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 20px 15px;
            vertical-align: middle;
            border-color: #eee;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #333;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
        }

        .customer-email {
            font-size: 0.9rem;
            color: #666;
        }

        .items-list {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .amount {
            font-weight: bold;
            color: #4caf50;
            font-size: 1.1rem;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #f3e5f5; color: #6a1b9a; }
        .status-delivered { background: #d4edda; color: #155724; }

        .action-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            min-width: 120px;
        }

        .update-btn {
            background: linear-gradient(45deg, #4caf50, #66bb6a);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .empty-state {
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            color: #ddd;
        }

        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            display: none;
        }

        .toast-container {
            z-index: 10000;
        }

        .revenue-card {
            background: linear-gradient(45deg, #4caf50, #66bb6a);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            margin-bottom: 20px;
        }

        .revenue-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }

        .revenue-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .table-controls {
                width: 100%;
                flex-direction: column;
            }

            .search-input, .filter-select {
                min-width: auto;
                width: 100%;
            }

            .action-group {
                flex-direction: column;
                width: 100%;
            }

            .status-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <header class="admin-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="admin-title">
                            <i class="fas fa-shopping-cart"></i>
                            Order Management Dashboard
                        </h1>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="admin-info">Welcome, Admin | <?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $status_counts['pending']; ?></h3>
                                <p>Pending Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon processing">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $status_counts['processing']; ?></h3>
                                <p>Processing</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon shipped">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $status_counts['shipped']; ?></h3>
                                <p>Shipped</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon delivered">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $status_counts['delivered']; ?></h3>
                                <p>Delivered</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="revenue-card">
                            <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                            <p><i class="fas fa-chart-line"></i> Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>All Orders</h3>
                        <div class="table-controls">
                            <input type="text" class="form-control search-input" id="searchInput" placeholder="Search orders...">
                            <select class="form-select filter-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ordersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> Order ID</th>
                                    <th><i class="fas fa-user"></i> Customer</th>
                                    <th><i class="fas fa-box"></i> Items</th>
                                    <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                    <th><i class="fas fa-calendar"></i> Date</th>
                                    <th><i class="fas fa-edit"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr data-status="<?= htmlspecialchars(safeGet($row, 'status', 'pending')) ?>">
                                            <td class="order-id">#<?= htmlspecialchars(safeGet($row, 'id', '0')) ?></td>
                                            <td>
                                                <div class="customer-name"><?= htmlspecialchars(safeGet($row, 'customer_name', 'Unknown Customer')) ?></div>
                                                <div class="customer-email"><?= htmlspecialchars(safeGet($row, 'customer_email', '')) ?></div>
                                            </td>
                                            <td class="items-list" title="<?= htmlspecialchars(safeGet($row, 'items', 'No items')) ?>">
                                                <?= htmlspecialchars(safeGet($row, 'items', 'No items')) ?>
                                            </td>
                                            <td class="amount">₹<?= number_format((float)safeGet($row, 'total_amount', 0), 2) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= htmlspecialchars(safeGet($row, 'status', 'pending')) ?>">
                                                    <?= ucfirst(htmlspecialchars(safeGet($row, 'status', 'pending'))) ?>
                                                </span>
                                            </td>
                                            <td class="order-date">
                                                <?= safeGet($row, 'created_at') !== 'N/A' ? date('M d, Y H:i', strtotime($row['created_at'])) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <form class="status-form" method="post" action="">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars(safeGet($row, 'id', '0')) ?>">
                                                    <div class="action-group">
                                                        <select name="status" class="form-select status-select">
                                                            <option value="pending" <?= safeGet($row, 'status', 'pending') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="processing" <?= safeGet($row, 'status', 'pending') == 'processing' ? 'selected' : '' ?>>Processing</option>
                                                            <option value="shipped" <?= safeGet($row, 'status', 'pending') == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                            <option value="delivered" <?= safeGet($row, 'status', 'pending') == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm update-btn">
                                                            <i class="fas fa-save"></i> Update
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr class="no-orders">
                                        <td colspan="7" class="text-center empty-state">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <h5>No orders found</h5>
                                            <p class="text-muted">Orders will appear here once customers start placing them.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <?php if (isset($success_message)): ?>
        <div class="toast show align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="toast show align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                if (row.classList.contains('no-orders')) return;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filterStatus = this.value;
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                if (row.classList.contains('no-orders')) return;
                
                const rowStatus = row.getAttribute('data-status');
                if (filterStatus === '' || rowStatus === filterStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Auto-refresh every 30 seconds to show new orders
        setInterval(function() {
            location.reload();
        }, 30000);

        // Show loading spinner on form submission
        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingSpinner').style.display = 'block';
            });
        });

        // Auto-hide toasts after 5 seconds
        setTimeout(function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            });
        }, 5000);
    </script>
</body>
</html>

<?php
$conn->close();
?>