<?php
session_start();

$cartCount = 0;

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += $qty;
    }
}

// Get error message if it exists
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']); // Clear it after reading

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

// Get filter parameters from GET
$selected_brands = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$selected_memory = isset($_GET['memory']) ? array_map('strval', (array)$_GET['memory']) : [];
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$sort_labels = [
    'newest' => 'Newest',
    'price_low' => 'Price: Low to High',
    'price_high' => 'Price: High to Low'
];
$current_sort_label = isset($sort_labels[$sort_by]) ? $sort_labels[$sort_by] : 'Newest';

// Apply filters if any are selected
if (!empty($selected_brands) || !empty($selected_memory)) {
    $products = $product->getFilteredProducts($selected_brands, $selected_memory, $sort_by);
} else {
    $products = $product->getAllProducts($sort_by);
}

$is_logged_in = AuthHelper::isLoggedIn();
$username = $is_logged_in ? $_SESSION['username'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Graphics Cards – tonbits</title>
  <link rel="stylesheet" href="../assets/style/style.css" />
  <link rel="stylesheet" href="../assets/style/products-styles.css" />
  <link rel="stylesheet" href="../assets/style/modal-styles.css" />

</head>
<body>
    <style>
  /* Container to align things and position our arrow icon */
  .sort-dropdown-container {
    position: relative;
    display: inline-block;
  }

  /* Make the select mimic your original button perfectly */
  .sort-dropdown-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    
    /* Inherit layout properties from your existing .sort-btn class */
    background: var(--card-bg, rgba(255, 255, 255, 0.03)); 
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.6rem 2.5rem 0.6rem 1rem; /* Extra right padding for the icon */
    font-size: 0.9rem;
    font-family: inherit;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
  }

  /* Subtle hover state */
  .sort-dropdown-select:hover {
    border-color: #aa00ff; /* Your signature purple accent color */
    background: rgba(170, 0, 255, 0.05);
  }

  /* Target the dropdown overlay menu options */
  .sort-dropdown-select option {
    background-color: #120e24; /* Dark purple/black matching your theme */
    color: #ffffff;
    padding: 10px;
  }

  /* Vector arrow positioning */
  .sort-dropdown-icon {
    width: 14px;
    height: 14px;
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Allows clicks to pass through to the select element */
    color: rgba(255, 255, 255, 0.6);
  }
</style>

<div class="bg-layer"></div>
<div class="bg-glow-bottom"></div>

<!--Error pag lagpas order stock limit-->
<?php if ($error_message): ?>
<div class="modal-overlay" id="errorModal" style="display: flex;">
  <div class="modal-content">
    <button class="modal-close" onclick="document.getElementById('errorModal').style.display='none';">&times;</button>
    <h2 class="modal-title">Stock Limit</h2>
    <p class="modal-message"><?php echo htmlspecialchars($error_message); ?></p>
    <button class="modal-btn" onclick="document.getElementById('errorModal').style.display='none';">Got it</button>
  </div>
</div>
<?php endif; ?>

<nav class="navbar">
  <div class="nav-container">
    <div class="nav-brand">
      <h1 class="logo">
        <span class="logo-white">ton</span><span class="logo-purple">bits</span>
      </h1>
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
      <a href="../index.php">Store</a>
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

