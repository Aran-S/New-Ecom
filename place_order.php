<?php
session_start();
include("configs/db.php");

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
$user_level = $_SESSION['user_level'] ?? $_SESSION['level'] ?? 0;
$address_id = $_SESSION['selected_address_id'] ?? null;
if (!$logged_in || (int)$user_level !== 2 || !$user_id || !$address_id) {
    header('Location: checkout.php');
    exit;
}

$stmt = $con->prepare("SELECT c.id AS cart_id, c.`count`, p.id AS product_id, p.product_name, p.price, p.stock, p.user_id, p.category_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

if (empty($items)) {
    $_SESSION['order_error'] = 'Cart is empty.';
    header('Location: cart.php');
    exit;
}

foreach ($items as $it) {
    if ((int)$it['stock'] < (int)$it['count']) {
        $_SESSION['order_error'] = 'Not enough stock for ' . $it['product_name'];
        header('Location: checkout.php');
        exit;
    }
}

$addr_stmt = $con->prepare("SELECT `addresss` FROM `address` WHERE id = ? AND user_id = ?");
$addr_stmt->bind_param('ii', $address_id, $user_id);
$addr_stmt->execute();
$addr_res = $addr_stmt->get_result();
$order_address = '';
if ($addr_row = $addr_res->fetch_assoc()) $order_address = $addr_row['addresss'];
$addr_stmt->close();

$order_number = 'ORD' . time() . strtoupper(substr(md5(uniqid()), 0, 6));
$payment_method = 'COD';
$warranty_years_default = 0;
$ordered_on = date('Y-m-d H:i:s');

try {
    $con->begin_transaction();

    $insert_order = $con->prepare("INSERT INTO orders (user_id, seller_id, category_id, product_id, quantity, payment_method, ordered_on, order_address, warranty_years, delivery_status, amount, order_number, order_placed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $update_stock = $con->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    if (!$insert_order || !$update_stock) throw new Exception('DB prepare failed');

    foreach ($items as $it) {
        $qty = (int)$it['count'];
        $price = (float)$it['price'];
        $pid = (int)$it['product_id'];
        $seller_id = isset($it['seller_id']) ? (int)$it['seller_id'] : 0;
        $category_id = isset($it['category_id']) ? (int)$it['category_id'] : 0;
        $amount = $price * $qty;
        $delivery_status = 1;
        $order_placed_flag = 1;

        $insert_order->bind_param('iiiisissiiidsi', $user_id, $seller_id, $category_id, $pid, $qty, $payment_method, $ordered_on, $order_address, $warranty_years_default, $delivery_status, $amount, $order_number, $order_placed_flag);
        if (!$insert_order->execute()) throw new Exception('Failed to insert order: ' . $insert_order->error);

        $update_stock->bind_param('iii', $qty, $pid, $qty);
        if (!$update_stock->execute() || $con->affected_rows === 0) throw new Exception('Failed to update stock for product ' . $pid);
    }

    $insert_order->close();
    $update_stock->close();

    $d = $con->prepare("DELETE FROM cart WHERE user_id = ?");
    $d->bind_param('i', $user_id);
    if (!$d->execute()) throw new Exception('Failed to clear cart');
    $d->close();

    $con->commit();
    unset($_SESSION['selected_address_id']);
    header('Location: order_success.php?id=' . urlencode($order_number));
    exit;
} catch (Exception $e) {
    $con->rollback();
    $_SESSION['order_error'] = $e->getMessage();
    header('Location: checkout.php');
    exit;
}
