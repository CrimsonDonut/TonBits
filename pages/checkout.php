<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Product.php";
require_once "../models/Order.php";
require_once "../models/UserAddress.php";

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
$address_model = new UserAddress($db);

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit();
}

$error = null;
$success = null;
$user_addresses = $address_model->getAddressesByUser($user_id);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $use_new_address = isset($_POST['use_new_address']) ? true : false;
    $street_address = trim(htmlspecialchars($_POST['street_address'] ?? '', ENT_QUOTES, 'UTF-8'));
    
    // If address fields are filled, treat as new address
    if (!empty($street_address)) {
        $use_new_address = true;
    }
    
    $notes = trim(htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8'));
    $payment_method = trim(htmlspecialchars($_POST['payment_method'] ?? '', ENT_QUOTES, 'UTF-8'));
    $address_id = null;

    // Validate payment method first
    $valid_payment_methods = ['ewallet', 'cod', 'card'];
    if (empty($payment_method) || !in_array($payment_method, $valid_payment_methods)) {
        $error = "Please select a payment method.";
    }

    // Handle address selection
    if ($use_new_address) {
        // Create new address
        $barangay = trim(htmlspecialchars($_POST['barangay'] ?? '', ENT_QUOTES, 'UTF-8'));
        $city = trim(htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8'));
        $province = trim(htmlspecialchars($_POST['province'] ?? '', ENT_QUOTES, 'UTF-8'));

        if (empty($street_address) || empty($barangay) || empty($city) || empty($province)) {
            $error = "Please fill in all address fields.";
        } else {
            $address_id = $address_model->create($user_id, $street_address, $barangay, $city, $province, false);
            if (!$address_id) {
                $error = "Failed to create address.";
            }
        }
    } else {
        // Use existing address
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : null;
        if (empty($address_id)) {
            $error = "Please select a shipping address.";
        }
    }

    if (!$error) {
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
            // Create order using the new address_id and payment method
            $result = $order_model->createOrderFromCart($user_id, $cart_items, $address_id, $payment_method, $notes);

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
    <style>
        .address-selector { margin: 20px 0; }
        .address-option { 
            padding: 15px; 
            margin: 10px 0; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            cursor: pointer;
            transition: all 0.3s;
        }
        .address-option:hover { border-color: #00d4ff; }
        .address-option input[type="radio"] { margin-right: 10px; }
        .address-form { 
            display: none; 
            padding: 20px; 
            border: 2px solid #00d4ff; 
            border-radius: 5px;
            margin: 20px 0;
        }
        .address-form input { 
            width: 100%; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 3px;
        }
        .payment-methods { 
            border: 2px solid rgba(255,255,255,0.2); 
            border-radius: 8px; 
            padding: 15px;
            background: rgba(0,0,0,0.3);
        }
        .payment-option { 
            padding: 10px 0; 
            margin: 8px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        .payment-option:hover { 
            color: #00d4ff;
        }
        .payment-option input[type="radio"] { 
            margin-right: 10px;
            cursor: pointer;
        }
        .payment-option label {
            cursor: pointer;
            flex: 1;
            margin: 0;
        }
    </style>
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

                    <!-- Shipping Address Selection -->
                    <div class="form-group">
                        <label>Shipping Address</label>
                        
                        <?php if (!empty($user_addresses)): ?>
                            <div class="address-selector">
                                <h4>Select from saved addresses:</h4>
                                <?php foreach ($user_addresses as $addr): ?>
                                    <div class="address-option" style="display: flex; gap: 15px;">
                                        <input type="radio" name="address_id" value="<?php echo htmlspecialchars($addr->address_id); ?>" 
                                               id="addr_<?php echo htmlspecialchars($addr->address_id); ?>" 
                                               onchange="document.getElementById('address-form').style.display='none'" required>
                                        <label for="addr_<?php echo htmlspecialchars($addr->address_id); ?>" style="flex: 1; cursor: pointer; margin: 0;">
                                            <strong><?php echo htmlspecialchars($addr->street_address); ?></strong><br>
                                            <?php echo htmlspecialchars($addr->barangay . ', ' . $addr->city . ', ' . $addr->province); ?>
                                            <?php if ($addr->is_default): ?><br><em style="color: #00d4ff;">● Default</em><?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr style="margin: 20px 0; opacity: 0.3;">
                            <div style="text-align: center; margin: 20px 0;">
                                <label style="cursor: pointer; color: #00d4ff;">
                                    <input type="checkbox" name="use_new_address" onchange="
                                        if(this.checked) {
                                            document.getElementById('address-form').style.display='block';
                                            document.querySelectorAll('input[name=address_id]').forEach(r => r.checked=false);
                                        } else {
                                            document.getElementById('address-form').style.display='none';
                                        }
                                    ">
                                    Use a new address
                                </label>
                            </div>
                        <?php else: ?>
                            <p style="color: #999; margin: 15px 0;">No saved addresses yet. Add one below.</p>
                        <?php endif; ?>

                        <!-- New Address Form -->
                        <div id="address-form" class="address-form" style="display: <?php echo empty($user_addresses) ? 'block' : 'none'; ?>;">
                            <h4 style="margin-top: 0;">Enter New Address</h4>
                            <input type="text" name="street_address" placeholder="Street Address (e.g., #91 Sitio Bulalacao)">
                            <input type="text" name="barangay" placeholder="Barangay">
                            <input type="text" name="city" placeholder="City">
                            <input type="text" name="province" placeholder="Province">
                        </div>
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

                    <!-- Payment Method Selection -->
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-methods">
                            <div class="payment-option">
                                <input type="radio" id="ewallet" name="payment_method" value="ewallet" required>
                                <label for="ewallet">E-Wallets</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="cod" name="payment_method" value="cod" required>
                                <label for="cod">Cash on Delivery</label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" id="card" name="payment_method" value="card" required>
                                <label for="card">Credit/Debit Card</label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" onclick="return validateCheckout()">Complete Order</button>
                </form>

                <!-- Back to Cart Link -->
                <p class="auth-footer">
                    <a href="cart.php" class="auth-link">← Back to Cart</a>
                </p>
            </div>
        </div>
    </section>

    <script>
        function validateCheckout() {
            const addressForm = document.getElementById('address-form');
            const useNewAddress = document.querySelector('input[name="use_new_address"]');
            const isShowingNewForm = addressForm.style.display !== 'none';
            
            // If address form is shown, validate its fields
            if (isShowingNewForm) {
                const street = document.querySelector('input[name="street_address"]').value.trim();
                const barangay = document.querySelector('input[name="barangay"]').value.trim();
                const city = document.querySelector('input[name="city"]').value.trim();
                const province = document.querySelector('input[name="province"]').value.trim();
                
                if (!street || !barangay || !city || !province) {
                    alert('Please fill in all address fields.');
                    return false;
                }
            } else {
                // If using saved address, make sure one is selected
                const selectedAddress = document.querySelector('input[name="address_id"]:checked');
                if (!selectedAddress) {
                    alert('Please select a shipping address.');
                    return false;
                }
            }
            
            // Validate payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                alert('Please select a payment method.');
                return false;
            }
            
            return true;
        }
    </script>

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
