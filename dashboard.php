<?php include("header.php");
     if ($level == "1") { ?>
        <?php
        $seller = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM seller_details WHERE user_id='$id'"));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $company_name = $_POST['company_name'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            $address = $_POST['address'] ?? '';

            if ($seller) {
                mysqli_query($con, "UPDATE seller_details SET company_name='$company_name', contact_number='$contact_number', address='$address' WHERE user_id='$id'");
                $message = "Details updated successfully!";
            } else {
                mysqli_query($con, "INSERT INTO seller_details (user_id, company_name, contact_number, address, created_on) VALUES ('$id', '$company_name', '$contact_number', '$address', NOW())");
                $message = "Details saved successfully!";
            }
            $seller = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM seller_details WHERE user_id='$id'"));
        }

        $total_orders_received = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM orders WHERE seller_id='$id' AND order_placed=1"))['total'] ?? 0;
        ?>

        <div class="container mt-4">
            <p class="fw-bold mb-3">Logged in as <?= htmlspecialchars($name) ?></p>

            <div class="row mt-5 d-flex justify-content-center">
                <div class="col-md-4">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h5>Total Orders Received</h5>
                            <h3><?= $total_orders_received ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h4>Seller Details</h4>
                    <?php if (isset($message)) echo "<p class='text-success'>$message</p>"; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" required value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" required value="<?= htmlspecialchars($seller['contact_number'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" required><?= htmlspecialchars($seller['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $seller ? "Update Details" : "Save Details" ?></button>
                    </form>
                </div>
            </div>
        </div>

    <?php
}
include("footer.php");
    ?>