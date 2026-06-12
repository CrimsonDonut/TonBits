<?php
require_once "../config/Database.php";
require_once "../models/Product.php";

$database = new Database();
$db = $database->connect();

$product = new Product($db);

$message = "";

// GET product data with sanitization
if (isset($_GET['id'])) {
    $id = (is_numeric($_GET['id']) && (int)$_GET['id'] > 0) ? (int)$_GET['id'] : null;
    if ($id === null) {
        throw new Exception("Invalid product ID.");
    }
    $current = $product->getProductById($id);
}

// UPDATE product with sanitization
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitize all inputs
        $id = (isset($_POST['id']) && is_numeric($_POST['id']) && (int)$_POST['id'] > 0) ? (int)$_POST['id'] : null;
        $name = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
        $description = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
        $price = trim($_POST['price'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $memory_size = trim($_POST['memory_size'] ?? '');
        $features_text = trim($_POST['features'] ?? '');
        $specifications_text = trim($_POST['specifications'] ?? '');

        // Validate required fields using string testing functions
        if ($id === null) {
            throw new Exception("Invalid product ID.");
        }
        
        // String Testing: empty() - Check if name is empty
        if (empty($name)) {
            throw new Exception("Product name is required and cannot be empty.");
        }
        
        // String Testing: strlen() - Validate name length
        if (strlen($name) < 3) {
            throw new Exception("Product name must be at least 3 characters.");
        }
        if (strlen($name) > 100) {
            throw new Exception("Product name must not exceed 100 characters.");
        }
        
        // String Testing: is_numeric() - Validate price is numeric
        if (empty($price) || !is_numeric($price) || (float)$price <= 0) {
            throw new Exception("Price is required and must be a valid positive number.");
        }
        
        // String Testing: is_numeric() - Validate quantity is numeric
        if (empty($quantity) || !is_numeric($quantity) || (int)$quantity < 0) {
            throw new Exception("Quantity is required and must be a valid non-negative number.");
        }

        if (empty($brand)) {
            throw new Exception("Brand cannot be empty.");
        }

        if (empty($memory_size)) {
            throw new Exception("Memory size cannot be empty.");
        }

        if (!is_numeric($memory_size)) {
            throw new Exception("Memory size must be a valid number.");
        }

        if (empty($features_text)) {
            throw new Exception("Features cannot be empty.");
        }

        if (empty($specifications_text)) {
            throw new Exception("Specifications cannot be empty.");
        }

        // Parse features (one per line)
        $features = array_filter(array_map('trim', explode("\n", $features_text)));
        if (empty($features)) {
            throw new Exception("At least one feature is required.");
        }

        // Parse specifications (key:value pairs, one per line)
        $specifications = [];
        $spec_lines = array_filter(array_map('trim', explode("\n", $specifications_text)));
        foreach ($spec_lines as $line) {
            if (strpos($line, ':') === false) {
                throw new Exception("Specifications must be in 'key: value' format.");
            }
            [$key, $value] = explode(':', $line, 2);
            $specifications[trim($key)] = trim($value);
        }
        if (empty($specifications)) {
            throw new Exception("At least one specification is required.");
        }
        
        // String Testing: str_word_count() - Validate description has meaningful content
        if (!empty($description) && str_word_count($description) < 3) {
            throw new Exception("Description must have at least 3 words if provided.");
        }
        
        $price = (float)$price;
        $quantity = (int)$quantity;
        $memory_size = (int)$memory_size;

        // Handle image (optional update)
        $imageName = $_POST['existing_image'] ?? '';
        
        if (!empty($_FILES['image']['name'])) {
            $newImageName = basename($_FILES['image']['name']);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($newImageName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception("Invalid image format. Allowed: jpg, jpeg, png, gif, webp");
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
            
            if (!in_array($fileMimeType, $allowedMimeTypes)) {
                throw new Exception("Invalid image MIME type. Must be a valid image.");
            }

            // Add unique prefix to prevent overwrites
            $newImageName = time() . '_' . $newImageName;
            $targetDir = "../uploads/";
            $targetFile = $targetDir . $newImageName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imageName = $newImageName;
            } else {
                throw new Exception("Image upload failed.");
            }
        }

        if ($product->updateProduct($id, $name, $description, $price, $quantity, $imageName, $brand, $features, $specifications)) {
            $message = "Product updated successfully!";
        } else {
            $message = "Update failed.";
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
    <title>Edit Product - tonbits Admin</title>
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
                <a href="dashboard.php">Dashboard</a>
                <a href="add_product.php">Add Product</a>
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
                <span>Edit Product</span>
            </div>

            <h1 class="admin-title">Edit Product</h1>

            <?php if ($message): ?>
                <div class="message-<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($current)): ?>
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $current->id; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo $current->image; ?>">

                        <div class="form-group">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($current->name); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($current->description); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" value="<?php echo $current->price; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-input" min="0" value="<?php echo $current->quantity; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="brand" class="form-label">Brand</label>
                            <select id="brand" name="brand" class="form-input" required>
                                <option value="">Select a brand</option>
                                <option value="NVIDIA" <?php echo $current->brand === 'NVIDIA' ? 'selected' : ''; ?>>NVIDIA</option>
                                <option value="AMD" <?php echo $current->brand === 'AMD' ? 'selected' : ''; ?>>AMD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="memory_size" class="form-label">Memory Size (GB)</label>
                            <input type="number" id="memory_size" name="memory_size" class="form-input" min="0" step="1" value="<?php echo $current->memory_size; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="features" class="form-label">Features (One per line)</label>
                            <textarea id="features" name="features" class="form-textarea" required><?php echo implode("\n", $current->features); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="specifications" class="form-label">Specifications (Key: Value, one per line)</label>
                            <textarea id="specifications" name="specifications" class="form-textarea" required><?php 
                                foreach ($current->specifications as $key => $value) {
                                    echo htmlspecialchars($key . ': ' . $value) . "\n";
                                }
                            ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Current Image</label>
                            <div class="current-image">
                                <img src="../uploads/<?php echo htmlspecialchars($current->image); ?>" alt="<?php echo htmlspecialchars($current->name); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="image" class="form-label">Update Image (Optional)</label>
                            <input type="file" id="image" name="image" class="form-file">
                        </div>

                        <button type="submit" class="btn-admin btn-submit">Update Product</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="message-error">Product not found.</div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>