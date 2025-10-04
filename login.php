<?php
session_start();
require_once('configs/db.php');

if (isset($_SESSION["user_loggedin"]) && $_SESSION["user_loggedin"] === true) {
    header("location: index.php");
    exit;
}

$user_err = $user_pass_err = "";
$admin_err = $admin_pass_err = "";

if ($_SERVER["REQUEST_METHOD"] === 'POST') {

    if (isset($_POST['user_login'])) {
        $email = trim($_POST['user_email'] ?? '');
        $password = trim($_POST['user_password'] ?? '');

        if (empty($email)) $user_err = "Please enter your email!";
        if (empty($password)) $user_pass_err = "Please enter your password!";

        if (empty($user_err) && empty($user_pass_err)) {
            $password_hashed = md5($password);
            $sql = "SELECT id, name, level, password, active_status FROM users WHERE mail=? LIMIT 1";
            if ($stmt = mysqli_prepare($con, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $level, $db_password, $active_status);
                    mysqli_stmt_fetch($stmt);
                    if ($password_hashed === $db_password) {
                        if ($level == 1 && $active_status == 0) $user_pass_err = "Your account is not activated yet!";
                        else {
                            $_SESSION['user_loggedin'] = true;
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_level'] = (int)$level;

                         
                            header("location: index.php");
                            exit;
                        }
                    } else $user_pass_err = "Invalid password!";
                } else $user_err = "No account found with that email.";
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
            <img src="https://cdn-icons-png.flaticon.com/512/891/891462.png" class="brand-logo" alt="logo">
            <h3 class="fw-bold mb-4">E-Com</h3>

            <div class="tab-content" id="loginTabsContent">
                <div class="tab-pane fade show active" id="user">
                    <form method="post">
                        <div class="mb-3 text-start">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" name="user_email" placeholder="Enter your email" required>
                            <span class="text-danger"><?= $user_err ?></span>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="userPassword" name="user_password" placeholder="Enter your password" required>
                            <span class="text-danger"><?= $user_pass_err ?></span>
                        </div>
                        <button type="submit" name="user_login" class="btn btn-primary w-100">Login as User</button>
                        <p class="mt-3 mb-0 text-center">First time user? <a href="user-register.php">Sign Up</a></p>
                    </form>
                </div>
            </div>

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