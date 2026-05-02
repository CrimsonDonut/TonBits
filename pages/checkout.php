<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Product.php";
require_once "../models/Order.php";

$database = new Database();
$db = $database->connect();

// Check if user is logged in
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product = new Product($db);
$order_model = new Order($db);

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit();
}

$error = null;
$success = null;

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim(htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8'));
    $notes = trim(htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8'));

    // Validate shipping address
    if (empty($shipping_address) || strlen($shipping_address) < 10) {
        $error = "Please enter a valid shipping address (at least 10 characters).";
    } else {
        // Get cart items
        $cart_items = [];
        $total = 0;

        foreach ($_SESSION['cart'] as $id => $qty) {
            $prod = $product->getProductById($id);
            if ($prod) {
                $prod->quantity_in_cart = $qty;
                $cart_items[] = $prod;
                $total += $prod->price * $qty;
            }
        }

        if (empty($cart_items)) {
            $error = "Cart items are no longer available.";
        } else {
            // Create order using Order model (with timestamps)
            $result = $order_model->createOrderFromCart($user_id, $cart_items, $shipping_address, $notes);

            if ($result['success']) {
                // Clear cart session
                unset($_SESSION['cart']);
                $success = true;
                $order_id = $result['order_id'];
                
                // Redirect to order confirmation page
                header("Location: order-confirmation.php?order_id=" . $order_id);
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Get current cart total for preview
$cart_total = 0;
$cart_items_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        $prod = $product->getProductById($id);
        if ($prod) {
            $cart_total += $prod->price * $qty;
            $cart_items_count += $qty;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - tonbits</title>
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
                <a href="cart.php">Cart</a>
            </div>
            <button class="btn-shop" onclick="window.location.href='cart.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Cart (<?php echo $cart_items_count; ?>)</span>
            </button>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Checkout Section -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">Checkout</h1>
                <p class="auth-subtitle">Complete your purchase</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="error-message">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Checkout Form -->
                <form method="POST" class="auth-form">
                    <!-- Cart Summary -->
                    <div class="checkout-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span>Items:</span>
                            <span><?php echo $cart_items_count; ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>₱<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                    </div>

                    <!-- Shipping Address Field -->
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address</label>
                        <textarea 
                            id="shipping_address" 
                            name="shipping_address" 
                            placeholder="Enter your complete shipping address"
                            required
                            rows="4"
                        ></textarea>
                    </div>

                    <!-- Notes Field -->
                    <div class="form-group">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            placeholder="Any special instructions or notes"
                            rows="3"
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">Complete Order</button>
                </form>

                <!-- Back to Cart Link -->
                <p class="auth-footer">
                    <a href="cart.php" class="auth-link">← Back to Cart</a>
                </p>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
