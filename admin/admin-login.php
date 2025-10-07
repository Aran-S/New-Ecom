<?php
session_start();
require_once('../configs/db.php');

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

$admin_err = $admin_pass_err = "";

if ($_SERVER["REQUEST_METHOD"] === 'POST') {

    if (isset($_POST['admin_login'])) {
        $email = trim($_POST['admin_email'] ?? '');
        $password = trim($_POST['admin_password'] ?? '');

        if (empty($email)) $admin_err = "Please enter your email!";
        if (empty($password)) $admin_pass_err = "Please enter your password!";

        if (empty($admin_err) && empty($admin_pass_err)) {
            $password_hashed = md5($password);
            $sql = "SELECT id, name, level, password, active_status FROM users WHERE mail=? AND level=1 LIMIT 1";
            if ($stmt = mysqli_prepare($con, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $level, $db_password, $active_status);
                    mysqli_stmt_fetch($stmt);
                    if ($password_hashed === $db_password) {
                        if ($active_status == 0) $admin_pass_err = "Your account is not activated yet!";
                        else {
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["level"] = $level;
                            header("location: dashboard.php");
                            exit;
                        }
                    } else $admin_pass_err = "Invalid password!";
                } else $admin_err = "No admin account found with that email.";
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Com Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 450px;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }

        .nav-tabs .nav-link {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="card login-card p-4">
        <div class="card-body text-center">
            <img src="https://cdn-icons-png.flaticon.com/512/891/891462.png" class="brand-logo mb-3" alt="logo" style="width: 80px;">
            <h3 class="fw-bold mb-4">E-Com</h3>

            <form method="post">
                <div class="mb-3 text-start">
                    <label for="adminEmail" class="form-label">Admin Email</label>
                    <input type="email" class="form-control" id="adminEmail" name="admin_email" placeholder="Enter email" required>
                    <span class="text-danger">
                        <?= isset($admin_err) ? $admin_err : '' ?>
                    </span>
                </div>

                <div class="mb-3 text-start">
                    <label for="adminPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="adminPassword" name="admin_password" placeholder="Enter password" required>
                    <span class="text-danger">
                        <?= isset($admin_pass_err) ? $admin_pass_err : '' ?>
                    </span>
                </div>

                <button type="submit" name="admin_login" class="btn btn-danger w-100">Login as Admin</button>
                <div class="text-center mt-3">
                    <!-- <a href="index.php" class=" text-decoration-underline">Back to Shop</a> -->
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>