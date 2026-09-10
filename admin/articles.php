<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Manage Articles - Admin";
$success_message = '';
$error_message = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $pdo = getDBConnection();
        
        // Temporarily disable foreign key checks so missing tables won't crash execution
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Delete article and existing linked records
        $pdo->prepare("DELETE FROM comments WHERE article_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM article_ratings WHERE article_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM bookmarks WHERE article_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM reading_progress WHERE article_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$delete_id]);
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $success_message = "Article deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Failed to delete article: " . $e->getMessage();
    }
}

// Handle Status Change
if (isset($_POST['change_status'])) {
    $article_id = intval($_POST['article_id']);
    $new_status = sanitizeInput($_POST['status']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE articles SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $article_id]);
        $success_message = "Article status updated!";
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// Build query
try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
    $where_conditions[] = "(a.title LIKE :search OR a.scientific_name LIKE :search OR a.content LIKE :search OR u.username LIKE :search)";
    $params['search'] = "%$search%";
}
    
    if ($status_filter) {
        $where_conditions[] = "status = :status";
        $params['status'] = $status_filter;
    }
    
    if ($category_filter) {
        $where_conditions[] = "category = :category";
        $params['category'] = $category_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM articles $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_articles = $count_stmt->fetchColumn();
    
    // Get articles
    $offset = ($page - 1) * $per_page;
    $query = "
        SELECT a.*, u.username as author_name
        FROM articles a
        LEFT JOIN users u ON a.author_id = u.id
        $where_clause
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    $total_pages = ceil($total_articles / $per_page);
    
} catch (Exception $e) {
    error_log("Articles admin error: " . $e->getMessage());
    $articles = [];
    $total_articles = 0;
    $total_pages = 0;
}

$additional_css = "
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .filters-section {
        background: white;
        padding: 1.5rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 1rem;
    }
    
    .table-container {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead {
        background: var(--gray-50);
    }
    
    .data-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
    }
    
    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .data-table tr:hover {
        background: var(--gray-50);
    }
    
    .action-btns {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .article-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: var(--border-radius-md);
    }
    
    .status-form {
        display: inline-flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .status-select {
        padding: 0.25rem 0.5rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius-sm);
    }
    
    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            min-width: 800px;
        }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/footet.css">
<div class="admin-container">
    <div class="page-header">
        <h1>Manage Articles</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="article-edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Article
            </a>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET">
            <div class="filters-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search articles..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <option value="anatomy" <?php echo $category_filter === 'anatomy' ? 'selected' : ''; ?>>Anatomy</option>
                        <option value="behavior" <?php echo $category_filter === 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                        <option value="ecology" <?php echo $category_filter === 'ecology' ? 'selected' : ''; ?>>Ecology</option>
                        <option value="conservation" <?php echo $category_filter === 'conservation' ? 'selected' : ''; ?>>Conservation</option>
                        <option value="identification" <?php echo $category_filter === 'identification' ? 'selected' : ''; ?>>Identification</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="articles.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Articles Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                            No articles found. <a href="article-edit.php">Create your first article</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?php echo $article['id']; ?></td>
                            <td>
                                <?php if ($article['featured_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                         class="article-thumb" alt="Article thumbnail">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: var(--gray-200); border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center;">
                                        🐛
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($article['title']); ?></strong><br>
                                <?php if ($article['scientific_name']): ?>
                                    <em style="font-size: 0.85rem; color: var(--gray-600);">
                                        <?php echo htmlspecialchars($article['scientific_name']); ?>
                                    </em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($article['author_name']); ?></td>
                            <td><?php echo ucfirst($article['category']); ?></td>
                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="draft" <?php echo $article['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $article['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="archived" <?php echo $article['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                    <button type="submit" name="change_status" style="display: none;">Update</button>
                                </form>
                            </td>
                            <td><?php echo number_format($article['views_count']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($article['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="../article.php?id=<?php echo $article['id']; ?>" 
                                       class="btn btn-sm btn-info" target="_blank" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="article-edit.php?id=<?php echo $article['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $article['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this article?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter, 'category' => $category_filter])); ?>">
                    ← Previous
                </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter, 'category' => $category_filter])); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'status' => $status_filter, 'category' => $category_filter])); ?>">
                    Next →
                </a>
            <?php endif; ?>
        </div>
        
        <p style="text-align: center; color: var(--gray-600); margin-top: 1rem;">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> - <?php echo min($page * $per_page, $total_articles); ?> 
            of <?php echo number_format($total_articles); ?> articles
        </p>
    <?php endif; ?>
</div>

