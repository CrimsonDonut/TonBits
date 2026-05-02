<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";

$database = new Database();
$conn = $database->connect();

$error = null;
$form_data = ['email' => '', 'password' => ''];
$remember_me = false;

// Check if there's a success message from registration
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}

// Check for remember-me cookie
if (!AuthHelper::isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $auth_helper = new AuthHelper($conn);
    $token_result = $auth_helper->verifyRememberMeToken($_COOKIE['remember_token']);
    
    if ($token_result['valid']) {
        $auth_helper->createSession($token_result['user_data']);
        header("Location: ../index.php");
        exit();
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// If already logged in, redirect to home
if (AuthHelper::isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FOR LOOP: Sanitize POST data
    $post_fields = ['email' => 'email', 'password' => 'password', 'remember_me' => 'checkbox'];
    $sanitized = [];

    foreach ($post_fields as $field_name => $field_type) {
        if ($field_type === 'checkbox') {
            $sanitized[$field_name] = isset($_POST[$field_name]) && $_POST[$field_name] === 'on';
        } else {
            $sanitized[$field_name] = isset($_POST[$field_name]) ? trim(strip_tags($_POST[$field_name])) : '';
        }
    }

    $form_data['email'] = $sanitized['email'];
    $form_data['password'] = $sanitized['password'];
    $remember_me = $sanitized['remember_me'];

    // Validate required fields
    if (empty($form_data['email']) || empty($form_data['password'])) {
        $error = "Email/Username and password are required.";
    } else {
        $auth_helper = new AuthHelper($conn);
        $login_result = $auth_helper->validateLoginInput($form_data['email'], $form_data['password']);

        if ($login_result['valid']) {
            // Create session
            $auth_helper->createSession($login_result['user_data']);

            // Handle remember-me
            if ($remember_me) {
                $token = $auth_helper->createRememberMeToken($login_result['user_data']['id']);
                if ($token) {
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                }
            }

            // Redirect to home
            header("Location: ../index.php");
            exit();
        } else {
            $error = $login_result['error'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - tonbits</title>
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
                <a href="../index.php">Products</a>
                <a href="../index.php">Features</a>
                <a href="../index.php">Specs</a>
                <a href="../index.php">Support</a>
            </div>
            <div class="nav-buttons">
                <button class="btn-shop" onclick="window.location.href='cart.php'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span>View Cart</span>
                </button>
                <div class="profile-dropdown-container">
                    <button class="btn-profile" id="profileBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="login.php" class="dropdown-item">Login</a>
                        <a href="register.php" class="dropdown-item">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Background Effects -->
    <div class="bg-effects">
        <div class="bg-gradient"></div>
        <div class="bg-glow-1"></div>
        <div class="bg-glow-2"></div>
    </div>

    <!-- Error Modal -->
    <?php if ($error): ?>
        <div class="modal-overlay" id="errorModal" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Login Error</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="error-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn-modal-close" id="closeErrorBtn">Try Again</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Login Form Section -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Login to your tonbits account</p>

                <!-- Success Message from Registration -->
                <?php if ($success_message): ?>
                    <div class="success-message">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <!-- Email/Username Field -->
                    <div class="form-group">
                        <label for="email">Email or Username</label>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($form_data['email']); ?>"
                            placeholder="Enter your email or username"
                            required
                        >
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="form-group checkbox">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember_me"
                        >
                        <label for="remember_me">Remember me for 30 days</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">Login</button>
                </form>

                <!-- Registration Link -->
                <p class="auth-footer">
                    Don't have an account? <a href="register.php" class="auth-link">Register here</a>
                </p>
            </div>
        </div>
    </section>

    <!-- Error Modal Handler Script -->
    <script>
        const errorModal = document.getElementById('errorModal');
        
        if (errorModal) {
            // Close modal when clicking the close button
            const closeBtn = errorModal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    errorModal.style.display = 'none';
                });
            }

            // Close modal when clicking the "Try Again" button
            const closeErrorBtn = document.getElementById('closeErrorBtn');
            if (closeErrorBtn) {
                closeErrorBtn.addEventListener('click', function() {
                    errorModal.style.display = 'none';
                });
            }

            // Close modal when clicking outside the modal content
            errorModal.addEventListener('click', function(event) {
                if (event.target === errorModal) {
                    errorModal.style.display = 'none';
                }
            });

            // Close modal when pressing Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && errorModal.style.display === 'flex') {
                    errorModal.style.display = 'none';
                }
            });
        }
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
