<?php
// config.php - Fixed version
define('DEVELOPMENT_MODE', true);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'insect_life');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('SITE_URL', 'http://localhost/insect');
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

// FUNCTION DEFINITIONS FIRST
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

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return floor($time/2592000) . ' months ago';
}

function formatReadingTime($minutes) {
    if ($minutes < 1) return '< 1 min read';
    if ($minutes == 1) return '1 min read';
    if ($minutes < 60) return $minutes . ' min read';
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    return $remaining_minutes == 0 ? $hours . ' hour read' : $hours . 'h ' . $remaining_minutes . 'm read';
}

function logActivity($action, $details = null, $user_id = null) {
    error_log("Activity: $action");
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
            die("<h1>Cannot Create Database</h1><p>Error: " . $e->getMessage() . "</p><p>Make sure MySQL is running in XAMPP Control Panel.</p>");
        }
        return false;
    }
}

function createTablesIfNotExist($pdo) {
    try {
        // Check if tables already exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            return; // Tables already exist
        }
        
        // Create users table
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
        
        $pdo->exec("
            CREATE TABLE user_interests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                interest VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE articles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(200) NOT NULL,
                slug VARCHAR(200) UNIQUE NOT NULL,
                content LONGTEXT NOT NULL,
                excerpt TEXT,
                featured_image VARCHAR(255),
                scientific_name VARCHAR(100),
                insect_order VARCHAR(50),
                insect_family VARCHAR(50),
                category ENUM('anatomy','behavior','ecology','conservation','identification','general') DEFAULT 'general',
                difficulty_level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
                estimated_read_time INT DEFAULT 5,
                author_id INT NOT NULL,
                status ENUM('draft','published','archived') DEFAULT 'draft',
                views_count INT DEFAULT 0,
                published_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE reading_progress (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                article_id INT NOT NULL,
                progress_percentage DECIMAL(5,2) DEFAULT 0,
                last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY user_article (user_id, article_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE article_ratings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                article_id INT NOT NULL,
                rating TINYINT CHECK (rating BETWEEN 1 AND 5),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_article_rating (user_id, article_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE badges (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                icon VARCHAR(50),
                category ENUM('reading','knowledge','exploration','social','special') DEFAULT 'reading',
                requirements JSON NOT NULL,
                points INT DEFAULT 10,
                rarity ENUM('common','uncommon','rare','epic','legendary') DEFAULT 'common',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE user_badges (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                badge_id INT NOT NULL,
                earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_badge (user_id, badge_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE assessments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                category VARCHAR(50),
                difficulty_level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
                total_questions INT NOT NULL,
                passing_score DECIMAL(5,2) DEFAULT 70.00,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE bookmarks (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                article_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_article_bookmark (user_id, article_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE comments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                article_id INT NOT NULL,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                is_approved TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, email, password_hash, first_name, last_name, user_type, is_active, email_verified) 
                    VALUES ('admin', 'admin@insectlife.com', '$admin_password', 'Admin', 'User', 'admin', 1, 1)");
        
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
    
    try {
        if (!ensureDatabaseExists()) {
            throw new Exception("Cannot create database");
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        createTablesIfNotExist($pdo);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        
        if (DEVELOPMENT_MODE) {
            die("<h1>Database Error</h1><p>" . $e->getMessage() . "</p><p><strong>Fix:</strong> Make sure MySQL is running in XAMPP Control Panel.</p>");
        } else {
            die("Database connection failed.");
        }
    }
}

// Initialize
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
try {
    getDBConnection();
} catch (Exception $e) {
    if (DEVELOPMENT_MODE) {
        die("<h1>Initialization Error</h1><p>" . $e->getMessage() . "</p>");
    }
}