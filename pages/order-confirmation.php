<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Order.php";

$database = new Database();
$db = $database->connect();

// Check if user is logged in
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_model = new Order($db);

// Get order ID from URL
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : null;

if (!$order_id) {
    header("Location: order-history.php");
    exit();
}

// Get order details
$order = $order_model->getOrderById($order_id);

if (!$order || $order->user_id != $user_id) {
    header("Location: order-history.php");
    exit();
}

// Get order items
$order_items = $order_model->getOrderItems($order_id);
$delivery_status = $order_model->getDeliveryStatus($order);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - tonbits</title>
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
                <a href="order-history.php">My Orders</a>
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

    <!-- Order Confirmation Section -->
    <section class="order-confirmation-section">
        <div class="order-confirmation-container">
            <!-- Success Header -->
            <div class="confirmation-header">
                <div class="success-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h1>Order Confirmed!</h1>
                <p class="order-number">Order #<?php echo $order->order_id; ?></p>
            </div>

            <!-- Order Details Card -->
            <div class="confirmation-card">
                <div class="card-section">
                    <h2>Order Information</h2>
                    <div class="info-row">
                        <span class="label">Order Date:</span>
                        <span class="value"><?php echo $order->created_at_formatted; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="value status <?php echo strtolower($order->status); ?>"><?php echo ucfirst($order->status); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Estimated Delivery:</span>
                        <span class="value"><?php echo $order->estimated_delivery_formatted; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Days Until Delivery:</span>
                        <span class="value"><?php echo $delivery_status['days_until_delivery']; ?> days</span>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card-section">
                    <h2>Order Items</h2>
                    <div class="order-items-list">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <span class="item-name"><?php echo htmlspecialchars($item->product_name); ?></span>
                                    <span class="item-qty">Qty: <?php echo $item->quantity; ?></span>
                                </div>
                                <div class="item-price">
                                    <span class="price-each">₱<?php echo number_format($item->price, 2); ?> each</span>
                                    <span class="subtotal">₱<?php echo number_format($item->subtotal, 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card-section">
                    <h2>Shipping Address</h2>
                    <p class="address-text"><?php echo nl2br(htmlspecialchars($order->shipping_address)); ?></p>
                </div>

                <!-- Order Total -->
                <div class="card-section total-section">
                    <div class="total-row">
                        <span>Total Amount:</span>
                        <span class="total-amount">₱<?php echo number_format($order->total_amount, 2); ?></span>
                    </div>
                </div>

                <!-- Notes if provided -->
                <?php if ($order->notes): ?>
                    <div class="card-section">
                        <h2>Order Notes</h2>
                        <p class="notes-text"><?php echo nl2br(htmlspecialchars($order->notes)); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="confirmation-actions">
                <a href="order-history.php" class="btn-action">View My Orders</a>
                <a href="../index.php" class="btn-action secondary">Continue Shopping</a>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
