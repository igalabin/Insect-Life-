<?php
session_start();
require_once 'config.php';

$page_title = "Assessments - Insect Life";
$page_description = "Test your knowledge about insects with our interactive assessments and earn badges.";
$page_keywords = "insect quiz, assessment, test, badges, learning";

// Get user data if logged in
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

try {
    $pdo = getDBConnection();
    
    // Get all active assessments
    $stmt = $pdo->prepare("
        SELECT a.*,
               COUNT(DISTINCT uar.user_id) as total_attempts,
               AVG(uar.score) as avg_score
        FROM assessments a
        LEFT JOIN user_assessment_results uar ON a.id = uar.assessment_id
        WHERE a.is_active = 1
        GROUP BY a.id
        ORDER BY a.difficulty_level, a.title
    ");
    $stmt->execute();
    $assessments = $stmt->fetchAll();
    
    // Get user's assessment history if logged in
    $user_attempts = [];
    if ($user_id) {
        $stmt = $pdo->prepare("
            SELECT assessment_id, MAX(score) as best_score, COUNT(*) as attempt_count,
                   MAX(completed_at) as last_attempt
            FROM user_assessment_results
            WHERE user_id = ?
            GROUP BY assessment_id
        ");
        $stmt->execute([$user_id]);
        $user_attempts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
} catch (Exception $e) {
    error_log("Assessments page error: " . $e->getMessage());
    $assessments = [];
    $user_attempts = [];
}

$additional_css = "
    .assessments-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .assessments-header h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: white;
    }
    
    .assessments-header p {
        font-size: 1.1rem;
        opacity: 0.95;
        color: white;
    }
    
    .assessments-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem 3rem;
    }
    
    .difficulty-tabs {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-bottom: 3rem;
        flex-wrap: wrap;
    }
    
    .difficulty-tab {
        padding: 0.75rem 2rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-lg);
        background: white;
        color: var(--gray-700);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
    }
    
    .difficulty-tab:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .difficulty-tab.active {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-color: var(--primary-blue);
        color: white;
    }
    
    .assessments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .assessment-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
        position: relative;
    }
    
    .assessment-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }
    
    .assessment-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        z-index: 1;
    }
    
    .badge-beginner {
        background: var(--secondary-green);
        color: white;
    }
    
    .badge-intermediate {
        background: var(--accent-orange);
        color: white;
    }
    
    .badge-advanced {
        background: #ef4444;
        color: white;
    }
    
    .assessment-header {
        background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
        padding: 2rem;
        text-align: center;
        position: relative;
    }
    
    .assessment-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .assessment-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .assessment-category {
        color: var(--primary-blue);
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .assessment-body {
        padding: 2rem;
    }
    
    .assessment-description {
        color: var(--gray-600);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .assessment-meta {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--border-radius-md);
    }
    
    .meta-item {
        text-align: center;
    }
    
    .meta-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .meta-label {
        font-size: 0.85rem;
        color: var(--gray-600);
    }
    
    .assessment-stats {
        display: flex;
        justify-content: space-between;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--border-radius-md);
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray-600);
    }
    
    .user-progress {
        background: rgba(59, 130, 246, 0.1);
        border-left: 4px solid var(--primary-blue);
        padding: 1rem;
        border-radius: var(--border-radius-md);
        margin-bottom: 1.5rem;
    }
    
    .progress-title {
        font-weight: 600;
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
    }
    
    .progress-details {
        font-size: 0.9rem;
        color: var(--gray-700);
    }
    
    .assessment-actions {
        display: flex;
        gap: 1rem;
    }
    
    .btn-start {
        flex: 1;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        border: none;
        border-radius: var(--border-radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-align: center;
        text-decoration: none;
        display: block;
    }
    
    .btn-start:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        color: white;
    }
    
    .btn-view-details {
        padding: 1rem 1.5rem;
        background: white;
        color: var(--primary-blue);
        border: 2px solid var(--primary-blue);
        border-radius: var(--border-radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .btn-view-details:hover {
        background: var(--primary-blue);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--gray-500);
    }
    
    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .info-section {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        margin-bottom: 3rem;
    }
    
    .info-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 1rem;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }
    
    .info-item {
        text-align: center;
    }
    
    .info-item-icon {
        font-size: 2rem;
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
    }
    
    .info-item-title {
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .info-item-text {
        color: var(--gray-600);
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .assessments-header h1 {
            font-size: 2rem;
        }
        
        .assessments-grid {
            grid-template-columns: 1fr;
        }
        
        .difficulty-tabs {
            flex-direction: column;
        }
        
        .assessment-actions {
            flex-direction: column;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>

<div class="assessments-header">
    <h1>Knowledge Assessments</h1>
    <p>Test your insect knowledge and earn badges as you progress</p>
</div>

<div class="assessments-container">
    <?php if (!isLoggedIn()): ?>
    <!-- Login prompt for non-logged-in users -->
    <div class="info-section" style="text-align: center; background: rgba(59, 130, 246, 0.05); border: 2px solid var(--primary-blue);">
        <h2 style="color: var(--primary-blue); margin-bottom: 1rem;">Sign in to track your progress</h2>
        <p style="color: var(--gray-700); margin-bottom: 1.5rem;">
            Create a free account to save your assessment scores, earn badges, and track your learning journey.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="login.php?redirect=assessments.php" class="btn btn-primary">Sign In</a>
            <a href="register.php?redirect=assessments.php" class="btn btn-secondary">Create Account</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- How it works section -->
    <div class="info-section">
        <h2 class="info-title">How Assessments Work</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-item-icon">📝</div>
                <h3 class="info-item-title">Take the Quiz</h3>
                <p class="info-item-text">Answer multiple-choice questions about insect biology, behavior, and ecology</p>
            </div>
            <div class="info-item">
                <div class="info-item-icon">🎯</div>
                <h3 class="info-item-title">Get Your Score</h3>
                <p class="info-item-text">Receive instant feedback and see which areas you've mastered</p>
            </div>
            <div class="info-item">
                <div class="info-item-icon">🏆</div>
                <h3 class="info-item-title">Earn Badges</h3>
                <p class="info-item-text">Pass assessments to unlock achievement badges and track your progress</p>
            </div>
            <div class="info-item">
                <div class="info-item-icon">📈</div>
                <h3 class="info-item-title">Keep Learning</h3>
                <p class="info-item-text">Retake assessments to improve your score and deepen your knowledge</p>
            </div>
        </div>
    </div>

    <!-- Difficulty filter tabs -->
    <div class="difficulty-tabs">
        <a href="?difficulty=all" class="difficulty-tab <?php echo !isset($_GET['difficulty']) || $_GET['difficulty'] === 'all' ? 'active' : ''; ?>">
            All Levels
        </a>
        <a href="?difficulty=beginner" class="difficulty-tab <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] === 'beginner' ? 'active' : ''; ?>">
            Beginner
        </a>
        <a href="?difficulty=intermediate" class="difficulty-tab <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] === 'intermediate' ? 'active' : ''; ?>">
            Intermediate
        </a>
        <a href="?difficulty=advanced" class="difficulty-tab <?php echo isset($_GET['difficulty']) && $_GET['difficulty'] === 'advanced' ? 'active' : ''; ?>">
            Advanced
        </a>
    </div>

    <!-- Assessments Grid -->
    <?php 
    $filter_difficulty = isset($_GET['difficulty']) && $_GET['difficulty'] !== 'all' ? $_GET['difficulty'] : null;
    $filtered_assessments = $filter_difficulty 
        ? array_filter($assessments, fn($a) => $a['difficulty_level'] === $filter_difficulty)
        : $assessments;
    ?>

    <?php if (empty($filtered_assessments)): ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No assessments available</h3>
        <p>Check back soon for new assessments!</p>
    </div>
    <?php else: ?>
    <div class="assessments-grid">
        <?php foreach ($filtered_assessments as $assessment): ?>
        <div class="assessment-card">
            <div class="assessment-badge badge-<?php echo $assessment['difficulty_level']; ?>">
                <?php echo ucfirst($assessment['difficulty_level']); ?>
            </div>
            
            <div class="assessment-header">
                <div class="assessment-icon">
                    <?php
                    $icons = [
                        'beginner' => '🌱',
                        'intermediate' => '🦋',
                        'advanced' => '🔬'
                    ];
                    echo $icons[$assessment['difficulty_level']] ?? '📝';
                    ?>
                </div>
                <h3 class="assessment-title"><?php echo htmlspecialchars($assessment['title']); ?></h3>
                <div class="assessment-category"><?php echo htmlspecialchars($assessment['category']); ?></div>
            </div>
            
            <div class="assessment-body">
                <p class="assessment-description">
                    <?php echo htmlspecialchars($assessment['description']); ?>
                </p>
                
                <div class="assessment-meta">
                    <div class="meta-item">
                        <div class="meta-value"><?php echo $assessment['total_questions']; ?></div>
                        <div class="meta-label">Questions</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-value"><?php echo number_format($assessment['passing_score']); ?>%</div>
                        <div class="meta-label">Passing Score</div>
                    </div>
                </div>
                
                <?php if ($assessment['total_attempts'] > 0): ?>
                <div class="assessment-stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo number_format($assessment['total_attempts']); ?> attempts</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Avg: <?php echo number_format($assessment['avg_score'], 1); ?>%</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($user_id && isset($user_attempts[$assessment['id']])): ?>
                <div class="user-progress">
                    <div class="progress-title">Your Progress</div>
                    <div class="progress-details">
                        Best Score: <strong><?php echo number_format($user_attempts[$assessment['id']]['best_score']); ?>%</strong>
                        <br>
                        Attempts: <?php echo $user_attempts[$assessment['id']]['attempt_count']; ?>
                        <?php if ($user_attempts[$assessment['id']]['best_score'] >= $assessment['passing_score']): ?>
                            <br><span style="color: var(--secondary-green); font-weight: 600;">✓ Passed</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="assessment-actions">
                    <a href="take-assessment.php?id=<?php echo $assessment['id']; ?>" class="btn-start">
                        <i class="fas fa-play"></i>
                        <?php echo ($user_id && isset($user_attempts[$assessment['id']])) ? 'Retake Assessment' : 'Start Assessment'; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$additional_js = "
    // Add animation to cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                cardObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.assessment-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = (index * 0.1) + 's';
        cardObserver.observe(card);
    });
    
    // Add CSS for animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
";

include 'includes/footer.php';
?>