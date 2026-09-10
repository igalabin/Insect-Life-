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
$rating = intval($input['rating'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$article_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Insert or update rating
    $stmt = $pdo->prepare("
        INSERT INTO article_ratings (user_id, article_id, rating, created_at) 
        VALUES (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating),
            created_at = NOW()
    ");
    $stmt->execute([$user_id, $article_id, $rating]);
    
    // Get updated average rating
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
        FROM article_ratings 
        WHERE article_id = ?
    ");
    $stmt->execute([$article_id]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'avg_rating' => round($result['avg_rating'], 1),
        'rating_count' => $result['rating_count']
    ]);
    
} catch (Exception $e) {
    error_log("Rating error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}