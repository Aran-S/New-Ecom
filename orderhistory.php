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

$status_map = [
    1 => 'Order Placed',
    2 => 'Dispatched',
    3 => 'Shipped',
    4 => 'Out for Delivery',
    5 => 'Delivered'
];

$stmt = $con->prepare("SELECT order_number, MIN(ordered_on) AS ordered_on, payment_method, SUM(amount) AS total_amount, MIN(delivery_status) AS delivery_status
    FROM orders
    WHERE user_id = ?
    GROUP BY order_number
    ORDER BY ordered_on DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) $orders[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <h4 class="mb-4">Your Orders</h4>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">You have not placed any orders yet.</div>
        <?php else: ?>
            <div class="accordion" id="ordersAccordion">
                <?php foreach ($orders as $idx => $o):
                    $ordNum = htmlspecialchars($o['order_number']);
                    $ordered_on = htmlspecialchars(date('d M Y, H:i', strtotime($o['ordered_on'])));
                    $total = number_format((float)$o['total_amount'], 2);
                    $pm = htmlspecialchars($o['payment_method'] ?? 'COD');
                    $ds = (int)$o['delivery_status'];
                    $status_text = $status_map[$ds] ?? 'Placed';
                    $collapseId = "orderDetails" . $idx;
                ?>
                    <div class="accordion-item mb-2">
                        <h2 class="accordion-header" id="heading<?= $idx ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= $ordNum ?></strong><br>
                                        <small class="text-muted"><?= $ordered_on ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div>Total: ₹<?= $total ?></div>
                                        <div class="small text-muted"><?= $pm ?> • <?= htmlspecialchars($status_text) ?></div>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $idx ?>" data-bs-parent="#ordersAccordion">
                            <div class="accordion-body">
                                <?php
                                $items = [];
                                $s2 = $con->prepare("SELECT o.product_id, o.quantity, o.amount, o.payment_method, o.delivery_status, p.product_name, p.image
                            FROM orders o
                            LEFT JOIN products p ON o.product_id = p.id
                            WHERE o.order_number = ? AND o.user_id = ?
                            ORDER BY o.ordered_on ASC");
                                $s2->bind_param('si', $o['order_number'], $user_id);
                                $s2->execute();
                                $r2 = $s2->get_result();
                                while ($it = $r2->fetch_assoc()) $items[] = $it;
                                $s2->close();
                                ?>
                                <?php if (empty($items)): ?>
                                    <div class="alert alert-secondary">No items found for this order.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $it):
                                                    $pname = htmlspecialchars($it['product_name'] ?? 'Product');
                                                    $qty = (int)$it['quantity'];
                                                    $amt = number_format((float)$it['amount'], 2);
                                                    $price_per = $qty ? number_format((float)$it['amount'] / $qty, 2) : '0.00';
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($it['image'])): ?>
                                                                    <img src="uploads/products/<?= htmlspecialchars($it['image']) ?>" alt="" style="height:48px;width:48px;object-fit:cover" class="me-2">
                                                                <?php endif; ?>
                                                                <div><?= $pname ?></div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end">₹<?= $price_per ?></td>
                                                        <td class="text-center"><?= $qty ?></td>
                                                        <td class="text-end">₹<?= $amt ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-3 d-flex justify-content-between">
                                    <div><small class="text-muted">Order #: <?= $ordNum ?></small></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>