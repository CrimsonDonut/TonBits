<?php
require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Image upload
    $imageName = $_FILES['image']['name'];
    $targetDir = "../uploads/";
    $targetFile = $targetDir . basename($imageName);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        if ($product->addProduct($name, $description, $price, $quantity, $imageName)) {
            $message = "Product added successfully!";
        } else {
            $message = "Error adding product.";
        }
    } else {
        $message = "Image upload failed.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
</head>
<body>

<h2>Add Product</h2>

<p><?php echo $message; ?></p>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required><br><br>

    <textarea name="description" placeholder="Description"></textarea><br><br>

    <input type="number" step="0.01" name="price" placeholder="Price" required><br><br>

    <input type="number" name="quantity" placeholder="Quantity" required><br><br>

    <input type="file" name="image" required><br><br>

    <button type="submit">Add Product</button>
</form>

<br>
<a href="dashboard.php">Go to Dashboard</a>

</body>
</html>