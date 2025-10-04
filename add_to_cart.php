<?php
session_start();
include("configs/db.php");
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
$user_level = $_SESSION['user_level'] ?? $_SESSION['level'] ?? 0;
if (!$logged_in || (int)$user_level !== 2 || !$user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid product']);
    exit;
}
$product_id = (int)$_POST['product_id'];
$count = isset($_POST['count']) && is_numeric($_POST['count']) ? (int)$_POST['count'] : 1;

$stmt = $con->prepare("SELECT id, stock FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

$stock = (int)$row['stock'];
if ($stock < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Out of stock']);
    exit;
}

$stmt = $con->prepare("SELECT id, `count` FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $r = $res->fetch_assoc();
    $new_q = (int)$r['count'] + $count;
    if ($new_q > $stock) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Not enough stock']);
        exit;
    }
    $u = $con->prepare("UPDATE cart SET `count` = ? WHERE id = ? AND user_id = ?");
    $u->bind_param('iii', $new_q, $r['id'], $user_id);
    if (!$u->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update cart']);
        $u->close();
        exit;
    }
    $u->close();
} else {
    $to_insert = min($stock, $count);
    $i = $con->prepare("INSERT INTO cart (user_id, product_id, `count`, created_on) VALUES (?,?,?, NOW())");
    $i->bind_param('iii', $user_id, $product_id, $to_insert);
    if (!$i->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to insert cart']);
        $i->close();
        exit;
    }
    $i->close();
}

echo json_encode(['status' => 'success', 'message' => 'Added to cart']);
