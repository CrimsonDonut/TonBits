<?php
/**
 * Database Migration Runner
 * Applies SQL migration files from the migrations directory
 * Usage: Run this file to automatically apply all pending migrations
 */

class MigrationRunner {
    private $conn;
    private $migrations_table = 'migrations';
    private $migrations_dir;

    public function __construct($db_conn, $migrations_dir) {
        $this->conn = $db_conn;
        $this->migrations_dir = $migrations_dir;
        $this->ensureMigrationsTable();
    }

    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTable() {
        try {
            $query = "CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);
        } catch (PDOException $e) {
            echo "Error creating migrations table: " . $e->getMessage();
        }
    }

    /**
     * Get list of already executed migrations
     */
    private function getExecutedMigrations() {
        try {
            $query = "SELECT migration_name FROM {$this->migrations_table}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $executed = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $executed[] = $row['migration_name'];
            }
            return $executed;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get list of migration files from directory
     */
    private function getPendingMigrations() {
        if (!is_dir($this->migrations_dir)) {
            return [];
        }

        $files = scandir($this->migrations_dir);
        $migrations = [];
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && substr($file, -4) === '.sql') {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Run all pending migrations
     */
    public function runMigrations() {
        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "No migration files found.\n";
            return ['success' => true, 'message' => 'No migrations to run'];
        }

        $results = [
            'executed' => [],
            'failed' => [],
            'skipped' => []
        ];

        foreach ($pending as $migration_file) {
            if (in_array($migration_file, $executed)) {
                $results['skipped'][] = $migration_file;
                continue;
            }

            try {
                $sql_file = $this->migrations_dir . '/' . $migration_file;
                $sql = file_get_contents($sql_file);

                // Execute the SQL file
                $this->conn->exec($sql);

                // Record the migration as executed
                $query = "INSERT INTO {$this->migrations_table} (migration_name) VALUES (:name)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':name', $migration_file);
                $stmt->execute();

                $results['executed'][] = $migration_file;
                echo "✓ Executed: $migration_file\n";

            } catch (PDOException $e) {
                $results['failed'][] = [
                    'file' => $migration_file,
                    'error' => $e->getMessage()
                ];
                echo "✗ Failed: $migration_file - " . $e->getMessage() . "\n";
            }
        }

        return [
            'success' => count($results['failed']) === 0,
            'results' => $results
        ];
    }

    /**
     * Get migration status
     */
    public function getStatus() {
        $executed = $this->getExecutedMigrations();
        $all = $this->getPendingMigrations();
        $pending = array_diff($all, $executed);

        return [
            'total' => count($all),
            'executed' => count($executed),
            'pending' => count($pending),
            'executed_migrations' => $executed,
            'pending_migrations' => $pending
        ];
    }
}

// Usage example (uncomment to use)
/*
require_once __DIR__ . '/Database.php';

$db = new Database();
$conn = $db->connect();

if ($conn) {
    $migrations_dir = __DIR__ . '/migrations';
    $runner = new MigrationRunner($conn, $migrations_dir);
    
    echo "=== Database Migration Status ===\n";
    $status = $runner->getStatus();
    echo "Total migrations: " . $status['total'] . "\n";
    echo "Executed: " . $status['executed'] . "\n";
    echo "Pending: " . $status['pending'] . "\n\n";

    echo "=== Running Migrations ===\n";
    $result = $runner->runMigrations();
    
    echo "\n=== Migration Summary ===\n";
    echo "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "Database connection failed\n";
}
*/
?>
