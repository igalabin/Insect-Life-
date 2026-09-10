<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$article_id = intval($data['article_id'] ?? 0);
$progress = floatval($data['progress'] ?? 0);
$time_spent = intval($data['time_spent'] ?? 0);

if (!$article_id || $progress < 0 || $progress > 100) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if progress record exists
    $stmt = $pdo->prepare("SELECT id, total_time_spent FROM reading_progress WHERE user_id = ? AND article_id = ?");
    $stmt->execute([$user_id, $article_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing progress
        $new_time = $existing['total_time_spent'] + $time_spent;
        $stmt = $pdo->prepare("
            UPDATE reading_progress 
            SET progress_percentage = ?, 
                total_time_spent = ?,
                last_read_at = NOW()
            WHERE user_id = ? AND article_id = ?
        ");
        $stmt->execute([$progress, $new_time, $user_id, $article_id]);
    } else {
        // Insert new progress
        $stmt = $pdo->prepare("
            INSERT INTO reading_progress 
            (user_id, article_id, progress_percentage, total_time_spent, first_read_at, last_read_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $article_id, $progress, $time_spent]);
    }
    
    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'time_spent' => $time_spent
    ]);
    
} catch (Exception $e) {
    error_log("Progress update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

---

<?php
// ====================
// api/toggle-bookmark.php
// ====================
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$article_id = intval($data['article_id'] ?? 0);

if (!$article_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid article ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if bookmark exists
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?");
    $stmt->execute([$user_id, $article_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$user_id, $article_id]);
        $bookmarked = false;
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, article_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $article_id]);
        $bookmarked = true;
    }
    
    echo json_encode([
        'success' => true,
        'bookmarked' => $bookmarked
    ]);
    
} catch (Exception $e) {
    error_log("Bookmark toggle error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

---

<?php
// ====================
// api/submit-comment.php
// ====================
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$article_id = intval($data['article_id'] ?? 0);
$content = sanitizeInput($data['content'] ?? '');

if (!$article_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (article_id, user_id, content, is_approved)
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$article_id, $user_id, $content]);
    $comment_id = $pdo->lastInsertId();
    
    // Get user info for response
    $stmt = $pdo->prepare("
        SELECT u.username, u.first_name, u.last_name, c.created_at
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $comment_id,
            'username' => $comment['username'],
            'first_name' => $comment['first_name'],
            'last_name' => $comment['last_name'],
            'content' => $content,
            'created_at' => $comment['created_at'],
            'time_ago' => formatTimeAgo($comment['created_at'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Comment submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

---

<?php
// ====================
// api/get-comments.php
// ====================
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$article_id = intval($_GET['article_id'] ?? 0);

if (!$article_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid article ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.article_id = ? AND c.is_approved = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$article_id]);
    $comments = $stmt->fetchAll();
    
    // Format comments
    $formatted_comments = array_map(function($comment) {
        return [
            'id' => $comment['id'],
            'username' => $comment['username'],
            'first_name' => $comment['first_name'],
            'last_name' => $comment['last_name'],
            'content' => $comment['content'],
            'created_at' => $comment['created_at'],
            'time_ago' => formatTimeAgo($comment['created_at'])
        ];
    }, $comments);
    
    echo json_encode([
        'success' => true,
        'comments' => $formatted_comments,
        'count' => count($formatted_comments)
    ]);
    
} catch (Exception $e) {
    error_log("Get comments error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

---

<?php
// ====================
// api/delete-comment.php
// ====================
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$comment_id = intval($data['comment_id'] ?? 0);

if (!$comment_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if user owns the comment or is admin
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }
    
    $is_admin = $_SESSION['user_type'] === 'admin';
    $is_owner = $comment['user_id'] == $user_id;
    
    if (!$is_admin && !$is_owner) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    // Delete comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Delete comment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>