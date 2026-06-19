<?php

class Product {
    private $conn;
    private $table = "products";
    private $specs_table = "product_specifications";
    private $features_table = "product_features";
    
    public $product_id;
    public $id;  
    public $name;
    public $description;
    public $price;
    public $quantity;
    public $quantity_in_stock;
    public $image;
    public $image_url;
    public $features;
    public $specifications;
    public $brand;
    public $memory_size;

    public function __get($name) {
        switch ($name) {
            case 'formatted_price':
                return '₱' . number_format($this->price, 2);
            case 'in_stock':
                return ($this->quantity_in_stock ?? $this->quantity ?? 0) > 0;
            default:
                if (property_exists($this, $name)) {
                    return $this->$name;
                }
                throw new Exception("Undefined property: " . $name);
        }
    }

    public function __set($name, $value) {
        if ($name === "price" && $value < 0) {
            throw new Exception("Price cannot be negative!");
        }

        if (($name === "quantity" || $name === "quantity_in_stock") && $value < 0) {
            throw new Exception("Stock cannot be negative");
        }

        // Handle backward compatibility
        if ($name === "quantity") {
            $this->quantity_in_stock = $value;
        }
        if ($name === "id") {
            $this->product_id = $value;
        }
        if ($name === "image") {
            $this->image_url = $value;
        }

        $this->$name = $value;
    }

    public function __construct($db) {
        $this->conn = $db;
    }


    public function getAllProducts(string $sort_by = 'newest'): array {
        $query = "SELECT * FROM " . $this->table . $this->getOrderClause($sort_by);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $products = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = $this->mapRowToProduct($row);
            $product->specifications = $this->getProductSpecifications($product->product_id);
            $product->features = $this->getProductFeatures($product->product_id);
            $products[] = $product;
        }

        return $products;
    }
    
    private function getOrderClause(string $sort_by): string {
        switch ($sort_by) {
            case 'price_low':
                return " ORDER BY price ASC";
            case 'price_high':
                return " ORDER BY price DESC";
            case 'newest':
    default:
                return " ORDER BY product_id DESC";
        }
    }

