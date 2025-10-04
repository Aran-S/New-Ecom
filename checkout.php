<?php
session_start();
include("configs/db.php");

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
$user_level = $_SESSION['user_level'] ?? $_SESSION['level'] ?? 0;
if (!$logged_in || (int)$user_level !== 2 || !$user_id) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_address') {
        $address_text = trim($_POST['addresss'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        if ($address_text && $contact) {
            $stmt = $con->prepare("INSERT INTO address (user_id, addresss, contact_number, created_on) VALUES (?,?,?,NOW())");
            $stmt->bind_param('iss', $user_id, $address_text, $contact);
            $stmt->execute();
            $stmt->close();
            $_SESSION['checkout_success'] = 'Address added.';
        } else {
            $_SESSION['checkout_error'] = 'Address and contact are required.';
        }
        header('Location: checkout.php');
        exit;
    }

    if ($_POST['action'] === 'select_address') {
        $address_id = (int)($_POST['address_id'] ?? 0);
        $_SESSION['selected_address_id'] = $address_id;
        header('Location: checkout.php');
        exit;
    }

    if ($_POST['action'] === 'place_order') {
        $selected = $_SESSION['selected_address_id'] ?? 0;
        if (!$selected) {
            $_SESSION['checkout_error'] = 'Select a delivery address first.';
            header('Location: checkout.php');
            exit;
        }

        $cart_items = [];
        $stmt = $con->prepare("SELECT c.product_id, c.count, p.price, p.stock, p.user_id AS seller_id, p.category_id, p.warranty AS warranty_years, p.product_name
                               FROM cart c 
                               JOIN products p ON c.product_id=p.id 
                               WHERE c.user_id=?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $cart_items[] = $r;
        $stmt->close();

        if (empty($cart_items)) {
            $_SESSION['checkout_error'] = 'Cart is empty.';
            header('Location: checkout.php');
            exit;
        }

        foreach ($cart_items as $item) {
            if ($item['stock'] < $item['count']) {
                $_SESSION['checkout_error'] = 'Insufficient stock for product: ' . htmlspecialchars($item['product_name']);
                header('Location: checkout.php');
                exit;
            }

            $new_stock = $item['count'] - $item['count'];
            $stmt = $con->prepare("UPDATE products SET stock=? WHERE id=?");
            $stmt->bind_param('ii', $new_stock, $item['product_id']);
            $stmt->execute();
            $stmt->close();

            $amount = $item['price'] * $item['count'];
            $delivery_status = 'Pending';
            $payment_method = 'COD';
            $order_number = '01' . rand(100, 999);
            $order_placed = 1;

            $stmt = $con->prepare("INSERT INTO orders 
                (user_id, seller_id, category_id, product_id, quantity, payment_method, ordered_on, order_address, warranty_years, delivery_status, amount, order_number, order_placed) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param(
                'iiiiiisssisii',
                $user_id,
                $item['seller_id'],
                $item['category_id'],
                $item['product_id'],
                $item['count'],
                $payment_method,
                date('Y-m-d H:i:s'),
                $selected,
                $item['warranty_years'],
                $delivery_status,
                $amount,
                $order_number,
                $order_placed
            );
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $con->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['selected_address_id']);
        echo '<script>alert("Order placed successfully!"); window.location.href="orderhistory.php";</script>';
        exit;
    }
}

$addresses = [];
$stmt = $con->prepare("SELECT id, addresss, contact_number FROM address WHERE user_id=? ORDER BY created_on DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $addresses[] = $r;
$stmt->close();

$cart_items = [];
$stmt = $con->prepare("SELECT c.id as cart_id, c.count, p.id as product_id, p.product_name, p.price FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $cart_items[] = $r;
$stmt->close();

$selected = $_SESSION['selected_address_id'] ?? 0;
$success_msg = $_SESSION['checkout_success'] ?? null;
$error_msg = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_success'], $_SESSION['checkout_error']);

$total = 0;
foreach ($cart_items as $it) $total += $it['price'] * (int)$it['count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <h4>Checkout</h4>
        <?php if ($success_msg): ?><div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

        <h5>Select Address</h5>
        <form method="post">
            <input type="hidden" name="action" value="select_address">
            <?php foreach ($addresses as $a): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="address_id" value="<?= (int)$a['id'] ?>" id="addr<?= $a['id'] ?>" <?= $selected == $a['id'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="addr<?= $a['id'] ?>">
                        <?= nl2br(htmlspecialchars($a['addresss'])) ?><br><small>Contact: <?= htmlspecialchars($a['contact_number']) ?></small>
                    </label>
                </div>
            <?php endforeach; ?>
            <button class="btn btn-primary mb-3">Use Selected Address</button>
        </form>

        <h5>Add New Address</h5>
        <form method="post" class="mb-4">
            <input type="hidden" name="action" value="add_address">
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="addresss" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control" required>
            </div>
            <button class="btn btn-success">Add Address</button>
        </form>

        <h5>Order Review</h5>
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">Cart is empty.</div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $it): $subtotal = $it['price'] * $it['count']; ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td class="text-end">₹<?= number_format($it['price'], 2) ?></td>
                            <td class="text-center"><?= (int)$it['count'] ?></td>
                            <td class="text-end">₹<?= number_format($subtotal, 2) ?></td>
                            <td class="text-center">COD</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="fw-bold">Total: ₹<?= number_format($total, 2) ?></div>
                <form method="post">
                    <input type="hidden" name="action" value="place_order">
                    <button class="btn btn-success">Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>