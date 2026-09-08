<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Edit Badge - Admin";
$success_message = '';
$error_message = '';

$badge_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $badge_id > 0;

// Get badge data if editing
$badge = null;
if ($is_edit) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
        $stmt->execute([$badge_id]);
        $badge = $stmt->fetch();
        
        if (!$badge) {
            $error_message = "Badge not found.";
            $is_edit = false;
        }
    } catch (Exception $e) {
        $error_message = "Error loading badge: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? '🏆');
        $category = sanitizeInput($_POST['category'] ?? 'reading');
        $rarity = sanitizeInput($_POST['rarity'] ?? 'common');
        $points = intval($_POST['points'] ?? 10);
        $requirements_text = sanitizeInput($_POST['requirements'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Convert requirements to JSON format
        $requirements = json_encode(['description' => $requirements_text]);
        
        $errors = [];
        if (empty($name)) $errors[] = "Badge name is required.";
        if (empty($description)) $errors[] = "Description is required.";
        if ($points < 0) $errors[] = "Points must be 0 or greater.";
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                if ($is_edit) {
                    $stmt = $pdo->prepare("
                        UPDATE badges SET
                            name = ?, description = ?, icon = ?, category = ?, 
                            rarity = ?, points = ?, requirements = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name, $description, $icon, $category,
                        $rarity, $points, $requirements, $is_active, $badge_id
                    ]);
                    $success_message = "Badge updated successfully!";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO badges (
                            name, description, icon, category, rarity, 
                            points, requirements, is_active, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $name, $description, $icon, $category, $rarity,
                        $points, $requirements, $is_active
                    ]);
                    $badge_id = $pdo->lastInsertId();
                    $success_message = "Badge created successfully!";
                    $is_edit = true;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM badges WHERE id = ?");
                $stmt->execute([$badge_id]);
                $badge = $stmt->fetch();
                
            } catch (Exception $e) {
                $error_message = "Error saving badge: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Get requirements text from JSON
$requirements_text = '';
if ($badge && !empty($badge['requirements'])) {
    $req_data = json_decode($badge['requirements'], true);
    $requirements_text = $req_data['description'] ?? '';
}

$additional_css = "
    .editor-container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
    .editor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .editor-form { background: white; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 2rem; }
    .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--gray-200); }
    .form-section:last-of-type { border-bottom: none; }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--gray-900); }
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--gray-700); }
    .form-control { width: 100%; padding: 0.75rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-md); font-size: 1rem; }
    .form-control:focus { outline: none; border-color: var(--primary-blue); }
    .icon-picker { display: grid; grid-template-columns: repeat(8, 1fr); gap: 0.5rem; }
    .icon-option { padding: 1rem; text-align: center; font-size: 2rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-md); cursor: pointer; transition: all 0.2s; }
    .icon-option:hover { background: var(--gray-50); transform: scale(1.1); }
    .icon-option.selected { border-color: var(--primary-blue); background: rgba(59, 130, 246, 0.1); }
    .rarity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 1rem; }
    .rarity-option { padding: 1rem; border-radius: var(--border-radius-md); text-align: center; cursor: pointer; transition: all 0.2s; border: 2px solid transparent; position: relative; overflow: hidden; }
    .rarity-option:hover { transform: translateY(-2px); }
    .rarity-option.selected { border-color: var(--gray-900); box-shadow: var(--shadow-lg); }
    .rarity-common { background: #9ca3af; color: white; }
    .rarity-uncommon { background: #10b981; color: white; }
    .rarity-rare { background: #3b82f6; color: white; }
    .rarity-epic { background: #a855f7; color: white; }
    .rarity-legendary { background: linear-gradient(90deg, #f59e0b, #dc2626); color: white; }
    .rarity-cardinal { 
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #dc2626 100%); 
        color: white; 
        animation: cardinalShine 2s ease-in-out infinite;
        box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
    }
    @keyframes cardinalShine {
        0%, 100% { 
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.5), inset 0 0 20px rgba(255, 255, 255, 0.1);
        }
        50% { 
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.8), inset 0 0 30px rgba(255, 255, 255, 0.3);
        }
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
            rgba(255, 255, 255, 0.3),
            transparent
        );
        transform: rotate(45deg);
        animation: cardinalSweep 3s linear infinite;
    }
    @keyframes cardinalSweep {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    .action-buttons { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; }
    .badge-preview { background: var(--gray-50); padding: 2rem; border-radius: var(--border-radius-lg); text-align: center; margin-bottom: 2rem; }
    .badge-preview-icon { font-size: 5rem; margin-bottom: 1rem; }
    .badge-preview-name { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="editor-container">
    <div class="editor-header">
        <h1><?php echo $is_edit ? 'Edit Badge' : 'Create New Badge'; ?></h1>
        <a href="badges.php" class="btn btn-secondary">Back to Badges</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($is_edit && $badge): ?>
    <div class="badge-preview">
        <div class="badge-preview-icon"><?php echo $badge['icon'] ?? '🏆'; ?></div>
        <div class="badge-preview-name"><?php echo htmlspecialchars($badge['name']); ?></div>
        <div style="color: var(--gray-600);"><?php echo htmlspecialchars($badge['description']); ?></div>
        <div style="margin-top: 1rem; color: var(--primary-blue); font-weight: 600;">
            <i class="fas fa-star"></i> <?php echo number_format($badge['points']); ?> Points
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="editor-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <!-- Basic Info -->
        <div class="form-section">
            <h2 class="section-title">Basic Information</h2>
            
            <div class="form-group">
                <label class="form-label">Badge Name *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($badge['name'] ?? ''); ?>" 
                       placeholder="e.g., First Steps" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($badge['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Requirements</label>
                <textarea name="requirements" class="form-control" rows="2" 
                          placeholder="e.g., Read 10 articles about butterflies"><?php echo htmlspecialchars($requirements_text); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Points Awarded *</label>
                <input type="number" name="points" class="form-control" 
                       value="<?php echo htmlspecialchars($badge['points'] ?? '10'); ?>" 
                       min="0" required>
            </div>
        </div>

        <!-- Icon Selection -->
        <div class="form-section">
            <h2 class="section-title">Badge Icon (Emoji)</h2>
            <input type="hidden" name="icon" id="icon-input" value="<?php echo htmlspecialchars($badge['icon'] ?? '🏆'); ?>">
            <div class="icon-picker">
                <?php
                $icons = ['🏆', '🥇', '🥈', '🥉', '⭐', '🌟', '💫', '✨', 
                          '🎯', '🎖️', '🏅', '👑', '💎', '🔥', '⚡', '💪',
                          '🦋', '🐛', '🐞', '🐝', '🦗', '🕷️', '🦟', '🪲',
                          '📚', '🎓', '🔬', '🧪', '🌱', '🌿', '🍃', '🌸'];
                
                foreach ($icons as $emoji) {
                    $selected = ($badge['icon'] ?? '🏆') === $emoji ? 'selected' : '';
                    echo "<div class='icon-option $selected' data-icon='$emoji'>$emoji</div>";
                }
                ?>
            </div>
        </div>

        <!-- Category -->
        <div class="form-section">
            <h2 class="section-title">Category</h2>
            <select name="category" class="form-control" required>
                <option value="reading" <?php echo ($badge['category'] ?? 'reading') === 'reading' ? 'selected' : ''; ?>>Reading</option>
                <option value="knowledge" <?php echo ($badge['category'] ?? '') === 'knowledge' ? 'selected' : ''; ?>>Knowledge</option>
                <option value="exploration" <?php echo ($badge['category'] ?? '') === 'exploration' ? 'selected' : ''; ?>>Exploration</option>
                <option value="social" <?php echo ($badge['category'] ?? '') === 'social' ? 'selected' : ''; ?>>Social</option>
                <option value="special" <?php echo ($badge['category'] ?? '') === 'special' ? 'selected' : ''; ?>>Special</option>
            </select>
        </div>

        <!-- Rarity -->
        <div class="form-section">
            <h2 class="section-title">Rarity</h2>
            <div class="rarity-grid">
                <label class="rarity-option rarity-common <?php echo ($badge['rarity'] ?? 'common') === 'common' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="common" <?php echo ($badge['rarity'] ?? 'common') === 'common' ? 'checked' : ''; ?> style="display: none;">
                    <div><strong>COMMON</strong></div>
                </label>
                <label class="rarity-option rarity-uncommon <?php echo ($badge['rarity'] ?? '') === 'uncommon' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="uncommon" <?php echo ($badge['rarity'] ?? '') === 'uncommon' ? 'checked' : ''; ?> style="display: none;">
                    <div><strong>UNCOMMON</strong></div>
                </label>
                <label class="rarity-option rarity-rare <?php echo ($badge['rarity'] ?? '') === 'rare' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="rare" <?php echo ($badge['rarity'] ?? '') === 'rare' ? 'checked' : ''; ?> style="display: none;">
                    <div><strong>RARE</strong></div>
                </label>
                <label class="rarity-option rarity-epic <?php echo ($badge['rarity'] ?? '') === 'epic' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="epic" <?php echo ($badge['rarity'] ?? '') === 'epic' ? 'checked' : ''; ?> style="display: none;">
                    <div><strong>EPIC</strong></div>
                </label>
                <label class="rarity-option rarity-legendary <?php echo ($badge['rarity'] ?? '') === 'legendary' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="legendary" <?php echo ($badge['rarity'] ?? '') === 'legendary' ? 'checked' : ''; ?> style="display: none;">
                    <div><strong>LEGENDARY</strong></div>
                </label>
                <label class="rarity-option rarity-cardinal <?php echo ($badge['rarity'] ?? '') === 'cardinal' ? 'selected' : ''; ?>">
                    <input type="radio" name="rarity" value="cardinal" <?php echo ($badge['rarity'] ?? '') === 'cardinal' ? 'checked' : ''; ?> style="display: none;">
                    <div style="position: relative; z-index: 1;"><strong>CARDINAL</strong></div>
                </label>
            </div>
        </div>

        <!-- Status -->
        <div class="form-section">
            <h2 class="section-title">Status</h2>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <input type="checkbox" name="is_active" id="is_active" style="width: 20px; height: 20px;" <?php echo ($badge['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label for="is_active" style="margin: 0; cursor: pointer;"><strong>Active Badge</strong> - Users can earn this badge</label>
            </div>
        </div>

        <div class="action-buttons">
            <a href="badges.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update Badge' : 'Create Badge'; ?>
            </button>
        </div>
    </form>
</div>

<script>
// Icon selection
document.querySelectorAll('.icon-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('icon-input').value = this.dataset.icon;
    });
});

// Rarity selection
document.querySelectorAll('.rarity-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.rarity-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
    });
});
</script>