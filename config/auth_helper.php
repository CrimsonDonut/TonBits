<?php

class AuthHelper {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Validate email format and check if already exists in database
     * @return array ['valid' => bool, 'error' => string or null]
     */
    public function validateEmail($email) {
        $error = null;

        // Check format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
            return ['valid' => false, 'error' => $error];
        }

        // Check if email already exists
        $query = "SELECT user_ID FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
            return ['valid' => false, 'error' => $error];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate password strength
     * Requirements: 8+ chars, at least 1 number, at least 1 special character
     * @return array ['valid' => bool, 'error' => string or null]
     */
    public function validatePassword($password) {
        $error = null;

        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
            return ['valid' => false, 'error' => $error];
        }

        if (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one number.";
            return ['valid' => false, 'error' => $error];
        }

        if (!preg_match('/[!@#$%^&*]/', $password)) {
            $error = "Password must contain at least one special character (!@#$%^&*).";
            return ['valid' => false, 'error' => $error];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate username
     * Requirements: 3-20 characters, alphanumeric and underscore only, must be unique
     * @return array ['valid' => bool, 'error' => string or null]
     */
    public function validateUsername($username) {
        $error = null;

        if (strlen($username) < 3 || strlen($username) > 20) {
            $error = "Username must be between 3 and 20 characters long.";
            return ['valid' => false, 'error' => $error];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = "Username can only contain letters, numbers, and underscores.";
            return ['valid' => false, 'error' => $error];
        }

        // Check if username already exists
        $query = "SELECT user_ID FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = "Username is already taken.";
            return ['valid' => false, 'error' => $error];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate that password and confirm password match
     * @return array ['valid' => bool, 'error' => string or null]
     */
    public function validatePasswordMatch($password, $confirm_password) {
        if ($password !== $confirm_password) {
            return ['valid' => false, 'error' => 'Passwords do not match.'];
        }
        return ['valid' => true, 'error' => null];
    }

    /**
     * Register a new user
     * @return array ['success' => bool, 'message' => string, 'user_id' => int or null]
     */
    public function registerUser($username, $email, $password) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);

            if ($stmt->execute()) {
                $user_id = $this->conn->lastInsertId();
                return ['success' => true, 'message' => 'Account created successfully!', 'user_id' => $user_id];
            } else {
                return ['success' => false, 'message' => 'Error creating account.', 'user_id' => null];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'user_id' => null];
        }
    }

    /**
     * Validate login credentials
     * @return array ['valid' => bool, 'user_data' => array or null, 'error' => string or null]
     */
    public function validateLoginInput($username_or_email, $password) {
        // Query user by email or username
        $query = "SELECT user_ID, username, email, password, is_admin FROM users WHERE email = :user OR username = :user";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user', $username_or_email);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return ['valid' => false, 'user_data' => null, 'error' => 'Invalid email/username or password.'];
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password, $user['password'])) {
            return ['valid' => false, 'user_data' => null, 'error' => 'Invalid email/username or password.'];
        }

        return ['valid' => true, 'user_data' => $user, 'error' => null];
    }

    /**
     * Create session for logged-in user
     */
    public function createSession($user_data) {
        $_SESSION['user_id'] = $user_data['user_ID'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['is_admin'] = $user_data['is_admin'];
    }

/**
 * Check if the currently logged-in user is an admin
 */
public static function isAdmin() {
    return self::isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;
}

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Create remember-me token and store in database
     * @return string token
     */
    public function createRememberMeToken($user_id) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

            $query = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expires_at', $expires_at, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return $token;
            }
            return null;
        } catch (PDOException $e) {
            error_log("Remember-me token creation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify remember-me token and restore session
     * @return array ['valid' => bool, 'user_data' => array or null]
     */
    public function verifyRememberMeToken($token) {
        try {
            $query = "SELECT u.user_ID, u.username, u.email, rt.expires_at FROM remember_tokens rt 
                      JOIN users u ON rt.user_id = u.user_ID 
                      WHERE rt.token = :token AND rt.expires_at > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return ['valid' => false, 'user_data' => null];
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['valid' => true, 'user_data' => $user];
        } catch (PDOException $e) {
            return ['valid' => false, 'user_data' => null];
        }
    }

    /**
     * Logout user - destroy session and remove remember-me token
     */
    public static function logout($token = null) {
        session_destroy();
        if ($token) {
            // Token cleanup could be done here if needed
        }
    }
}
?>
