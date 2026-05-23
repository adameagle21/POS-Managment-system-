<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') header("Location: users.php");

$id = $_GET['id'] ?? 0;
if($id != $_SESSION['user_id']) {
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
}
header("Location: users.php");
exit();
?>