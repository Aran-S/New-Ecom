<?php include("header.php"); ?>
<?php
if ($level == 1) {
    $query = "SELECT * FROM orders WHERE seller_id='$id' ORDER BY id DESC";
    $result = mysqli_query($con, $query);
?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>Orders List</h4>
                <div class="table-responsive pt-3">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Order Number</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Ordered On</th>
                                <th>Warranty (Years)</th>
                                <th>Delivery Status</th>
                                <th>Order Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $product_name = mysqli_fetch_assoc(mysqli_query($con, "SELECT product_name FROM products WHERE id='{$row['product_id']}'"))['product_name'] ?? 'N/A';
                                    $category_name = mysqli_fetch_assoc(mysqli_query($con, "SELECT category FROM category WHERE id='{$row['category_id']}'"))['category'] ?? 'N/A';
                                    $address = mysqli_fetch_assoc(mysqli_query($con, "SELECT addresss FROM address WHERE id='{$row['order_address']}'"))['addresss'] ?? 'N/A';

                                    if ($row['delivery_status'] == 1) {
                                        $status = "Order Placed / Pending Dispatch";
                                        $action_btn = "<a href='update_status.php?order_id={$row['id']}&status=2' class='btn btn-sm btn-warning'>Mark as Dispatched</a>";
                                    } elseif ($row['delivery_status'] == 2) {
                                        $status = "Shipped";
                                        $action_btn = "<a href='update_status.php?order_id={$row['id']}&status=3' class='btn btn-sm btn-info'>Mark In Transit</a>";
                                    } elseif ($row['delivery_status'] == 3) {
                                        $status = "In Transit";
                                        $action_btn = "<a href='update_status.php?order_id={$row['id']}&status=4' class='btn btn-sm btn-success'>Mark Delivered</a>";
                                    } elseif ($row['delivery_status'] == 4) {
                                        $status = "Delivered";
                                        $action_btn = "<span class='text-success'>Completed</span>";
                                    } else {
                                        $status = "Unknown";
                                        $action_btn = "";
                                    }
                            ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($row['order_number']) ?></td>
                                        <td><?= htmlspecialchars($product_name) ?></td>
                                        <td><?= htmlspecialchars($category_name) ?></td>
                                        <td><?= number_format($row['amount'], 2) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($row['ordered_on'])) ?></td>
                                        <td><?= $row['warranty_years'] ?></td>
                                        <td><?= $status ?></td>
                                        <td><?= htmlspecialchars($address) ?></td>
                                        <td><?= $action_btn ?></td>
                                    </tr>
                            <?php
                                    $i++;
                                }
                            } else {
                                echo '<tr><td colspan="10" class="text-center">No orders found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php
}
?>

<?php if ($level == 2) { ?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>Your Order History</h4>
                <div class="table-responsive pt-3">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Order Number</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Ordered On</th>
                                <th>Warranty (Years)</th>
                                <th>Delivery Status</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $order_sql = "SELECT * FROM orders WHERE user_id='$id' AND order_placed=1 ORDER BY id DESC";
                            $result = $con->query($order_sql);
                            $i = 1;

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $product_name = mysqli_fetch_assoc(mysqli_query($con, "SELECT product_name FROM products WHERE id='" . $row['product_id'] . "'"))['product_name'] ?? 'N/A';
                                    $category_name = mysqli_fetch_assoc(mysqli_query($con, "SELECT category FROM category WHERE id='" . $row['category_id'] . "'"))['category'] ?? 'N/A';
                                    $address = mysqli_fetch_assoc(mysqli_query($con, "SELECT addresss FROM address WHERE id='{$row['order_address']}'"))['addresss'] ?? 'N/A';

                                $status = ($row['delivery_status'] == 1) ? "Order Placed" : (($row['delivery_status'] == 2) ? "Dispatched" : (($row['delivery_status'] == 3) ? "Delivered" : "Pending"));
                            ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($row['order_number']) ?></td>
                                        <td><?= htmlspecialchars($product_name) ?></td>
                                        <td><?= htmlspecialchars($category_name) ?></td>
                                        <td><?= number_format($row['amount'], 2) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($row['ordered_on'])) ?></td>
                                        <td><?= $row['warranty_years'] ?></td>
                                        <td><?= $status ?></td>
                                        <td><?= htmlspecialchars($address) ?></td>
                                    </tr>
                            <?php
                                    $i++;
                                }
                            } else {
                                echo '<tr><td colspan="9" class="text-center">No orders found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php } ?>

<?php include("footer.php"); ?>