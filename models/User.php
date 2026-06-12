<?php

class User {
    private $conn;
    private $table = 'users';

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $is_admin;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new user
     */
    public function create($username, $email, $password) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (username, email, password) 
                      VALUES (:username, :email, :password)";

            $stmt = $this->conn->prepare($query);

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;

        } catch (PDOException $e) {
            throw new Exception("Error creating user: " . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Verify password
     */
    public function verifyPassword($plain_password, $hashed_password) {
        return password_verify($plain_password, $hashed_password);
    }

    /**
     * Update user
     */
    public function update($user_id, $data) {
        try {
            $allowed_fields = ['username', 'email', 'password', 'is_admin'];
            $set_clause = [];
            $params = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $set_clause[] = "$field = :$field";
                    if ($field === 'password') {
                        $params[':' . $field] = password_hash($value, PASSWORD_BCRYPT);
                    } else {
                        $params[':' . $field] = $value;
                    }
                }
            }

            if (empty($set_clause)) {
                return false;
            }

            $query = "UPDATE " . $this->table . " SET " . implode(', ', $set_clause) . " WHERE user_id = :user_id";
            $params[':user_id'] = $user_id;

            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function delete($user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();

        } catch (PDOException $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }
}
?>
