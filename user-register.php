<?php include("configs/db.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | ShopEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            max-width: 450px;
            width: 100%;
            border-radius: 1rem;
            padding: 2rem;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>

    <div class="register-card">
        <h3 class="text-center mb-4">User Registration</h3>
        <?php
        if (isset($_POST['submit'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $mobile = $_POST['mobile'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $dob = $_POST['dob'];
            $created_at = date('Y-m-d H:i:s');
            $randval = mt_rand(100000, 999999);
            $level = '2';

            if ($password !== $confirm_password) {
                echo '<div class="alert alert-danger">Passwords do not match.</div>';
            } else {
                $checkQuery = "SELECT * FROM users WHERE mail='$email'";
                $checkResult = mysqli_query($con, $checkQuery);
                if (mysqli_num_rows($checkResult) > 0) {
                    echo '<div class="alert alert-danger">Email already registered.</div>';
                } else {
                    $hashedPassword = md5($password);
                    $insertQuery = "INSERT INTO users (name, mail, mobile, dob,password, created_on, level, randval) VALUES ('$name', '$email', '$mobile', '$dob', '$hashedPassword', '$created_at', '$level', '$randval')";
                    if (mysqli_query($con, $insertQuery)) {
                        echo '<div class="alert alert-success">Registration successful. <a href="login.php">Login here</a>.</div>';
                    } else {
                        echo '<div class="alert alert-danger">Error: ' . mysqli_error($con) . '</div>';
                    }
                }
            }
        }

        ?>
        <form autocomplete="off" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" name="name" id="name" placeholder="Enter your full name" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
            </div>

            <div class="mb-3">
                <label for="mobile" class="form-label">Mobile Number</label>
                <input type="mobile" class="form-control" name="mobile" id="mobile" placeholder="Enter your mobile number" required>
            </div>
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" class="form-control" name="dob" id="dob" placeholder="Enter your date of birth" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="mt-3 text-center">Already have an account? <a href="index.php">Login Here</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

</html>