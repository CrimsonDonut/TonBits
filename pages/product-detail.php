<?php
session_start();

$cartCount = 0;

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += $qty;
    }
}

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$product_id) {
    header("Location: ../index.php");
    exit;
}

$product = new Product($db);
$productData = $product->getProductById($product_id);

if (!$productData) {
    header("Location: ../index.php");
    exit;
}

// Check if user is logged in
$is_logged_in = AuthHelper::isLoggedIn();
$username = $is_logged_in ? $_SESSION['username'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($productData->name); ?> - tonbits</title>
    <link rel="stylesheet" href="../assets/style/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.cdnfonts.com/css/cyberpunks">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="../index.php" style="text-decoration: none;">
                    <h1 class="logo">
                        <span class="logo-white">ton</span><span class="logo-purple">bits</span>
                    </h1>
                </a>
            </div>
            <div class="nav-links">
                <div class="products-dropdown-container">
                    <button class="btn-products" id="productsBtn">Products</button>
                    <div class="products-dropdown" id="productsDropdown">
                        <a href="all-products.php" class="dropdown-item">All Products</a>
                        <a href="all-products.php?brand=NVIDIA" class="dropdown-item">Nvidia</a>
                        <a href="all-products.php?brand=AMD" class="dropdown-item">AMD</a>
                    </div>
                </div>
                <a href="../index.php#support">Support</a>
            </div>
            <div class="nav-buttons">
                <button class="btn-shop" onclick="window.location.href='cart.php'">
                    <div class="cart-icon-wrapper">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>

                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <span>View Cart</span>
                </button>
                <div class="profile-dropdown-container">
                    <button class="btn-profile" id="profileBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <?php if ($is_logged_in): ?>
                            <div class="dropdown-header">
                                <span class="dropdown-username"><?php echo htmlspecialchars($username); ?></span>
                            </div>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="dropdown-item">Login</a>
                            <a href="register.php" class="dropdown-item">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Product Detail Section -->
    <div class="product-details-page">
        <div class="container">
            <!-- Back Button -->
            <a href="../index.php" class="back-button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Back to Products
            </a>

            <!-- Product Header -->
            <div class="product-header">
                <!-- Left: Image -->
                <div class="product-image-section">
                    <div class="product-image-container">
                        <div class="product-glow"></div>
                        <img src="../uploads/<?php echo htmlspecialchars($productData->image); ?>" alt="<?php echo htmlspecialchars($productData->name); ?>" class="product-image">
                    </div>
                </div>

                <!-- Right: Info -->
                <div class="product-info-section">
                    <h1 class="product-title"><?php echo htmlspecialchars($productData->name); ?></h1>

                    <p class="product-description">
                        <?php echo htmlspecialchars($productData->description); ?>
                    </p>

                    <div class="product-price">
                        <div class="price-amount"><?php echo $productData->formatted_price; ?></div>
                    </div>

                    <!-- Quick Specs -->
                    <div class="quick-specs">
                        <?php 
                        if (!empty($productData->specifications) && is_array($productData->specifications)):
                            $quickSpecs = array_slice($productData->specifications, 0, 4);
                            foreach ($quickSpecs as $label => $value): 
                        ?>
                        <div class="spec-item">
                            <div class="spec-label"><?php echo htmlspecialchars($label); ?></div>
                            <div class="spec-value"><?php echo htmlspecialchars($value); ?></div>
                        </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                        <p style="color: #9ca3af; grid-column: 1 / -1;">Empty</p>
                        <?php endif; ?>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="cta-buttons">
                        <?php if ($productData->in_stock): ?>
                            <form method="POST" action="cart.php" style="display: flex; gap: 1rem; width: 100%;">
                                <input type="hidden" name="product_id" value="<?php echo $productData->id; ?>">
                                <button type="submit" class="btn-primary">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    Add to Cart
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                Out of Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="features-section">
                <h2 class="section-title">Key Features</h2>
                <div class="features-grid">
                    <?php 
                    if (!empty($productData->features) && is_array($productData->features)): 
                        foreach ($productData->features as $feature): 
                    ?>
                    <div class="feature-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                    <p style="color: #9ca3af; grid-column: 1 / -1;">Empty</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Technical Specifications -->
            <div class="specs-section">
                <h2 class="section-title">Technical Specifications</h2>
                <div class="specs-container">
                    <div class="specs-grid">
                        <?php 
                        if (!empty($productData->specifications) && is_array($productData->specifications)):
                            foreach ($productData->specifications as $specName => $specValue): 
                        ?>
                        <div class="spec-row">
                            <span class="spec-name"><?php echo htmlspecialchars($specName); ?></span>
                            <span class="spec-detail"><?php echo htmlspecialchars($specValue); ?></span>
                        </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                        <p style="color: #9ca3af; grid-column: 1 / -1;">Empty</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
