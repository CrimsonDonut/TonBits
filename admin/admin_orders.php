<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";
require_once "../models/Order.php";

$database = new Database();
$db = $database->connect();

// Check if user is logged in and is admin
if (!AuthHelper::isAdmin()) {
    header("Location: ../pages/login.php");
    exit();
}



$order_model = new Order($db);

// Get filter status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if ($status_filter && !in_array($status_filter, $valid_statuses)) {
    $status_filter = null;
}

// Get orders with optional status filter
$orders = $status_filter ? $order_model->getAllOrders($status_filter) : $order_model->getAllOrders();

// Get order statistics
$all_orders = $order_model->getAllOrders();
$status_counts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($all_orders as $order) {
    if (isset($status_counts[$order->status])) {
        $status_counts[$order->status]++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - tonbits Admin</title>
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
                <a href="dashboard.php">Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Admin Orders Section -->
    <section class="admin-section">
        <div class="admin-container">
            <div class="admin-header">
                <h1 class="admin-title">Orders Management</h1>
                <p class="admin-subtitle">View and manage all customer orders</p>
            </div>

            <!-- Status Statistics -->
            <div class="status-stats">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo count($all_orders); ?></div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?php echo $status_counts['pending']; ?></div>
                </div>
                <div class="stat-card processing">
                    <div class="stat-label">Processing</div>
                    <div class="stat-value"><?php echo $status_counts['processing']; ?></div>
                </div>
                <div class="stat-card shipped">
                    <div class="stat-label">Shipped</div>
                    <div class="stat-value"><?php echo $status_counts['shipped']; ?></div>
                </div>
                <div class="stat-card delivered">
                    <div class="stat-label">Delivered</div>
                    <div class="stat-value"><?php echo $status_counts['delivered']; ?></div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="admin_orders.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">All Orders</a>
                <?php foreach ($valid_statuses as $status): ?>
                    <a href="admin_orders.php?status=<?php echo $status; ?>" 
                       class="filter-btn <?php echo $status_filter === $status ? 'active' : ''; ?>">
                        <?php echo ucfirst($status); ?> (<?php echo $status_counts[$status]; ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>No orders found.</p>
                </div>
            <?php else: ?>
                <!-- Orders Table -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer ID</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Est. Delivery</th>
                                    <th>Completed</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order->order_id; ?></strong></td>
                                        <td><?php echo $order->user_id; ?></td>
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
                                        <td><?php echo $order->completed_at_formatted; ?></td>
                                        <td>
                                            <a href="../pages/order-confirmation.php?order_id=<?php echo $order->order_id; ?>" class="btn-action-small">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
