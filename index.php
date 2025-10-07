<?php
session_start();
include("configs/db.php");

$logged_in = (isset($_SESSION['user_loggedin']) && $_SESSION['user_loggedin'] === true)
    || (isset($_SESSION['level']) && (int)$_SESSION['level'] == 2);

$user_level = 0;
if (isset($_SESSION['user_level'])) {
    $user_level = (int)$_SESSION['user_level'];
} elseif (isset($_SESSION['level'])) {
    $user_level = (int)$_SESSION['level'];
}

$categories = mysqli_query($con, "SELECT * FROM category ORDER BY category ASC");

$where = [];
if (isset($_GET['category']) && $_GET['category'] != '') {
    $cat = (int)$_GET['category'];
    $where[] = "p.category_id = $cat";
}
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $min = (float)$_GET['min_price'];
    $where[] = "p.price >= $min";
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $max = (float)$_GET['max_price'];
    $where[] = "p.price <= $max";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT p.*, c.category 
        FROM products p 
        LEFT JOIN category c ON p.category_id = c.id 
        $where_sql 
        ORDER BY p.created_on DESC";
$result = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>E-Store - Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image {
            height: 200px;
            object-fit: cover
        }

        .product-card {
            transition: transform .15s
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08)
        }

        .price {
            color: #e47911;
            font-weight: 700
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            left: 10px
        }

        .rating i {
            color: #ffc107
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-shopping-bag"></i> E-Store</a>
            <div class="d-flex">
                <?php if ($logged_in): ?>
                    <a class="btn btn-sm btn-warning me-2" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="orderhistory.php"><i class="fas fa-list"></i> Orders</a>
                    <a class="btn btn-sm btn-outline-light me-2" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                    <a class="btn btn-sm btn-outline-light" href="logout.php" onclick="return confirmLogout(event)">Logout</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light me-2" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h3 class="mb-4">All Products</h3>

        <form method="get" class="row mb-4">
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $c['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['category']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="min_price" class="form-control" placeholder="Min Price" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <input type="number" name="max_price" class="form-control" placeholder="Max Price" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="row">
                <?php while ($p = mysqli_fetch_assoc($result)): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100 position-relative">
                            <?php if ($p['count'] <= 0): ?>
                                <span class="badge bg-danger stock-badge">Out of Stock</span>
                            <?php endif; ?>
                            <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>"
                                alt="<?= htmlspecialchars($p['product_name']) ?>"
                                class="card-img-top product-image"
                                style="cursor:pointer">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title" style="cursor:pointer">
                                    <?= htmlspecialchars($p['product_name']) ?>
                                </h6>
                                <p class="text-muted small mb-1">
                                    <?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?> |
                                    <?= htmlspecialchars($p['brand'] ?? 'No Brand') ?>
                                </p>
                                <div class="rating mb-1">
                                    <?php $rating = (float)($p['rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa<?= $i <= $rating ? 's' : 'r' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="small text-truncate mb-2">
                                    <?= htmlspecialchars(substr($p['description'], 0, 80)) ?>
                                    <?= strlen($p['description']) > 80 ? '...' : '' ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="price mb-2">₹<?= number_format($p['price'], 2) ?></div>
                                    <p class="small mb-2">Stock: <?= (int)$p['count'] ?></p>
                                    <?php if ((int)$p['count'] > 0 && (int)$p['count'] < 5): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-warning text-dark">Hurry — only <?= (int)$p['count'] ?> left!</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-grid gap-2">
                                        <?php if ($logged_in): ?>
                                            <button class="btn btn-warning btn-sm" onclick="addToCart(<?= (int)$p['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                            <button class="btn btn-success btn-sm" onclick="buyNow(<?= (int)$p['id'] ?>)">
                                                <i class="fas fa-bolt"></i> Buy Now
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="addToWishlist(<?= (int)$p['id'] ?>)">
                                                <i class="fas fa-heart"></i> Wishlist
                                            </button>
                                        <?php else: ?>
                                            <a class="btn btn-outline-primary btn-sm" href="login.php">Login to explore product</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>No products found</h5>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container d-flex justify-content-between align-items-center">
            <div>&copy; <?= date('Y') ?> E-Store. All rights reserved.</div>
            <div><small><a href="admin/admin-login.php" target="_blank" class="text-light text-decoration-none"><i class="fas fa-shield-alt"></i> Admin Access</a></small></div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function addToCart(productId) {
            console.log('addToCart called with productId:', productId);
            <?php if ($logged_in && $user_level == 2): ?>
                $.ajax({
                    url: 'add_to_cart.php',
                    method: 'POST',
                    data: {
                        product_id: productId
                    },
                    dataType: 'json',
                    timeout: 10000
                }).done(function(r) {
                    if (r && r.status === 'success') {
                        alert(r.message || 'Added to cart');
                        location.reload();
                    } else {
                        alert(r && r.message ? r.message : 'Could not add to cart');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('add_to_cart error', textStatus, errorThrown, jqXHR.responseText);
                    alert('Request failed: ' + (jqXHR.responseText || textStatus));
                });
            <?php else: ?>
                alert('Please login to add products to cart');
                location.href = 'login.php';
            <?php endif; ?>
        }

        function buyNow(productId) {
            <?php if ($logged_in && $user_level == 2): ?>
                location.href = 'buy_now.php?product_id=' + productId;
            <?php else: ?>
                alert('Please login to buy products');
                location.href = 'login.php';
            <?php endif; ?>
        }

        function addToWishlist(productId) {
            console.log('addToWishlist called with productId:', productId);
            <?php if ($logged_in && $user_level == 2): ?>
                $.ajax({
                    url: 'add_to_wishlist.php',
                    method: 'POST',
                    data: {
                        product_id: productId
                    },
                    dataType: 'json',
                    timeout: 10000
                }).done(function(r) {
                    if (r && r.status === 'success') {
                        alert(r.message || 'Added to wishlist');
                    } else {
                        alert(r && r.message ? r.message : 'Could not add to wishlist');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('add_to_wishlist error', textStatus, errorThrown, jqXHR.responseText);
                    alert('Request failed: ' + (jqXHR.responseText || textStatus));
                });
            <?php else: ?>
                alert('Please login to add products to wishlist');
                location.href = 'login.php';
            <?php endif; ?>
        }

        function confirmLogout(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
                return false;
            }
            return true;
        }
    </script>
</body>

</html>