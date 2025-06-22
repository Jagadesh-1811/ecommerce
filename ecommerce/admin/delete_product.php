<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include '../includes/db.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: manage_products.php");
    exit();
}

$product_id = (int)$_GET['id'];

try {
    // First, get the product details to check if it exists and get the image filename
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error_message'] = "Product not found.";
        header("Location: manage_products.php");
        exit();
    }
    
    // Store product name for success message
    $product_name = $product['name'];
    $product_image = $product['image'];
    
    // Delete the product from database
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete_result = $delete_stmt->execute([$product_id]);
    
    if ($delete_result) {
        // If deletion was successful, try to delete the image file
        if (!empty($product_image) && file_exists("../images/" . $product_image)) {
            // Attempt to delete the image file
            if (unlink("../images/" . $product_image)) {
                $_SESSION['success_message'] = "Product '$product_name' and its image have been deleted successfully.";
            } else {
                $_SESSION['success_message'] = "Product '$product_name' has been deleted successfully, but the image file could not be removed.";
            }
        } else {
            $_SESSION['success_message'] = "Product '$product_name' has been deleted successfully.";
        }
    } else {
        $_SESSION['error_message'] = "Failed to delete product. Please try again.";
    }
    
} catch (PDOException $e) {
    // Handle database errors
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle other errors
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

// Redirect back to manage products page
header("Location: manage_products.php");
exit();
?>