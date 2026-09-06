<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php?redirect=admin/index.php');
}

$page_title = "Admin Dashboard - Insect Life";

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Total counts
    $stats = [];
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_articles'] = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $stats['published_articles'] = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
    $stats['total_comments'] = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['pending_comments'] = $pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0")->fetchColumn();
    $stats['total_assessments'] = $pdo->query("SELECT COUNT(*) FROM assessments")->fetchColumn();
    $stats['total_badges'] = $pdo->query("SELECT COUNT(*) FROM badges")->fetchColumn();
    
    // Recent activity
    $recent_users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recent_articles = $pdo->query("SELECT id, title, status, created_at FROM articles ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recent_comments = $pdo->query("
        SELECT c.*, u.username, a.title as article_title 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN articles a ON c.article_id = a.id 
        ORDER BY c.created_at DESC LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $stats = [];
    $recent_users = [];
    $recent_articles = [];
    $recent_comments = [];
}

$additional_css = "
    body {
        background: var(--gray-50);
    }
    
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .admin-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .admin-nav {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 1rem;
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .nav-link {
        padding: 0.75rem 1.5rem;
        background: var(--gray-100);
        color: var(--gray-700);
        text-decoration: none;
        border-radius: var(--border-radius-md);
        font-weight: 500;
        transition: all var(--transition-fast);
    }
    
    .nav-link:hover, .nav-link.active {
        background: var(--primary-blue);
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), var(--secondary-green));
    }
    
    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-weight: 500;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
    }
    
    .content-section {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .section-header {
        padding: 1.5rem;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900);
    }
    
    .section-body {
        padding: 1.5rem;
    }
    
    .list-item {
        padding: 1rem 0;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .list-item:last-child {
        border-bottom: none;
    }
    
    .item-title {
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .item-meta {
        font-size: 0.85rem;
        color: var(--gray-500);
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .badge-success { background: var(--secondary-green); color: white; }
    .badge-warning { background: var(--accent-orange); color: white; }
    .badge-danger { background: #ef4444; color: white; }
    .badge-info { background: var(--primary-blue); color: white; }
    
    @media (max-width: 768px) {
        .admin-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">


<div class="admin-container">
    <div class="admin-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Manage your Insect Life platform</p>
        </div>
        <div>
            <a href="../index.php" class="btn btn-secondary">View Site</a>
        </div>
    </div>

    <!-- Navigation -->
    <div class="admin-nav">
        <a href="index.php" class="nav-link active">Dashboard</a>
        <a href="articles.php" class="nav-link">Articles</a>
        <a href="users.php" class="nav-link">Users</a>
        <a href="assessments.php" class="nav-link">Assessments</a>
        <a href="badges.php" class="nav-link">Badges</a>
        <a href="comments.php" class="nav-link">Comments</a>
        <a href="settings.php" class="nav-link">Settings</a>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo number_format($stats['published_articles'] ?? 0); ?></div>
            <div class="stat-label">Published Articles</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-number"><?php echo number_format($stats['total_comments'] ?? 0); ?></div>
            <div class="stat-label">Total Comments</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-number"><?php echo number_format($stats['total_badges'] ?? 0); ?></div>
            <div class="stat-label">Active Badges</div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="content-grid">
        <!-- Recent Users -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Recent Users</h2>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php if (empty($recent_users)): ?>
                    <p style="text-align: center; color: var(--gray-500);">No users yet</p>
                <?php else: ?>
                    <?php foreach ($recent_users as $user): ?>
                        <div class="list-item">
                            <div class="item-title"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="item-meta">
                                <?php echo htmlspecialchars($user['email']); ?> • 
                                <?php echo formatTimeAgo($user['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Articles -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Recent Articles</h2>
                <a href="articles.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php if (empty($recent_articles)): ?>
                    <p style="text-align: center; color: var(--gray-500);">No articles yet</p>
                <?php else: ?>
                    <?php foreach ($recent_articles as $article): ?>
                        <div class="list-item">
                            <div class="item-title"><?php echo htmlspecialchars($article['title']); ?></div>
                            <div class="item-meta">
                                <span class="badge badge-<?php echo $article['status'] === 'published' ? 'success' : ($article['status'] === 'draft' ? 'warning' : 'info'); ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span> • 
                                <?php echo formatTimeAgo($article['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Comments -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Recent Comments</h2>
                <a href="comments.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="section-body">
                <?php if (empty($recent_comments)): ?>
                    <p style="text-align: center; color: var(--gray-500);">No comments yet</p>
                <?php else: ?>
                    <?php foreach ($recent_comments as $comment): ?>
                        <div class="list-item">
                            <div class="item-title">
                                <?php echo htmlspecialchars($comment['username']); ?> on 
                                "<?php echo htmlspecialchars(substr($comment['article_title'], 0, 40)); ?>..."
                            </div>
                            <div class="item-meta">
                                <?php echo htmlspecialchars(substr($comment['content'], 0, 60)); ?>... • 
                                <?php echo formatTimeAgo($comment['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

