<?php

class Product {
    private $conn;
    private $table = "products";
    public $id;
    public $name;
    public $description;
    public $price;
    public $quantity;
    public $image;
    public $features;
    public $specifications;
    public $brand;
    public $memory_size;
public function __get($name) {
    switch ($name) {
        case 'formatted_price':
            return '₱' . number_format($this->price, 2);
        case 'in_stock':
            return $this->quantity > 0;
        default:
            if (property_exists($this, $name)) {
                return $this->$name;
            }
            throw new Exception("Undefined property: " . $name);
    }
}

public function __set($name, $value) {
    if ($name === "price" && $value < 0) {
        throw new Exception("Price cannot be negative");
    }

    if ($name === "quantity" && $value < 0) {
        throw new Exception("Stock cannot be negative");
    }

    $this->$name = $value;
}

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllProducts(): array {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $products = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new Product($this->conn);

            // assign values using __set()
            $product->id = $row['id'];
            $product->name = $row['name'];
            $product->price = $row['price'];
            $product->description = $row['description'];
            $product->quantity = $row['quantity'];
            $product->image = $row['image'];
            $product->brand = $row['brand'] ?? 'NVIDIA';
            $product->memory_size = $row['memory_size'] ?? 16;

            $products[] = $product;
        }

        return $products;
    }

    public function getFilteredProducts($brands = [], $memory_sizes = []): array {
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($brands)) {
            $placeholders = implode(',', array_fill(0, count($brands), '?'));
            $query .= " AND brand IN (" . $placeholders . ")";
        }
        
        if (!empty($memory_sizes)) {
            $placeholders = implode(',', array_fill(0, count($memory_sizes), '?'));
            $query .= " AND memory_size IN (" . $placeholders . ")";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $params = array_merge($brands, $memory_sizes);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param);
        }
        
        $stmt->execute();
        $products = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product = new Product($this->conn);
            $product->id = $row['id'];
            $product->name = $row['name'];
            $product->price = $row['price'];
            $product->description = $row['description'];
            $product->quantity = $row['quantity'];
            $product->image = $row['image'];
            $product->brand = $row['brand'] ?? 'NVIDIA';
            $product->memory_size = $row['memory_size'] ?? 16;

            $products[] = $product;
        }

        return $products;
    }

    public function addProduct($name, $description, $price, $quantity, $image, $brand = 'NVIDIA', $memory_size = 16, $features = '', $specifications = '') {
        $query = "INSERT INTO products (name, description, price, quantity, image, brand, memory_size, features, specifications) 
                  VALUES (:name, :description, :price, :quantity, :image, :brand, :memory_size, :features, :specifications)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':brand', $brand);
        $stmt->bindParam(':memory_size', $memory_size);
        $stmt->bindParam(':features', $features);
        $stmt->bindParam(':specifications', $specifications);

        return $stmt->execute();
    }

    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateProduct($id, $name, $description, $price, $quantity, $image, $brand = 'NVIDIA', $memory_size = 16, $features = '', $specifications = '') {
    $query = "UPDATE products 
              SET name = :name, description = :description, price = :price, quantity = :quantity, image = :image, brand = :brand, memory_size = :memory_size, features = :features, specifications = :specifications 
              WHERE id = :id";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':memory_size', $memory_size);
    $stmt->bindParam(':features', $features);
    $stmt->bindParam(':specifications', $specifications);

    return $stmt->execute();
    }
    public function getProductById($id) {
        $query = "SELECT * FROM products WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $product = new Product($this->conn);

            $product->id = $row['id'];
            $product->name = $row['name'];
            $product->price = $row['price'];
            $product->description = $row['description'];
            $product->quantity = $row['quantity'];
            $product->image = $row['image'];
            $product->features = json_decode($row['features'] ?? '[]', true);
            $product->specifications = json_decode($row['specifications'] ?? '{}', true);
            $product->brand = $row['brand'] ?? 'NVIDIA';
            $product->memory_size = $row['memory_size'] ?? 16;

            return $product;
        }

        return null;
    }
    public function decreaseStock($id, $qty) {
    $query = "UPDATE products SET quantity = quantity - :qty WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":qty", $qty);
    $stmt->bindParam(":id", $id);
    return $stmt->execute();
    }
    public function increaseStock($id, $qty) {
    $query = "UPDATE products SET quantity = quantity + :qty WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":qty", $qty);
    $stmt->bindParam(":id", $id);
    return $stmt->execute();
}
}