//get filtered products based on selected brands and memory sizes
public function getFilteredProducts($brands = [], $memory_sizes = [], string $sort_by = 'newest'): array {
    $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
    
    if (!empty($brands)) {
        $placeholders = implode(',', array_fill(0, count($brands), '?'));
        $query .= " AND brand IN (" . $placeholders . ")";
    }
    

    $query .= $this->getOrderClause($sort_by);
    
    $stmt = $this->conn->prepare($query);
    
    $params = array_merge($brands);
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    
    $stmt->execute();
    $products = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product = $this->mapRowToProduct($row);
        
        if (!empty($memory_sizes)) {
            $specs = $this->getProductSpecifications($product->product_id);
            
            // Check if product matches any selected memory size
            $matches_memory = false;
            foreach ($memory_sizes as $memory_size) {
                foreach ($specs as $spec_key => $spec_value) {
                    if ($spec_key === 'Memory' && stripos($spec_value, $memory_size . 'GB') === 0) {
                        $matches_memory = true;
                        break 2;
                    }
                }
            }
            
            // Skip product if it doesn't match any selected memory size
            if (!$matches_memory) {
                continue;
            }
        }
        
        $product->specifications = $this->getProductSpecifications($product->product_id);
        $product->features = $this->getProductFeatures($product->product_id);
        $products[] = $product;
    }

    return $products;
}

    // Add a new product with features and specifications
    public function addProduct($name, $description, $price, $quantity, $image, $brand = 'NVIDIA', $features = [], $specifications = []) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table . " (name, description, price, quantity_in_stock, image_url, brand) 
                      VALUES (:name, :description, :price, :quantity, :image, :brand)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':brand', $brand);

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert product");
            }

            $product_id = $this->conn->lastInsertId();

            // Add features
            if (!empty($features)) {
                $this->addProductFeatures($product_id, $features);
            }

            // Add specifications
            if (!empty($specifications)) {
                $this->addProductSpecifications($product_id, $specifications);
            }

            $this->conn->commit();
            return $product_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a product and its related data
     */
    public function deleteProduct($id) {
        try {
            $this->conn->beginTransaction();
            
            // Delete will cascade automatically due to foreign key constraints
            $query = "DELETE FROM " . $this->table . " WHERE product_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            $result = $stmt->execute();
            $this->conn->commit();
            
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Update a product
     */
    public function updateProduct($id, $name, $description, $price, $quantity, $image, $brand = 'NVIDIA', $features = [], $specifications = []) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE " . $this->table . " 
                      SET name = :name, description = :description, price = :price, 
                          quantity_in_stock = :quantity, image_url = :image, brand = :brand 
                      WHERE product_id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':brand', $brand);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update product");
            }

            // Update features
            if (!empty($features)) {
                $this->deleteProductFeatures($id);
                $this->addProductFeatures($id, $features);
            }

            // Update specifications
            if (!empty($specifications)) {
                $this->deleteProductSpecifications($id);
                $this->addProductSpecifications($id, $specifications);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Get product by ID with all related data
     */
    public function getProductById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE product_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $product = $this->mapRowToProduct($row);
            $product->specifications = $this->getProductSpecifications($product->product_id);
            $product->features = $this->getProductFeatures($product->product_id);
            return $product;
        }

        return null;
    }

    /**
     * Decrease stock for a product
     */
    public function decreaseStock($id, $qty) {
        $query = "UPDATE " . $this->table . " SET quantity_in_stock = quantity_in_stock - :qty WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":qty", $qty);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    /**
     * Increase stock for a product
     */
    public function increaseStock($id, $qty) {
        $query = "UPDATE " . $this->table . " SET quantity_in_stock = quantity_in_stock + :qty WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":qty", $qty);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    /**
     * Get specifications for a product
     * Returns an associative array like ['Memory' => '16GB', 'Boost Clock' => '2.79 GHz', ...]
     */
    public function getProductSpecifications($product_id) {
        $query = "SELECT spec_key, spec_value FROM " . $this->specs_table . " 
                  WHERE product_id = :product_id 
                  ORDER BY spec_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();

        $specs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $specs[$row['spec_key']] = $row['spec_value'];
        }
        return $specs;
    }

    /**
     * Get features for a product
     */
    public function getProductFeatures($product_id) {
        $query = "SELECT feature_text FROM " . $this->features_table . " 
                  WHERE product_id = :product_id 
                  ORDER BY display_order, feature_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();

        $features = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $features[] = $row['feature_text'];
        }
        return $features;
    }

    /**
     * Add specifications for a product
     */
    private function addProductSpecifications($product_id, $specifications) {
        if (empty($specifications)) return true;

        // Handle both array of arrays and flat spec_key/spec_value pairs
        $query = "INSERT INTO " . $this->specs_table . " (product_id, spec_key, spec_value) 
                  VALUES (:product_id, :spec_key, :spec_value)";
        
        $stmt = $this->conn->prepare($query);

        foreach ($specifications as $key => $value) {
            if (is_array($value)) {
                // If value is an array, use key and value from array
                $spec_key = $value['spec_key'] ?? $key;
                $spec_value = $value['spec_value'] ?? $value;
            } else {
                // Otherwise key is spec_key and value is spec_value
                $spec_key = $key;
                $spec_value = $value;
            }

            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':spec_key', $spec_key);
            $stmt->bindParam(':spec_value', $spec_value);

            if (!$stmt->execute()) {
                throw new Exception("Failed to add specification");
            }
        }

        return true;
    }

    /**
     * Add features for a product
     */
    private function addProductFeatures($product_id, $features) {
        if (empty($features)) return true;

        $query = "INSERT INTO " . $this->features_table . " (product_id, feature_text, display_order) 
                  VALUES (:product_id, :feature_text, :display_order)";
        
        $stmt = $this->conn->prepare($query);

        foreach ($features as $index => $feature) {
            $feature_text = is_array($feature) ? $feature['feature_text'] ?? $feature : $feature;
            $display_order = $index;

            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':feature_text', $feature_text);
            $stmt->bindParam(':display_order', $display_order);

            if (!$stmt->execute()) {
                throw new Exception("Failed to add feature");
            }
        }

        return true;
    }

    /**
     * Delete all specifications for a product
     */
    private function deleteProductSpecifications($product_id) {
        $query = "DELETE FROM " . $this->specs_table . " WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    /**
     * Delete all features for a product
     */
    private function deleteProductFeatures($product_id) {
        $query = "DELETE FROM " . $this->features_table . " WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    /**
     * Map database row to Product object
     */
    private function mapRowToProduct($row) {
        $product = new Product($this->conn);
        
        $product->product_id = $row['product_id'];
        $product->id = $row['product_id']; // Backward compatibility
        $product->name = $row['name'];
        $product->price = $row['price'];
        $product->description = $row['description'];
        $product->quantity_in_stock = $row['quantity_in_stock'];
        $product->quantity = $row['quantity_in_stock']; // Backward compatibility
        $product->image_url = $row['image_url'];
        $product->image = $row['image_url']; // Backward compatibility
        $product->brand = $row['brand'] ?? 'NVIDIA';
        
        // Set memory_size default (can be stored as a specification)
        $product->memory_size = 16;
        
        return $product;
    }
}