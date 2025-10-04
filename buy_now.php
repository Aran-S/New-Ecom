<?php
session_start();
include("configs/db.php");
$date = date('Y-m-d H:i:s');

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
if (!$logged_in || !$user_id) {
    header('Location: login.php');
    exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

$stmt = $con->prepare("SELECT id, product_name, price, stock, user_id, category_id, warranty FROM products WHERE id=?");
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

$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
if ($quantity > $product['stock']) $quantity = $product['stock'];
$total_amount = $product['price'] * $quantity;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_address') {
        $address_text = trim($_POST['addresss'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        if ($address_text && $contact) {
            $stmt = $con->prepare("INSERT INTO address (user_id,addresss,contact_number,created_on) VALUES (?,?,?,?)");
            $stmt->bind_param('isss', $user_id, $address_text, $contact, $date);
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

    if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
        $selected = $_SESSION['buy_now_selected_address'] ?? 0;
        if (!$selected) {
            $_SESSION['buy_now_error'] = 'Select a delivery address first.';
            header("Location: buy_now.php?product_id={$product_id}");
            exit;
        }

        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        if ($quantity > $product['stock']) $quantity = $product['stock'];

        $amount = $product['price'] * $quantity;
        $seller_id = $product['user_id'];
        $category_id = $product['category_id'];
        $warranty_years = $product['warranty'];
        $delivery_status = 'Pending';
        $payment_method = 'COD';
        $order_number = '01' . rand(100, 999);
        $order_placed = 1;

        $stmt = $con->prepare("INSERT INTO orders (user_id, seller_id, category_id, product_id, quantity, payment_method, ordered_on, order_address, warranty_years, delivery_status, amount, order_number, order_placed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iiiiiisssisii', $user_id, $seller_id, $category_id, $product_id, $quantity, $payment_method, $date, $selected, $warranty_years, $delivery_status, $amount, $order_number, $order_placed);
        $stmt->execute();
        $stmt->close();

      

        unset($_SESSION['buy_now_selected_address']);
        echo '<script>
            alert("Order placed successfully!");
            window.location.href = "orderhistory.php";
        </script>';
        exit;
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
    <script>
        function updateTotal() {
            const price = <?= $product['price'] ?>;
            const qty = parseInt(document.getElementById('quantity').value) || 1;
            const subtotal = price * qty;
            document.getElementById('subtotal').innerText = '₹' + subtotal.toFixed(2);
            document.getElementById('total').innerText = '₹' + subtotal.toFixed(2);
        }
    </script>
</head>

<body>
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="container my-4">
        <h4>Buy Now: <?= htmlspecialchars($product['product_name']) ?></h4>

        <h5>Select Address</h5>
        <form method="post">
            <input type="hidden" name="action" value="select_address">
            <?php foreach ($addresses as $a): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="address_id" value="<?= $a['id'] ?>" id="addr<?= $a['id'] ?>" <?= $selected === (int)$a['id'] ? 'checked' : '' ?>>
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
        <form method="post">
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
                    <td class="text-end">₹<?= number_format($product['price'], 2) ?></td>
                    <td class="text-center">
                        <input type="number" id="quantity" name="quantity" value="<?= $quantity ?>" min="1" max="<?= $product['stock'] ?>" class="form-control text-center" style="width:80px;" oninput="updateTotal()">
                    </td>
                    <td class="text-end" id="subtotal">₹<?= number_format($total_amount, 2) ?></td>
                    <td class="text-center">COD</td>
                </tr>
            </table>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="fw-bold">Total: <span id="total">₹<?= number_format($total_amount, 2) ?></span></div>
                <input type="hidden" name="action" value="place_order">
                <button class="btn btn-success">Place Order</button>
            </div>
        </form>
    </div>
</body>

</html>