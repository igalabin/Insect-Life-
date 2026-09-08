<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php?redirect=dashboard.php');
}

$page_title = "Dashboard - Insect Life";
$page_description = "Your personal insect learning dashboard with reading progress, badges, and recommendations.";
$page_keywords = "dashboard, progress, badges, learning, insect library";

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $pdo = getDBConnection();
    
    // Get user profile data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get reading statistics
    $stats = [];
    
    // Articles read
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reading_progress WHERE user_id = ? AND progress_percentage = 100");
    $stmt->execute([$user_id]);
    $stats['articles_completed'] = $stmt->fetchColumn();
    
    // Articles in progress
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reading_progress WHERE user_id = ? AND progress_percentage > 0 AND progress_percentage < 100");
    $stmt->execute([$user_id]);
    $stats['articles_in_progress'] = $stmt->fetchColumn();
    
    // Total reading time (in minutes)
    $stmt = $pdo->prepare("
        SELECT SUM(a.estimated_read_time) 
        FROM reading_progress rp 
        JOIN articles a ON rp.article_id = a.id 
        WHERE rp.user_id = ? AND rp.progress_percentage = 100
    ");
    $stmt->execute([$user_id]);
    $stats['total_read_time'] = $stmt->fetchColumn() ?: 0;
    
    // Badges earned
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['badges_earned'] = $stmt->fetchColumn();
    
    // Get recent reading activity
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.featured_image, a.difficulty_level, a.insect_order,
               rp.progress_percentage, rp.last_read_at
        FROM reading_progress rp
        JOIN articles a ON rp.article_id = a.id
        WHERE rp.user_id = ?
        ORDER BY rp.last_read_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll();
    
    // Get earned badges
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earned_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $earned_badges = $stmt->fetchAll();
    
    // Get recommendations
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.featured_image, a.difficulty_level, a.scientific_name, a.insect_order
        FROM articles a
        WHERE a.status = 'published' 
        AND a.difficulty_level = ?
        AND a.id NOT IN (
            SELECT article_id FROM reading_progress WHERE user_id = ?
        )
        ORDER BY a.published_at DESC
        LIMIT 4
    ");
    $stmt->execute([$user['experience_level'], $user_id]);
    $recommendations = $stmt->fetchAll();
    
    // Get learning streak
    $stmt = $pdo->prepare("
        SELECT DATE(last_read_at) as read_date, COUNT(*) as articles_read
        FROM reading_progress 
        WHERE user_id = ? AND last_read_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(last_read_at)
        ORDER BY read_date DESC
    ");
    $stmt->execute([$user_id]);
    $reading_days = $stmt->fetchAll();
    
    // Calculate current streak
    $current_streak = 0;
    $yesterday = new DateTime('yesterday');
    $today = new DateTime('today');
    
    foreach ($reading_days as $day) {
        $read_date = new DateTime($day['read_date']);
        if ($read_date == $today || $read_date == $yesterday) {
            $current_streak++;
            $yesterday->sub(new DateInterval('P1D'));
        } else {
            break;
        }
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['articles_completed' => 0, 'articles_in_progress' => 0, 'total_read_time' => 0, 'badges_earned' => 0];
    $recent_activity = [];
    $earned_badges = [];
    $recommendations = [];
    $current_streak = 0;
}

$additional_css = "
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .welcome-message {
        position: relative;
        z-index: 1;
    }
    
    .welcome-message h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: white;
    }
    
    .welcome-message p {
        font-size: 1.1rem;
        opacity: 0.9;
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }
    
    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        text-align: center;
        transition: all var(--transition-normal);
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
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }
    
    .stat-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-weight: 500;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .dashboard-section {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .section-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900);
        margin: 0;
    }
    
    .section-link {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .section-content {
        padding: 1.5rem 2rem;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .activity-image {
        width: 60px;
        height: 60px;
        border-radius: var(--border-radius-md);
        background: var(--gray-200);
        margin-right: 1rem;
        flex-shrink: 0;
        overflow: hidden;
    }
    
    .activity-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .activity-details {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }
    
    .activity-meta {
        font-size: 0.85rem;
        color: var(--gray-500);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .progress-bar {
        width: 100%;
        height: 6px;
        background: var(--gray-200);
        border-radius: 3px;
        margin-top: 0.5rem;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-radius: 3px;
        transition: width var(--transition-normal);
    }
    
    .badge-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    
    .badge-item {
        text-align: center;
        padding: 1rem;
        border-radius: var(--border-radius-md);
        background: var(--gray-50);
        transition: all var(--transition-fast);
    }
    
    .badge-item:hover {
        background: var(--gray-100);
        transform: translateY(-2px);
    }
    
    .badge-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .badge-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 0.25rem;
    }
    
    .badge-date {
        font-size: 0.75rem;
        color: var(--gray-500);
    }
    
    .recommendation-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .recommendation-card {
        display: block;
        text-decoration: none;
        color: inherit;
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        transition: all var(--transition-fast);
    }
    
    .recommendation-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: inherit;
    }
    
    .recommendation-image {
        height: 100px;
        background: var(--gray-200);
        overflow: hidden;
    }
    
    .recommendation-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .recommendation-content {
        padding: 1rem;
    }
    
    .recommendation-title {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        color: var(--gray-900);
        line-height: 1.3;
    }
    
    .recommendation-meta {
        font-size: 0.8rem;
        color: var(--gray-500);
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--gray-500);
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .streak-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(251, 146, 60, 0.1);
        color: #ea580c;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .recommendation-grid {
            grid-template-columns: 1fr;
        }
        
        .badge-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .welcome-message h1 {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-container {
            padding: 1rem 0.5rem;
        }
    }
