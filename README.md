# ecommerce
this is an ecoomerce website 
# 🛒 Basic eCommerce Website (PHP + MySQL)

A simple eCommerce website built using PHP, HTML, CSS, and MySQL. This project provides a basic structure for online shopping, including user registration/login, product listing, cart management, and order processing.

## 📁 Project Features

* User Registration & Login (Session-based)
* Admin Dashboard for managing products
* Product Listing with categories
* Add to Cart & Checkout
* Order Confirmation
* Responsive UI using basic HTML/CSS
* MySQL database integration

## 🛠️ Tech Stack

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL
* **Server:** Apache (XAMPP or WAMP recommended)

---

## 🚀 How to Run the Project

### Prerequisites

* PHP 7.x or later
* MySQL
* Apache Server (use [XAMPP](https://www.apachefriends.org/index.html) or [WAMP](https://www.wampserver.com/))
* A web browser

### Steps to Setup

1. **Clone the Repository**

   ```bash
   git clone https://github.com/yourusername/ecommerce-php.git
   ```

2. **Move Project to Server Folder**

   * Copy the folder to your `htdocs` directory (for XAMPP) or `www` directory (for WAMP).

3. **Create MySQL Database**

   * Go to `http://localhost/phpmyadmin`
   * Create a database (e.g., `ecommerce`)
   * Import the `ecommerce.sql` file (available in the `database/` folder)

4. **Update Database Connection**

   * Open `includes/db.php`
   * Modify database credentials:

     ```php
     $conn = new mysqli("localhost", "root", "", "ecommerce");
     ```

5. **Run the Application**

   * Visit `http://localhost/ecommerce-php/` in your browser

---

## 📂 Folder Structure

```
ecommerce-php/
│
├── includes/         # DB config and reusable code
│   └── db.php
├── admin/            # Admin panel files
│   └── add_product.php
├── cart/             # Cart and checkout pages
│   └── view_cart.php
├── css/              # CSS stylesheets
├── js/               # JavaScript files
├── images/           # Product images
├── index.php         # Home page
├── login.php         # User login
├── register.php      # User registration
├── logout.php        # Logout functionality
├── product.php       # Single product view
├── order_success.php # Order confirmation
└── database/
    └── ecommerce.sql # SQL file to create and populate DB
```

---

## 👤 Author

* **Your Name** – [yourusername](https://github.com/Jagadesh-1811)

## 📃 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 💡 Future Enhancements

* Payment Gateway Integration
* Product Reviews & Ratings
* Search and Filter Options
* Email Notifications

