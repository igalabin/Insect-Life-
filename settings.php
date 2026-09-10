<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php?redirect=settings.php');
}

$page_title = "Account Settings - Insect Life";
$page_description = "Manage your account settings and preferences.";

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirectTo('logout.php');
    }
} catch (Exception $e) {
    error_log("Settings page error: " . $e->getMessage());
    $error_message = 'Error loading settings.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_profile':
                try {
                    $first_name = sanitizeInput($_POST['first_name'] ?? '');
                    $last_name = sanitizeInput($_POST['last_name'] ?? '');
                    $experience_level = sanitizeInput($_POST['experience_level'] ?? '');
                    $newsletter_subscribed = isset($_POST['newsletter_subscribed']) ? 1 : 0;

                    if (empty($first_name) || empty($last_name)) {
                        $error_message = 'First name and last name are required.';
                    } else {
                        // 1. Update primary user record
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET first_name = ?, last_name = ?, experience_level = ?, newsletter_subscribed = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$first_name, $last_name, $experience_level, $newsletter_subscribed, $user_id]);

                        if ($stmt->rowCount() > 0) {
                            $success_message = "Profile updated successfully! (User ID: $user_id)";
                        } else {
                            $success_message = "Profile saved (no changes were made).";
                        }

                        // 2. Update user interests
                        $interests = $_POST['interests'] ?? [];
                        try {
                            $stmt = $pdo->prepare("DELETE FROM user_interests WHERE user_id = ?");
                            $stmt->execute([$user_id]);

                            if (!empty($interests)) {
                                $stmt = $pdo->prepare("INSERT INTO user_interests (user_id, interest) VALUES (?, ?)");
                                foreach ($interests as $interest) {
                                    $stmt->execute([$user_id, sanitizeInput($interest)]);
                                }
                            }
                        } catch (Exception $ie) {
                            error_log("Interests update error: " . $ie->getMessage());
                        }

                        // 3. Reload fresh user data for view
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                    }
                } catch (Exception $e) {
                    error_log("Profile update error: " . $e->getMessage());
                    $error_message = 'Database Error: ' . $e->getMessage();
                }
                break;

            case 'change_password':
                try {
                    $current_password = $_POST['current_password'] ?? '';
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';

                    if (!password_verify($current_password, $user['password_hash'])) {
                        $error_message = 'Current password is incorrect.';
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = 'New passwords do not match.';
                    } elseif (strlen($new_password) < 8) {
                        $error_message = 'New password must be at least 8 characters long.';
                    } else {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$new_password_hash, $user_id]);

                        $success_message = 'Password changed successfully!';
                    }
                } catch (Exception $e) {
                    error_log("Password change error: " . $e->getMessage());
                    $error_message = 'Error changing password.';
                }
                break;
        }
    }
}
                

// Get user interests
try {
    $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_interests = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $user_interests = [];
}

$additional_css = "
    .settings-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .settings-header {
        text-align: center;
        margin-bottom: 3rem;
        color: white;
    }
    
    .settings-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .interests-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .interest-option {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
        background: white;
    }
    
    .interest-option:hover {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.05);
    }
    
    .interest-option.selected {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-blue);
    }
    
    .danger-zone {
        border-top: 1px solid var(--gray-200);
        padding-top: 2rem;
        margin-top: 2rem;
    }
    
    .danger-zone h3 {
        color: #dc2626;
        margin-bottom: 1rem;
    }
    
    .btn-danger {
        background: #dc2626;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .interests-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>

<div class="settings-container">
    <div class="settings-header">
        <h1>Account Settings</h1>
        <p>Manage your profile information and preferences</p>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <!-- Profile Settings -->
    <div class="settings-card">
        <div class="card-header">
            <h2 class="card-title">Profile Information</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" class="form-control" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small class="form-text">Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <small class="form-text">Email cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="experience_level" class="form-label">Experience Level</label>
                    <select id="experience_level" name="experience_level" class="form-control">
                        <option value="beginner" <?php echo $user['experience_level'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo $user['experience_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo $user['experience_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                        <option value="expert" <?php echo $user['experience_level'] === 'expert' ? 'selected' : ''; ?>>Expert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Interests</label>
                    <div class="interests-grid">
                        <?php
                        $interests = [
                            'butterflies' => '🦋 Butterflies & Moths',
                            'beetles' => '🪲 Beetles', 
                            'ants' => '🐜 Ants & Social Insects',
                            'bees' => '🐝 Bees & Wasps',
                            'conservation' => '🌿 Conservation',
                            'photography' => '📸 Insect Photography',
                            'research' => '🔬 Scientific Research',
                            'gardening' => '🌱 Garden Insects'
                        ];
                        
                        foreach ($interests as $value => $label):
                        ?>
                        <label class="interest-option <?php echo in_array($value, $user_interests) ? 'selected' : ''; ?>">
                            <input type="checkbox" name="interests[]" value="<?php echo $value; ?>"
                                   <?php echo in_array($value, $user_interests) ? 'checked' : ''; ?>>
                            <span><?php echo $label; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                       <input type="checkbox" name="newsletter_subscribed"
    <?php echo !empty($user['newsletter_subscribed']) ? 'checked' : ''; ?>>
                        Subscribe to newsletter for latest updates
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Password Settings -->
    <div class="settings-card">
        <div class="card-header">
            <h2 class="card-title">Change Password</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" 
                           class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>

    <!-- Account Management -->
    <div class="settings-card">
        <div class="card-header">
            <h2 class="card-title">Account Management</h2>
        </div>
        <div class="card-body">
            <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            <p><strong>Last login:</strong> <?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
            
            <div class="danger-zone">
                <h3>Danger Zone</h3>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <button type="button" class="btn-danger" onclick="confirmDeleteAccount()">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interest selection styling
    document.querySelectorAll('.interest-option input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.parentElement.classList.add('selected');
            } else {
                this.parentElement.classList.remove('selected');
            }
        });
    });
    
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function checkPasswordMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', checkPasswordMatch);
    confirmPassword.addEventListener('input', checkPasswordMatch);
});

function confirmDeleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        if (confirm('This will permanently delete all your data, including reading progress and badges. Are you absolutely sure?')) {
            // Redirect to account deletion page
            window.location.href = 'delete-account.php';
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>