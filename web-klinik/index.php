<?php
session_start();
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Klinik PT Makmur Lestari Primatama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color:rgb(56, 60, 66);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .login-box {
            margin-top: auto;
            margin-bottom: auto;
        }
        footer {
            text-align: center;
            margin-top: auto;
            padding: 15px 0;
            color: #888;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center flex-column login-box">
    <img src="img/logo.png" class="logo" alt="Logo">
    <h4 class="text-white">Halaman Login</h4>
    <h5 class="mb-7 text-white">Klinik PT Makmur Lestari Primatama</h5> 


    <?php if ($error): ?>
    <div class="alert alert-danger w-100" style="max-width: 400px;"><?= $error ?></div>
    <?php endif; ?>

    <form action="proses_login.php" method="POST" class="w-100" style="max-width: 400px;">
        <div class="mb-3">
            <input type="text" name="username" value="" autocomplete="off" class="form-control form-control-lg" placeholder="Username" required>
        </div>
        <div class="mb-3">
            <div class="input-group">
                <input type="password" name="password" value="" autocomplete="off" id="password" class="form-control form-control-lg" placeholder="Password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">👁</button>
            </div>
        </div>
        <div class="d-grid">
            <button class="btn btn-primary btn-lg">Login</button>
        </div>
    </form>
</div>

<footer>
    &copy; Copyright IT MLP 2025
</footer>

<script>
function togglePassword() {
    const passField = document.getElementById("password");
    passField.type = passField.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
