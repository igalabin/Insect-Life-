<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Edit User - Admin";
$success_message = '';
$error_message = '';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $user_id > 0;

// Get user data if editing
$user = null;
if ($is_edit) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error_message = "User not found.";
            $is_edit = false;
        }
    } catch (Exception $e) {
        $error_message = "Error loading user: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $user_type = sanitizeInput($_POST['user_type'] ?? 'user');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $bio = sanitizeInput($_POST['bio'] ?? '');
        
        $errors = [];
        if (empty($username)) $errors[] = "Username is required.";
        if (empty($email)) $errors[] = "Email is required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
        
        // Check for duplicate username/email (excluding current user)
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $user_id]);
                if ($stmt->fetch()) {
                    $errors[] = "Username already exists.";
                }
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $errors[] = "Email already exists.";
                }
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                if ($is_edit) {
                    // Update existing user
                    $stmt = $pdo->prepare("
                        UPDATE users SET
                            username = ?, email = ?, first_name = ?, last_name = ?,
                            user_type = ?, is_active = ?, bio = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $username, $email, $first_name, $last_name,
                        $user_type, $is_active, $bio, $user_id
                    ]);
                    
                    // Update password if provided
                    if (!empty($_POST['password'])) {
                        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$password_hash, $user_id]);
                    }
                    
                    $success_message = "User updated successfully!";
                } else {
                    // Create new user
                    $password = $_POST['password'] ?? '';
                    if (empty($password)) {
                        $errors[] = "Password is required for new users.";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO users (
                                username, email, password_hash, first_name, last_name,
                                user_type, is_active, bio, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $username, $email, $password_hash, $first_name, $last_name,
                            $user_type, $is_active, $bio
                        ]);
                        $user_id = $pdo->lastInsertId();
                        $success_message = "User created successfully!";
                        $is_edit = true;
                    }
                }
                
                // Reload user data
                if ($is_edit && empty($errors)) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                }
                
            } catch (Exception $e) {
                $error_message = "Error saving user: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Get user statistics if editing
$stats = null;
if ($is_edit && $user) {
    try {
        $pdo = getDBConnection();
        
        $stats = [
            'articles_read' => $pdo->prepare("SELECT COUNT(DISTINCT article_id) FROM reading_progress WHERE user_id = ?"),
            'comments_posted' => $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?"),
            'assessments_taken' => $pdo->prepare("SELECT COUNT(*) FROM user_assessments WHERE user_id = ?"),
            'badges_earned' => $pdo->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ?"),
            'bookmarks' => $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?")
        ];
        
        foreach ($stats as $key => $stmt) {
            $stmt->execute([$user_id]);
            $stats[$key] = $stmt->fetchColumn();
        }
        
    } catch (Exception $e) {
        error_log("User stats error: " . $e->getMessage());
        $stats = null;
    }
}

$additional_css = "
    .editor-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .editor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .user-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: white; padding: 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); text-align: center; }
    .stat-number { font-size: 2rem; font-weight: bold; color: var(--primary-blue); }
    .stat-label { font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem; }
    .editor-form { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 2rem; }
    .form-section { margin-bottom: 2rem; }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-200); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group-full { grid-column: 1 / -1; }
    .action-buttons { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; }
    .danger-zone { background: rgba(239, 68, 68, 0.05); border: 2px solid rgba(239, 68, 68, 0.2); border-radius: var(--border-radius-lg); padding: 1.5rem; margin-top: 2rem; }
    .danger-zone h3 { color: #dc2626; margin-bottom: 1rem; }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .user-stats { grid-template-columns: repeat(2, 1fr); }
    }
";

include 'includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<div class="editor-container">
    <div class="editor-header">
        <h1><?php echo $is_edit ? 'Edit User' : 'Create New User'; ?></h1>
        <a href="users.php" class="btn btn-secondary">Back to Users</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($is_edit && $stats): ?>
    <div class="user-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['articles_read']); ?></div>
            <div class="stat-label">Articles Read</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['comments_posted']); ?></div>
            <div class="stat-label">Comments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['assessments_taken']); ?></div>
            <div class="stat-label">Assessments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['badges_earned']); ?></div>
            <div class="stat-label">Badges</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['bookmarks']); ?></div>
            <div class="stat-label">Bookmarks</div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="editor-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <!-- Account Information -->
        <div class="form-section">
            <h2 class="section-title">Account Information</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">User Type</label>
                    <select name="user_type" class="form-control">
                        <option value="user" <?php echo ($user['user_type'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="contributor" <?php echo ($user['user_type'] ?? '') === 'contributor' ? 'selected' : ''; ?>>Contributor</option>
                        <option value="admin" <?php echo ($user['user_type'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="is_active" id="is_active" style="width: 20px; height: 20px;"
                               <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="is_active" style="margin: 0; cursor: pointer;">Active Account</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="form-section">
            <h2 class="section-title">Personal Information</h2>
            
            <div class="form-group-full">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                <small style="color: var(--gray-500);">Brief description about the user</small>
            </div>
        </div>

        <!-- Password -->
        <div class="form-section">
            <h2 class="section-title"><?php echo $is_edit ? 'Change Password' : 'Set Password'; ?></h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">
                        <?php echo $is_edit ? 'New Password (leave blank to keep current)' : 'Password *'; ?>
                    </label>
                    <input type="password" name="password" class="form-control" 
                           <?php echo !$is_edit ? 'required' : ''; ?>>
                    <small style="color: var(--gray-500);">Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirm" class="form-control">
                </div>
            </div>
        </div>

        <?php if ($is_edit && $user): ?>
        <!-- Account Details -->
        <div class="form-section">
            <h2 class="section-title">Account Details</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Member Since</label>
                    <input type="text" class="form-control" 
                           value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Login</label>
                    <input type="text" class="form-control" 
                           value="<?php echo $user['last_login'] ? formatTimeAgo($user['last_login']) : 'Never'; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Articles Read</label>
                    <input type="text" class="form-control" 
                           value="<?php echo number_format($stats['articles_read']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Comments</label>
                    <input type="text" class="form-control" 
                           value="<?php echo number_format($stats['comments_posted']); ?>" readonly>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="users.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update User' : 'Create User'; ?>
            </button>
        </div>
    </form>

    <?php if ($is_edit && $user && $user['id'] != $_SESSION['user_id']): ?>
    <!-- Danger Zone -->
    <div class="danger-zone">
        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        <p style="color: var(--gray-700); margin-bottom: 1rem;">
            Deleting this user will permanently remove all their data, including comments, reading progress, and assessment results.
        </p>
        <a href="users.php?delete=<?php echo $user['id']; ?>&confirm=1" 
           class="btn btn-danger" 
           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')">
            <i class="fas fa-trash"></i> Delete User
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.editor-form');
    const password = document.querySelector('input[name="password"]');
    const confirm = document.querySelector('input[name="password_confirm"]');
    
    form.addEventListener('submit', function(e) {
        if (password.value && password.value !== confirm.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            confirm.focus();
            return false;
        }
        
        if (password.value && password.value.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            password.focus();
            return false;
        }
    });
});
</script>

