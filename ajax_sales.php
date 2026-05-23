<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Set JSON header
header('Content-Type: application/json');

// Only accept POST requests
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Handle add to cart
if(isset($_POST['ajax_add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $unit_price = (float)$_POST['unit_price'];
    $sale_date = $_POST['sale_date'];
    
    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_name, sku, price_regular, price_last, quantity as stock, discount_price, discount_expiry FROM products WHERE id = $product_id"));
    
    $response = ['success' => false, 'message' => ''];
    
    if(!$product) {
        $response['message'] = "Product not found!";
    } 
    elseif($product['price_last'] > 0 && $unit_price < $product['price_last']) {
        $response['message'] = "Cannot sell below Last Price! Last Price: $" . number_format($product['price_last'], 2);
    } 
    elseif($quantity > $product['stock']) {
        $response['message'] = "Insufficient stock! Only {$product['stock']} units available.";
    } 
    else {
        if(!isset($_SESSION['manual_cart'])) {
            $_SESSION['manual_cart'] = [];
            $_SESSION['manual_cart_date'] = $sale_date;
        }
        
        // Check if item already in cart
        $found = false;
        foreach($_SESSION['manual_cart'] as $key => $item) {
            if($item['product_id'] == $product_id) {
                $new_qty = $item['quantity'] + $quantity;
                if($new_qty > $product['stock']) {
                    $response['message'] = "Total quantity would exceed stock! Only {$product['stock']} units available.";
                    echo json_encode($response);
                    exit();
                }
                $_SESSION['manual_cart'][$key]['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if(!$found) {
            $_SESSION['manual_cart'][] = [
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'sku' => $product['sku'],
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'cost' => $product['price_regular'],
                'has_discount' => ($product['discount_price'] && $product['discount_expiry'] >= date('Y-m-d'))
            ];
        }
        $response['success'] = true;
        $response['message'] = "✓ Item added to cart!";
    }
    echo json_encode($response);
    exit();
}

// Handle remove from cart
if(isset($_POST['ajax_remove_cart_item'])) {
    $index = (int)$_POST['index'];
    if(isset($_SESSION['manual_cart'][$index])) {
        unset($_SESSION['manual_cart'][$index]);
        $_SESSION['manual_cart'] = array_values($_SESSION['manual_cart']);
    }
    echo json_encode(['success' => true]);
    exit();
}

// Handle get cart data
if(isset($_POST['ajax_get_cart'])) {
    $cart_total = 0;
    $cart_items = $_SESSION['manual_cart'] ?? [];
    foreach($cart_items as $item) {
        $cart_total += $item['quantity'] * $item['unit_price'];
    }
    echo json_encode([
        'items' => $cart_items,
        'total' => $cart_total,
        'date' => $_SESSION['manual_cart_date'] ?? date('Y-m-d')
    ]);
    exit();
}

// Handle clear cart
if(isset($_POST['ajax_clear_cart'])) {
    unset($_SESSION['manual_cart']);
    unset($_SESSION['manual_cart_date']);
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
    exit();
}

// Handle complete sale from AJAX
if(isset($_POST['ajax_complete_sale'])) {
    $payment_method = $_POST['payment_method'];
    $sale_date = $_SESSION['manual_cart_date'] ?? date('Y-m-d');
    $invoice_no = 'MAN-' . date('Ymd') . '-' . rand(1000, 9999);
    $total_amount = 0;
    $total_profit = 0;
    $error = false;
    $error_msg = '';
    
    if(empty($_SESSION['manual_cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit();
    }
    
    if(empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'Please select a payment method']);
        exit();
    }
    
    foreach($_SESSION['manual_cart'] as $item) {
        $total = $item['quantity'] * $item['unit_price'];
        $profit = $total - ($item['cost'] * $item['quantity']);
        $total_amount += $total;
        $total_profit += $profit;
        
        // Update stock
        $update_stock = mysqli_query($conn, "UPDATE products SET quantity = quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
        if(!$update_stock) {
            $error = true;
            $error_msg = mysqli_error($conn);
        }
        
        // Insert sale
        $insert = mysqli_query($conn, "INSERT INTO sales (invoice_no, sale_date, product_id, quantity, unit_price, total, profit) 
                            VALUES ('$invoice_no', '$sale_date', '{$item['product_id']}', '{$item['quantity']}', '{$item['unit_price']}', '$total', '$profit')");
        if(!$insert) {
            $error = true;
            $error_msg = mysqli_error($conn);
        }
    }
    
    if(!$error) {
        unset($_SESSION['manual_cart']);
        unset($_SESSION['manual_cart_date']);
        echo json_encode([
            'success' => true, 
            'message' => "Sale completed successfully!\nInvoice: $invoice_no\nTotal: $" . number_format($total_amount, 2) . "\nPayment: $payment_method\nProfit: $" . number_format($total_profit, 2),
            'invoice' => $invoice_no,
            'total' => $total_amount,
            'profit' => $total_profit,
            'payment_method' => $payment_method
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $error_msg]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
?>