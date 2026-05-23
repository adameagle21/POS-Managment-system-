<?php
require_once __DIR__ . '/database.php';

/**
 * Execute query and return result
 */
function runQuery($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}

/**
 * Get single row
 */
function getRow($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

/**
 * Get all rows
 */
function getRows($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Get total count
 */
function getCount($table, $condition = '') {
    global $conn;
    $sql = "SELECT COUNT(*) as total FROM $table";
    if($condition) {
        $sql .= " WHERE $condition";
    }
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

/**
 * Escape string for safe query
 */
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

/**
 * Get last inserted ID
 */
function lastId() {
    global $conn;
    return mysqli_insert_id($conn);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

/**
 * Redirect to page
 */
function redirect($page) {
    header("Location: " . $page);
    exit();
}
?>