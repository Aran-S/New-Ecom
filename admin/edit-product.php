<?php
include("header.php");
if ($level == 1) {

    if (isset($_POST['update_product'])) {
        $pid = intval($_POST['product_id']);
        $name = mysqli_real_escape_string($con, $_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $count = intval($_POST['count']);
        $category_id = intval($_POST['category_id']);

        $update_sql = "UPDATE products SET product_name='$name', price='$price', stock='$stock', count='$count', category_id='$category_id' WHERE id='$pid' AND user_id='$id'";
        mysqli_query($con, $update_sql);
        echo "<script>window.location='products.php';</script>";
        exit;
    }

    $query = "SELECT * FROM products WHERE user_id='$id' ORDER BY id DESC";
    $result = mysqli_query($con, $query);
?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>Products List</h4>
                <div class="table-responsive pt-3">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $catQuery = "SELECT * FROM category WHERE id='" . $row['category_id'] . "'";
                                    $catRow = mysqli_fetch_assoc(mysqli_query($con, $catQuery));
                                    $catName = $catRow ? $catRow['category'] : 'N/A';
                            ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td><?= htmlspecialchars($catName) ?></td>
                                        <td><?= $row['price'] ?></td>
                                        <td><?= $row['stock'] ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="post" action="">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Product</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Product Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['product_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Price</label>
                                                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $row['price'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Stock</label>
                                                            <input type="number" name="stock" class="form-control" value="<?= $row['stock'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Count</label>
                                                            <input type="number" name="count" class="form-control" value="<?= $row['count'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Category</label>
                                                            <select name="category_id" class="form-select" required>
                                                                <?php
                                                                $allCats = mysqli_query($con, "SELECT * FROM category WHERE user_id='$id'");
                                                                while ($c = mysqli_fetch_assoc($allCats)) {
                                                                    $selected = ($c['id'] == $row['category_id']) ? "selected" : "";
                                                                    echo "<option value='{$c['id']}' $selected>" . htmlspecialchars($c['category']) . "</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" name="update_product" class="btn btn-success">Save Changes</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                    $i++;
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center">No products found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
}
include("footer.php");
?>