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

            $products[] = $product;
        }

        return $products;
    }

    public function addProduct($name, $description, $price, $quantity, $image) {
        $query = "INSERT INTO products (name, description, price, quantity, image) 
                  VALUES (:name, :description, :price, :quantity, :image)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':image', $image);

        return $stmt->execute();
    }

    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateProduct($id, $name, $description, $price, $quantity, $image) {
    $query = "UPDATE products 
              SET name = :name, description = :description, price = :price, quantity = :quantity, image = :image 
              WHERE id = :id";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':image', $image);

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