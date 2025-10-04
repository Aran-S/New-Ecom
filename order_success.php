<?php
session_start();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$logged_in = isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Order Placed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
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
    <div class="container my-5">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="mb-3">Thank you! Your order has been placed.</h4>
                <?php if ($order_id): ?>
                    <p>Your order ID: <strong><?= $order_id ?></strong></p>
                <?php endif; ?>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                <a href="orderhistory.php" class="btn btn-secondary">View Orders</a>
            </div>
        </div>
    </div>
</body>

</html>