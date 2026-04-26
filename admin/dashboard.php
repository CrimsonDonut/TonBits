<?php
require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);
$products = $product->getAllProducts();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<h2>Inventory Dashboard</h2>

<a href="add_product.php">➕ Add New Product</a>

<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Image</th>
        <th>Action</th>
    </tr>

    <?php foreach ($products as $row): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td>₱<?php echo number_format($row['price'], 2); ?></td>
            <td><?php echo $row['quantity']; ?></td>
            <td>
                <img src="../uploads/<?php echo $row['image']; ?>" width="60">
            </td>
            <td>
                <a href="delete_product.php?id=<?php echo $row['id']; ?>" 
                   onclick="return confirm('Delete this product?')">
                   ❌ Delete
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<br>
<a href="../index.php">Back to Store</a>

</body>
</html>