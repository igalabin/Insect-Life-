<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Site Settings - Admin";
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // General Settings
            if (isset($_POST['save_general'])) {
                $site_name = sanitizeInput($_POST['site_name'] ?? '');
                $site_description = sanitizeInput($_POST['site_description'] ?? '');
                $site_keywords = sanitizeInput($_POST['site_keywords'] ?? '');
                $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
                $items_per_page = intval($_POST['items_per_page'] ?? 12);
                
                $settings = [
                    'site_name' => $site_name,
                    'site_description' => $site_description,
                    'site_keywords' => $site_keywords,
                    'contact_email' => $contact_email,
                    'items_per_page' => $items_per_page
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success_message = 'General settings saved successfully!';
            }
            
            // User Settings
            if (isset($_POST['save_user'])) {
                $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
                $require_email_verification = isset($_POST['require_email_verification']) ? 1 : 0;
                $default_user_type = sanitizeInput($_POST['default_user_type'] ?? 'user');
                $session_timeout = intval($_POST['session_timeout'] ?? 3600);
                
                $settings = [
                    'allow_registration' => $allow_registration,
                    'require_email_verification' => $require_email_verification,
                    'default_user_type' => $default_user_type,
                    'session_timeout' => $session_timeout
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success_message = 'User settings saved successfully!';
            }
            
            // Content Settings
            if (isset($_POST['save_content'])) {
                $default_article_status = sanitizeInput($_POST['default_article_status'] ?? 'draft');
                $enable_comments = isset($_POST['enable_comments']) ? 1 : 0;
                $auto_approve_comments = isset($_POST['auto_approve_comments']) ? 1 : 0;
                $max_upload_size = intval($_POST['max_upload_size'] ?? 5);
                
                $settings = [
                    'default_article_status' => $default_article_status,
                    'enable_comments' => $enable_comments,
                    'auto_approve_comments' => $auto_approve_comments,
                    'max_upload_size' => $max_upload_size
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success_message = 'Content settings saved successfully!';
            }
            
            // Security Settings
            if (isset($_POST['save_security'])) {
                $enable_rate_limiting = isset($_POST['enable_rate_limiting']) ? 1 : 0;
                $max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
                $lockout_duration = intval($_POST['lockout_duration'] ?? 30);
                $password_min_length = intval($_POST['password_min_length'] ?? 8);
                
                $settings = [
                    'enable_rate_limiting' => $enable_rate_limiting,
                    'max_login_attempts' => $max_login_attempts,
                    'lockout_duration' => $lockout_duration,
                    'password_min_length' => $password_min_length
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
                
                $success_message = 'Security settings saved successfully!';
            }
            
        } catch (Exception $e) {
            error_log("Settings error: " . $e->getMessage());
            $error_message = 'Error saving settings: ' . $e->getMessage();
        }
    }
}

// Load current settings
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    error_log("Settings load error: " . $e->getMessage());
    $settings = [];
}

// Helper function to get setting value
function getSetting($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}

