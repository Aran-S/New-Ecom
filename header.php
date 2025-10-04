<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: admin-login.php');
    exit;
}
?>

<?php include('configs/db.php'); ?>
<?php
$id = $_SESSION['id'];
$level = $_SESSION['level'];
$sql = "SELECT * FROM users WHERE id='$id'";
$result = mysqli_query($con, $sql);
$row = mysqli_fetch_assoc($result);
$name = $row['name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>
    <div class="d-flex">
        <nav class="flex-column bg-primary text-white vh-100 p-3" style="width:250px;">
            <h4 class="text-center mb-4">E-Com</h4>
            <ul class="nav nav-pills flex-column">
                <?php if ($level == "1") { ?>
                    <li class="nav-item mb-2"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item mb-2"><a class="nav-link text-white" href="categories.php">Manage Categories</a></li>
                    <li class="nav-item dropdown mb-2">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Manage Products
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="productsDropdown">
                            <li><a class="dropdown-item" href="add-product.php">Add Product</a></li>
                            <li><a class="dropdown-item" href="edit-product.php">Edit Product</a></li>
                        </ul>
                    </li>
                    <li class="nav-item mb-2"><a class="nav-link text-white" href="orders.php">Orders</a></li>
                    <li class="nav-item mt-auto"><a class="nav-link text-white" onclick="return confirm('Are you sure you want to logout?');" href="configs/logout.php">Logout</a></li>
                <?php } ?>
            </ul>
        </nav>
        <div class="flex-grow-1 p-4">