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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'remove' && isset($_POST['wishlist_id'])) {
        $wid = (int)$_POST['wishlist_id'];
        $d = $con->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $d->bind_param('ii', $wid, $user_id);
        $d->execute();
        $d->close();
        $_SESSION['msg'] = 'Removed from wishlist';
        header('Location: wishlist.php');
        exit;
    }
    if ($action === 'add_to_cart' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $count = 1;
        $stmt = $con->prepare("SELECT id, stock FROM products WHERE id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            $_SESSION['msg'] = 'Product not found';
            header('Location: wishlist.php');
            exit;
        }
        $prod = $res->fetch_assoc();
        $stmt->close();
        if ((int)$prod['stock'] < 1) {
            $_SESSION['msg'] = 'Out of stock';
            header('Location: wishlist.php');
            exit;
        }
        $stmt = $con->prepare("SELECT id, `count` FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $new_q = (int)$r['count'] + $count;
            if ($new_q > (int)$prod['stock']) {
                $_SESSION['msg'] = 'Not enough stock';
                header('Location: wishlist.php');
                exit;
            }
            $u = $con->prepare("UPDATE cart SET `count` = ? WHERE id = ? AND user_id = ?");
            $u->bind_param('iii', $new_q, $r['id'], $user_id);
            $u->execute();
            $u->close();
        } else {
            $to_insert = min((int)$prod['stock'], $count);
            $i = $con->prepare("INSERT INTO cart (user_id, product_id, `count`, created_on) VALUES (?,?,?, NOW())");
            $i->bind_param('iii', $user_id, $product_id, $to_insert);
            $i->execute();
            $i->close();
        }
        $del = $con->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $del->bind_param('ii', $user_id, $product_id);
        $del->execute();
        $del->close();
        $_SESSION['msg'] = 'Added to cart';
        header('Location: wishlist.php');
        exit;
    }
}

$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

$items = [];
$s = $con->prepare("SELECT w.id AS wid, p.id AS pid, p.product_name, p.price, p.image, p.stock, p.description 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ? 
    ORDER BY w.created_on DESC");
$s->bind_param('i', $user_id);
$s->execute();
$res = $s->get_result();
while ($r = $res->fetch_assoc()) $items[] = $r;
$s->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your Wishlist</title>
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">E-Store</a>
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

    <div class="container my-4">
        <h4>Your Wishlist</h4>
        <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if (empty($items)): ?>
            <div class="alert alert-info">Your wishlist is empty.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Stock</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($it['image'])): ?>
                                            <img src="uploads/products/<?= htmlspecialchars($it['image']) ?>" class="product-image me-3" alt="">
                                        <?php endif; ?>
                                        <div>
                                            <div><strong><?= htmlspecialchars($it['product_name']) ?></strong></div>
                                            <div class="text-muted small"><?= htmlspecialchars(substr($it['description'], 0, 80)) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">₹<?= number_format($it['price'], 2) ?></td>
                                <td class="text-center"><?= (int)$it['stock'] ?></td>
                                <td class="text-end">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?= (int)$it['pid'] ?>">
                                        <button class="btn btn-sm btn-success" type="submit">Add to Cart</button>
                                    </form>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="wishlist_id" value="<?= (int)$it['wid'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Remove this item from wishlist?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
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