$additional_css = "
    .settings-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .settings-nav { display: flex; gap: 0.5rem; background: white; padding: 1rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem; overflow-x: auto; }
    .settings-nav-link { padding: 0.75rem 1.5rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: var(--border-radius-md); font-weight: 500; transition: all var(--transition-fast); white-space: nowrap; }
    .settings-nav-link:hover { background: var(--primary-blue); color: white; }
    .settings-nav-link.active { background: var(--primary-blue); color: white; box-shadow: var(--shadow-md); }
    .settings-section { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 2rem; margin-bottom: 2rem; }
    .section-title { font-size: 1.5rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--gray-200); }
    .section-description { color: var(--gray-600); margin-bottom: 2rem; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .form-group-full { grid-column: 1 / -1; }
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700); }
    .form-control { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-md); font-size: 1rem; transition: border-color var(--transition-fast); }
    .form-control:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .form-text { font-size: 0.875rem; color: var(--gray-500); margin-top: 0.5rem; }
    .form-check { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius-md); }
    .form-check-input { width: 20px; height: 20px; accent-color: var(--primary-blue); cursor: pointer; }
    .form-check-label { font-size: 0.95rem; color: var(--gray-700); cursor: pointer; }
    .settings-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-200); }
    .info-box { background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-blue); padding: 1rem 1.5rem; border-radius: var(--border-radius-md); margin-bottom: 2rem; }
    .info-box i { color: var(--primary-blue); margin-right: 0.5rem; }
    .warning-box { background: rgba(251, 191, 36, 0.1); border-left: 4px solid var(--accent-orange); padding: 1rem 1.5rem; border-radius: var(--border-radius-md); margin-bottom: 2rem; }
    .warning-box i { color: var(--accent-orange); margin-right: 0.5rem; }
    @media (max-width: 768px) {
        .settings-container { padding: 1rem 0.5rem; }
        .form-grid { grid-template-columns: 1fr; }
        .settings-nav { flex-wrap: wrap; }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<div class="settings-container">
    <div class="page-header">
        <h1>Site Settings</h1>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
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

    <!-- Settings Navigation -->
    <div class="settings-nav">
        <a href="#general" class="settings-nav-link active" onclick="showSection('general', event)">
            <i class="fas fa-cog"></i> General
        </a>
        <a href="#users" class="settings-nav-link" onclick="showSection('users', event)">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="#content" class="settings-nav-link" onclick="showSection('content', event)">
            <i class="fas fa-file-alt"></i> Content
        </a>
        <a href="#security" class="settings-nav-link" onclick="showSection('security', event)">
            <i class="fas fa-shield-alt"></i> Security
        </a>
        <a href="#maintenance" class="settings-nav-link" onclick="showSection('maintenance', event)">
            <i class="fas fa-tools"></i> Maintenance
        </a>
    </div>

    <!-- General Settings -->
    <div id="general-section" class="settings-section">
        <h2 class="section-title">General Settings</h2>
        <p class="section-description">Configure basic site information and appearance</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('site_name', 'Insect Life')); ?>" required>
                    <small class="form-text">The name of your website</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('contact_email', 'admin@insectlife.com')); ?>" required>
                    <small class="form-text">Main contact email address</small>
                </div>
                
                <div class="form-group-full">
                    <label class="form-label">Site Description</label>
                    <textarea name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars(getSetting('site_description', 'Explore the fascinating world of insects')); ?></textarea>
                    <small class="form-text">Brief description for search engines</small>
                </div>
                
                <div class="form-group-full">
                    <label class="form-label">Site Keywords</label>
                    <input type="text" name="site_keywords" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('site_keywords', 'insects, entomology, education')); ?>">
                    <small class="form-text">Comma-separated keywords for SEO</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Items Per Page</label>
                    <input type="number" name="items_per_page" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('items_per_page', '12')); ?>" min="6" max="50">
                    <small class="form-text">Default pagination size</small>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" name="save_general" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save General Settings
                </button>
            </div>
        </form>
    </div>

    <!-- User Settings -->
    <div id="users-section" class="settings-section" style="display: none;">
        <h2 class="section-title">User Settings</h2>
        <p class="section-description">Manage user registration and authentication settings</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-check">
                <input type="checkbox" name="allow_registration" class="form-check-input" 
                       <?php echo getSetting('allow_registration', '1') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <strong>Allow User Registration</strong><br>
                    <small>Enable new users to create accounts on your site</small>
                </label>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="require_email_verification" class="form-check-input"
                       <?php echo getSetting('require_email_verification', '1') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <strong>Require Email Verification</strong><br>
                    <small>Users must verify their email address before accessing the site</small>
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Default User Type</label>
                    <select name="default_user_type" class="form-control">
                        <option value="user" <?php echo getSetting('default_user_type', 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="contributor" <?php echo getSetting('default_user_type', 'user') === 'contributor' ? 'selected' : ''; ?>>Contributor</option>
                    </select>
                    <small class="form-text">Role assigned to new users</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Session Timeout (seconds)</label>
                    <input type="number" name="session_timeout" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('session_timeout', '3600')); ?>" min="300" max="86400">
                    <small class="form-text">How long users stay logged in (3600 = 1 hour)</small>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" name="save_user" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save User Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Content Settings -->
    <div id="content-section" class="settings-section" style="display: none;">
        <h2 class="section-title">Content Settings</h2>
        <p class="section-description">Configure content creation and moderation settings</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Default Article Status</label>
                    <select name="default_article_status" class="form-control">
                        <option value="draft" <?php echo getSetting('default_article_status', 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo getSetting('default_article_status', 'draft') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                    <small class="form-text">Initial status for new articles</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Max Upload Size (MB)</label>
                    <input type="number" name="max_upload_size" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('max_upload_size', '5')); ?>" min="1" max="50">
                    <small class="form-text">Maximum file size for uploads</small>
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="enable_comments" class="form-check-input"
                       <?php echo getSetting('enable_comments', '1') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <strong>Enable Comments</strong><br>
                    <small>Allow users to comment on articles</small>
                </label>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="auto_approve_comments" class="form-check-input"
                       <?php echo getSetting('auto_approve_comments', '0') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <strong>Auto-Approve Comments</strong><br>
                    <small>Automatically publish comments without moderation</small>
                </label>
            </div>

            <div class="settings-actions">
                <button type="submit" name="save_content" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Content Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Security Settings -->
    <div id="security-section" class="settings-section" style="display: none;">
        <h2 class="section-title">Security Settings</h2>
        <p class="section-description">Configure security and protection settings</p>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Warning:</strong> Changing these settings may affect user login and site security. Proceed with caution.
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-check">
                <input type="checkbox" name="enable_rate_limiting" class="form-check-input"
                       <?php echo getSetting('enable_rate_limiting', '1') ? 'checked' : ''; ?>>
                <label class="form-check-label">
                    <strong>Enable Rate Limiting</strong><br>
                    <small>Protect against brute force attacks</small>
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Max Login Attempts</label>
                    <input type="number" name="max_login_attempts" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('max_login_attempts', '5')); ?>" min="3" max="10">
                    <small class="form-text">Failed attempts before lockout</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lockout Duration (minutes)</label>
                    <input type="number" name="lockout_duration" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('lockout_duration', '30')); ?>" min="5" max="1440">
                    <small class="form-text">How long accounts are locked</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Minimum Password Length</label>
                    <input type="number" name="password_min_length" class="form-control" 
                           value="<?php echo htmlspecialchars(getSetting('password_min_length', '8')); ?>" min="6" max="32">
                    <small class="form-text">Required password length</small>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" name="save_security" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Security Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Maintenance Section -->
    <div id="maintenance-section" class="settings-section" style="display: none;">
        <h2 class="section-title">Maintenance & Tools</h2>
        <p class="section-description">Database optimization and system maintenance</p>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            These tools help maintain your site's performance and data integrity.
        </div>

        <div class="form-grid">
            <div class="card">
                <h4><i class="fas fa-database"></i> Clear Cache</h4>
                <p>Remove temporary cached data to free up space.</p>
                <button type="button" class="btn btn-secondary" onclick="clearCache()">
                    <i class="fas fa-trash"></i> Clear Cache
                </button>
            </div>

            <div class="card">
                <h4><i class="fas fa-broom"></i> Optimize Database</h4>
                <p>Optimize database tables for better performance.</p>
                <button type="button" class="btn btn-secondary" onclick="optimizeDB()">
                    <i class="fas fa-wrench"></i> Optimize Now
                </button>
            </div>

            <div class="card">
                <h4><i class="fas fa-download"></i> Backup Database</h4>
                <p>Create a backup of your entire database.</p>
                <button type="button" class="btn btn-secondary" onclick="backupDB()">
                    <i class="fas fa-download"></i> Backup Now
                </button>
            </div>

            <div class="card">
                <h4><i class="fas fa-chart-line"></i> Generate Reports</h4>
                <p>Generate usage and analytics reports.</p>
                <button type="button" class="btn btn-secondary" onclick="generateReports()">
                    <i class="fas fa-file-export"></i> Generate
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showSection(section, event) {
    event.preventDefault();
    
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
    
    // Remove active class from all links
    document.querySelectorAll('.settings-nav-link').forEach(l => l.classList.remove('active'));
    
    // Show selected section
    document.getElementById(section + '-section').style.display = 'block';
    
    // Add active class to clicked link
    event.target.closest('.settings-nav-link').classList.add('active');
    
    // Update URL hash
    window.location.hash = section;
}

// Show section based on URL hash on page load
window.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    if (hash) {
        const link = document.querySelector(`a[href="#${hash}"]`);
        if (link) {
            link.click();
        }
    }
});

// Maintenance functions
function clearCache() {
    if (confirm('Clear all cached data?')) {
        fetch('ajax/clear-cache.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Cache cleared successfully!' : 'Error: ' + data.error);
            });
    }
}

function optimizeDB() {
    if (confirm('Optimize database tables? This may take a few moments.')) {
        fetch('ajax/optimize-db.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Database optimized successfully!' : 'Error: ' + data.error);
            });
    }
}

function backupDB() {
    if (confirm('Create a database backup?')) {
        window.location.href = 'ajax/backup-db.php';
    }
}

function generateReports() {
    window.location.href = 'reports.php';
}
</script>

