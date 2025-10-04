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
    header('Content-Type: application/json');
    $action = $_POST['action'];
    if ($action === 'update' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
        $cart_id = (int)$_POST['cart_id'];
        $quantity = max(1, (int)$_POST['quantity']);
        $stmt = $con->prepare("SELECT product_id FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $cart_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cart item not found']);
            exit;
        }
        $prod = $res->fetch_assoc();
        $stmt->close();
        $stmt = $con->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param('i', $prod['product_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }
        $p = $res->fetch_assoc();
        $stmt->close();
        if ($quantity > (int)$p['stock']) {
            echo json_encode(['status' => 'error', 'message' => 'Not enough stock']);
            exit;
        }
        $u = $con->prepare("UPDATE cart SET `count` = ? WHERE id = ? AND user_id = ?");
        $u->bind_param('iii', $quantity, $cart_id, $user_id);
        $u->execute();
        $u->close();
        echo json_encode(['status' => 'success', 'message' => 'Quantity updated']);
        exit;
    }
    if ($action === 'remove' && isset($_POST['cart_id'])) {
        $cart_id = (int)$_POST['cart_id'];
        $d = $con->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $d->bind_param('ii', $cart_id, $user_id);
        $d->execute();
        $d->close();
        echo json_encode(['status' => 'success', 'message' => 'Item removed']);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

$stmt = $con->prepare("SELECT c.id AS cart_id, c.`count`, p.id AS product_id, p.product_name, p.price, p.image, p.stock, p.description
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_on DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
$stmt->close();
$total = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-image {
            height: 60px;
            width: 60px;
            object-fit: cover
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">E-Store</a>
            <div class="d-flex">
                <?php if ($logged_in): ?>
                    <a class="btn btn-sm btn-warning me-2" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="orderhistory.php"><i class="fas fa-list"></i> Orders</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                    <a class="btn btn-sm btn-outline-light" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container my-4">
        <h4 class="mb-3">Shopping Cart</h4>
        <?php if (empty($items)): ?>
            <div class="alert alert-info">Your cart is empty.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it):
                            $subtotal = $it['price'] * (int)$it['count'];
                            $total += $subtotal;
                        ?>
                            <tr data-cart-id="<?= (int)$it['cart_id'] ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/products/<?= htmlspecialchars($it['image']) ?>" class="product-image me-3" alt="">
                                        <div>
                                            <div><strong><?= htmlspecialchars($it['product_name']) ?></strong></div>
                                            <div class="text-muted small"><?= htmlspecialchars(substr($it['description'], 0, 80)) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">₹<?= number_format($it['price'], 2) ?></td>
                                <td class="text-center" style="width:140px">
                                    <div class="input-group input-group-sm justify-content-center">
                                        <button class="btn btn-outline-secondary btn-decr" type="button">-</button>
                                        <input type="number" min="1" max="<?= (int)$it['stock'] ?>" class="form-control text-center qty-input" value="<?= (int)$it['count'] ?>">
                                        <button class="btn btn-outline-secondary btn-incr" type="button">+</button>
                                    </div>
                                </td>
                                <td class="text-end">₹<?= number_format($subtotal, 2) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary btn-update">Update</button>
                                    <button class="btn btn-sm btn-danger btn-remove">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end align-items-center">
                <div class="me-4">
                    <div class="fw-bold">Total</div>
                    <div class="h4">₹<?= number_format($total, 2) ?></div>
                </div>
                <div>
                    <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            function ajaxAction(data, cb) {
                $.post('cart.php', data, function(resp) {
                    try {
                        var r = (typeof resp === 'object') ? resp : JSON.parse(resp);
                    } catch (e) {
                        alert('Invalid response');
                        return;
                    }
                    if (r.status === 'success') {
                        cb && cb(null, r);
                    } else {
                        cb && cb(r.message || 'Error');
                    }
                }).fail(function() {
                    cb && cb('Request failed');
                });
            }

            $(document).on('click', '.btn-incr', function(e) {
                e.preventDefault();
                var row = $(this).closest('tr');
                var input = row.find('.qty-input');
                var max = parseInt(input.attr('max') || '9999', 10);
                var val = parseInt(input.val() || '0', 10);
                if (isNaN(val)) val = 0;
                val = Math.min(max, val + 1);
                input.val(val);
            });

            $(document).on('click', '.btn-decr', function(e) {
                e.preventDefault();
                var row = $(this).closest('tr');
                var input = row.find('.qty-input');
                var min = parseInt(input.attr('min') || '1', 10);
                var val = parseInt(input.val() || '0', 10);
                if (isNaN(val)) val = 0;
                val = Math.max(min, val - 1);
                input.val(val);
            });

            $(document).on('click', '.btn-update', function() {
                var row = $(this).closest('tr');
                var cart_id = parseInt(row.data('cart-id'), 10);
                var qty = parseInt(row.find('.qty-input').val() || '1', 10);
                ajaxAction({
                    action: 'update',
                    cart_id: cart_id,
                    quantity: qty
                }, function(err) {
                    if (err) {
                        alert(err);
                    } else {
                        location.reload();
                    }
                });
            });

            $(document).on('click', '.btn-remove', function() {
                if (!confirm('Remove this item?')) return;
                var row = $(this).closest('tr');
                var cart_id = parseInt(row.data('cart-id'), 10);
                ajaxAction({
                    action: 'remove',
                    cart_id: cart_id
                }, function(err) {
                    if (err) {
                        alert(err);
                    } else {
                        location.reload();
                    }
                });
            });

            $(document).on('change', '.qty-input', function() {
                var input = $(this);
                var min = parseInt(input.attr('min') || '1', 10);
                var max = parseInt(input.attr('max') || '9999', 10);
                var val = parseInt(input.val() || '0', 10);
                if (isNaN(val) || val < min) val = min;
                if (val > max) val = max;
                input.val(val);
            });
        });
    </script>
</body>

</html>