<div class="page-content">
  <div class="container">

    <div class="page-header">
      <div class="eyebrow">
        <div class="eyebrow-line"></div>
        <span class="eyebrow-label">tonbits store</span>
      </div>
      <h1 class="page-title">All Graphics Cards</h1>
      <div class="brand-tabs">
        <a href="all-products.php" class="tab tab-all <?php echo empty($selected_brands) ? 'active' : ''; ?>">All</a>
        <a href="all-products.php?brand=NVIDIA" class="tab tab-nvidia <?php echo in_array('NVIDIA', $selected_brands) && count($selected_brands) == 1 ? 'active' : ''; ?>">NVIDIA</a>
        <a href="all-products.php?brand=AMD" class="tab tab-amd <?php echo in_array('AMD', $selected_brands) && count($selected_brands) == 1 ? 'active' : ''; ?>">AMD</a>
      </div>
    </div>

  <div class="toolbar">
    <span class="product-count"><?php echo count($products); ?> products</span>
    
    <div class="sort-dropdown-container">
      <select id="sortSelect" class="sort-dropdown-select">
        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Sort: Newest</option>
        <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Sort: Price: Low to High</option>
        <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Sort: Price: High to Low</option>
      </select>
      <svg class="sort-dropdown-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </div>
  </div>  

    <div class="layout">
      <aside class="sidebar">
        <div class="filter-header">
          <span class="filter-header-label">Filters</span>
        </div>

        <form id="filterForm">
          <div class="filter-section">            <button type="button" class="filter-section-title">Memory Size
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
            </button>
            <div class="filter-options">
              <label class="filter-option">
                <input type="checkbox" name="memory" value="32" class="filter-checkbox" <?php echo in_array('32', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">32GB</span>
              </label>
              <label class="filter-option">
                <input type="checkbox" name="memory" value="24" class="filter-checkbox" <?php echo in_array('24', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">24GB</span>
              </label>
              <label class="filter-option">
                <input type="checkbox" name="memory" value="20" class="filter-checkbox" <?php echo in_array('20', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">20GB</span>
              </label>
              <label class="filter-option">
                <input type="checkbox" name="memory" value="16" class="filter-checkbox" <?php echo in_array('16', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">16GB</span>
              </label>
              <label class="filter-option">
                <input type="checkbox" name="memory" value="12" class="filter-checkbox" <?php echo in_array('12', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">12GB</span>
              </label>
              <label class="filter-option">
                <input type="checkbox" name="memory" value="8" class="filter-checkbox" <?php echo in_array('8', $selected_memory) ? 'checked' : ''; ?>>
                <span class="custom-checkbox"></span><span class="option-label">8GB</span>
              </label>
            </div>
          </div>
        </form>
      </aside>

      <div class="product-grid">
        <?php if (!empty($products)): ?>
          <?php foreach ($products as $gpu): ?>
            <div class="product-card">
              <span class="card-brand-pill <?php echo strpos($gpu->name, 'RTX') !== false ? 'pill-nvidia' : 'pill-amd'; ?>">
                <?php echo strpos($gpu->name, 'RTX') !== false ? 'NVIDIA' : 'AMD'; ?>
              </span>
              <div class="card-image">
                <img src="../uploads/<?php echo htmlspecialchars($gpu->image); ?>" alt="<?php echo htmlspecialchars($gpu->name); ?>" />
                <div class="image-fade"></div>
              </div>
              <div class="card-body">
                <h3 class="card-name"><?php echo htmlspecialchars($gpu->name); ?></h3>
                <p class="card-desc"><?php echo htmlspecialchars(substr($gpu->description, 0, 100)); ?>...</p>
                <div class="card-footer">
                  <div class="price-block">
                    <div class="price-current"><?php echo $gpu->formatted_price; ?></div>
                    <div class="stock-info">Stock: <?php echo $gpu->quantity; ?></div>
                  </div>
                  <?php if ($gpu->in_stock): ?>
                    <form method="POST" action="cart.php" style="margin: 0;">
                      <input type="hidden" name="product_id" value="<?php echo $gpu->id; ?>">
                      <button type="submit" class="add-to-cart">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Add to Cart
                      </button>
                    </form>
                  <?php else: ?>
                    <button class="add-to-cart" disabled style="opacity: 0.5; cursor: not-allowed;">Out of Stock</button>
                  <?php endif; ?>
                </div>
              </div>
              <a href="product-detail.php?id=<?php echo $gpu->id; ?>" class="view-details">View Details →</a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="grid-column: 1 / -1; text-align: center; color: rgba(255,255,255,0.3); padding: 2rem;">No products found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Master function to gather and transition states
function updateFilters() {
  const currentParams = new URLSearchParams(window.location.search);
  const newParams = new URLSearchParams();
  
  // 1. Maintain active Brand tags
  const currentBrands = currentParams.getAll('brand');
  currentBrands.forEach(b => newParams.append('brand', b));
  
  // 2. Fetch current Checkbox selections 
  const memory = Array.from(document.querySelectorAll('input[name="memory"]:checked')).map(el => el.value);
  memory.forEach(m => newParams.append('memory', m));
  
  // 3. Fetch Sorting criteria
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect && sortSelect.value !== 'newest') {
    newParams.append('sort', sortSelect.value);
  }
  
  // Navigate smoothly
  const url = newParams.toString() ? '?' + newParams.toString() : window.location.pathname;
  window.location.href = url;
}

// Event Listeners for Filters Form
const filterForm = document.getElementById('filterForm');
if (filterForm) {
  filterForm.addEventListener('change', function(e) {
    if (e.target.classList.contains('filter-checkbox')) {
      updateFilters();
    }
  }, true);
}

// Event Listener for the Sorting Dropdown
const sortSelect = document.getElementById('sortSelect');
if (sortSelect) {
  sortSelect.addEventListener('change', updateFilters);
}

// Keep your custom checkbox styling logic active
function updateAllCheckboxStyling() {
  document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
    const customCheckbox = checkbox.parentElement.querySelector('.custom-checkbox');
    if (customCheckbox) {
      if (checkbox.checked) {
        customCheckbox.style.background = '#aa00ff';
        customCheckbox.style.borderColor = '#aa00ff';
      } else {
        customCheckbox.style.background = 'transparent';
        customCheckbox.style.borderColor = 'rgba(255,255,255,0.2)';
      }
    }
  });
}

updateAllCheckboxStyling();
document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', updateAllCheckboxStyling);
});
</script>

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
