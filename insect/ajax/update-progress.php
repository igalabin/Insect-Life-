<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$article_id = intval($input['article_id'] ?? 0);
$progress = floatval($input['progress'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$article_id || $progress < 0 || $progress > 100) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Update reading progress
    $stmt = $pdo->prepare("
        INSERT INTO reading_progress (user_id, article_id, progress_percentage, last_read_at) 
        VALUES (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            progress_percentage = GREATEST(progress_percentage, VALUES(progress_percentage)),
            last_read_at = NOW()
    ");
    $stmt->execute([$user_id, $article_id, $progress]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Progress update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>