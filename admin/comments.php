<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Manage Comments - Admin";
$success_message = '';
$error_message = '';

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_message = "Comment deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting comment: " . $e->getMessage();
    }
}

// Handle Approval Toggle
if (isset($_POST['toggle_approval'])) {
    $comment_id = intval($_POST['comment_id']);
    $new_status = intval($_POST['is_approved']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE comments SET is_approved = ? WHERE id = ?");
        $stmt->execute([$new_status, $comment_id]);
        $success_message = "Comment status updated!";
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(c.content LIKE :search OR u.username LIKE :search OR a.title LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "c.is_approved = :status";
        $params['status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $count_query = "SELECT COUNT(*) FROM comments c $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_comments = $count_stmt->fetchColumn();
    
    $offset = ($page - 1) * $per_page;
    $query = "
        SELECT c.*, u.username, a.title as article_title
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN articles a ON c.article_id = a.id
        $where_clause
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $comments = $stmt->fetchAll();
    
    $total_pages = ceil($total_comments / $per_page);
    
} catch (Exception $e) {
    error_log("Comments admin error: " . $e->getMessage());
    $comments = [];
    $total_comments = 0;
    $total_pages = 0;
}

$additional_css = "
    .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .filters-section { background: white; padding: 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem; }
    .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
    .comments-list { display: flex; flex-direction: column; gap: 1rem; }
    .comment-card { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 1.5rem; }
    .comment-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .comment-user { display: flex; align-items: center; gap: 0.75rem; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-blue), var(--secondary-green)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
    .user-info { flex: 1; }
    .username { font-weight: 600; color: var(--gray-900); }
    .comment-meta { font-size: 0.85rem; color: var(--gray-500); }
    .comment-content { color: var(--gray-700); line-height: 1.6; margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius-md); }
    .comment-article { font-size: 0.9rem; color: var(--gray-600); margin-bottom: 1rem; }
    .comment-article a { color: var(--primary-blue); text-decoration: none; }
    .comment-actions { display: flex; gap: 0.5rem; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; }
    .status-approved { background: var(--secondary-green); color: white; }
    .status-pending { background: #fbbf24; color: white; }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<div class="admin-container">
    <div class="page-header">
        <h1>Manage Comments</h1>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="filters-section">
        <form method="GET">
            <div class="filters-grid">
                <input type="text" name="search" class="form-control" placeholder="Search comments..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Approved</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="comments.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($comments)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: var(--border-radius-lg);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
            <p>No comments found.</p>
        </div>
    <?php else: ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <div class="comment-user">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($comment['username'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <div class="username"><?php echo htmlspecialchars($comment['username']); ?></div>
                                <div class="comment-meta"><?php echo formatTimeAgo($comment['created_at']); ?></div>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                            <input type="hidden" name="is_approved" value="<?php echo $comment['is_approved'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_approval" class="status-badge <?php echo $comment['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo $comment['is_approved'] ? 'Approved' : 'Pending'; ?>
                            </button>
                        </form>
                    </div>
                    
                    <div class="comment-article">
                        On article: <a href="../article.php?id=<?php echo $comment['article_id']; ?>" target="_blank">
                            <?php echo htmlspecialchars($comment['article_title']); ?>
                        </a>
                    </div>
                    
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                    
                    <div class="comment-actions">
                        <a href="../article.php?id=<?php echo $comment['article_id']; ?>#comment-<?php echo $comment['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View
                        </a>
                        <a href="?delete=<?php echo $comment['id']; ?>&confirm=1" class="btn btn-sm btn-danger" onclick="return confirm('Delete this comment?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter])); ?>">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter])); ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

