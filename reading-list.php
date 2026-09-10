<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php?redirect=reading-list.php');
}

$page_title = 'Reading List - ' . SITE_NAME;
$page_description = 'Your saved articles and reading progress.';
$user_id = $_SESSION['user_id'];

// Handle bookmark toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_bookmark'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security token mismatch.';
    } else {
        $article_id = intval($_POST['article_id']);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        try {
            $pdo = getDBConnection();
            
            // Check if bookmark exists
            $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?");
            $stmt->execute([$user_id, $article_id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Remove bookmark
                $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND article_id = ?");
                $stmt->execute([$user_id, $article_id]);
                $_SESSION['success_message'] = 'Bookmark removed!';
            } else {
                // Add bookmark
                $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, article_id, notes) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $article_id, $notes]);
                $_SESSION['success_message'] = 'Article bookmarked!';
            }
        } catch (Exception $e) {
            error_log("Bookmark error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error updating bookmark.';
        }
    }
}

// Handle notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security token mismatch.';
    } else {
        $bookmark_id = intval($_POST['bookmark_id']);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE bookmarks SET notes = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$notes, $bookmark_id, $user_id]);
            $_SESSION['success_message'] = 'Notes updated!';
        } catch (Exception $e) {
            error_log("Update notes error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error updating notes.';
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';

try {
    $pdo = getDBConnection();
    
    // Get bookmarked articles
    $bookmarks_query = "
        SELECT 
            b.id as bookmark_id,
            b.notes,
            b.created_at as bookmarked_at,
            a.*,
            u.username as author_name,
            rp.progress_percentage,
            rp.last_read_at,
            rp.total_time_spent,
            AVG(ar.rating) as avg_rating,
            COUNT(ar.id) as rating_count
        FROM bookmarks b
        INNER JOIN articles a ON b.article_id = a.id
        LEFT JOIN users u ON a.author_id = u.id
        LEFT JOIN reading_progress rp ON a.id = rp.article_id AND rp.user_id = b.user_id
        LEFT JOIN article_ratings ar ON a.id = ar.article_id
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ";
    $stmt = $pdo->prepare($bookmarks_query);
    $stmt->execute([$user_id]);
    $bookmarks = $stmt->fetchAll();
    
    // Get reading progress (articles with progress > 0 but not bookmarked)
    $progress_query = "
        SELECT 
            rp.*,
            a.*,
            u.username as author_name,
            AVG(ar.rating) as avg_rating,
            COUNT(ar.id) as rating_count
        FROM reading_progress rp
        INNER JOIN articles a ON rp.article_id = a.id
        LEFT JOIN users u ON a.author_id = u.id
        LEFT JOIN article_ratings ar ON a.id = ar.article_id
        LEFT JOIN bookmarks b ON a.id = b.article_id AND b.user_id = rp.user_id
        WHERE rp.user_id = ? AND rp.progress_percentage > 0 AND b.id IS NULL
        GROUP BY rp.id
        ORDER BY rp.last_read_at DESC
    ";
    $stmt = $pdo->prepare($progress_query);
    $stmt->execute([$user_id]);
    $in_progress = $stmt->fetchAll();
    
    // Get completed articles (100% progress)
    $completed = array_filter($in_progress, fn($item) => $item['progress_percentage'] >= 100);
    $in_progress = array_filter($in_progress, fn($item) => $item['progress_percentage'] < 100);
    
} catch (Exception $e) {
    error_log("Reading list error: " . $e->getMessage());
    $bookmarks = [];
    $in_progress = [];
    $completed = [];
}

$additional_css = "
    .reading-list-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: var(--gray-600);
        font-size: 1.1rem;
    }
    
    .filter-tabs {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-bottom: 3rem;
        flex-wrap: wrap;
    }
    
    .filter-tab {
        padding: 0.75rem 1.5rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-lg);
        background: white;
        color: var(--gray-700);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
    }
    
    .filter-tab:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }
    
    .filter-tab.active {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-color: var(--primary-blue);
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        text-align: center;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-size: 0.95rem;
    }
    
    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .articles-list {
        display: grid;
        gap: 1.5rem;
    }
    
    .article-item {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 1.5rem;
        display: grid;
        grid-template-columns: 200px 1fr auto;
        gap: 1.5rem;
        transition: all var(--transition-normal);
    }
    
    .article-item:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }
    
    .article-thumbnail {
        width: 200px;
        height: 150px;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        position: relative;
    }
    
    .article-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .progress-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(0,0,0,0.2);
    }
    
    .progress-bar {
        height: 100%;
        background: var(--secondary-green);
        transition: width var(--transition-normal);
    }
    
    .article-info {
        flex: 1;
    }
    
    .article-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .article-title a {
        color: var(--gray-900);
        text-decoration: none;
    }
    
    .article-title a:hover {
        color: var(--primary-blue);
    }
    
    .article-meta {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: var(--gray-600);
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .article-notes {
        margin-top: 1rem;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--border-radius-md);
        border-left: 3px solid var(--primary-blue);
    }
    
    .notes-header {
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notes-text {
        color: var(--gray-700);
        line-height: 1.6;
    }
    
    .notes-form {
        margin-top: 0.5rem;
    }
    
    .notes-form textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-size: 0.95rem;
        resize: vertical;
        min-height: 80px;
    }
    
    .notes-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .article-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-end;
    }
    
    .progress-info {
        text-align: right;
        font-size: 0.9rem;
        color: var(--gray-600);
    }
    
    .progress-percentage {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--secondary-green);
    }
    
    .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
    }
    
    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .article-item {
            grid-template-columns: 1fr;
        }
        
        .article-thumbnail {
            width: 100%;
        }
        
        .article-actions {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
";

include 'includes/header.php';
?>

<div class="reading-list-container">
    <div class="page-header">
        <h1><i class="fas fa-bookmark"></i> My Reading List</h1>
        <p>Track your reading progress and manage your saved articles</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($bookmarks); ?></div>
            <div class="stat-label">Bookmarked</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($in_progress); ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($completed); ?></div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $total_time = array_sum(array_column($in_progress, 'total_time_spent')) + 
                              array_sum(array_column($completed, 'total_time_spent'));
                echo round($total_time / 60); 
                ?>
            </div>
            <div class="stat-label">Minutes Read</div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            All Articles
        </a>
        <a href="?filter=bookmarks" class="filter-tab <?php echo $filter === 'bookmarks' ? 'active' : ''; ?>">
            <i class="fas fa-bookmark"></i> Bookmarks
        </a>
        <a href="?filter=in_progress" class="filter-tab <?php echo $filter === 'in_progress' ? 'active' : ''; ?>">
            <i class="fas fa-book-reader"></i> In Progress
        </a>
        <a href="?filter=completed" class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Completed
        </a>
    </div>
    
    <?php 
    // Combine and filter articles based on selected filter
    $display_articles = [];
    
    if ($filter === 'all' || $filter === 'bookmarks') {
        foreach ($bookmarks as $item) {
            $item['type'] = 'bookmark';
            $display_articles[] = $item;
        }
    }
    
    if ($filter === 'all' || $filter === 'in_progress') {
        foreach ($in_progress as $item) {
            $item['type'] = 'in_progress';
            $display_articles[] = $item;
        }
    }
    
    if ($filter === 'all' || $filter === 'completed') {
        foreach ($completed as $item) {
            $item['type'] = 'completed';
            $display_articles[] = $item;
        }
    }
    ?>
    
    <?php if (empty($display_articles)): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3>No articles yet</h3>
            <p>Start reading articles from our library to see them here!</p>
            <a href="library.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-book-open"></i> Browse Library
            </a>
        </div>
    <?php else: ?>
        <!-- Bookmarks Section -->
        <?php if (($filter === 'all' || $filter === 'bookmarks') && !empty($bookmarks)): ?>
        <div style="margin-bottom: 3rem;">
            <h2 class="section-title">
                <i class="fas fa-bookmark"></i>
                Bookmarked Articles
            </h2>
            <div class="articles-list">
                <?php foreach ($bookmarks as $article): ?>
                    <?php include 'includes/reading-list-item.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- In Progress Section -->
        <?php if (($filter === 'all' || $filter === 'in_progress') && !empty($in_progress)): ?>
        <div style="margin-bottom: 3rem;">
            <h2 class="section-title">
                <i class="fas fa-book-reader"></i>
                Currently Reading
            </h2>
            <div class="articles-list">
                <?php foreach ($in_progress as $article): ?>
                    <?php $article['type'] = 'in_progress'; ?>
                    <?php include 'includes/reading-list-item.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Completed Section -->
        <?php if (($filter === 'all' || $filter === 'completed') && !empty($completed)): ?>
        <div style="margin-bottom: 3rem;">
            <h2 class="section-title">
                <i class="fas fa-check-circle"></i>
                Completed
            </h2>
            <div class="articles-list">
                <?php foreach ($completed as $article): ?>
                    <?php $article['type'] = 'completed'; ?>
                    <?php include 'includes/reading-list-item.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleNotes(bookmarkId) {
    const notesDiv = document.getElementById('notes-text-' + bookmarkId);
    const formDiv = document.getElementById('notes-form-' + bookmarkId);
    
    if (formDiv.style.display === 'none') {
        notesDiv.style.display = 'none';
        formDiv.style.display = 'block';
    } else {
        notesDiv.style.display = 'block';
        formDiv.style.display = 'none';
    }
}

function cancelNotes(bookmarkId) {
    const notesDiv = document.getElementById('notes-text-' + bookmarkId);
    const formDiv = document.getElementById('notes-form-' + bookmarkId);
    notesDiv.style.display = 'block';
    formDiv.style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>