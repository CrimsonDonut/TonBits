<?php

class UserAddress {
    private $conn;
    private $table = 'user_addresses';

    public $address_id;
    public $user_id;
    public $street_address;
    public $barangay;
    public $city;
    public $province;
    public $is_default;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new address for a user
     */
    public function create($user_id, $street_address, $barangay, $city, $province, $is_default = false) {
        try {
            // If this is the default address, unset any other default addresses
            if ($is_default) {
                $this->setNonDefault($user_id);
            }

            $query = "INSERT INTO " . $this->table . " 
                      (user_id, street_address, barangay, city, province, is_default) 
                      VALUES (:user_id, :street_address, :barangay, :city, :province, :is_default)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':street_address', $street_address);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_default', $is_default);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;

        } catch (PDOException $e) {
            throw new Exception("Error creating address: " . $e->getMessage());
        }
    }

    /**
     * Get all addresses for a user
     */
    public function getAddressesByUser($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE user_id = :user_id 
                      ORDER BY is_default DESC, created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $addresses = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $addresses[] = $row;
            }

            return $addresses;

        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get default address for a user
     */
    public function getDefaultAddress($user_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE user_id = :user_id AND is_default = 1 
                      LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get address by ID
     */
    public function getAddressById($address_id) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE address_id = :address_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':address_id', $address_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ);

        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Update an address
     */
    public function update($address_id, $user_id, $street_address, $barangay, $city, $province, $is_default = false) {
        try {
            // If this is the default address, unset any other default addresses
            if ($is_default) {
                $this->setNonDefault($user_id);
            }

            $query = "UPDATE " . $this->table . " 
                      SET street_address = :street_address, 
                          barangay = :barangay, 
                          city = :city, 
                          province = :province, 
                          is_default = :is_default 
                      WHERE address_id = :address_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':address_id', $address_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':street_address', $street_address);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_default', $is_default);

            return $stmt->execute();

        } catch (PDOException $e) {
            throw new Exception("Error updating address: " . $e->getMessage());
        }
    }

    /**
     * Delete an address
     */
    public function delete($address_id, $user_id) {
        try {
            $query = "DELETE FROM " . $this->table . " 
                      WHERE address_id = :address_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':address_id', $address_id);
            $stmt->bindParam(':user_id', $user_id);

            return $stmt->execute();

        } catch (PDOException $e) {
            throw new Exception("Error deleting address: " . $e->getMessage());
        }
    }

    /**
     * Set all addresses for a user to non-default
     */
    private function setNonDefault($user_id) {
        try {
            $query = "UPDATE " . $this->table . " SET is_default = 0 WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Format address as a readable string
     */
    public function formatAddress($address) {
        return $address->street_address . ', ' . $address->barangay . 
               ', ' . $address->city . ', ' . $address->province;
    }
}
?>
