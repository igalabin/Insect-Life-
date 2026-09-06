<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php?redirect=profile.php');
}

$page_title = "My Profile - Insect Life";
$page_description = "View your profile, achievements, and learning progress.";
$page_keywords = "profile, achievements, progress, badges";

$user_id = $_SESSION['user_id'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

// Handle badge equip/unequip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'equip_badge') {
        $badge_id = intval($_POST['badge_id'] ?? 0);
        
        try {
            $pdo = getDBConnection();
            
            // Verify user owns this badge
            $stmt = $pdo->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
            $stmt->execute([$user_id, $badge_id]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE users SET equipped_badge_id = ? WHERE id = ?");
                $stmt->execute([$badge_id, $user_id]);
                $success_message = "Badge equipped successfully!";
            }
        } catch (Exception $e) {
            error_log("Badge equip error: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'unequip_badge') {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE users SET equipped_badge_id = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = "Badge unequipped successfully!";
        } catch (Exception $e) {
            error_log("Badge unequip error: " . $e->getMessage());
        }
    }
}

try {
    $pdo = getDBConnection();
    
    // Get user profile data with equipped badge
    $stmt = $pdo->prepare("
        SELECT u.*, b.id as badge_id, b.name as badge_name, b.icon as badge_icon, b.rarity as badge_rarity
        FROM users u
        LEFT JOIN badges b ON u.equipped_badge_id = b.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirectTo('logout.php');
    }
    
    // Get user statistics
    $stats = [];
    
    // Articles completed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reading_progress WHERE user_id = ? AND progress_percentage = 100");
    $stmt->execute([$user_id]);
    $stats['articles_completed'] = $stmt->fetchColumn();
    
    // Total reading time
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
    
    // Total badges available
    $stats['total_badges'] = $pdo->query("SELECT COUNT(*) FROM badges WHERE is_active = 1")->fetchColumn();
    
    // Comments posted
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['comments_posted'] = $stmt->fetchColumn();
    
    // Assessments taken
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_assessment_results WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['assessments_taken'] = $stmt->fetchColumn();
    
    // Average assessment score
    $stmt = $pdo->prepare("SELECT AVG(score) FROM user_assessment_results WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['avg_score'] = $stmt->fetchColumn() ?: 0;
    
    // Bookmarks count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['bookmarks_count'] = $stmt->fetchColumn();
    
    // Get earned badges
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earned_at
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_at DESC
    ");
    $stmt->execute([$user_id]);
    $earned_badges = $stmt->fetchAll();
    
    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.featured_image, a.difficulty_level, a.scientific_name,
               rp.progress_percentage, rp.last_read_at
        FROM reading_progress rp
        JOIN articles a ON rp.article_id = a.id
        WHERE rp.user_id = ?
        ORDER BY rp.last_read_at DESC
        LIMIT 8
    ");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll();
    
    // Get user interests
    $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_interests = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Calculate learning streak
    $stmt = $pdo->prepare("
        SELECT DATE(last_read_at) as read_date
        FROM reading_progress 
        WHERE user_id = ? AND last_read_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(last_read_at)
        ORDER BY read_date DESC
    ");
    $stmt->execute([$user_id]);
    $reading_days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $current_streak = 0;
    $yesterday = new DateTime('yesterday');
    $today = new DateTime('today');
    
    foreach ($reading_days as $day) {
        $read_date = new DateTime($day);
        if ($read_date == $today || $read_date == $yesterday) {
            $current_streak++;
            $yesterday->sub(new DateInterval('P1D'));
        } else {
            break;
        }
    }
    
} catch (Exception $e) {
    error_log("Profile page error: " . $e->getMessage());
    $stats = ['articles_completed' => 0, 'total_read_time' => 0, 'badges_earned' => 0, 'total_badges' => 0, 
              'comments_posted' => 0, 'assessments_taken' => 0, 'avg_score' => 0, 'bookmarks_count' => 0];
    $earned_badges = [];
    $recent_activity = [];
    $user_interests = [];
    $current_streak = 0;
}

$additional_css = "
    .profile-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-radius: var(--border-radius-lg);
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        box-shadow: var(--shadow-xl);
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
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
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
        border: 4px solid rgba(255,255,255,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        color: white;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        box-shadow: var(--shadow-lg);
    }
    
    .equipped-badge {
        position: absolute;
        bottom: -5px;
        right: -5px;
        width: 45px;
        height: 45px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        border: 3px solid;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        z-index: 2;
    }
    
    .equipped-badge.rarity-common { border-color: #9ca3af; }
    .equipped-badge.rarity-uncommon { border-color: #10b981; }
    .equipped-badge.rarity-rare { border-color: #3b82f6; }
    .equipped-badge.rarity-epic { border-color: #a855f7; }
    .equipped-badge.rarity-legendary { border-color: #f59e0b; }
    .equipped-badge.rarity-cardinal { 
        border-color: #dc2626; 
        animation: cardinalGlow 2s ease-in-out infinite;
    }
    
    @keyframes cardinalGlow {
        0%, 100% { box-shadow: 0 0 15px rgba(220, 38, 38, 0.6); }
        50% { box-shadow: 0 0 25px rgba(220, 38, 38, 0.9); }
    }
    
    .profile-info {
        flex: 1;
        position: relative;
        z-index: 1;
    }
    
    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .profile-meta {
        display: flex;
        gap: 2rem;
        color: white;
        font-size: 0.95rem;
        opacity: 0.9;
        flex-wrap: wrap;
    }
    
    .profile-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .streak-badge {
        background: rgba(251, 146, 60, 0.2);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .profile-actions {
        display: flex;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
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
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-size: 0.9rem;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .section-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    
    .section-header {
        padding: 1.5rem;
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
    
    .section-link:hover {
        text-decoration: underline;
    }
    
    .section-content {
        padding: 1.5rem;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .activity-item:last-child {
        border-bottom: none;
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
        min-width: 0;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .activity-meta {
        font-size: 0.85rem;
        color: var(--gray-500);
    }
    
    .progress-bar {
        width: 100%;
        height: 4px;
        background: var(--gray-200);
        border-radius: 2px;
        margin-top: 0.5rem;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-radius: 2px;
    }
    
    .badges-grid {
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
        cursor: pointer;
        position: relative;
        border: 2px solid transparent;
    }
    
    .badge-item:hover {
        background: var(--gray-100);
        transform: translateY(-2px);
    }
    
    .badge-item.equipped {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.05);
    }
    
    .badge-item.equipped::after {
        content: 'Equipped';
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        font-size: 0.7rem;
        background: var(--primary-blue);
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .badge-icon {
        font-size: 2.5rem;
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
    
    .badge-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
        justify-content: center;
    }
    
    .badge-actions button {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        border: none;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-equip {
        background: var(--primary-blue);
        color: white;
    }
    
    .btn-equip:hover {
        background: #2563eb;
    }
    
    .btn-unequip {
        background: var(--gray-400);
        color: white;
    }
    
    .btn-unequip:hover {
        background: var(--gray-500);
    }
    
    .interests-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .interest-tag {
        padding: 0.5rem 1rem;
        background: var(--gray-100);
        border-radius: 20px;
        font-size: 0.9rem;
        color: var(--gray-700);
        font-weight: 500;
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--gray-500);
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    
    .progress-ring {
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
        position: relative;
    }
    
    .progress-ring svg {
        transform: rotate(-90deg);
    }
    
    .progress-ring-bg {
        fill: none;
        stroke: var(--gray-200);
        stroke-width: 8;
    }
    
    .progress-ring-fill {
        fill: none;
        stroke: url(#gradient);
        stroke-width: 8;
        stroke-linecap: round;
        transition: stroke-dashoffset 1s ease-in-out;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--gray-900);
    }
    
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            padding: 2rem 1.5rem;
        }
        
        .profile-actions {
            width: 100%;
            flex-direction: column;
        }
        
        .profile-meta {
            justify-content: center;
        }
        
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .badges-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-name {
            font-size: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
        }
    }
";

include 'includes/header.php';
?>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            <?php if ($user['badge_id']): ?>
                <div class="equipped-badge rarity-<?php echo $user['badge_rarity']; ?>" 
                     title="<?php echo htmlspecialchars($user['badge_name']); ?>">
                    <?php echo $user['badge_icon']; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <h1 class="profile-name">
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </h1>
            <div class="profile-meta">
                <div class="profile-meta-item">
                    <i class="fas fa-user"></i>
                    @<?php echo htmlspecialchars($user['username']); ?>
                </div>
                <div class="profile-meta-item">
                    <i class="fas fa-calendar"></i>
                    Joined <?php echo date('F Y', strtotime($user['created_at'])); ?>
                </div>
                <div class="profile-meta-item">
                    <i class="fas fa-signal"></i>
                    <?php echo ucfirst($user['experience_level']); ?>
                </div>
            </div>
            
            <?php if ($current_streak > 1): ?>
            <div class="streak-badge">
                <i class="fas fa-fire"></i>
                <?php echo $current_streak; ?> day learning streak!
            </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-actions">
            <a href="settings.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo number_format($stats['articles_completed']); ?></div>
            <div class="stat-label">Articles Completed</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-number"><?php echo number_format($stats['total_read_time']); ?></div>
            <div class="stat-label">Minutes Read</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-number"><?php echo $stats['badges_earned']; ?>/<?php echo $stats['total_badges']; ?></div>
            <div class="stat-label">Badges Earned</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-number"><?php echo number_format($stats['comments_posted']); ?></div>
            <div class="stat-label">Comments Posted</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-number"><?php echo number_format($stats['assessments_taken']); ?></div>
            <div class="stat-label">Assessments Taken</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-number"><?php echo number_format($stats['avg_score'], 1); ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Recent Activity -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Recent Activity</h2>
                <a href="library.php" class="section-link">View All →</a>
            </div>
            <div class="section-content">
                <?php if (empty($recent_activity)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📖</div>
                        <p>No reading activity yet</p>
                        <a href="library.php" class="btn btn-primary" style="margin-top: 1rem;">Start Reading</a>
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
                                        🦋
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
                                    <?php echo formatTimeAgo($activity['last_read_at']); ?> • 
                                    <?php echo ucfirst($activity['difficulty_level']); ?>
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

        <!-- Sidebar -->
        <div>
            <!-- Badge Progress -->
            <div class="section-card" style="margin-bottom: 2rem;">
                <div class="section-header">
                    <h2 class="section-title">Badge Progress</h2>
                    <a href="badges.php" class="section-link">View All →</a>
                </div>
                <div class="section-content">
                    <div class="progress-ring">
                        <svg width="120" height="120">
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#10b981;stop-opacity:1" />
                                </linearGradient>
                            </defs>
                            <circle class="progress-ring-bg" cx="60" cy="60" r="54"></circle>
                            <circle class="progress-ring-fill" cx="60" cy="60" r="54"
                                    stroke-dasharray="339.292"
                                    stroke-dashoffset="<?php echo 339.292 * (1 - ($stats['badges_earned'] / max($stats['total_badges'], 1))); ?>">
                            </circle>
                        </svg>
                        <div class="progress-text">
                            <?php echo round(($stats['badges_earned'] / max($stats['total_badges'], 1)) * 100); ?>%
                        </div>
                    </div>
                    <p style="text-align: center; color: var(--gray-600); margin-bottom: 0;">
                        <strong><?php echo $stats['badges_earned']; ?></strong> of 
                        <strong><?php echo $stats['total_badges']; ?></strong> badges earned
                    </p>
                </div>
            </div>

            <!-- Interests -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Interests</h2>
                    <a href="settings.php" class="section-link">Edit →</a>
                </div>
                <div class="section-content">
                    <?php if (empty($user_interests)): ?>
                        <div class="empty-state">
                            <p>No interests set yet</p>
                            <a href="settings.php" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem;">Add Interests</a>
                        </div>
                    <?php else: ?>
                        <div class="interests-list">
                            <?php foreach ($user_interests as $interest): ?>
                                <span class="interest-tag"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $interest))); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Earned Badges -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Earned Badges (<?php echo count($earned_badges); ?>)</h2>
            <a href="badges.php" class="section-link">View All Badges →</a>
        </div>
        <div class="section-content">
            <?php if (empty($earned_badges)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🏆</div>
                    <p>No badges earned yet</p>
                    <a href="assessments.php" class="btn btn-secondary" style="margin-top: 1rem;">Take Assessment</a>
                </div>
            <?php else: ?>
                <div class="badges-grid">
                    <?php foreach ($earned_badges as $badge): ?>
                        <div class="badge-item <?php echo ($user['equipped_badge_id'] == $badge['id']) ? 'equipped' : ''; ?>" 
                             title="<?php echo htmlspecialchars($badge['description']); ?>">
                            <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                            <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                            <div class="badge-date"><?php echo formatTimeAgo($badge['earned_at']); ?></div>
                            
                            <div class="badge-actions">
                                <?php if ($user['equipped_badge_id'] == $badge['id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="unequip_badge">
                                        <button type="submit" class="btn-unequip">
                                            <i class="fas fa-times"></i> Unequip
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="equip_badge">
                                        <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                                        <button type="submit" class="btn-equip">
                                            <i class="fas fa-shield-alt"></i> Equip
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate stat numbers
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        const text = stat.textContent.trim();
        const match = text.match(/^([\d,]+)/);
        if (match) {
            const finalNumber = parseInt(match[1].replace(/,/g, ''));
            if (finalNumber > 0 && finalNumber < 10000) {
                animateNumber(stat, 0, finalNumber, 1500, text);
            }
        }
    });
    
    // Add hover effect to activity items
    document.querySelectorAll('.activity-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'var(--gray-50)';
            this.style.borderRadius = 'var(--border-radius-md)';
            this.style.margin = '0 -1rem';
            this.style.padding = '1rem';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
            this.style.margin = '0';
            this.style.padding = '1rem 0';
        });
    });
});

function animateNumber(element, start, end, duration, originalText) {
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        
        element.textContent = current.toLocaleString() + originalText.substring(originalText.indexOf(current.toString()) + current.toString().length);
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        } else {
            element.textContent = originalText;
        }
    }
    
    requestAnimationFrame(updateNumber);
}
</script>

<?php include 'includes/footer.php'; ?>