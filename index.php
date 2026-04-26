<?php
require_once "config/Database.php";
require_once "models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);
$products = $product->getAllProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tonbits - Premium GPU E-commerce</title>
    <link rel="stylesheet" href="assets/style/style.css">
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
                <a href="#products">Products</a>
                <a href="#features">Features</a>
                <a href="#specs">Specs</a>
                <a href="#support">Support</a>
            </div>
            <button class="btn-shop">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Shop Now</span>
            </button>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-lines">
            <div class="line-left"></div>
            <div class="line-right"></div>
        </div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <span>Powered by NVIDIA Blackwell Architecture</span>
                </div>
                <h1 class="hero-title">
                    Next-Gen Gaming<br>
                    <span class="gradient-text">Unleashed</span>
                </h1>
                <p class="hero-description">
                    Experience ultimate performance with ROG Strix GeForce RTX™ 5070 Ti.
                    Advanced cooling, AI-enhanced graphics, and unprecedented power delivery.
                </p>
                <div class="hero-buttons">
                    <button class="btn-primary">
                        Explore GPUs
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                    <button class="btn-secondary">Learn More</button>
                </div>
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-value">1406</div>
                        <div class="stat-label">AI TOPS</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">16GB</div>
                        <div class="stat-label">GDDR7</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">31%</div>
                        <div class="stat-label">More Airflow</div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="image-glow"></div>
                <img src="assets/style/images/GPU_Home.png" alt="ROG Strix GPU" class="gpu-image">
                <div class="circle-1"></div>
                <div class="circle-2"></div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Our <span class="gradient-text">GPU Lineup</span></h2>
                <p class="section-description">Choose from our premium collection of graphics cards engineered for peak performance</p>
            </div>
            <div class="products-grid">
                <?php if (!empty($products)): ?>
                    <?php $index = 0; foreach ($products as $row): ?>
                        <?php 
                            // Determine card variant based on product index
                            $variants = ['card-bestseller', 'card-new', 'card-flagship'];
                            $variant = $variants[$index % 3];
                            $badges = ['Best Seller', 'New', 'Flagship'];
                            $badge = $badges[$index % 3];
                            $badgeClasses = ['badge-bestseller', 'badge-new', 'badge-flagship'];
                            $badgeClass = $badgeClasses[$index % 3];
                            $index++;
                        ?>
                        <div class="product-card <?php echo $variant; ?>">
                            <div class="card-glow-ring"></div>
                            <div class="card-inner">
                                <div class="corner-accent-tl"></div>
                                <div class="corner-accent-br"></div>
                                <div class="card-badge <?php echo $badgeClass; ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                    <span><?php echo $badge; ?></span>
                                </div>
                                <div class="corner-glow"></div>
                                <div class="scan-line"></div>
                                <div class="product-image">
                                    <div class="image-glow-product"></div>
                                    <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                </div>
                                <div class="product-info">
                                    <div class="product-series">GPU</div>
                                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <div class="product-meta">
                                        <span class="product-memory"><?php echo htmlspecialchars($row['description'] ?? 'Specs not available'); ?></span>
                                        <span class="product-price">₱<?php echo number_format($row['price'], 2); ?></span>
                                    </div>
                                    <p class="product-stock">
                                        <?php if ($row['quantity'] > 0): ?>
                                            Stock: <?php echo $row['quantity']; ?>
                                        <?php else: ?>
                                            <span style="color:red;">Out of Stock</span>
                                        <?php endif; ?>
                                    </p>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-product">
                                            Add to Cart
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="9 18 15 12 9 6"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                                <div class="bottom-gradient"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: #9ca3af; padding: 2rem;">No products found.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="section-container">
            <div class="section-header">
                <h2 class="section-title">Cutting-Edge <span class="gradient-text">Features</span></h2>
                <p class="section-description">Engineered for gamers and creators who demand the absolute best</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon feature-icon-1">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                            <rect x="9" y="9" width="6" height="6"></rect>
                        </svg>
                    </div>
                    <h3 class="feature-title">NVIDIA Blackwell Architecture</h3>
                    <p class="feature-description">Fifth-Gen Tensor Cores and Fourth-Gen Ray Tracing Cores for unmatched AI and graphics performance</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon feature-icon-2">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <h3 class="feature-title">DLSS 4 with Multi Frame Generation</h3>
                    <p class="feature-description">AI-enhanced graphics delivering up to 2x performance boost with stunning visual quality</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon feature-icon-3">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Advanced Axial-Tech Cooling</h3>
                    <p class="feature-description">31% more airflow with triple fan design and 0dB technology for silent operation</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon feature-icon-4">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3H6a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3V6a3 3 0 0 0-3-3 3 3 0 0 0-3 3 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 3 3 0 0 0-3-3z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Premium Power Delivery</h3>
                    <p class="feature-description">Digital power control with 15K capacitors ensuring stable, high-performance operation</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon feature-icon-5">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Aura Sync RGB Lighting</h3>
                    <p class="feature-description">Customizable ARGB lighting for endless personalization and synchronization</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon feature-icon-6">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">MaxContact Vapor Chamber</h3>
                    <p class="feature-description">Phase-change thermal solution for optimal heat dissipation and reliability</p>
                </div>
            </div>
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
                    <div class="social-links">
                        <a href="#" class="social-link">
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