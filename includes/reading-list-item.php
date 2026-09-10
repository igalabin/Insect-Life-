<?php
// This partial is included in reading-list.php for each article
$image_path = !empty($article['featured_image']) ? $article['featured_image'] : 'assets/images/default-insect.jpg';
$progress = $article['progress_percentage'] ?? 0;
$is_bookmark = isset($article['bookmark_id']);
?>

<div class="article-item">
    <div class="article-thumbnail">
        <a href="article.php?id=<?php echo $article['id']; ?>">
            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                 alt="<?php echo htmlspecialchars($article['title']); ?>"
                 loading="lazy">
        </a>
        <?php if ($progress > 0): ?>
        <div class="progress-overlay">
            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="article-info">
        <h3 class="article-title">
            <a href="article.php?id=<?php echo $article['id']; ?>">
                <?php echo htmlspecialchars($article['title']); ?>
            </a>
        </h3>
        
        <?php if ($article['scientific_name']): ?>
        <div style="font-style: italic; color: var(--gray-600); margin-bottom: 0.5rem;">
            <?php echo htmlspecialchars($article['scientific_name']); ?>
        </div>
        <?php endif; ?>
        
        <div class="article-meta">
            <?php if ($article['category']): ?>
            <div class="meta-item">
                <i class="fas fa-tag"></i>
                <span><?php echo ucfirst($article['category']); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span><?php echo formatReadingTime($article['estimated_read_time']); ?></span>
            </div>
            
            <?php if ($article['difficulty_level']): ?>
            <div class="meta-item">
                <i class="fas fa-signal"></i>
                <span><?php echo ucfirst($article['difficulty_level']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($article['rating_count'] > 0): ?>
            <div class="meta-item">
                <i class="fas fa-star" style="color: var(--accent-yellow);"></i>
                <span><?php echo number_format($article['avg_rating'], 1); ?> (<?php echo $article['rating_count']; ?>)</span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($article['last_read_at'])): ?>
        <div style="font-size: 0.85rem; color: var(--gray-500); margin-top: 0.5rem;">
            Last read: <?php echo formatTimeAgo($article['last_read_at']); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($is_bookmark): ?>
        <!-- Notes Section for Bookmarks -->
        <div class="article-notes">
            <div class="notes-header">
                <span><i class="fas fa-sticky-note"></i> Notes</span>
                <button type="button" class="btn btn-sm btn-outline" onclick="toggleNotes(<?php echo $article['bookmark_id']; ?>)">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            
            <div id="notes-text-<?php echo $article['bookmark_id']; ?>" class="notes-text">
                <?php if ($article['notes']): ?>
                    <?php echo nl2br(htmlspecialchars($article['notes'])); ?>
                <?php else: ?>
                    <em style="color: var(--gray-500);">No notes yet. Click edit to add notes.</em>
                <?php endif; ?>
            </div>
            
            <form method="POST" id="notes-form-<?php echo $article['bookmark_id']; ?>" class="notes-form" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="bookmark_id" value="<?php echo $article['bookmark_id']; ?>">
                <textarea name="notes" placeholder="Add your notes here..."><?php echo htmlspecialchars($article['notes']); ?></textarea>
                <div class="notes-actions">
                    <button type="submit" name="update_notes" class="btn btn-sm btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelNotes(<?php echo $article['bookmark_id']; ?>)">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="article-actions">
        <?php if ($progress > 0): ?>
        <div class="progress-info">
            <div class="progress-percentage"><?php echo round($progress); ?>%</div>
            <div>Progress</div>
        </div>
        <?php endif; ?>
        
        <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-small">
            <i class="fas fa-book-open"></i>
            <?php echo $progress > 0 && $progress < 100 ? 'Continue Reading' : ($progress >= 100 ? 'Read Again' : 'Start Reading'); ?>
        </a>
        
        <form method="POST" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
            <button type="submit" name="toggle_bookmark" class="btn btn-outline btn-small">
                <i class="<?php echo $is_bookmark ? 'fas' : 'far'; ?> fa-bookmark"></i>
                <?php echo $is_bookmark ? 'Remove' : 'Bookmark'; ?>
            </button>
        </form>
        
        <?php if (isset($article['bookmarked_at'])): ?>
        <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.5rem;">
            Saved <?php echo formatTimeAgo($article['bookmarked_at']); ?>
        </div>
        <?php endif; ?>
    </div>
</div>