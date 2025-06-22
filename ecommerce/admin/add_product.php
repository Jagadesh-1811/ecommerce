<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<?php
include '../includes/db.php';

$success_message = '';
$error_message = '';

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $image = $_FILES['image']['name'];

    // Basic validation
    if (empty($name)) {
        $error_message = "Product name is required.";
    } elseif ($price <= 0) {
        $error_message = "Please enter a valid price greater than ₹0.";
    } elseif (empty($description)) {
        $error_message = "Product description is required.";
    } elseif (empty($image)) {
        $error_message = "Please select an image file.";
    } else {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $file_size = $_FILES['image']['size'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_ext, $allowed_types)) {
            $error_message = "Invalid file type. Please upload JPG, JPEG, PNG, GIF, or WebP files only.";
        } elseif ($file_size > $max_size) {
            $error_message = "File size too large. Please upload an image smaller than 5MB.";
        } else {
            // Generate unique filename to prevent conflicts
            $unique_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = "../images/" . $unique_name;
            
            // Create images directory if it doesn't exist
            if (!file_exists("../images/")) {
                mkdir("../images/", 0755, true);
            }
            
            // Upload the image
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                try {
                    // Insert product details into the database
                    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$name, $price, $description, $unique_name])) {
                        $success_message = "Product '$name' added successfully with price ₹" . number_format($price, 2) . "!";
                        // Clear form data after successful submission
                        $_POST = array();
                    } else {
                        $error_message = "Error adding product to database.";
                        // Delete uploaded file if database insert failed
                        if (file_exists($upload_path)) {
                            unlink($upload_path);
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                    // Delete uploaded file if database insert failed
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $error_message = "Error uploading image file. Please try again.";
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
    <title>Add Product - Indian E-Commerce</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .product-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            transform: translateY(0);
            animation: float 6s ease-in-out infinite;
        }

        .product-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .product-title {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .product-title:hover {
            color: #ffd700;
            transform: scale(1.05);
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.5);
        }

        .product-icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 15px;
            text-align: center;
            text-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .form-label:hover {
            color: #ffd700;
            transform: translateX(5px);
            text-shadow: 0 2px 10px rgba(255, 215, 0, 0.4);
        }

        .price-input-group {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffd700;
            font-size: 1.2rem;
            font-weight: bold;
            z-index: 2;
            text-shadow: 0 2px 10px rgba(255, 215, 0, 0.4);
        }

        .form-input, .form-textarea, .form-file {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .form-input.price-input {
            padding-left: 45px;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: 'Arial', sans-serif;
        }

        .form-input::placeholder, .form-textarea::placeholder {
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }

        .form-input:hover, .form-textarea:hover, .form-file:hover {
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .form-input:focus, .form-textarea:focus, .form-file:focus {
            border-color: #ff6b6b;
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.02);
            box-shadow: 0 0 25px rgba(255, 107, 107, 0.4);
        }

        .form-file {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .form-file::-webkit-file-upload-button {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .form-file::-webkit-file-upload-button:hover {
            background: linear-gradient(45deg, #45a049, #4CAF50);
            transform: scale(1.05);
        }

        .file-info {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
            text-align: center;
        }

        .submit-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 20px;
            text-transform: uppercase;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            position: relative;
            overflow: hidden;
        }

        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
        }

        .submit-button:hover {
            background: linear-gradient(45deg, #45a049, #4CAF50);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(76, 175, 80, 0.5);
            letter-spacing: 2px;
        }

        .submit-button:hover::before {
            left: 100%;
        }

        .submit-button:active {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }

        .success-message {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            font-weight: 500;
            animation: slideIn 0.5s ease-in-out;
            backdrop-filter: blur(5px);
        }

        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 107, 107, 0.3);
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
            backdrop-filter: blur(5px);
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-button {
            text-decoration: none;
            padding: 12px 25px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-block;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-button:hover {
            background: linear-gradient(45deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .price-display {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 5px;
            text-align: right;
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
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 768px) {
            .product-container {
                padding: 30px 20px;
                margin: 10px;
                max-width: 95%;
            }
            
            .product-title {
                font-size: 2rem;
                letter-spacing: 1px;
            }
            
            .form-input, .form-textarea, .form-file {
                padding: 12px 15px;
            }

            .form-input.price-input {
                padding-left: 40px;
            }
        }

        @media (max-width: 480px) {
            .product-title {
                font-size: 1.8rem;
            }
            
            .submit-button {
                padding: 15px;
                font-size: 1rem;
            }
        }

        /* Input focus effects */
        .form-group {
            position: relative;
        }

        .form-group::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #ffd700, #ff6b6b);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .form-group:focus-within::after {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="product-container">
        <div class="product-icon">🛍️</div>
        <h2 class="product-title">Add Product</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-input" 
                       placeholder="Enter product name" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                       required maxlength="255">
            </div>

            <div class="form-group">
                <label for="price" class="form-label">Price (Indian Rupees)</label>
                <div class="price-input-group">
                    <span class="currency-symbol">₹</span>
                    <input type="number" step="0.01" min="0.01" max="999999999" name="price" id="price" 
                           class="form-input price-input" 
                           placeholder="0.00" 
                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                           required>
                </div>
                <div class="price-display" id="priceDisplay"></div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Product Description</label>
                <textarea name="description" id="description" class="form-textarea" 
                          placeholder="Enter detailed product description..." 
                          required maxlength="1000"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="image" class="form-label">Product Image</label>
                <input type="file" name="image" id="image" class="form-file" 
                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                <div class="file-info">
                    Maximum file size: 5MB | Supported formats: JPG, PNG, GIF, WebP
                </div>
            </div>

            <button type="submit" name="add_product" class="submit-button">
                🛒 Add Product
            </button>
        </form>
        
        <div class="back-link">
            <a href="manage_products.php" class="back-button">← Back to Manage Products</a>
        </div>
    </div>

    <script>
        // Real-time price formatting
        document.getElementById('price').addEventListener('input', function() {
            const price = parseFloat(this.value);
            const display = document.getElementById('priceDisplay');
            
            if (!isNaN(price) && price > 0) {
                // Format price in Indian currency format
                const formatted = new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(price);
                
                display.textContent = `Formatted: ${formatted}`;
                display.style.color = '#4CAF50';
            } else {
                display.textContent = '';
            }
        });

        // File size validation
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file && file.size > maxSize) {
                alert('File size is too large. Please select an image smaller than 5MB.');
                this.value = '';
            }
        });

        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.success-message, .error-message');
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