<?php
include("header.php");

if ($level == 1) {

    if (isset($_POST['delete'])) {
        $cat_id = intval($_POST['category_id']);
        $delete_sql = "DELETE FROM category WHERE id = '$cat_id'";
        mysqli_query($con, $delete_sql);
        echo "<script>window.location='categories.php';</script>";
        exit;
    }

    if (isset($_POST['add_category'])) {
        $new_category = trim($_POST['category_name']);
        if (!empty($new_category)) {
            $insert_sql = "INSERT INTO category (category, user_id, created_on) VALUES ('$new_category', '$id', NOW())";
            mysqli_query($con, $insert_sql);
            echo "<script>window.location='categories.php';</script>";
            exit;
        }
    }
?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive pt-3">
                    <table class="table table-bordered" id="cat-table">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Created On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM category WHERE user_id='$id' ORDER BY id DESC";
                            $result = mysqli_query($con, $query);
                            $i = 1;
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= htmlspecialchars($row['category']) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($row['created_on'])) ?></td>
                                        <td>
                                            <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="category_id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                    $i++;
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h5>Add New Category</h5>
                <form method="post" action="">
                    <div class="mb-3">
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Electronics, Fashion, Etc.," required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </form>
            </div>
        </div>
    </div>

<?php
}
include("footer.php");
?>