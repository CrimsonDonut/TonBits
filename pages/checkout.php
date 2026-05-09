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

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3 class="logo">
                        <span class="logo-white">ton</span><span class="logo-purple">bits</span>
                    </h3>
                    <p class="footer-tagline">Premium GPU solutions for gamers and creators worldwide</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/carlanthony.pena" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Products</h4>
                    <ul>
                        <li><a href="#">Graphics Cards</a></li>
                        <li><a href="#">RTX 50 Series</a></li>
                        <li><a href="#">RTX 40 Series</a></li>
                        <li><a href="#">Accessories</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Driver Downloads</a></li>
                        <li><a href="#">Warranty</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-newsletter">
                    <h4>Stay Updated</h4>
                    <p>Subscribe to our newsletter for the latest updates</p>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Enter email">
                        <button>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 tonbits. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
