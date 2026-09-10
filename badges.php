<?php
session_start();
require_once 'config.php';

$page_title = "Badges - Insect Life";
$page_description = "View all available badges and track your progress in becoming an insect expert.";
$page_keywords = "badges, achievements, progress, insect expert";

$additional_css = "
    .badges-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .badges-header {
        text-align: center;
        margin-bottom: 3rem;
        color: white;
    }
    
    .badge-categories {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .category-tab {
        padding: 0.75rem 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--border-radius-lg);
        text-decoration: none;
        font-weight: 500;
        transition: all var(--transition-fast);
    }
    
    .category-tab:hover, .category-tab.active {
        background: rgba(255, 255, 255, 0.2);
        border-color: var(--secondary-green);
        color: white;
    }
    
    .badges-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .badge-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
        position: relative;
    }
    
    .badge-card.earned {
        border: 2px solid var(--secondary-green);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
    }
    
    .badge-card.earned::after {
        content: '✓';
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 30px;
        height: 30px;
        background: var(--secondary-green);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .badge-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }
    
    .badge-header {
        padding: 1.5rem;
        text-align: center;
        background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    }
    
    .badge-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .badge-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .badge-rarity {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .rarity-common { background: var(--gray-300); color: var(--gray-700); }
    .rarity-uncommon { background: var(--secondary-green); color: white; }
    .rarity-rare { background: var(--primary-blue); color: white; }
    .rarity-epic { background: var(--accent-orange); color: white; }
    .rarity-legendary { background: linear-gradient(135deg, #ffd700, #ff8c00); color: white; }
    .rarity-cardinal { 
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #dc2626 100%); 
        color: white;
        animation: cardinalShine 2s ease-in-out infinite;
        box-shadow: 0 0 15px rgba(220, 38, 38, 0.6);
    }
    .rarity-cardinal::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transform: rotate(45deg);
        animation: cardinalSweep 3s linear infinite;
    }
    @keyframes cardinalShine {
        0%, 100% { box-shadow: 0 0 15px rgba(220, 38, 38, 0.6); }
        50% { box-shadow: 0 0 25px rgba(220, 38, 38, 0.9); }
    }
    @keyframes cardinalSweep {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .badge-body {
        padding: 1.5rem;
    }
    
    .badge-description {
        color: var(--gray-600);
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    
    .badge-requirements {
        background: var(--gray-50);
        padding: 1rem;
        border-radius: var(--border-radius-md);
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .badge-progress {
        margin-top: 1rem;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--gray-200);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-radius: 4px;
        transition: width var(--transition-normal);
    }
    
    .progress-text {
        font-size: 0.85rem;
        color: var(--gray-500);
        text-align: center;
    }
    
    .badge-points {
        text-align: center;
        font-weight: 600;
        color: var(--accent-orange);
        margin-top: 1rem;
    }
    
    @media (max-width: 768px) {
        .badges-grid {
            grid-template-columns: 1fr;
        }
        
        .category-tab {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
    }
";

include 'includes/header.php';

try {
    $pdo = getDBConnection();
    
    // Get all badges
    $stmt = $pdo->prepare("SELECT * FROM badges WHERE is_active = 1 ORDER BY category, rarity, name");
    $stmt->execute();
    $all_badges = $stmt->fetchAll();
    
    // Get user's earned badges if logged in
    $earned_badges = [];
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT badge_id, earned_at FROM user_badges WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $earned_badges = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get filter
    $category_filter = $_GET['category'] ?? 'all';
    
    // Filter badges by category
    $badges = $all_badges;
    if ($category_filter !== 'all') {
        $badges = array_filter($all_badges, function($badge) use ($category_filter) {
            return $badge['category'] === $category_filter;
        });
    }
    
} catch (Exception $e) {
    error_log("Badges page error: " . $e->getMessage());
    $badges = [];
    $earned_badges = [];
}

$categories = [
    'all' => 'All Badges',
    'reading' => 'Reading',
    'knowledge' => 'Knowledge',
    'exploration' => 'Exploration',
    'social' => 'Social',
    'special' => 'Special'
];
?>

<div class="badges-container">
    <div class="badges-header">
        <h1>Achievement Badges</h1>
        <p>Earn badges by completing various activities and demonstrating your insect knowledge</p>
        <?php if (isLoggedIn()): ?>
            <p><strong><?php echo count($earned_badges); ?></strong> of <strong><?php echo count($all_badges); ?></strong> badges earned</p>
        <?php endif; ?>
    </div>

    <!-- Category Filters -->
    <div class="badge-categories">
        <?php foreach ($categories as $key => $label): ?>
            <a href="?category=<?php echo $key; ?>" 
               class="category-tab <?php echo $category_filter === $key ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Badges Grid -->
    <div class="badges-grid">
        <?php if (empty($badges)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: white;">
                <h3>No badges found</h3>
                <p>Try selecting a different category or check back later for new badges!</p>
            </div>
        <?php else: ?>
            <?php foreach ($badges as $badge): ?>
                <?php 
                $is_earned = isset($earned_badges[$badge['id']]);
                $requirements = json_decode($badge['requirements'], true);
                ?>
                <div class="badge-card <?php echo $is_earned ? 'earned' : ''; ?>">
                    <div class="badge-header">
                        <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                        <h3 class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></h3>
                        <span class="badge-rarity rarity-<?php echo $badge['rarity']; ?>">
                            <span style="position: relative; z-index: 1;"><?php echo ucfirst($badge['rarity']); ?></span>
                        </span>
                    </div>
                    
                    <div class="badge-body">
                        <p class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></p>
                        
                        <div class="badge-requirements">
                            <strong>Requirements:</strong><br>
                            <?php
                            if (is_array($requirements)) {
                                foreach ($requirements as $key => $value) {
                                    echo ucfirst(str_replace('_', ' ', $key)) . ': ' . $value . '<br>';
                                }
                            }
                            ?>
                        </div>
                        
                        <?php if ($is_earned): ?>
                            <div style="text-align: center; color: var(--secondary-green); font-weight: 600;">
                                Earned on <?php echo date('M j, Y', strtotime($earned_badges[$badge['id']])); ?>
                            </div>
                        <?php elseif (isLoggedIn()): ?>
                            <div class="badge-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 30%"></div>
                                </div>
                                <div class="progress-text">Progress: 30% (example)</div>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--gray-500);">
                                <a href="login.php" style="color: var(--primary-blue);">Login</a> to track progress
                            </div>
                        <?php endif; ?>
                        
                        <div class="badge-points">
                            +<?php echo $badge['points']; ?> points
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>