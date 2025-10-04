<?php
session_start();
include("configs/db.php");
$date = date('Y-m-d H:i:s');

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
$user_level = $_SESSION['user_level'] ?? $_SESSION['level'] ?? 0;
if (!$logged_in || !$user_id) {
    header('Location: login.php');
    exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

$stmt = $con->prepare("SELECT id, product_name, price, stock, user_id AS seller_id, category_id, warranty FROM products WHERE id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();
if (!$product || $product['stock'] < 1) {
    echo "Product not available.";
    exit;
}

$success_msg = $_SESSION['buy_now_success'] ?? null;
$error_msg = $_SESSION['buy_now_error'] ?? null;
unset($_SESSION['buy_now_success'], $_SESSION['buy_now_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_address') {
        $address_text = trim($_POST['addresss'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        if ($address_text && $contact) {
            $stmt = $con->prepare("INSERT INTO address (user_id,addresss,contact_number,created_on) VALUES (?,?,?,NOW())");
            $stmt->bind_param('iss', $user_id, $address_text, $contact);
            $stmt->execute();
            $stmt->close();
            $_SESSION['buy_now_success'] = 'Address added.';
        } else {
            $_SESSION['buy_now_error'] = 'Address and contact required.';
        }
        header("Location: buy_now.php?product_id={$product_id}");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'select_address') {
        $_SESSION['buy_now_selected_address'] = (int)$_POST['address_id'];
        header("Location: buy_now.php?product_id={$product_id}");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $con->prepare("SELECT stock FROM products WHERE id=?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$r || $r['stock'] < $qty) {
            $_SESSION['buy_now_error'] = 'Not enough stock to add to cart.';
            header("Location: buy_now.php?product_id={$product_id}");
            exit;
        }
        $s = $con->prepare("SELECT id, `count` FROM cart WHERE user_id=? AND product_id=?");
        $s->bind_param('ii', $user_id, $product_id);
        $s->execute();
        $rs = $s->get_result();
        if ($rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            $new_q = (int)$row['count'] + $qty;
            $u = $con->prepare("UPDATE cart SET `count`=? WHERE id=? AND user_id=?");
            $u->bind_param('iii', $new_q, $row['id'], $user_id);
            $u->execute();
            $u->close();
        } else {
            $i = $con->prepare("INSERT INTO cart (user_id, product_id, `count`, created_on) VALUES (?,?,?,NOW())");
            $i->bind_param('iii', $user_id, $product_id, $qty);
            $i->execute();
            $i->close();
        }
        $_SESSION['buy_now_success'] = 'Added to cart';
        header("Location: buy_now.php?product_id={$product_id}");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_to_wishlist') {
        $stmt = $con->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $i = $con->prepare("INSERT INTO wishlist (user_id, product_id, created_on) VALUES (?,?,NOW())");
            $i->bind_param('ii', $user_id, $product_id);
            $i->execute();
            $i->close();
            $_SESSION['buy_now_success'] = 'Added to wishlist';
        } else {
            $_SESSION['buy_now_success'] = 'Already in wishlist';
        }
        $stmt->close();
        header("Location: buy_now.php?product_id={$product_id}");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
        $selected = $_SESSION['buy_now_selected_address'] ?? 0;
        if (!$selected) {
            $_SESSION['buy_now_error'] = 'Select a delivery address first.';
            header("Location: buy_now.php?product_id={$product_id}");
            exit;
        }

        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $con->prepare("SELECT stock, price, user_id AS seller_id, category_id, warranty FROM products WHERE id=? FOR UPDATE");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();
        $stmt->close();
        if (!$prod || $prod['stock'] < $quantity) {
            $_SESSION['buy_now_error'] = 'Product out of stock or insufficient quantity.';
            header("Location: buy_now.php?product_id={$product_id}");
            exit;
        }

        $amount = $prod['price'] * $quantity;
        $seller_id = (int)$prod['seller_id'];
        $category_id = (int)$prod['category_id'];
        $warranty_years = (int)$prod['warranty'];
        $delivery_status = 1;
        $payment_method = 'COD';
        $order_number = 'ORD' . time() . strtoupper(substr(md5(uniqid()), 0, 6));
        $order_placed = 1;

        try {
            $con->begin_transaction();
            $ins = $con->prepare("INSERT INTO orders (user_id, seller_id, category_id, product_id, quantity, payment_method, ordered_on, order_address, warranty_years, delivery_status, amount, order_number, order_placed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            if (!$ins) throw new Exception('DB prepare failed');
            $ins->bind_param('iiiiissiiidsi', $user_id, $seller_id, $category_id, $product_id, $quantity, $payment_method, $date, $selected, $warranty_years, $delivery_status, $amount, $order_number, $order_placed);
            if (!$ins->execute()) throw new Exception('Insert order failed: ' . $ins->error);
            $ins->close();

            $upd = $con->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $upd->bind_param('iii', $quantity, $product_id, $quantity);
            if (!$upd->execute() || $con->affected_rows === 0) throw new Exception('Failed to update stock');
            $upd->close();

            $con->commit();
            unset($_SESSION['buy_now_selected_address']);
            $_SESSION['buy_now_success'] = 'Order placed successfully!';
            header("Location: orderhistory.php");
            exit;
        } catch (Exception $e) {
            $con->rollback();
            $_SESSION['buy_now_error'] = $e->getMessage();
            header("Location: buy_now.php?product_id={$product_id}");
            exit;
        }
    }
}

$addresses = [];
$stmt = $con->prepare("SELECT id,addresss,contact_number FROM address WHERE user_id=? ORDER BY created_on DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $addresses[] = $r;
$stmt->close();
$selected = $_SESSION['buy_now_selected_address'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Buy Now - <?= htmlspecialchars($product['product_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-image {
            height: 120px;
            width: 120px;
            object-fit: cover
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">E-Store</a>
            <div class="d-flex">
                <?php if ($logged_in): ?>
                    <a class="btn btn-sm btn-warning me-2" href="cart.php">Cart</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="orderhistory.php">Orders</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="wishlist.php">Wishlist</a>
                    <a class="btn btn-sm btn-outline-light" href="logout.php" onclick="return confirm('Logout?')">Logout</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light me-2" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php if ($success_msg): ?><div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <img src="uploads/products/<?= htmlspecialchars($product['id']) ?>.jpg" onerror="this.src='uploads/products/<?= htmlspecialchars($product['image'] ?? '') ?>';" class="product-image me-3" alt="">
                        <div>
                            <h5><?= htmlspecialchars($product['product_name']) ?></h5>
                            <div class="text-muted">₹<span id="unitPrice"><?= number_format($product['price'], 2) ?></span></div>
                            <div class="mt-2">Stock: <span id="stockCount"><?= (int)$product['stock'] ?></span></div>
                            <?php if ((int)$product['stock'] > 0 && (int)$product['stock'] < 5): ?>
                                <div class="mt-2"><span class="badge bg-warning text-dark">Hurry — only <?= (int)$product['stock'] ?> left!</span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <div class="input-group" style="width:150px">
                            <button class="btn btn-outline-secondary" type="button" id="decrBtn">-</button>
                            <input type="number" id="qtyInput" class="form-control text-center" value="1" min="1" max="<?= (int)$product['stock'] ?>">
                            <button class="btn btn-outline-secondary" type="button" id="incrBtn">+</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div>Subtotal: ₹<span id="subtotal"><?= number_format($product['price'], 2) ?></span></div>
                    </div>

                    <div class="d-flex gap-2">
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="quantity" id="form_qty_cart" value="1">
                            <button class="btn btn-warning btn-sm" type="submit">Add to Cart</button>
                        </form>

                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="add_to_wishlist">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Add to Wishlist</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h5>Select Address</h5>
                <form method="post">
                    <input type="hidden" name="action" value="select_address">
                    <?php foreach ($addresses as $a): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="address_id" value="<?= (int)$a['id'] ?>" id="addr<?= $a['id'] ?>" <?= $selected === (int)$a['id'] ? 'checked' : '' ?>>
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
                <table class="table">
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Payment Method</th>
                    </tr>
                    <tr>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td class="text-end">₹<span id="unitPrice2"><?= number_format($product['price'], 2) ?></span></td>
                        <td class="text-center"><span id="qtyDisplay">1</span></td>
                        <td class="text-end">₹<span id="subtotal2"><?= number_format($product['price'], 2) ?></span></td>
                        <td class="text-center">COD</td>
                    </tr>
                </table>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="fw-bold">Total: ₹<span id="totalAmount"><?= number_format($product['price'], 2) ?></span></div>
                    <form method="post" style="margin:0">
                        <input type="hidden" name="action" value="place_order">
                        <input type="hidden" name="quantity" id="form_qty_order" value="1">
                        <button class="btn btn-success">Place Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var unit = parseFloat(document.getElementById('unitPrice').innerText);
            var stock = parseInt(document.getElementById('stockCount').innerText, 10) || 0;
            var qtyInput = document.getElementById('qtyInput');
            var incr = document.getElementById('incrBtn');
            var decr = document.getElementById('decrBtn');
            var subtotal = document.getElementById('subtotal');
            var subtotal2 = document.getElementById('subtotal2');
            var totalAmount = document.getElementById('totalAmount');
            var qtyDisplay = document.getElementById('qtyDisplay');
            var formQtyOrder = document.getElementById('form_qty_order');
            var formQtyCart = document.getElementById('form_qty_cart');

            function updateAmounts(q) {
                if (isNaN(q) || q < 1) q = 1;
                if (q > stock) q = stock;
                var amt = (unit * q).toFixed(2);
                subtotal.innerText = amt;
                subtotal2.innerText = amt;
                totalAmount.innerText = amt;
                qtyDisplay.innerText = q;
                formQtyOrder.value = q;
                formQtyCart.value = q;
                qtyInput.value = q;
            }

            incr.addEventListener('click', function(e) {
                var v = parseInt(qtyInput.value || '0', 10) + 1;
                if (v > stock) v = stock;
                updateAmounts(v);
            });
            decr.addEventListener('click', function(e) {
                var v = parseInt(qtyInput.value || '0', 10) - 1;
                if (v < 1) v = 1;
                updateAmounts(v);
            });
            qtyInput.addEventListener('change', function() {
                var v = parseInt(qtyInput.value || '0', 10);
                if (isNaN(v) || v < 1) v = 1;
                if (v > stock) v = stock;
                updateAmounts(v);
            });
            updateAmounts(1);
        })();
    </script>
</body>

</html>