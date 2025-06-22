<?php
include '../includes/db.php';

// Admin credentials - CHANGE THESE TO YOUR DESIRED VALUES
$admin_email = 'admin@example.com';
$admin_password = '';
$admin_name = 'Administrator';

try {
    // Check if admin already exists
    $check_stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR role = 'admin'");
    $check_stmt->execute([$admin_email]);
    $existing_admin = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_admin) {
        echo "<div style='color: orange; text-align: center; margin: 20px;'>";
        echo "<h3>Admin user already exists!</h3>";
        echo "<p>Email: " . $existing_admin['email'] . "</p>";
        echo "<p>If you forgot the password, you can update it below.</p>";
        echo "</div>";
        
        // Option to update existing admin password
        if (isset($_POST['update_password'])) {
            $new_password = $_POST['new_password'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->execute([$hashed_password, $existing_admin['email']]);
            
            echo "<div style='color: green; text-align: center; margin: 20px;'>";
            echo "<h3>Password Updated Successfully!</h3>";
            echo "<p>Email: " . $existing_admin['email'] . "</p>";
            echo "<p>New Password: " . $new_password . "</p>";
            echo "</div>";
        }
        
        // Show password update form
        echo "<form method='POST' style='max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h4>Update Admin Password:</h4>";
        echo "<input type='password' name='new_password' placeholder='Enter new password' required style='width: 100%; padding: 10px; margin: 10px 0;'>";
        echo "<button type='submit' name='update_password' style='width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer;'>Update Password</button>";
        echo "</form>";
        
    } else {
        // Create new admin user
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        // Check if the users table has a 'name' column
        $columns_stmt = $conn->query("DESCRIBE users");
        $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('name', $columns)) {
            // Table has name column
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$admin_name, $admin_email, $hashed_password]);
        } else {
            // Table doesn't have name column
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$admin_email, $hashed_password]);
        }
        
        echo "<div style='color: green; text-align: center; margin: 50px; padding: 20px; border: 2px solid #28a745; border-radius: 10px; background: #f8f9fa;'>";
        echo "<h2>✅ Admin User Created Successfully!</h2>";
        echo "<p><strong>Email:</strong> " . $admin_email . "</p>";
        echo "<p><strong>Password:</strong> " . $admin_password . "</p>";
        echo "<p><strong>Role:</strong> admin</p>";
        echo "<hr>";
        echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file immediately after testing for security!</p>";
        echo "<a href='login.php' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; text-align: center; margin: 20px;'>";
    echo "<h3>Database Error!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure your users table exists and has the correct structure.</p>";
    echo "</div>";
    
    // Show expected table structure
    echo "<div style='margin: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd;'>";
    echo "<h4>Expected Users Table Structure:</h4>";
    echo "<pre>";
    echo "CREATE TABLE users (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    name VARCHAR(100),\n";
    echo "    email VARCHAR(100) UNIQUE NOT NULL,\n";
    echo "    password VARCHAR(255) NOT NULL,\n";
    echo "    role ENUM('user', 'admin') DEFAULT 'user',\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
    echo ");";
    echo "</pre>";
    echo "</div>";
}

// Display current users for debugging (remove in production)
echo "<div style='margin: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7;'>";
echo "<h4>Current Users in Database:</h4>";
try {
    $users_stmt = $conn->query("SELECT id, email, role FROM users");
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>No users found in database.</p>";
    } else {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p>Could not fetch users: " . $e->getMessage() . "</p>";
}
echo "</div>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning">
            <strong>⚠️ Security Warning:</strong> This file creates admin users and shows sensitive information. 
            Delete this file immediately after creating your admin user!
        </div>
    </div>
</body>
</html>