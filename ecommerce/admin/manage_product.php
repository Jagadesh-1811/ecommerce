<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<?php
include '../includes/db.php';
$stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
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

        .manage-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            animation: float 6s ease-in-out infinite;
        }

        .manage-container:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Message Styles */
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
            animation: slideDown 0.5s ease-out;
        }

        .success-message {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .error-message {
            background: linear-gradient(45deg, #f44336, #d32f2f);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .manage-title {
            text-align: center;
            margin-bottom: 40px;
            color: #fff;
            font-size: 2.8rem;
            font-weight: 300;
            letter-spacing: 3px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .manage-title:hover {
            color: #ffd700;
            transform: scale(1.05);
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.5);
        }

        .manage-icon {
            font-size: 3.5rem;
            color: #ffd700;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: transparent;
        }

        .table-header {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .table-header th {
            padding: 20px 15px;
            text-align: left;
            border: none;
            position: relative;
            transition: all 0.3s ease;
        }

        .table-header th:first-child {
            border-top-left-radius: 12px;
        }

        .table-header th:last-child {
            border-top-right-radius: 12px;
        }

        .table-header th:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .table-row {
            background: rgba(255, 255, 255, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            animation: slideIn 0.6s ease-out forwards;
        }

        .table-row:nth-child(even) {
            background: rgba(255, 255, 255, 0.12);
        }

        .table-row:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .table-row:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .table-row:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        .table-cell {
            padding: 18px 15px;
            color: #fff;
            border: none;
            vertical-align: middle;
            transition: all 0.3s ease;
        }

        .product-id {
            font-weight: bold;
            color: #ffd700;
            font-size: 1.1rem;
        }

        .product-name {
            font-weight: 600;
            color: #fff;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price {
            font-weight: bold;
            color: #4CAF50;
            font-size: 1.1rem;
        }

        .product-description {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: rgba(255, 255, 255, 0.9);
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-image:hover {
            transform: scale(1.5);
            border-color: #ffd700;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            z-index: 10;
            position: relative;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .edit-btn {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .edit-btn:hover {
            background: linear-gradient(45deg, #1976D2, #2196F3);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.4);
        }

        .delete-btn {
            background: linear-gradient(45deg, #f44336, #d32f2f);
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .delete-btn:hover {
            background: linear-gradient(45deg, #d32f2f, #f44336);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .back-section {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .back-button, .add-button {
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.4s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .back-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .add-button {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }

        .back-button:hover, .add-button:hover {
            transform: translateY(-3px);
            letter-spacing: 2px;
            border-color: rgba(255, 255, 255, 0.4);
        }

        .back-button:hover {
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .add-button:hover {
            box-shadow: 0 15px 40px rgba(76, 175, 80, 0.4);
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px 30px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            min-width: 150px;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ffd700;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .empty-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Staggered animation for table rows */
        .table-row:nth-child(1) { animation-delay: 0.1s; }
        .table-row:nth-child(2) { animation-delay: 0.2s; }
        .table-row:nth-child(3) { animation-delay: 0.3s; }
        .table-row:nth-child(4) { animation-delay: 0.4s; }
        .table-row:nth-child(5) { animation-delay: 0.5s; }

        @media (max-width: 1200px) {
            .manage-container {
                padding: 30px 20px;
            }
            
            .table-container {
                overflow-x: scroll;
            }
        }

        @media (max-width: 768px) {
            .manage-title {
                font-size: 2.2rem;
                letter-spacing: 2px;
            }
            
            .stats-bar {
                gap: 15px;
            }
            
            .stat-item {
                min-width: 120px;
                padding: 15px 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .back-section {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .manage-container {
                margin: 10px;
                padding: 20px 15px;
            }
            
            .manage-title {
                font-size: 1.8rem;
            }
            
            .table-cell {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="manage-container">
    <div class="manage-icon">🛍️</div>
    <h2 class="manage-title">Manage Products</h2>
    
    <?php
    // Display success message
    if (isset($_SESSION['success_message'])) {
        echo '<div class="message success-message">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    
    // Display error message
    if (isset($_SESSION['error_message'])) {
        echo '<div class="message error-message">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-number"><?php echo count($products); ?></span>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">✓</span>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">🚀</span>
            <div class="stat-label">Ready</div>
        </div>
    </div>

    <?php if (count($products) > 0): ?>
    <div class="table-container">
        <table class="products-table">
            <thead class="table-header">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <span class="product-id">#<?= $product['id']; ?></span>
                        </td>
                        <td class="table-cell">
                            <div class="product-name" title="<?= htmlspecialchars($product['name']); ?>">
                                <?= htmlspecialchars($product['name']); ?>
                            </div>
                        </td>
                        <td class="table-cell">
                            <span class="product-price">₹<?= number_format($product['price'], 2); ?></span>
                        </td>
                        <td class="table-cell">
                            <div class="product-description" title="<?= htmlspecialchars($product['description']); ?>">
                                <?= htmlspecialchars($product['description']); ?>
                            </div>
                        </td>
                        <td class="table-cell">
                            <img src="../images/<?= htmlspecialchars($product['image']); ?>" 
                                 alt="<?= htmlspecialchars($product['name']); ?>" 
                                 class="product-image"
                                 onerror="this.src='../images/placeholder.jpg';">
                        </td>
                        <td class="table-cell">
                            <div class="action-buttons">
                                <a href="delete_product.php?id=<?= $product['id']; ?>" 
                                   class="action-btn delete-btn"
                                   onclick="return confirm('Are you sure you want to delete \'<?= addslashes(htmlspecialchars($product['name'])); ?>\'? This action cannot be undone.');">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-text">No products found. Start by adding your first product!</div>
    </div>
    <?php endif; ?>

    <div class="back-section">
        <a href="add_product.php" class="add-button">➕ Add New Product</a>
        <a href="dashboard.php" class="back-button">← Back to Dashboard</a>
    </div>
</div>

<script>
// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            setTimeout(function() {
                message.remove();
            }, 300);
        }, 5000);
    });
});
</script>

</body>
</html>