<?php
require_once __DIR__ . '/config/db.php';

// Reset admin password to adam424
$new_password = md5('adam424');
$query = "UPDATE users SET password = '$new_password' WHERE username = 'admin'";

if(mysqli_query($conn, $query)) {
    echo "<h2 style='color:green'>✓ Admin password reset to: adam424</h2>";
} else {
    echo "<h2 style='color:red'>✗ Error: " . mysqli_error($conn) . "</h2>";
}

// Also check if admin exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE username = 'admin'");
if(mysqli_num_rows($check) > 0) {
    $admin = mysqli_fetch_assoc($check);
    echo "<p>Admin found: " . $admin['username'] . "</p>";
    echo "<p>Password hash: " . $admin['password'] . "</p>";
} else {
    echo "<p>Admin not found! Creating...</p>";
    mysqli_query($conn, "INSERT INTO users (username, password, role, is_active, firstname, lastname, email) 
                         VALUES ('admin', MD5('adam424'), 'admin', 1, 'Admin', 'User', 'admin@adamcar.com')");
    echo "<p>Admin created!</p>";
}

echo "<a href='login.php' style='background:#4f46e5; color:white; padding:10px 20px; text-decoration:none; border-radius:10px;'>Go to Login</a>";
?>