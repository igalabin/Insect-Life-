<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";

// Test 1: PHP is working
echo "<h2>✓ PHP is working</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test 2: Check if files exist
echo "<h2>File Check</h2>";
$files = ['config.php', 'includes/header.php', 'includes/footer.php', 'index.php'];
foreach ($files as $file) {
    echo $file . ": " . (file_exists($file) ? "✓ EXISTS" : "✗ MISSING") . "<br>";
}

// Test 3: Try to load config
echo "<h2>Loading Config</h2>";
try {
    require_once 'config.php';
    echo "✓ Config loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "<br>";
}

// Test 4: Test database
echo "<h2>Database Test</h2>";
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✓ MySQL connection works<br>";
    
    $stmt = $pdo->query("SHOW DATABASES LIKE 'insect_life'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database 'insect_life' exists<br>";
        
        $pdo2 = new PDO("mysql:host=localhost;dbname=insect_life", "root", "");
        $tables = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "<br>";
        if (count($tables) > 0) {
            echo "Tables: " . implode(', ', $tables) . "<br>";
        }
    } else {
        echo "✗ Database 'insect_life' does NOT exist<br>";
    }
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test 5: Check what error is in index.php
echo "<h2>Testing index.php</h2>";
ob_start();
try {
    include 'index.php';
    echo "✓ index.php loaded without fatal errors<br>";
} catch (Exception $e) {
    echo "✗ index.php error: " . $e->getMessage() . "<br>";
}
ob_end_clean();

echo "<h2>Done</h2>";
echo "<p><a href='index.php'>Try loading index.php</a></p>";
?>