<?php
include("header.php");
if ($level == 1) {

    if (isset($_POST['add_product'])) {
        $user_id = $_POST['user_id'];
        $category_id = $_POST['category_id'];
        $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
        $price = $_POST['price'];
        $description = mysqli_real_escape_string($con, $_POST['description']);
        $stock = $_POST['stock'];
        $count = $_POST['count'];

        $allowed_mime_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_file_size = 5 * 1024 * 1024;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_size = $_FILES['image']['size'];

            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file_tmp);
            finfo_close($file_info);

            if (!in_array($mime_type, $allowed_mime_types)) {
                echo "<script>alert('Invalid file type. Only JPEG, PNG, GIF and WebP images are allowed.'); window.location.href='add-product.php';</script>";
                exit;
            }

            if ($file_size > $max_file_size) {
                echo "<script>alert('File size too large. Maximum size allowed is 5MB.'); window.location.href='add-product.php';</script>";
                exit;
            }

            $upload_dir = "uploads/products/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $query = "INSERT INTO products (user_id, category_id, product_name, price, description, image, stock, count, created_on) 
                      VALUES ('$user_id', '$category_id', '$product_name', '$price', '$description', '$new_file_name', '$stock', '$count', NOW())";

                if (mysqli_query($con, $query)) {
                    echo "<script>alert('Product added successfully!'); window.location.href='add-product.php';</script>";
                } else {
                    unlink($upload_path);
                    echo "<script>alert('Error adding product to database.'); window.location.href='add-product.php';</script>";
                }
            } else {
                echo "<script>alert('Error uploading file.'); window.location.href='add-product.php';</script>";
            }
        } else {
            echo "<script>alert('Please select an image file.'); window.location.href='add-product.php';</script>";
        }
    }

?>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>Add New Product</h4>
                <form method="post" action="add-product.php" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?= $id ?>">

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            $catQuery = "SELECT * FROM category WHERE user_id='$id' ORDER BY category ASC";
                            $catResult = mysqli_query($con, $catQuery);
                            while ($cat = mysqli_fetch_assoc($catResult)) {
                                echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['category']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" id="product_name" class="form-control" placeholder="Enter product name" required>
                        <div id="product_name_feedback" class="mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Enter price" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter product description" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock</label>
                        <select name="stock" id="stock" class="form-select" required>
                            <option value="">Select Stock</option>
                            <option value="1">In Stock</option>
                            <option value="0">Out of Stock</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Count</label>
                        <input type="number" name="count" class="form-control" placeholder="Enter count" required>
                    </div>

                    <button type="submit" name="add_product" id="submit_btn" class="btn btn-success w-100">Add Product</button>
                </form>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#product_name').on('blur', function() {
        var product_name = $(this).val();
        var user_id = $('input[name="user_id"]').val();
        
        if (product_name.length > 0) {
            $.post('check-availability.php', {
                action: 'check_product_name',
                product_name: product_name,
                user_id: user_id
            }, function(data) {
                var result = JSON.parse(data);
                if (result.status === 'exists') {
                    $('#product_name_feedback').html('<small class="text-danger">' + result.message + '</small>');
                    $('#product_name').addClass('is-invalid');
                } else {
                    $('#product_name_feedback').html('<small class="text-success">' + result.message + '</small>');
                    $('#product_name').removeClass('is-invalid').addClass('is-valid');
                }
            });
        }
    });

    $('#stock').on('input', function() {
        var stock = parseInt($(this).val());
        
        if (!isNaN(stock)) {
            $.post('check-availability.php', {
                action: 'check_stock',
                stock: stock
            }, function(data) {
                var result = JSON.parse(data);
                
                $('#stock_feedback').html('<small class="text-muted">Stock: ' + stock + '</small>');
                
                if (result.status === 'available') {
                    $('#availability_status').html('<span class="badge bg-success">✓ Available</span>');
                    $('#stock').removeClass('is-invalid').addClass('is-valid');
                } else {
                    $('#availability_status').html('<span class="badge bg-danger">✗ Out of Stock</span>');
                    $('#stock').removeClass('is-valid').addClass('is-invalid');
                }
            });
        } else {
            $('#availability_status').html('');
            $('#stock_feedback').html('');
            $('#stock').removeClass('is-valid is-invalid');
        }
    });

    $('form').on('submit', function(e) {
        var hasErrors = false;
        
        if ($('#product_name').hasClass('is-invalid') || $('#stock').hasClass('is-invalid')) {
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            alert('Please fix the errors before submitting the form.');
        }
    });
});
</script>

<?php
}
include("footer.php");
?>