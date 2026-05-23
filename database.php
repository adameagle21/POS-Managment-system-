<?php
/**
 * Adam Car Accessories - Database Configuration
 * Author: Adam Car System
 * Version: 1.0
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'adam_car_db');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Set timezone
date_default_timezone_set('Africa/Mogadishu');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>