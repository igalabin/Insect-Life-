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

$article_id = intval($_POST['article_id'] ?? 0);
$comment = sanitizeInput($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$article_id || empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

if (strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'error' => 'Comment is too long']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (article_id, user_id, content, is_approved, created_at) 
        VALUES (?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$article_id, $user_id, $comment]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Comment add error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>