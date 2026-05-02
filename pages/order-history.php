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

// Get user orders with formatted timestamps
$orders = $order_model->getUserOrders($user_id);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - tonbits</title>
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

    <!-- Order History Section -->
    <section class="admin-section">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="admin-title">My Orders</h1>
                <p class="admin-subtitle">View your order history and status</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <div class="no-orders-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping now!</p>
                    <a href="../index.php" class="btn-submit">Start Shopping</a>
                </div>
            <?php else: ?>
                <!-- Orders Table -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Est. Delivery</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order->order_id; ?></td>
                                        <td>
                                            <div class="timestamp-cell">
                                                <div class="timestamp"><?php echo $order->created_at_formatted; ?></div>
                                                <div class="time-since"><?php echo $order->time_since_creation; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($order->status); ?>">
                                                <?php echo ucfirst($order->status); ?>
                                            </span>
                                        </td>
                                        <td><strong>₱<?php echo number_format($order->total_amount, 2); ?></strong></td>
                                        <td><?php echo $order->estimated_delivery_formatted; ?></td>
                                        <td>
                                            <a href="order-confirmation.php?order_id=<?php echo $order->order_id; ?>" class="btn-action-small">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Back to Store Link -->
            <div class="order-history-footer">
                <a href="../index.php" class="auth-link">← Back to Store</a>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
