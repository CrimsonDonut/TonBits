<?php

class Order {
    private $conn;
    private $table = 'orders';
    private $items_table = 'order_items';
    private $addresses_table = 'user_addresses';

    // Properties
    public $completed_at;
    public $order_id;
    public $user_id;
    public $address_id;
    public $total_amount;
    public $status;
    public $created_at;
    public $estimated_delivery;
    public $notes;
    public $payment_method;
    public $shipping_address; // For backward compatibility, will be loaded from user_addresses

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Create a new order from cart
     * @param int $user_id
     * @param array $cart_items Array of products with quantity
     * @param int $address_id Address ID from user_addresses table
     * @param string $payment_method Payment method (ewallet, cod, card)
     * @param string $notes Optional notes
     * @return array ['success' => bool, 'order_id' => int or null, 'message' => string]
     */
    public function createOrderFromCart($user_id, $cart_items, $address_id, $payment_method, $notes = '') {
        // Validate payment method
        $valid_methods = ['ewallet', 'cod', 'card'];
        if (!in_array($payment_method, $valid_methods)) {
            return ['success' => false, 'order_id' => null, 'message' => 'Invalid payment method'];
        }
        try {
            // Calculate total and validate cart
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item->price * $item->quantity_in_cart;
            }

            if ($total_amount <= 0) {
                return ['success' => false, 'order_id' => null, 'message' => 'Cart is empty'];
            }

            // Validate address exists and belongs to user
            $addr_query = "SELECT address_id FROM {$this->addresses_table} WHERE address_id = :address_id AND user_id = :user_id";
            $addr_stmt = $this->conn->prepare($addr_query);
            $addr_stmt->bindParam(':address_id', $address_id);
            $addr_stmt->bindParam(':user_id', $user_id);
            $addr_stmt->execute();

            if ($addr_stmt->rowCount() === 0) {
                return ['success' => false, 'order_id' => null, 'message' => 'Invalid shipping address'];
            }

            // Start transaction
            $this->conn->beginTransaction();

            // Get current timestamp
            $created_at = date('Y-m-d H:i:s');
            
            // Calculate estimated delivery date (3-5 business days)
            $estimated_delivery = $this->calculateEstimatedDelivery($created_at);

            // Insert order
            $query = "INSERT INTO {$this->table} 
                      (user_id, address_id, total_amount, status, payment_method, created_at, estimated_delivery, notes) 
                      VALUES (:user_id, :address_id, :total_amount, :status, :payment_method, :created_at, :estimated_delivery, :notes)";
            
            $stmt = $this->conn->prepare($query);
            
            $status = 'pending';
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':address_id', $address_id);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->bindParam(':estimated_delivery', $estimated_delivery);
            $stmt->bindParam(':notes', $notes);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'order_id' => null, 'message' => 'Failed to create order'];
            }

            $order_id = $this->conn->lastInsertId();

            // Insert order items
            $items_query = "INSERT INTO {$this->items_table} 
                           (order_id, product_id, quantity, price_at_purchase, subtotal) 
                           VALUES (:order_id, :product_id, :quantity, :price_at_purchase, :subtotal)";
            
            $items_stmt = $this->conn->prepare($items_query);

            foreach ($cart_items as $item) {
                $subtotal = $item->price * $item->quantity_in_cart;
                
                $items_stmt->bindParam(':order_id', $order_id);
                // Handle both old 'id' and new 'product_id' property names
                $product_id = $item->product_id ?? $item->id;
                $items_stmt->bindParam(':product_id', $product_id);
                $items_stmt->bindParam(':quantity', $item->quantity_in_cart);
                $items_stmt->bindParam(':price_at_purchase', $item->price);
                $items_stmt->bindParam(':subtotal', $subtotal);

                if (!$items_stmt->execute()) {
                    $this->conn->rollBack();
                    return ['success' => false, 'order_id' => null, 'message' => 'Failed to add items to order'];
                }
            }

            // Update product stock quantities - reduce stock by ordered quantity
            $stock_update_query = "UPDATE products SET quantity_in_stock = quantity_in_stock - :quantity WHERE product_id = :product_id";
            $stock_stmt = $this->conn->prepare($stock_update_query);

            foreach ($cart_items as $item) {
                $product_id = $item->product_id ?? $item->id;
                $stock_stmt->bindParam(':product_id', $product_id);
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
     * @param string $created_at Order creation timestamp
     * @return string Estimated delivery datetime
     */
    private function calculateEstimatedDelivery($created_at) {
        $business_days = rand(3, 5);
        $timestamp = strtotime($created_at);
        
        $days_added = 0;
        while ($days_added < $business_days) {
            $timestamp = strtotime('+1 day', $timestamp);
            $day_of_week = date('N', $timestamp); // 1=Monday, 7=Sunday
            
            if ($day_of_week < 6) {
                $days_added++;
            }
        }
        
        $delivery_hours = rand(14, 18);
        return date('Y-m-d ' . $delivery_hours . ':00:00', $timestamp);
    }

/**
     * Update order status and set completed_at timestamp if final status
     * @param int $order_id
     * @param string $new_status
     * @return bool Success
     */
    public function updateOrderStatus($order_id, $new_status) {
        try {
            // Check if the new status means the order is finalized
            if (in_array($new_status, ['delivered', 'cancelled'])) {
                $query = "UPDATE {$this->table} 
                         SET status = :status,
                             completed_at = CURRENT_TIMESTAMP
                         WHERE order_id = :order_id";
            } else {
                // If it's rolled back to pending/processing/shipped, reset completion time to null
                $query = "UPDATE {$this->table} 
                         SET status = :status,
                             completed_at = NULL
                         WHERE order_id = :order_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':order_id', $order_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    /**
     * Get order by ID with address details
     * @param int $order_id
     * @return object Order with formatted dates
     */
    public function getOrderById($order_id) {
        try {
            $query = "SELECT o.*, 
                      ua.street_address, ua.barangay, ua.city, ua.province
                      FROM {$this->table} o
                      LEFT JOIN {$this->addresses_table} ua ON o.address_id = ua.address_id
                      WHERE o.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();

            $order = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$order) {
                return null;
            }

            // Format address for backward compatibility
            if ($order->street_address) {
                $order->shipping_address = $order->street_address . ', ' . $order->barangay . 
                                          ', ' . $order->city . ', ' . $order->province;
            }

            $order->created_at_formatted = $this->formatTimestamp($order->created_at);
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
            $query = "SELECT o.*,
                      ua.street_address, ua.barangay, ua.city, ua.province
                      FROM {$this->table} o
                      LEFT JOIN {$this->addresses_table} ua ON o.address_id = ua.address_id
                      WHERE o.user_id = :user_id 
                      ORDER BY o.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $orders = [];
            while ($order = $stmt->fetch(PDO::FETCH_OBJ)) {
                // Format address for backward compatibility
                if ($order->street_address) {
                    $order->shipping_address = $order->street_address . ', ' . $order->barangay . 
                                              ', ' . $order->city . ', ' . $order->province;
                }

                $order->created_at_formatted = $this->formatTimestamp($order->created_at);
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
                $query = "SELECT o.*,
                          ua.street_address, ua.barangay, ua.city, ua.province
                          FROM {$this->table} o
                          LEFT JOIN {$this->addresses_table} ua ON o.address_id = ua.address_id
                          WHERE o.status = :status 
                          ORDER BY o.created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $status);
            } else {
                $query = "SELECT o.*,
                          ua.street_address, ua.barangay, ua.city, ua.province
                          FROM {$this->table} o
                          LEFT JOIN {$this->addresses_table} ua ON o.address_id = ua.address_id
                          ORDER BY o.created_at DESC";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->execute();

            $orders = [];
            while ($order = $stmt->fetch(PDO::FETCH_OBJ)) {
                // Format address for backward compatibility
                if ($order->street_address) {
                    $order->shipping_address = $order->street_address . ', ' . $order->barangay . 
                                              ', ' . $order->city . ', ' . $order->province;
                }

                $order->created_at_formatted = $this->formatTimestamp($order->created_at);
                $order->estimated_delivery_formatted = $this->formatTimestamp($order->estimated_delivery);
                
                // Bulletproof assignment: If completed_at doesn't exist yet, pass null to formatTimestamp
                $order->completed_at_formatted = $this->formatTimestamp($order->completed_at ?? null);
                
                $order->time_since_creation = $this->getTimeSinceCreation($order->created_at);
                $orders[] = $order;
            }

            return $orders;
        } catch (PDOException $e) {
            return [];
        }
    }
    /**
 * Get order statistics using aggregate queries
 * @return array Stats with total_revenue, order_count, and status breakdown
 */
    public function getOrderStats() {
        try {
            // SUM and COUNT aggregation — total revenue excluding cancelled orders
            $revenue_query = "SELECT 
                                COUNT(*) AS total_orders,
                                SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) AS total_revenue,
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_count,
                                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped_count,
                                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
                                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
                            FROM {$this->table}";
            $stmt = $this->conn->prepare($revenue_query);
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_OBJ);

            // Subquery — best selling product
            $top_query = "SELECT p.name, SUM(oi.quantity) AS total_sold
                        FROM {$this->items_table} oi
                        JOIN products p ON oi.product_id = p.product_id
                        GROUP BY oi.product_id, p.name
                        ORDER BY total_sold DESC
                        LIMIT 1";
            $stmt2 = $this->conn->prepare($top_query);
            $stmt2->execute();
            $top_product = $stmt2->fetch(PDO::FETCH_OBJ);

            return [
                'total_orders'     => $totals->total_orders ?? 0,
                'total_revenue'    => $totals->total_revenue ?? 0,
                'pending_count'    => $totals->pending_count ?? 0,
                'processing_count' => $totals->processing_count ?? 0,
                'shipped_count'    => $totals->shipped_count ?? 0,
                'delivered_count'  => $totals->delivered_count ?? 0,
                'cancelled_count'  => $totals->cancelled_count ?? 0,
                'top_product'      => $top_product->name ?? 'N/A',
                'top_product_sold' => $top_product->total_sold ?? 0,
            ];
        } catch (PDOException $e) {
            return [
                'total_orders' => 0, 'total_revenue' => 0,
                'pending_count' => 0, 'processing_count' => 0,
                'shipped_count' => 0, 'delivered_count' => 0,
                'cancelled_count' => 0, 'top_product' => 'N/A', 'top_product_sold' => 0
            ];
        }
    }
    

    /**
     * Get order items
     * @param int $order_id
     * @return array Order items
     */
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.*, p.name as product_name 
                     FROM {$this->items_table} oi
                     JOIN products p ON oi.product_id = p.product_id
                     WHERE oi.order_id = :order_id
                     ORDER BY oi.item_id ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();

            $items = [];
            while ($item = $stmt->fetch(PDO::FETCH_OBJ)) {
                $item->created_at_formatted = $this->formatTimestamp($item->created_at);
                $items[] = $item;
            }

            return $items;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Format timestamp to readable format
     * @param string $timestamp MySQL timestamp
     * @return string Formatted date
     */
    private function formatTimestamp($timestamp) {
        if (empty($timestamp)) {
            return 'N/A';
        }
        return date('M d, Y \a\t g:i A', strtotime($timestamp));
    }

    /**
     * Calculate time elapsed since order creation
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
        } elseif ($status === 'cancelled') {
            $timeline['delivery_status'] = 'Cancelled';
        } else {
            $days_until = floor(($estimated - $now) / 86400);
            $timeline['days_until_delivery'] = max(0, $days_until);
            $timeline['delivery_status'] = ucfirst($status);
        }

        return $timeline;
    }
}
?>
