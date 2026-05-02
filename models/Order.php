<?php

class Order {
    private $conn;
    private $table = 'orders';
    private $items_table = 'order_items';

    // Properties
    public $order_id;
    public $user_id;
    public $total_amount;
    public $status;
    public $created_at;
    public $completed_at;
    public $estimated_delivery;
    public $shipping_address;
    public $notes;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Create a new order from cart
     * @param int $user_id
     * @param array $cart_items Array of products with quantity
     * @param string $shipping_address
     * @param string $notes Optional notes
     * @return array ['success' => bool, 'order_id' => int or null, 'message' => string]
     */
    public function createOrderFromCart($user_id, $cart_items, $shipping_address, $notes = '') {
        try {
            // Calculate total and validate cart
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item->price * $item->quantity_in_cart;
            }

            if ($total_amount <= 0) {
                return ['success' => false, 'order_id' => null, 'message' => 'Cart is empty'];
            }

            // Start transaction
            $this->conn->beginTransaction();

            // Get current timestamp using date()
            $created_at = date('Y-m-d H:i:s');
            
            // Calculate estimated delivery date (3-5 business days)
            // Using strtotime() to add business days
            $estimated_delivery = $this->calculateEstimatedDelivery($created_at);

            // Insert order
            $query = "INSERT INTO {$this->table} 
                      (user_id, total_amount, status, created_at, estimated_delivery, shipping_address, notes) 
                      VALUES (:user_id, :total_amount, :status, :created_at, :estimated_delivery, :shipping_address, :notes)";
            
            $stmt = $this->conn->prepare($query);
            
            $status = 'pending';
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->bindParam(':estimated_delivery', $estimated_delivery);
            $stmt->bindParam(':shipping_address', $shipping_address);
            $stmt->bindParam(':notes', $notes);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'order_id' => null, 'message' => 'Failed to create order'];
            }

            $order_id = $this->conn->lastInsertId();

            // Insert order items
            $items_query = "INSERT INTO {$this->items_table} 
                           (order_id, product_id, quantity, price, subtotal, added_at) 
                           VALUES (:order_id, :product_id, :quantity, :price, :subtotal, :added_at)";
            
            $items_stmt = $this->conn->prepare($items_query);
            $added_at = date('Y-m-d H:i:s');

            foreach ($cart_items as $item) {
                $subtotal = $item->price * $item->quantity_in_cart;
                
                $items_stmt->bindParam(':order_id', $order_id);
                $items_stmt->bindParam(':product_id', $item->id);
                $items_stmt->bindParam(':quantity', $item->quantity_in_cart);
                $items_stmt->bindParam(':price', $item->price);
                $items_stmt->bindParam(':subtotal', $subtotal);
                $items_stmt->bindParam(':added_at', $added_at);

                if (!$items_stmt->execute()) {
                    $this->conn->rollBack();
                    return ['success' => false, 'order_id' => null, 'message' => 'Failed to add items to order'];
                }
            }

            // Update product stock quantities - reduce stock by ordered quantity
            $stock_update_query = "UPDATE products SET quantity = quantity - :quantity WHERE id = :product_id";
            $stock_stmt = $this->conn->prepare($stock_update_query);

            foreach ($cart_items as $item) {
                $stock_stmt->bindParam(':product_id', $item->id);
                $stock_stmt->bindParam(':quantity', $item->quantity_in_cart);

                if (!$stock_stmt->execute()) {
                    $this->conn->rollBack();
                    return ['success' => false, 'order_id' => null, 'message' => 'Failed to update product stock'];
                }
            }

            $this->conn->commit();

            return ['success' => true, 'order_id' => $order_id, 'message' => 'Order created successfully'];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'order_id' => null, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate estimated delivery date (3-5 business days from order creation)
     * Uses strtotime() for date calculations
     * @param string $created_at Order creation timestamp
     * @return string Estimated delivery datetime
     */
    private function calculateEstimatedDelivery($created_at) {
        // Random business days between 3-5
        $business_days = rand(3, 5);
        
        // Convert created_at to strtotime format
        $timestamp = strtotime($created_at);
        
        // Add business days (skip weekends)
        $days_added = 0;
        while ($days_added < $business_days) {
            $timestamp = strtotime('+1 day', $timestamp);
            $day_of_week = date('N', $timestamp); // 1=Monday, 7=Sunday
            
            // Skip if Saturday (6) or Sunday (7)
            if ($day_of_week < 6) {
                $days_added++;
            }
        }
        
        // Format as datetime string and add 2-4 hours for delivery window
        $delivery_hours = rand(14, 18); // 2PM to 6PM delivery window
        return date('Y-m-d ' . $delivery_hours . ':00:00', $timestamp);
    }

    /**
     * Update order status with completion timestamp
     * @param int $order_id
     * @param string $new_status
     * @return bool Success
     */
    public function updateOrderStatus($order_id, $new_status) {
        try {
            $completed_at = null;
            
            // If status is 'delivered' or 'cancelled', set completed_at timestamp
            if ($new_status === 'delivered' || $new_status === 'cancelled') {
                $completed_at = date('Y-m-d H:i:s');
            }

            $query = "UPDATE {$this->table} 
                     SET status = :status, completed_at = :completed_at 
                     WHERE order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':completed_at', $completed_at);
            $stmt->bindParam(':order_id', $order_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get order by ID with formatted timestamps
     * @param int $order_id
     * @return object Order with formatted dates
     */
public function getOrderById($order_id) {
    try {
        $query = "SELECT * FROM {$this->table} WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$order) {
            return null;
        }

        $order->created_at_formatted = $this->formatTimestamp($order->created_at);
        $order->completed_at_formatted = $order->completed_at ? $this->formatTimestamp($order->completed_at) : 'N/A';
        $order->estimated_delivery_formatted = $this->formatTimestamp($order->estimated_delivery);
        $order->time_since_creation = $this->getTimeSinceCreation($order->created_at);

        return $order;

    } catch (PDOException $e) {
        return null;
    }
}
    /**
     * Get all orders for a user with formatted timestamps
     * @param int $user_id
     * @return array Orders array
     */
    public function getUserOrders($user_id) {
        try {
            $query = "SELECT * FROM {$this->table} 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $orders = [];
            while ($order = $stmt->fetch(PDO::FETCH_OBJ)) {
                $order->created_at_formatted = $this->formatTimestamp($order->created_at);
                $order->completed_at_formatted = $order->completed_at ? $this->formatTimestamp($order->completed_at) : 'N/A';
                $order->estimated_delivery_formatted = $this->formatTimestamp($order->estimated_delivery);
                $order->time_since_creation = $this->getTimeSinceCreation($order->created_at);
                $orders[] = $order;
            }

            return $orders;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get all orders (for admin) with formatted timestamps
     * @param string $status Optional filter by status
     * @return array Orders array
     */
    public function getAllOrders($status = null) {
        try {
            if ($status) {
                $query = "SELECT * FROM {$this->table} 
                         WHERE status = :status 
                         ORDER BY created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $status);
            } else {
                $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->execute();

            $orders = [];
            while ($order = $stmt->fetch(PDO::FETCH_OBJ)) {
                $order->created_at_formatted = $this->formatTimestamp($order->created_at);
                $order->completed_at_formatted = $order->completed_at ? $this->formatTimestamp($order->completed_at) : 'N/A';
                $order->estimated_delivery_formatted = $this->formatTimestamp($order->estimated_delivery);
                $order->time_since_creation = $this->getTimeSinceCreation($order->created_at);
                $orders[] = $order;
            }

            return $orders;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get order items with timestamps
     * @param int $order_id
     * @return array Order items
     */
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.*, p.name as product_name 
                     FROM {$this->items_table} oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = :order_id
                     ORDER BY oi.added_at ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();

            $items = [];
            while ($item = $stmt->fetch(PDO::FETCH_OBJ)) {
                $item->added_at_formatted = $this->formatTimestamp($item->added_at);
                $items[] = $item;
            }

            return $items;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Format timestamp to readable format using date()
     * @param string $timestamp MySQL timestamp
     * @return string Formatted date
     */
    private function formatTimestamp($timestamp) {
        if (empty($timestamp)) {
            return 'N/A';
        }
        // Convert MySQL timestamp to readable format: Dec 25, 2024 at 3:45 PM
        return date('M d, Y \a\t g:i A', strtotime($timestamp));
    }

    /**
     * Calculate time elapsed since order creation using strtotime()
     * @param string $created_at Order creation timestamp
     * @return string Human-readable time difference
     */
    private function getTimeSinceCreation($created_at) {
        $created_timestamp = strtotime($created_at);
        $now = strtotime(date('Y-m-d H:i:s'));
        $difference = $now - $created_timestamp;

        if ($difference < 60) {
            return "Just now";
        } elseif ($difference < 3600) {
            $minutes = floor($difference / 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } elseif ($difference < 604800) {
            $days = floor($difference / 86400);
            return $days . " day" . ($days > 1 ? "s" : "") . " ago";
        } else {
            $weeks = floor($difference / 604800);
            return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
        }
    }

    /**
     * Get delivery status with timeline
     * @param object $order
     * @return array Status information with timeline
     */
    public function getDeliveryStatus($order) {
        $status = $order->status;
        $created = strtotime($order->created_at);
        $estimated = strtotime($order->estimated_delivery);
        $now = strtotime(date('Y-m-d H:i:s'));

        $timeline = [
            'created' => date('M d, Y g:i A', $created),
            'estimated_delivery' => date('M d, Y g:i A', $estimated),
            'days_until_delivery' => 0,
            'delivery_status' => ''
        ];

        if ($status === 'delivered') {
            $timeline['delivery_status'] = 'Delivered';
            $timeline['completed_date'] = date('M d, Y g:i A', strtotime($order->completed_at));
        } elseif ($status === 'cancelled') {
            $timeline['delivery_status'] = 'Cancelled';
            $timeline['cancelled_date'] = date('M d, Y g:i A', strtotime($order->completed_at));
        } else {
            $days_until = floor(($estimated - $now) / 86400);
            $timeline['days_until_delivery'] = max(0, $days_until);
            $timeline['delivery_status'] = ucfirst($status);
        }

        return $timeline;
    }
}
?>
