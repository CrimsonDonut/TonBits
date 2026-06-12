<?php

class RememberToken {
    private $conn;
    private $table = 'remember_tokens';

    public $token_id;
    public $user_id;
    public $token_hash;
    public $expires_at;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new remember token
     */
    public function create($user_id, $expires_at = null) {
        try {
            // Generate a random token
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);

            // If no expiration provided, set to 30 days from now
            if (!$expires_at) {
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            }

            $query = "INSERT INTO " . $this->table . " 
                      (user_id, token_hash, expires_at) 
                      VALUES (:user_id, :token_hash, :expires_at)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':token_hash', $token_hash);
            $stmt->bindParam(':expires_at', $expires_at);

            if ($stmt->execute()) {
                return [
                    'token_id' => $this->conn->lastInsertId(),
                    'token' => $token,
                    'token_hash' => $token_hash
                ];
            }
            return false;

        } catch (PDOException $e) {
            throw new Exception("Error creating token: " . $e->getMessage());
        }
    }

    /**
     * Verify a token
     */
    public function verify($token_hash, $user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE token_hash = :token_hash 
                      AND user_id = :user_id 
                      AND expires_at > NOW()
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token_hash', $token_hash);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get token by hash
     */
    public function getByHash($token_hash) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE token_hash = :token_hash 
                      AND expires_at > NOW()
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token_hash', $token_hash);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get all tokens for a user
     */
    public function getTokensByUser($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $tokens = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $tokens[] = $row;
            }

            return $tokens;

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Delete a token
     */
    public function delete($token_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE token_id = :token_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token_id', $token_id);
            return $stmt->execute();

        } catch (PDOException $e) {
            throw new Exception("Error deleting token: " . $e->getMessage());
        }
    }

    /**
     * Delete all expired tokens
     */
    public function deleteExpired() {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE expires_at < NOW()";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete all tokens for a user
     */
    public function deleteUserTokens($user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();

        } catch (PDOException $e) {
            throw new Exception("Error deleting user tokens: " . $e->getMessage());
        }
    }
}
?>
