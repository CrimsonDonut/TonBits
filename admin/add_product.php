<?php
require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate and sanitize inputs using string testing functions
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $specifications = trim($_POST['specifications'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $memory_size = trim($_POST['memory_size'] ?? '');

        // String Testing: empty() - Check if required fields are empty
        if (empty($name)) {
            throw new Exception("Product name cannot be empty.");
        }

        if (empty($features)) {
            throw new Exception("Features cannot be empty.");
        }

        if (empty($specifications)) {
            throw new Exception("Specifications cannot be empty.");
        }

        if (empty($brand)) {
            throw new Exception("Brand cannot be empty.");
        }

        if (empty($memory_size)) {
            throw new Exception("Memory size cannot be empty.");
        }

        if (empty($price)) {
            throw new Exception("Price cannot be empty.");
        }

        if (empty($price)) {
            throw new Exception("Price cannot be empty.");
        }
        if (empty($quantity)) {
            throw new Exception("Quantity cannot be empty.");
        }

        // String Testing: strlen() - Validate name length
        if (strlen($name) < 3) {
            throw new Exception("Product name must be at least 3 characters.");
        }
        if (strlen($name) > 100) {
            throw new Exception("Product name must not exceed 100 characters.");
        }

        // String Testing: is_numeric() - Validate price and quantity are numeric
        if (!is_numeric($memory_size)) {
            throw new Exception("Memory size must be a valid number.");
        }
        if (!is_numeric($price)) {
            throw new Exception("Price must be a valid number.");
        }
        if (!is_numeric($quantity)) {
            throw new Exception("Quantity must be a valid number.");
        }
        if ((float)$price <= 0) {
            throw new Exception("Price must be greater than 0.");
        }
        if ((int)$quantity < 0) {
            throw new Exception("Quantity cannot be negative.");
        }

        // String Testing: str_word_count() - Validate description has meaningful content
        if (!empty($description) && str_word_count($description) < 3) {
            throw new Exception("Description must have at least 3 words.");
        }

        $product->name = $name;
        $product->description = $description;
        $product->price = (float)$price;
        $product->quantity = (int)$quantity;
        $product->brand = $brand;
        $product->memory_size = (int)$memory_size;
        $product->features = $features;
        $product->specifications = $specifications;

        // Image upload
        $imageName = $_FILES['image']['name'];
        $targetDir = "../uploads/";
        $targetFile = $targetDir . basename($imageName);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            if ($product->addProduct(
                $product->name,
                $product->description,
                $product->price,
                $product->quantity,
                $imageName,
                $product->brand,
                $product->memory_size,
                $product->features,
                $product->specifications
            )) {
                $message = "Product added successfully!";
            } else {
                $message = "Error adding product.";
            }
        } else {
            $message = "Image upload failed.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - tonbits Admin</title>
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
            </div>
            <button class="btn-shop" onclick="window.location.href='dashboard.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span>Back to Dashboard</span>
            </button>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Admin Section -->
    <section class="admin-section">
        <div class="admin-container">
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <span>/</span>
                <span>Add Product</span>
            </div>

            <h1 class="admin-title">Add New Product</h1>

            <?php if ($message): ?>
                <div class="message-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" id="name" name="name" class="form-input" placeholder="Enter product name" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Enter product description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" placeholder="Enter price" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-input" min="0" placeholder="Enter quantity" required>
                    </div>

                    <div class="form-group">
                        <label for="brand" class="form-label">Brand</label>
                        <select id="brand" name="brand" class="form-input" required>
                            <option value="">Select a brand</option>
                            <option value="NVIDIA">NVIDIA</option>
                            <option value="AMD">AMD</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="memory_size" class="form-label">Memory Size (GB)</label>
                        <input type="number" id="memory_size" name="memory_size" class="form-input" min="0" step="1" placeholder="Enter memory size" required>
                    </div>

                    <div class="form-group">
                        <label for="features" class="form-label">Features (JSON Array)</label>
                        <textarea id="features" name="features" class="form-textarea" placeholder='["Advanced thermal design", "Maximum performance for gaming", "Next-gen ray tracing technology", "AI-enhanced graphics", "Premium build quality", "Durability"]' required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="specifications" class="form-label">Specifications (JSON Object)</label>
                        <textarea id="specifications" name="specifications" class="form-textarea" placeholder='{"AI Performance": "1406 TOPS", "Memory": "16GB GDDR7", "Boost Clock": "2.61 GHz", "TGP": "285W", "Memory Type": "GDDR7", "Memory Interface": "256-bit", "Ray Tracing": "4th Generation", "Connectivity": "3x DisplayPort 2.1, 1x HDMI 2.1"}' required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" id="image" name="image" class="form-file" required>
                    </div>

                    <button type="submit" class="btn-admin btn-submit">Add Product</button>
                </form>
            </div>
        </div>
    </section>
</body>
</html>