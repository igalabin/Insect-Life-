<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Manage Badges - Admin";
$success_message = '';
$error_message = '';

// Handle Badge Award to User
if (isset($_POST['award_badge'])) {
    $badge_id = intval($_POST['badge_id']);
    $user_id = intval($_POST['user_id']);
    
    try {
        $pdo = getDBConnection();
        
        // Check if user already has this badge
        $stmt = $pdo->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $stmt->execute([$user_id, $badge_id]);
        
        if ($stmt->fetch()) {
            $error_message = "User already has this badge!";
        } else {
            // Award the badge
            $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $badge_id]);
            $success_message = "Badge awarded successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error awarding badge: " . $e->getMessage();
    }
}

// Handle Badge Assignment to Assessment
if (isset($_POST['assign_to_assessment'])) {
    $badge_id = intval($_POST['badge_id']);
    $assessment_id = intval($_POST['assessment_id']);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE assessments SET badge_id = ? WHERE id = ?");
        $stmt->execute([$badge_id, $assessment_id]);
        $success_message = "Badge assigned to assessment successfully!";
    } catch (Exception $e) {
        $error_message = "Error assigning badge: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM badges WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_message = "Badge deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting badge: " . $e->getMessage();
    }
}

// Handle Status Toggle
if (isset($_POST['toggle_status'])) {
    $badge_id = intval($_POST['badge_id']);
    $new_status = intval($_POST['is_active']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE badges SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $badge_id]);
        $success_message = "Badge status updated!";
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$rarity_filter = isset($_GET['rarity']) ? sanitizeInput($_GET['rarity']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(name LIKE :search OR description LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($category_filter) {
        $where_conditions[] = "category = :category";
        $params['category'] = $category_filter;
    }
    
    if ($rarity_filter) {
        $where_conditions[] = "rarity = :rarity";
        $params['rarity'] = $rarity_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $count_query = "SELECT COUNT(*) FROM badges $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_badges = $count_stmt->fetchColumn();
    
    $offset = ($page - 1) * $per_page;
    $query = "
        SELECT b.*, 
               (SELECT COUNT(*) FROM user_badges WHERE badge_id = b.id) as earned_count,
               (SELECT COUNT(*) FROM assessments WHERE badge_id = b.id) as assessment_count
        FROM badges b
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $badges = $stmt->fetchAll();
    
    $total_pages = ceil($total_badges / $per_page);
    
    // Get all users for badge awarding
    $users_stmt = $pdo->query("SELECT id, username, email FROM users WHERE is_active = 1 ORDER BY username");
    $users = $users_stmt->fetchAll();
    
    // Get all assessments for badge assignment
    $assessments_stmt = $pdo->query("SELECT id, title, category FROM assessments WHERE is_active = 1 ORDER BY title");
    $assessments = $assessments_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Badges admin error: " . $e->getMessage());
    $badges = [];
    $total_badges = 0;
    $total_pages = 0;
    $users = [];
    $assessments = [];
}

$additional_css = "
    .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .filters-section { background: white; padding: 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem; }
    .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; }
    .badge-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
    .badge-card { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 1.5rem; transition: all var(--transition-normal); position: relative; overflow: hidden; }
    .badge-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-xl); }
    .badge-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .badge-card.rarity-common::before { background: #6b7280; }
    .badge-card.rarity-uncommon::before { background: #059669; }
    .badge-card.rarity-rare::before { background: #2563eb; }
    .badge-card.rarity-epic::before { background: #7c3aed; }
    .badge-card.rarity-legendary::before { background: linear-gradient(90deg, #f59e0b, #dc2626); }
    .badge-card.rarity-cardinal::before { 
        background: linear-gradient(90deg, #dc2626, #991b1b, #dc2626);
        height: 6px;
        animation: cardinalPulse 2s ease-in-out infinite;
    }
    @keyframes cardinalPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .badge-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .badge-icon { font-size: 3rem; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: var(--gray-50); border-radius: var(--border-radius-md); }
    .badge-info { flex: 1; }
    .badge-name { font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.25rem; }
    .badge-meta { font-size: 0.85rem; color: var(--gray-500); display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .badge-description { color: var(--gray-600); margin-bottom: 1rem; line-height: 1.5; font-size: 0.95rem; }
    .badge-stats { display: flex; gap: 1rem; margin-bottom: 1rem; padding: 0.75rem; background: var(--gray-50); border-radius: var(--border-radius-md); }
    .stat-item { flex: 1; text-align: center; }
    .stat-value { font-size: 1.5rem; font-weight: 600; color: var(--primary-blue); display: block; }
    .stat-label { font-size: 0.75rem; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.25rem; }
    .badge-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .badge-actions .btn { flex: 1; min-width: 100px; }
    .rarity-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; position: relative; overflow: hidden; z-index: 1; }
    .rarity-badge.rarity-common { background: #4b5563; color: #ffffff; }
    .rarity-badge.rarity-uncommon { background: #059669; color: #ffffff; }
    .rarity-badge.rarity-rare { background: #2563eb; color: #ffffff; }
    .rarity-badge.rarity-epic { background: #7c3aed; color: #ffffff; }
    .rarity-badge.rarity-legendary { background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%); color: #ffffff; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3); }
    .rarity-badge.rarity-cardinal { 
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #dc2626 100%); 
        color: #ffffff; 
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
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
        background: linear-gradient(
            45deg,
            transparent,
            rgba(255, 255, 255, 0.4),
            transparent
        );
        transform: rotate(45deg);
        animation: cardinalSweep 3s linear infinite;
    }
    @keyframes cardinalShine {
        0%, 100% { 
            box-shadow: 0 0 15px rgba(220, 38, 38, 0.6);
        }
        50% { 
            box-shadow: 0 0 25px rgba(220, 38, 38, 0.9);
        }
    }
    @keyframes cardinalSweep {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s ease; }
    .status-active { background: #059669; color: #ffffff; }
    .status-active:hover { background: #047857; }
    .status-inactive { background: #dc2626; color: #ffffff; }
    .status-inactive:hover { background: #b91c1c; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 2rem; border-radius: var(--border-radius-lg); max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .modal-title { font-size: 1.5rem; font-weight: 600; }
    .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--gray-500); }
    .modal-close:hover { color: var(--gray-900); }
    @media (max-width: 768px) {
        .filters-grid { grid-template-columns: 1fr; }
        .badge-grid { grid-template-columns: 1fr; }
        .page-header { flex-direction: column; }
    }
";
include 'includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <div class="page-header">
        <h1>Manage Badges</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="badge-edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Badge
            </a>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="filters-section">
        <form method="GET">
            <div class="filters-grid">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search badges..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <option value="reading" <?php echo $category_filter === 'reading' ? 'selected' : ''; ?>>Reading</option>
                    <option value="knowledge" <?php echo $category_filter === 'knowledge' ? 'selected' : ''; ?>>Knowledge</option>
                    <option value="exploration" <?php echo $category_filter === 'exploration' ? 'selected' : ''; ?>>Exploration</option>
                    <option value="social" <?php echo $category_filter === 'social' ? 'selected' : ''; ?>>Social</option>
                    <option value="special" <?php echo $category_filter === 'special' ? 'selected' : ''; ?>>Special</option>
                </select>
                
                <select name="rarity" class="form-control">
                    <option value="">All Rarities</option>
                    <option value="common" <?php echo $rarity_filter === 'common' ? 'selected' : ''; ?>>Common</option>
                    <option value="uncommon" <?php echo $rarity_filter === 'uncommon' ? 'selected' : ''; ?>>Uncommon</option>
                    <option value="rare" <?php echo $rarity_filter === 'rare' ? 'selected' : ''; ?>>Rare</option>
                    <option value="epic" <?php echo $rarity_filter === 'epic' ? 'selected' : ''; ?>>Epic</option>
                    <option value="legendary" <?php echo $rarity_filter === 'legendary' ? 'selected' : ''; ?>>Legendary</option>
                    <option value="cardinal" <?php echo $rarity_filter === 'cardinal' ? 'selected' : ''; ?>>Cardinal</option>
                </select>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="badges.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($badges)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: var(--border-radius-lg);">
            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🏆</div>
            <h3>No badges found</h3>
            <p>Create your first badge to reward users!</p>
            <a href="badge-edit.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Create Badge
            </a>
        </div>
    <?php else: ?>
        <div class="badge-grid">
            <?php foreach ($badges as $badge): ?>
                <div class="badge-card">
                    <div class="badge-header">
                        <div class="badge-icon"><?php echo $badge['icon'] ?? '🏆'; ?></div>
                        <div class="badgeinfo">
                            <h3 class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></h3>
                            <div class="badge-meta">
                                <span class="rarity-badge rarity-<?php echo $badge['rarity']; ?>">
                                    <span style="position: relative; z-index: 1;"><?php echo ucfirst($badge['rarity']); ?></span>
                                </span>
                                <span><i class="fas fa-folder"></i> <?php echo ucfirst($badge['category']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="badge-description">
                        <?php echo htmlspecialchars($badge['description']); ?>
                    </div>
                    
                    <div class="badge-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($badge['points']); ?></span>
                            <span class="stat-label">Points</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($badge['earned_count']); ?></span>
                            <span class="stat-label">Earned</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($badge['assessment_count']); ?></span>
                            <span class="stat-label">Assessments</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="badge_id" value="<?php echo $badge['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $badge['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_status" 
                                    class="status-badge <?php echo $badge['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $badge['is_active'] ? 'Active' : 'Inactive'; ?>
                            </button>
                        </form>
                        
                        <div style="font-size: 0.85rem; color: var(--gray-500);">
                            Created <?php echo date('M j, Y', strtotime($badge['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="badge-actions">
                        <button onclick="openAwardModal(<?php echo $badge['id']; ?>, '<?php echo htmlspecialchars($badge['name']); ?>')" 
                                class="btn btn-sm btn-success" title="Award to User">
                            <i class="fas fa-gift"></i> Award
                        </button>
                        <button onclick="openAssignModal(<?php echo $badge['id']; ?>, '<?php echo htmlspecialchars($badge['name']); ?>')" 
                                class="btn btn-sm btn-info" title="Assign to Assessment">
                            <i class="fas fa-link"></i> Assign
                        </button>
                        <a href="badge-edit.php?id=<?php echo $badge['id']; ?>" 
                           class="btn btn-sm btn-primary" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="?delete=<?php echo $badge['id']; ?>&confirm=1" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Delete this badge?')"
                           title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'category' => $category_filter, 'rarity' => $rarity_filter])); ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'category' => $category_filter, 'rarity' => $rarity_filter])); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'category' => $category_filter, 'rarity' => $rarity_filter])); ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Award Badge Modal -->
<div id="awardModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Award Badge to User</h2>
            <button class="modal-close" onclick="closeAwardModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="badge_id" id="award_badge_id">
            <div class="form-group">
                <label class="form-label">Badge</label>
                <input type="text" id="award_badge_name" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Select User *</label>
                <select name="user_id" class="form-control" required>
                    <option value="">Choose a user...</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAwardModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <button type="submit" name="award_badge" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-gift"></i> Award Badge
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Assign to Assessment Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Assign Badge to Assessment</h2>
            <button class="modal-close" onclick="closeAssignModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="badge_id" id="assign_badge_id">
            <div class="form-group">
                <label class="form-label">Badge</label>
                <input type="text" id="assign_badge_name" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Select Assessment *</label>
                <select name="assessment_id" class="form-control" required>
                    <option value="">Choose an assessment...</option>
                    <?php foreach ($assessments as $assessment): ?>
                        <option value="<?php echo $assessment['id']; ?>">
                            <?php echo htmlspecialchars($assessment['title']); ?> 
                            (<?php echo htmlspecialchars($assessment['category']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAssignModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <button type="submit" name="assign_to_assessment" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-link"></i> Assign Badge
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAwardModal(badgeId, badgeName) {
    document.getElementById('award_badge_id').value = badgeId;
    document.getElementById('award_badge_name').value = badgeName;
    document.getElementById('awardModal').classList.add('active');
}

function closeAwardModal() {
    document.getElementById('awardModal').classList.remove('active');
}

function openAssignModal(badgeId, badgeName) {
    document.getElementById('assign_badge_id').value = badgeId;
    document.getElementById('assign_badge_name').value = badgeName;
    document.getElementById('assignModal').classList.add('active');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('active');
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>