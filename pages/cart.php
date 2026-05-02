<?php
session_start();

require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

// error message for cart actions
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart with sanitization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (isset($_POST['product_id']) && is_numeric($_POST['product_id']) && (int)$_POST['product_id'] > 0) ? (int)$_POST['product_id'] : null;

    if ($product_id === null) {
        $_SESSION['error'] = "Invalid product ID.";
        header("Location: ../index.php");
        exit();
    }

    $item = $product->getProductById($product_id);

    if (!$item) {
        $_SESSION['error'] = "Product not found.";
        header("Location: ../index.php");
        exit();
    }

    $stock = $item->quantity;
    $currentQty = $_SESSION['cart'][$product_id] ?? 0;

    // Check if adding one more item would exceed available stock
    if ($currentQty + 1 <= $stock) {
        // Add to cart (without reducing stock in database)
        $_SESSION['cart'][$product_id] = $currentQty + 1;
    } else {
        $_SESSION['error'] = "Cannot add more items. Only " . ($stock - $currentQty) . " item(s) available.";
    }

    header("Location: ../index.php");
    exit();
}

// Remove item from cart with sanitization
if (isset($_GET['remove'])) {
    $remove_id = (is_numeric($_GET['remove']) && (int)$_GET['remove'] > 0) ? (int)$_GET['remove'] : null;

    if ($remove_id === null) {
        $_SESSION['error'] = "Invalid product ID.";
        header("Location: cart.php");
        exit();
    }

    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }

    header("Location: cart.php");
    exit();
}

// Clear cart button with sanitization
if (isset($_GET['clear'])) {
    $clear = trim(htmlspecialchars($_GET['clear'] ?? '', ENT_QUOTES, 'UTF-8'));
    if (strtolower($clear) === 'all') {
        unset($_SESSION['cart']);
    }
    header("Location: cart.php");
    exit();
}

// Calculate grand total ARITHMETIC
$grandTotal = 0;
$cartItems = [];

foreach ($_SESSION['cart'] as $id => $qty) {
    $prod = $product->getProductById($id);
    if ($prod) {
        $prod->quantity_in_cart = $qty;
        $cartItems[] = $prod;
        $grandTotal += $prod->price * $qty;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - tonbits</title>
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
                <a href="../index.php">Store</a>
                <a href="../index.php#products">Products</a>
                <a href="../index.php#features">Features</a>
            </div>
            <button class="btn-shop" onclick="window.location.href='cart.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Cart</span>
            </button>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Cart Section -->
    <section class="admin-section">
        <div class="admin-container">
            <div class="breadcrumb">
                <a href="../index.php">Store</a>
                <span>/</span>
                <span>Shopping Cart</span>
            </div>

            <div class="admin-header">
                <h1 class="admin-title">Shopping Cart</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="message-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($cartItems)): ?>
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): 
                                    $itemTotal = $item->price * $item->quantity_in_cart;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="product-row">
                                                <img src="../uploads/<?php echo htmlspecialchars($item->image); ?>" class="cart-item-image" alt="<?php echo htmlspecialchars($item->name); ?>">
                                                <span><?php echo htmlspecialchars($item->name); ?></span>
                                            </div>
                                        </td>
                                        <td>₱<?php echo number_format($item->price, 2); ?></td>
                                        <td><?php echo $item->quantity_in_cart; ?></td>
                                        <td>₱<?php echo number_format($itemTotal, 2); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-admin btn-delete" onclick="if(confirm('Remove this item?')) window.location.href='cart.php?remove=<?php echo $item->id; ?>'">Remove</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="cart-footer">
                    <div class="cart-summary-box">
                        <div class="summary-row">
                            <span>Items:</span>
                            <strong><?php echo count($cartItems); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <strong>₱<?php echo number_format($grandTotal, 2); ?></strong>
                        </div>
                        <div class="summary-row total">
                            <span>Grand Total:</span>
                            <strong>₱<?php echo number_format($grandTotal, 2); ?></strong>
                        </div>
<a href="checkout.php" class="btn-admin btn-add" 
   style="display: block; width: 100%; margin-top: 1rem; text-align: center;">
    Proceed to Checkout
</a>
                        <button class="btn-admin btn-delete" onclick="if(confirm('Clear entire cart?')) window.location.href='cart.php?clear=true'" style="width: 100%; margin-top: 0.5rem;">
                            Clear Cart
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-wrapper">
                        <div class="empty-state">
                            <div class="empty-state-icon">🛒</div>
                            <h2>Your Cart is Empty</h2>
                            <p>Add some Graphics Cards to get started!</p>
                            <a href="../index.php" class="btn-admin btn-add">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3 class="logo">
                        <span class="logo-white">ton</span><span class="logo-purple">bits</span>
                    </h3>
                    <p class="footer-tagline">Premium GPU solutions for gamers and creators worldwide</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="../index.php">Store</a></li>
                        <li><a href="cart.php">Cart</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 tonbits. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>