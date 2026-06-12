<?php
session_start();
require_once "../config/auth_helper.php";
if (!AuthHelper::isAdmin()) {
    header("Location: ../pages/login.php");
    exit();
}
require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);
$products = $product->getAllProducts();

// Ensure $products is always an array
if (!is_array($products)) {
    $products = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - tonbits</title>
    <link rel="stylesheet" href="../assets/style/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.cdnfonts.com/css/cyberpunks">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1 class="logo">
                    <span class="logo-white">ton</span><span class="logo-purple">bits</span>
                </h1>
                
            </div>
            <div class="nav-links">
                <a href="admin_orders.php">Manage Orders</a>
            </div>
            <button class="btn-shop" onclick="window.location.href='../index.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span>Back to Store</span>
            </button>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Admin Section -->
    <section class="admin-section">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="admin-title">Inventory Dashboard</h1>
                <div class="admin-actions">
                    <button class="btn-admin btn-add" onclick="window.location.href='add_product.php'">
                        <span>➕</span>
                        <span>Add New Product</span>
                    </button>
                </div>
            </div>

            <div class="table-container">
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Image</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product->id; ?></td>
                                        <td><?php echo htmlspecialchars($product->name); ?></td>
                                        <td><?php echo $product->formatted_price; ?></td>
                                        <td><?php echo $product->quantity; ?></td>
                                        <td>
                                            <img src="../uploads/<?php echo htmlspecialchars($product->image); ?>" class="table-image" alt="<?php echo htmlspecialchars($product->name); ?>">
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-admin btn-edit" onclick="window.location.href='edit_product.php?id=<?php echo $product->id; ?>'">Edit</button>
                                                <button class="btn-admin btn-delete" onclick="if(confirm('Are you sure you want to delete this product?')) window.location.href='delete_product.php?id=<?php echo $product->id; ?>'">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                        No products found. <a href="add_product.php" style="color: #aa00ff; text-decoration: underline; font-weight: 600;">Add your first product</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</body>
</html>