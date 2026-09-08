<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    redirectTo('../login.php');
}

$page_title = "Edit Article - Admin";
$success_message = '';
$error_message = '';

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $article_id > 0;

// Get article data if editing
$article = null;
if ($is_edit) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            $error_message = "Article not found.";
            $is_edit = false;
        }
    } catch (Exception $e) {
        $error_message = "Error loading article: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? ''; // Don't sanitize - editor content
        $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
        $scientific_name = sanitizeInput($_POST['scientific_name'] ?? '');
        $insect_order = sanitizeInput($_POST['insect_order'] ?? '');
        $insect_family = sanitizeInput($_POST['insect_family'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $difficulty_level = sanitizeInput($_POST['difficulty_level'] ?? '');
        $estimated_read_time = intval($_POST['estimated_read_time'] ?? 5);
        $status = sanitizeInput($_POST['status'] ?? 'draft');
        
        // Keep existing image if no new upload
        $featured_image = $article['featured_image'] ?? '';
        
        // Handle image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/articles/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_tmp = $_FILES['featured_image']['tmp_name'];
            $file_name = $_FILES['featured_image']['name'];
            $file_size = $_FILES['featured_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $error_message = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
            } elseif ($file_size > $max_size) {
                $error_message = "File too large. Maximum size: 5MB";
            } else {
                // Generate unique filename
                $new_filename = 'article_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old image if exists
                    if (!empty($article['featured_image']) && file_exists('../' . $article['featured_image'])) {
                        unlink('../' . $article['featured_image']);
                    }
                    
                    $featured_image = 'uploads/articles/' . $new_filename;
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }
        
        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        }
        
        $errors = [];
        if (empty($title)) $errors[] = "Title is required.";
        if (empty($content)) $errors[] = "Content is required.";
        if (empty($slug)) $errors[] = "Slug is required.";
        
        if (empty($errors) && empty($error_message)) {
            try {
                $pdo = getDBConnection();
                
                if ($is_edit) {
                    // Update existing article
                    $stmt = $pdo->prepare("
                        UPDATE articles SET
                            title = ?, slug = ?, content = ?, excerpt = ?,
                            scientific_name = ?, insect_order = ?, insect_family = ?,
                            category = ?, difficulty_level = ?, estimated_read_time = ?,
                            status = ?, featured_image = ?,
                            published_at = CASE WHEN status = 'published' AND published_at IS NULL THEN NOW() ELSE published_at END
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $title, $slug, $content, $excerpt,
                        $scientific_name, $insect_order, $insect_family,
                        $category, $difficulty_level, $estimated_read_time,
                        $status, $featured_image, $article_id
                    ]);
                    $success_message = "Article updated successfully!";
                } else {
                    // Create new article
                    $stmt = $pdo->prepare("
                        INSERT INTO articles (
                            title, slug, content, excerpt, scientific_name,
                            insect_order, insect_family, category, difficulty_level,
                            estimated_read_time, status, featured_image, author_id,
                            published_at, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                                  CASE WHEN ? = 'published' THEN NOW() ELSE NULL END, NOW())
                    ");
                    $stmt->execute([
                        $title, $slug, $content, $excerpt, $scientific_name,
                        $insect_order, $insect_family, $category, $difficulty_level,
                        $estimated_read_time, $status, $featured_image, $_SESSION['user_id'],
                        $status
                    ]);
                    $article_id = $pdo->lastInsertId();
                    $success_message = "Article created successfully!";
                    $is_edit = true;
                }
                
                // Reload article data
                $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
                $stmt->execute([$article_id]);
                $article = $stmt->fetch();
                
            } catch (Exception $e) {
                $error_message = "Error saving article: " . $e->getMessage();
            }
        } else if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Handle image deletion
if (isset($_GET['delete_image']) && $is_edit && $article) {
    try {
        if (!empty($article['featured_image']) && file_exists('../' . $article['featured_image'])) {
            unlink('../' . $article['featured_image']);
        }
        
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE articles SET featured_image = NULL WHERE id = ?");
        $stmt->execute([$article_id]);
        
        $success_message = "Image deleted successfully!";
        
        // Reload article data
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
        $article = $stmt->fetch();
    } catch (Exception $e) {
        $error_message = "Error deleting image: " . $e->getMessage();
    }
}

$additional_css = "
    .editor-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .editor-form {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--gray-200);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    textarea.form-control {
        min-height: 300px;
        font-family: monospace;
    }
    
    .image-upload-area {
        border: 2px dashed var(--gray-300);
        border-radius: var(--border-radius-md);
        padding: 2rem;
        text-align: center;
        background: var(--gray-50);
        transition: all var(--transition-fast);
        cursor: pointer;
    }
    
    .image-upload-area:hover {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.05);
    }
    
    .image-upload-area.dragover {
        border-color: var(--primary-blue);
        background: rgba(59, 130, 246, 0.1);
    }
    
    .upload-icon {
        font-size: 3rem;
        color: var(--gray-400);
        margin-bottom: 1rem;
    }
    
    .image-preview {
        position: relative;
        margin-top: 1rem;
    }
    
    .image-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-md);
    }
    
    .image-preview-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .file-input-label {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: var(--primary-blue);
        color: white;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .file-input-label:hover {
        background: var(--primary-blue-dark);
        transform: translateY(-2px);
    }
    
    input[type='file'] {
        display: none;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/header.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/footer.css">

<div class="editor-container">
    <div class="editor-header">
        <h1><?php echo $is_edit ? 'Edit Article' : 'Create New Article'; ?></h1>
        <a href="articles.php" class="btn btn-secondary">Back to Articles</a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="editor-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <!-- Basic Information -->
        <div class="form-section">
            <h2 class="section-title">Basic Information</h2>
            
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-control" 
                       value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Slug (URL)</label>
                <input type="text" name="slug" class="form-control" 
                       value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>"
                       placeholder="auto-generated-from-title">
                <small>Leave empty to auto-generate from title</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Content *</label>
                <textarea name="content" class="form-control" required><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Featured Image Upload -->
        <div class="form-section">
            <h2 class="section-title">Featured Image</h2>
            
            <?php if (!empty($article['featured_image'])): ?>
                <div class="image-preview">
                    <img src="../<?php echo htmlspecialchars($article['featured_image']); ?>" alt="Current featured image">
                    <div class="image-preview-actions">
                        <label for="featured_image" class="file-input-label">
                            <i class="fas fa-upload"></i> Change Image
                        </label>
                        <a href="?id=<?php echo $article_id; ?>&delete_image=1" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this image?')">
                            <i class="fas fa-trash"></i> Delete Image
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="image-upload-area" id="upload-area">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <p><strong>Click to upload</strong> or drag and drop</p>
                    <p style="font-size: 0.875rem; color: var(--gray-500); margin-top: 0.5rem;">
                        JPG, PNG, GIF or WEBP (Max 5MB)
                    </p>
                </div>
            <?php endif; ?>
            
            <input type="file" 
                   id="featured_image" 
                   name="featured_image" 
                   accept="image/jpeg,image/png,image/gif,image/webp">
            
            <?php if (empty($article['featured_image'])): ?>
                <label for="featured_image" class="file-input-label" style="margin-top: 1rem;">
                    <i class="fas fa-upload"></i> Choose File
                </label>
            <?php endif; ?>
            
            <div id="file-name" style="margin-top: 1rem; color: var(--gray-600);"></div>
        </div>

        <!-- Scientific Information -->
        <div class="form-section">
            <h2 class="section-title">Scientific Information</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Scientific Name</label>
                    <input type="text" name="scientific_name" class="form-control" 
                           value="<?php echo htmlspecialchars($article['scientific_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Insect Order</label>
                    <select name="insect_order" class="form-control">
                        <option value="">Select Order</option>
                        <option value="lepidoptera" <?php echo ($article['insect_order'] ?? '') === 'lepidoptera' ? 'selected' : ''; ?>>Lepidoptera</option>
                        <option value="coleoptera" <?php echo ($article['insect_order'] ?? '') === 'coleoptera' ? 'selected' : ''; ?>>Coleoptera</option>
                        <option value="hymenoptera" <?php echo ($article['insect_order'] ?? '') === 'hymenoptera' ? 'selected' : ''; ?>>Hymenoptera</option>
                        <option value="diptera" <?php echo ($article['insect_order'] ?? '') === 'diptera' ? 'selected' : ''; ?>>Diptera</option>
                        <option value="hemiptera" <?php echo ($article['insect_order'] ?? '') === 'hemiptera' ? 'selected' : ''; ?>>Hemiptera</option>
                        <option value="orthoptera" <?php echo ($article['insect_order'] ?? '') === 'orthoptera' ? 'selected' : ''; ?>>Orthoptera</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Family</label>
                    <input type="text" name="insect_family" class="form-control" 
                           value="<?php echo htmlspecialchars($article['insect_family'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="general" <?php echo ($article['category'] ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="anatomy" <?php echo ($article['category'] ?? '') === 'anatomy' ? 'selected' : ''; ?>>Anatomy</option>
                        <option value="behavior" <?php echo ($article['category'] ?? '') === 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                        <option value="ecology" <?php echo ($article['category'] ?? '') === 'ecology' ? 'selected' : ''; ?>>Ecology</option>
                        <option value="conservation" <?php echo ($article['category'] ?? '') === 'conservation' ? 'selected' : ''; ?>>Conservation</option>
                        <option value="identification" <?php echo ($article['category'] ?? '') === 'identification' ? 'selected' : ''; ?>>Identification</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Publishing Options -->
        <div class="form-section">
            <h2 class="section-title">Publishing Options</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Difficulty Level</label>
                    <select name="difficulty_level" class="form-control">
                        <option value="beginner" <?php echo ($article['difficulty_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo ($article['difficulty_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo ($article['difficulty_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estimated Read Time (minutes)</label>
                    <input type="number" name="estimated_read_time" class="form-control" 
                           value="<?php echo htmlspecialchars($article['estimated_read_time'] ?? '5'); ?>" min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?php echo ($article['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($article['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo ($article['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="articles.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="status" value="draft" class="btn btn-outline">Save as Draft</button>
            <button type="submit" name="status" value="published" class="btn btn-primary">
                <?php echo $is_edit ? 'Update Article' : 'Publish Article'; ?>
            </button>
        </div>
    </form>
</div>

<script>
// File input handling
const fileInput = document.getElementById('featured_image');
const uploadArea = document.getElementById('upload-area');
const fileName = document.getElementById('file-name');

if (fileInput) {
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            fileName.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                if (uploadArea) {
                    uploadArea.innerHTML = `
                        <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: var(--border-radius-md);">
                        <p style="margin-top: 1rem; color: var(--secondary-green);">
                            <i class="fas fa-check-circle"></i> Image ready to upload
                        </p>
                    `;
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

// Drag and drop support
if (uploadArea) {
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        this.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });
}
</script>

