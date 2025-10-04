<?php
session_start();
include "koneksi.php";

$valid_user = 'admin';
$valid_pass = 'admin123';

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === $valid_user && $password === $valid_pass) {
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
    exit;
} else {
    $_SESSION['login_error'] = "Username atau password salah!";
    header("Location: index.php");
    exit;
}
?>
