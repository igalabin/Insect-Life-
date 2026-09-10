<?php
// config.php - Docker Optimized Version
define('DEVELOPMENT_MODE', true);

// Database configuration (Docker service name is 'db')
define('DB_HOST', 'db');
define('DB_NAME', 'insect_life');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('SITE_URL', 'http://localhost:8000');
define('SITE_NAME', 'Insect Life');
define('ADMIN_EMAIL', 'admin@insectlife.com');

// Security settings
define('CSRF_TOKEN_EXPIRY', 3600);
define('SESSION_TIMEOUT', 7200);

// Error reporting
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);

$pdo = null;

// Helper Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirectTo($url) {
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
    } else {
        header("Location: $url");
    }
    exit();
}

function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && 
           isset($_SESSION['csrf_token_time']) &&
           hash_equals($_SESSION['csrf_token'], $token) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_EXPIRY;
}

function ensureDatabaseExists() {
    try {
        $tempPdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        error_log("Database creation error: " . $e->getMessage());
        if (DEVELOPMENT_MODE) {
            die("<h1>Cannot Create Database</h1><p>Error: " . $e->getMessage() . "</p><p>Make sure the Docker <code>db</code> container is running.</p>");
        }
        return false;
    }
}

function createTablesIfNotExist($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            return;
        }
        
        $pdo->exec("
            CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                experience_level ENUM('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
                user_type ENUM('user','admin') DEFAULT 'user',
                is_active TINYINT(1) DEFAULT 1,
                email_verified TINYINT(1) DEFAULT 0,
                email_verification_token VARCHAR(100),
                newsletter_subscribed TINYINT(1) DEFAULT 0,
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT IGNORE INTO users 
            (username, email, password_hash, first_name, last_name, experience_level, user_type) 
            VALUES 
            ('admin', 'admin@insectlife.com', '$admin_password', 'Admin', 'User', 'beginner', 'admin')");
        
    } catch (PDOException $e) {
        error_log("Table creation error: " . $e->getMessage());
        if (DEVELOPMENT_MODE) {
            die("<h1>Cannot Create Tables</h1><p>Error: " . $e->getMessage() . "</p>");
        }
    }
}

function getDBConnection() {
    global $pdo;

    if ($pdo !== null) {
        return $pdo;
    }

    ensureDatabaseExists();

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        createTablesIfNotExist($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        if (DEVELOPMENT_MODE) {
            die("<h1>Database Connection Error</h1><p>" . $e->getMessage() . "</p><p>Ensure the Docker <code>db</code> service is running.</p>");
        }
        return null;
    }
}

// Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Auto-connect to database
getDBConnection();