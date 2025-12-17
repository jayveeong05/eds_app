<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // 1. Try to get config from Environment Variables (Vercel/Neon)
        // Neon uses "POSTGRES_*" or "PG*" usually. We'll check for standard names.
        $this->host = getenv('POSTGRES_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
        $this->db_name = getenv('POSTGRES_DATABASE') ?: (getenv('DB_NAME') ?: 'eds_db');
        $this->username = getenv('POSTGRES_USER') ?: (getenv('DB_USER') ?: 'root');
        $this->password = getenv('POSTGRES_PASSWORD') ?: (getenv('DB_PASS') ?: '');
        
        // Vercel/Neon often requires SSL
        $sslMode = getenv('POSTGRES_SSLMODE') ?: 'require'; 

        try {
            // Detect Environment: Cloud (Postgres) vs Local (MySQL)
            // If POSTGRES_HOST is set, we assume we are using Neon/Postgres
            if (getenv('POSTGRES_HOST')) {
                // Use PostgreSQL (Neon)
                $dsn = "pgsql:host=" . $this->host . ";port=5432;dbname=" . $this->db_name . ";sslmode=" . $sslMode;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } else {
                // Local Development (MySQL) fallback
                // Note: For local dev to keep working, you might need to set DB_HOST=localhost in your local environment 
                // or rely on these defaults if they match your local setup
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->exec("set names utf8");
            }
            
        } catch(PDOException $exception) {
            // For security, don't echo raw connection errors in production
            // error_log("Connection error: " . $exception->getMessage());
            
            // Echo a generic JSON error if it's an API call
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                echo json_encode(["message" => "Database connection failed: " . $exception->getMessage()]);
            } else {
                echo "Database Connection Error: " . $exception->getMessage();
            }
            exit; // Stop execution
        }

        return $this->conn;
    }
}
?>
