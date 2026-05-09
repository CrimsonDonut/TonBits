<?php
session_start();

require_once "../config/Database.php";
require_once "../config/auth_helper.php";

$database = new Database();
$conn = $database->connect();

$errors = [];
$form_data = ['username' => '', 'email' => '', 'password' => '', 'confirm_password' => ''];
$success = false;

// DO-WHILE LOOP: Keep form visible until valid submission
do {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Sanitize inputs
        $form_data['username'] = trim($_POST['username'] ?? '');
        $form_data['email'] = trim($_POST['email'] ?? '');
        $form_data['password'] = $_POST['password'] ?? '';
        $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';

        // FOR LOOP: Validate all fields programmatically
        $fields_to_validate = [
            'username' => ['value' => $form_data['username'], 'type' => 'username'],
            'email' => ['value' => $form_data['email'], 'type' => 'email'],
            'password' => ['value' => $form_data['password'], 'type' => 'password'],
            'confirm_password' => ['value' => $form_data['confirm_password'], 'type' => 'confirm_password']
        ];

        $auth_helper = new AuthHelper($conn);

        // FOR loop to validate each field
        foreach ($fields_to_validate as $field_name => $field_info) {
            $field_value = $field_info['value'];
            $field_type = $field_info['type'];

            // Check if field is empty
            if (empty($field_value)) {
                $errors[$field_name] = ucfirst(str_replace('_', ' ', $field_name)) . " is required.";
                continue;
            }

            // Validate based on field type
            switch ($field_type) {
                case 'username':
                    $validation_result = $auth_helper->validateUsername($field_value);
                    if (!$validation_result['valid']) {
                        $errors[$field_name] = $validation_result['error'];
                    }
                    break;

                case 'email':
                    $validation_result = $auth_helper->validateEmail($field_value);
                    if (!$validation_result['valid']) {
                        $errors[$field_name] = $validation_result['error'];
                    }
                    break;

                case 'password':
                    $validation_result = $auth_helper->validatePassword($field_value);
                    if (!$validation_result['valid']) {
                        $errors[$field_name] = $validation_result['error'];
                    }
                    break;

                case 'confirm_password':
                    // Validate match with password
                    $match_result = $auth_helper->validatePasswordMatch($form_data['password'], $field_value);
                    if (!$match_result['valid']) {
                        $errors[$field_name] = $match_result['error'];
                    }
                    break;
            }
        }

        // If no errors, register the user
        if (empty($errors)) {
            $register_result = $auth_helper->registerUser(
                $form_data['username'],
                $form_data['email'],
                $form_data['password']
            );

            if ($register_result['success']) {
                $_SESSION['success_message'] = $register_result['message'];
                header("Location: login.php");
                exit();
            } else {
                $errors['general'] = $register_result['message'];
            }
        }
    }

} while (false); // End do-while - form will be shown again if there are POST errors

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - tonbits</title>
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
                <a href="../index.php#support">Support</a>
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

    <!-- Error Modal for General Errors -->
    <?php if (!empty($errors['general'])): ?>
        <div class="modal-overlay" id="errorModal" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Registration Error</h2>
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
                    <p><?php echo htmlspecialchars($errors['general']); ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn-modal-close" id="closeErrorBtn">Try Again</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Registration Form Section -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join tonbits to start shopping</p>

                <form method="POST" class="auth-form">
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($form_data['username']); ?>"
                            placeholder="Choose a username (3-20 characters)"
                            required
                        >
                        <?php if (!empty($errors['username'])): ?>
                            <span class="form-error"><?php echo htmlspecialchars($errors['username']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($form_data['email']); ?>"
                            placeholder="Enter your email"
                            required
                        >
                        <?php if (!empty($errors['email'])): ?>
                            <span class="form-error"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="8+ characters, 1 number, 1 special character"
                            required
                        >
                        <?php if (!empty($errors['password'])): ?>
                            <span class="form-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                        <small class="password-hint">Must contain: 8+ characters, at least one number, and one special character (!@#$%^&*)</small>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Re-enter your password"
                            required
                        >
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <span class="form-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>

                <!-- Login Link -->
                <p class="auth-footer">
                    Already have an account? <a href="login.php" class="auth-link">Login here</a>
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

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3 class="logo">
                        <span class="logo-white">ton</span><span class="logo-purple">bits</span>
                    </h3>
                    <p class="footer-tagline">Premium GPU solutions for gamers and creators worldwide</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/carlanthony.pena" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Products</h4>
                    <ul>
                        <li><a href="#">Graphics Cards</a></li>
                        <li><a href="#">RTX 50 Series</a></li>
                        <li><a href="#">RTX 40 Series</a></li>
                        <li><a href="#">Accessories</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Driver Downloads</a></li>
                        <li><a href="#">Warranty</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-newsletter">
                    <h4>Stay Updated</h4>
                    <p>Subscribe to our newsletter for the latest updates</p>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Enter email">
                        <button>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 tonbits. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
