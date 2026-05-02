<?php
session_start();

require_once "../config/auth_helper.php";

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