";

include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="dashboard-header">
        <div class="welcome-message">
            <h1>Welcome back, <?php echo htmlspecialchars($username); ?>! 🐛</h1>
            <p>Continue your journey through the fascinating world of insects</p>
            <?php if ($current_streak > 1): ?>
                <div class="streak-indicator">
                    <i class="fas fa-fire"></i>
                    <?php echo $current_streak; ?> day learning streak!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo number_format($stats['articles_completed']); ?></div>
            <div class="stat-label">Articles Completed</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-number"><?php echo $stats['articles_in_progress']; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🕐</div>
            <div class="stat-number"><?php echo number_format($stats['total_read_time']); ?></div>
            <div class="stat-label">Minutes Read</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-number"><?php echo $stats['badges_earned']; ?></div>
            <div class="stat-label">Badges Earned</div>
        </div>
    </div>

    <!-- Dashboard Main Content -->
    <div class="dashboard-grid">
        <!-- Recent Activity -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Recent Activity</h2>
                <a href="library.php" class="section-link">View All →</a>
            </div>
            <div class="section-content">
                <?php if (empty($recent_activity)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📖</div>
                        <p>Start reading to see your activity here!</p>
                        <a href="library.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Library</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-image">
                                <?php if ($activity['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($activity['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($activity['title']); ?>">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--gray-300); font-size: 1.5rem;">
                                        🐛
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <a href="article.php?id=<?php echo $activity['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </a>
                                </div>
                                <div class="activity-meta">
                                    <span><?php echo ucfirst($activity['difficulty_level']); ?></span>
                                    <span><?php echo ucfirst($activity['insect_order']); ?></span>
                                    <span><?php echo formatTimeAgo($activity['last_read_at']); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $activity['progress_percentage']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Badges -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">Recent Badges</h2>
                <a href="badges.php" class="section-link">View All →</a>
            </div>
            <div class="section-content">
                <?php if (empty($earned_badges)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏆</div>
                        <p>Complete assessments to earn badges!</p>
                        <a href="assessments.php" class="btn btn-secondary" style="margin-top: 1rem;">Take Assessment</a>
                    </div>
                <?php else: ?>
                    <div class="badge-grid">
                        <?php foreach ($earned_badges as $badge): ?>
                            <div class="badge-item">
                                <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                                <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                <div class="badge-date"><?php echo formatTimeAgo($badge['earned_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">Recommended for You</h2>
            <a href="library.php?difficulty=<?php echo htmlspecialchars($user['experience_level']); ?>" class="section-link">
                View More →
            </a>
        </div>
        <div class="section-content">
            <?php if (empty($recommendations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💡</div>
                    <p>All caught up! Check back later for new recommendations.</p>
                </div>
            <?php else: ?>
                <div class="recommendation-grid">
                    <?php foreach ($recommendations as $article): ?>
                        <a href="article.php?id=<?php echo $article['id']; ?>" class="recommendation-card">
                            <div class="recommendation-image">
                                <?php if ($article['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--gray-300); font-size: 2rem;">
                                        🐛
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="recommendation-content">
                                <div class="recommendation-title">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </div>
                                <div class="recommendation-meta">
                                    <?php if ($article['scientific_name']): ?>
                                        <em><?php echo htmlspecialchars($article['scientific_name']); ?></em><br>
                                    <?php endif; ?>
                                    <?php echo ucfirst($article['difficulty_level']); ?> • <?php echo ucfirst($article['insect_order']); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$additional_js = "
    // Animate statistics on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stat numbers
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalNumber = parseInt(stat.textContent.replace(/,/g, ''));
            animateNumber(stat, 0, finalNumber, 1500);
        });
        
        // Animate progress bars
        const progressBars = document.querySelectorAll('.progress-fill');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });
        
        // Add hover effects to activity items
        document.querySelectorAll('.activity-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--gray-50)';
                this.style.borderRadius = 'var(--border-radius-md)';
                this.style.padding = '1rem';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
                this.style.padding = '1rem 0';
            });
        });
    });
    
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const originalText = element.textContent;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + (end - start) * progress);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = originalText; // Restore original formatting
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
";

include 'includes/footer.php';
?>