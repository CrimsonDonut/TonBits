<?php
session_start();

require_once "../config/auth_helper.php";

// Remove 1-minute login cookie
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, '/');
}

// Remove remember-me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Logout user
AuthHelper::logout();

// Redirect to home
header("Location: ../index.php");
exit();
?>
