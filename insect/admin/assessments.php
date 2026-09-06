<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Manage Assessments - Admin";
$success_message = '';
$error_message = '';

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM assessments WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_message = "Assessment deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting assessment: " . $e->getMessage();
    }
}

// Handle Status Change
if (isset($_POST['toggle_status'])) {
    $assessment_id = intval($_POST['assessment_id']);
    $new_status = intval($_POST['is_active']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE assessments SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $assessment_id]);
        $success_message = "Assessment status updated!";
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(title LIKE :search OR description LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($difficulty_filter) {
        $where_conditions[] = "difficulty_level = :difficulty";
        $params['difficulty'] = $difficulty_filter;
    }
    
    if ($category_filter) {
        $where_conditions[] = "category = :category";
        $params['category'] = $category_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $count_query = "SELECT COUNT(*) FROM assessments $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_assessments = $count_stmt->fetchColumn();
    
    $offset = ($page - 1) * $per_page;
    $query = "
        SELECT a.*, 
               COUNT(DISTINCT ua.id) as attempt_count,
               AVG(ua.score) as avg_score
        FROM assessments a
        LEFT JOIN user_assessments ua ON a.id = ua.assessment_id
        $where_clause
        GROUP BY a.id
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
    $assessments = $stmt->fetchAll();
    
    $total_pages = ceil($total_assessments / $per_page);
    
} catch (Exception $e) {
    error_log("Assessments admin error: " . $e->getMessage());
    $assessments = [];
    $total_assessments = 0;
    $total_pages = 0;
}

$additional_css = "
    .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .filters-section { background: white; padding: 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem; }
    .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; }
    .assessments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
    .assessment-card { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 1.5rem; transition: all var(--transition-normal); }
    .assessment-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-xl); }
    .assessment-header { margin-bottom: 1rem; }
    .assessment-title { font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.5rem; }
    .assessment-meta { display: flex; gap: 1rem; font-size: 0.85rem; color: var(--gray-600); margin-bottom: 1rem; flex-wrap: wrap; }
    .assessment-description { color: var(--gray-700); line-height: 1.5; margin-bottom: 1rem; }
    .assessment-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius-md); }
    .stat-item { text-align: center; }
    .stat-value { font-size: 1.5rem; font-weight: 600; color: var(--primary-blue); display: block; }
    .stat-label { font-size: 0.75rem; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; }
    .assessment-actions { display: flex; gap: 0.5rem; }
    .difficulty-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .difficulty-beginner { background: var(--secondary-green); color: white; }
    .difficulty-intermediate { background: var(--accent-orange); color: white; }
    .difficulty-advanced { background: #ef4444; color: white; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; }
    .status-active { background: var(--secondary-green); color: white; }
    .status-inactive { background: #ef4444; color: white; }
    @media (max-width: 768px) {
        .filters-grid { grid-template-columns: 1fr; }
        .assessments-grid { grid-template-columns: 1fr; }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<div class="admin-container">
    <div class="page-header">
        <h1>Manage Assessments</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="assessment-edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Assessment
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

    <div class="filters-section">
        <form method="GET">
            <div class="filters-grid">
                <input type="text" name="search" class="form-control" placeholder="Search assessments..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="difficulty" class="form-control">
                    <option value="">All Levels</option>
                    <option value="beginner" <?php echo $difficulty_filter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                    <option value="intermediate" <?php echo $difficulty_filter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="advanced" <?php echo $difficulty_filter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                </select>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <option value="anatomy" <?php echo $category_filter === 'anatomy' ? 'selected' : ''; ?>>Anatomy</option>
                    <option value="behavior" <?php echo $category_filter === 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                    <option value="ecology" <?php echo $category_filter === 'ecology' ? 'selected' : ''; ?>>Ecology</option>
                    <option value="identification" <?php echo $category_filter === 'identification' ? 'selected' : ''; ?>>Identification</option>
                </select>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="assessments.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($assessments)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: var(--border-radius-lg);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
            <p>No assessments found. <a href="assessment-edit.php">Create your first assessment</a></p>
        </div>
    <?php else: ?>
        <div class="assessments-grid">
            <?php foreach ($assessments as $assessment): ?>
                <div class="assessment-card">
                    <div class="assessment-header">
                        <h3 class="assessment-title"><?php echo htmlspecialchars($assessment['title']); ?></h3>
                        <div class="assessment-meta">
                            <span class="difficulty-badge difficulty-<?php echo $assessment['difficulty_level']; ?>">
                                <?php echo ucfirst($assessment['difficulty_level']); ?>
                            </span>
                            <span><i class="fas fa-folder"></i> <?php echo ucfirst($assessment['category']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo $assessment['time_limit']; ?> min</span>
                        </div>
                    </div>
                    
                    <div class="assessment-description">
                        <?php echo htmlspecialchars(substr($assessment['description'], 0, 120)) . '...'; ?>
                    </div>
                    
                    <div class="assessment-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $assessment['question_count']; ?></span>
                            <span class="stat-label">Questions</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($assessment['attempt_count']); ?></span>
                            <span class="stat-label">Attempts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $assessment['avg_score'] ? round($assessment['avg_score']) . '%' : 'N/A'; ?></span>
                            <span class="stat-label">Avg Score</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="assessment_id" value="<?php echo $assessment['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $assessment['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_status" class="status-badge <?php echo $assessment['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $assessment['is_active'] ? 'Active' : 'Inactive'; ?>
                            </button>
                        </form>
                        
                        <div style="font-size: 0.85rem; color: var(--gray-500);">
                            Created <?php echo date('M j, Y', strtotime($assessment['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="assessment-actions">
                        <a href="../assessment.php?id=<?php echo $assessment['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Preview">
                            <i class="fas fa-eye"></i> Preview
                        </a>
                        <a href="assessment-edit.php?id=<?php echo $assessment['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="?delete=<?php echo $assessment['id']; ?>&confirm=1" class="btn btn-sm btn-danger" onclick="return confirm('Delete this assessment and all attempts?')" title="Delete">
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
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'difficulty' => $difficulty_filter, 'category' => $category_filter])); ?>">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'difficulty' => $difficulty_filter, 'category' => $category_filter])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'difficulty' => $difficulty_filter, 'category' => $category_filter])); ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

