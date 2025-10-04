<?php
session_start();
include("configs/db.php");
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
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

$stmt = $con->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}
$stmt->close();

$stmt = $con->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Already in wishlist']);
    exit;
}
$stmt->close();

$i = $con->prepare("INSERT INTO wishlist (user_id, product_id, created_on) VALUES (?,?,NOW())");
$i->bind_param('ii', $user_id, $product_id);
$i->execute();
$i->close();

echo json_encode(['status' => 'success', 'message' => 'Added to wishlist']);
