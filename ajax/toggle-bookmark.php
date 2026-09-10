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
$user_id = $_SESSION['user_id'];

if (!$article_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid article ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if bookmark exists
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?");
    $stmt->execute([$user_id, $article_id]);
    $bookmark = $stmt->fetch();
    
    if ($bookmark) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$user_id, $article_id]);
        $bookmarked = false;
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, article_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $article_id]);
        $bookmarked = true;
    }
    
    echo json_encode(['success' => true, 'bookmarked' => $bookmarked]);
    
} catch (Exception $e) {
    error_log("Bookmark toggle error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>