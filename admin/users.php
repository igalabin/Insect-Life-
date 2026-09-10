<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Manage Users - Admin";
$success_message = '';
$error_message = '';

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id != $_SESSION['user_id']) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success_message = "User deleted successfully!";
        } catch (Exception $e) {
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $error_message = "You cannot delete your own account!";
    }
}

// Handle Status Change
if (isset($_POST['toggle_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = intval($_POST['is_active']);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        $success_message = "User status updated!";
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

try {
    $pdo = getDBConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(username LIKE :search OR email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if ($type_filter) {
        $where_conditions[] = "user_type = :type";
        $params['type'] = $type_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "is_active = :status";
        $params['status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM users $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetchColumn();
    
    // Get users
    $offset = ($page - 1) * $per_page;
    $query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $total_pages = ceil($total_users / $per_page);
    
} catch (Exception $e) {
    error_log("Users admin error: " . $e->getMessage());
    $users = [];
    $total_users = 0;
    $total_pages = 0;
}

$additional_css = "
    .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .filters-section { background: white; padding: 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem; }
    .filters-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem; }
    .table-container { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead { background: var(--gray-50); }
    .data-table th { padding: 1rem; text-align: left; font-weight: 600; color: var(--gray-700); border-bottom: 2px solid var(--gray-200); }
    .data-table td { padding: 1rem; border-bottom: 1px solid var(--gray-100); }
    .data-table tr:hover { background: var(--gray-50); }
    .action-btns { display: flex; gap: 0.5rem; }
    .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
    .status-active { background: var(--secondary-green); color: white; }
    .status-inactive { background: #ef4444; color: white; }
    @media (max-width: 768px) {
        .filters-grid { grid-template-columns: 1fr; }
        .table-container { overflow-x: auto; }
        .data-table { min-width: 800px; }
    }
";

include 'includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <div class="page-header">
        <h1>Manage Users</h1>
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
                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="user" <?php echo $type_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $type_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="users.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem;">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo ucfirst($user['user_type']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_status" class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>" style="border: none; cursor: pointer;">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? formatTimeAgo($user['last_login']) : 'Never'; ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>&confirm=1" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'type' => $type_filter, 'status' => $status_filter])); ?>">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'type' => $type_filter, 'status' => $status_filter])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search, 'type' => $type_filter, 'status' => $status_filter])); ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

