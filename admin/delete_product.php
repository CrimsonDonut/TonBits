<?php
require_once "../config/Database.php";
require_once "../models/Product.php";

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->connect();

    $product = new Product($db);

    $id = $_GET['id'];
    $product->deleteProduct($id);
}

header("Location: dashboard.php");
exit;