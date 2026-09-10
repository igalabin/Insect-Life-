<?php
session_start();
require_once 'config.php';

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

if (!$article_id) {
    redirectTo('library.php');
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to comment.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security token mismatch.';
    } else {
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (empty($content)) {
            $_SESSION['error_message'] = 'Comment cannot be empty.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$article_id, $user_id, $content]);
                $_SESSION['success_message'] = 'Comment posted successfully!';
                header("Location: article.php?id=$article_id#comments");
                exit;
            } catch (Exception $e) {
                error_log("Comment error: " . $e->getMessage());
                $_SESSION['error_message'] = 'Error posting comment.';
            }
        }
    }
}

// Handle rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to rate.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Security token mismatch.';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        
        if ($rating < 1 || $rating > 5) {
            $_SESSION['error_message'] = 'Invalid rating.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO article_ratings (user_id, article_id, rating) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE rating = ?, created_at = NOW()
                ");
                $stmt->execute([$user_id, $article_id, $rating, $rating]);
                $_SESSION['success_message'] = 'Rating submitted!';
                header("Location: article.php?id=$article_id");
                exit;
            } catch (Exception $e) {
                error_log("Rating error: " . $e->getMessage());
                $_SESSION['error_message'] = 'Error submitting rating.';
            }
        }
    }
}

try {
    $pdo = getDBConnection();
    
    // Get article
    $stmt = $pdo->prepare("
        SELECT a.*, u.username as author_name,
               AVG(ar.rating) as avg_rating,
               COUNT(DISTINCT ar.id) as rating_count,
               COUNT(DISTINCT c.id) as comment_count
        FROM articles a
        LEFT JOIN users u ON a.author_id = u.id
        LEFT JOIN article_ratings ar ON a.id = ar.article_id
        LEFT JOIN comments c ON a.id = c.article_id AND c.is_approved = 1
        WHERE a.id = ? AND a.status = 'published'
        GROUP BY a.id
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        $_SESSION['error_message'] = 'Article not found.';
        redirectTo('library.php');
    }
    
    // Update views
    $pdo->prepare("UPDATE articles SET views_count = views_count + 1 WHERE id = ?")->execute([$article_id]);
    
    // Get user's reading progress
    $progress = 0;
    $is_bookmarked = false;
    $user_rating = null;
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT progress_percentage FROM reading_progress WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$user_id, $article_id]);
        $progress_row = $stmt->fetch();
        $progress = $progress_row ? $progress_row['progress_percentage'] : 0;
        
        $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$user_id, $article_id]);
        $is_bookmarked = $stmt->fetch() ? true : false;
        
        $stmt = $pdo->prepare("SELECT rating FROM article_ratings WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$user_id, $article_id]);
        $rating_row = $stmt->fetch();
        $user_rating = $rating_row ? $rating_row['rating'] : null;
    }
    
    // Get comments
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.first_name, u.last_name
        FROM comments c
        INNER JOIN users u ON c.user_id = u.id
        WHERE c.article_id = ? AND c.is_approved = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$article_id]);
    $comments = $stmt->fetchAll();
    
    $page_title = htmlspecialchars($article['title']) . ' - Insect Life';
    $page_description = htmlspecialchars(substr(strip_tags($article['content']), 0, 160));
    
} catch (Exception $e) {
    error_log("Article page error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error loading article.';
    redirectTo('library.php');
}

$additional_css = "
    .article-hero {
        height: 400px;
        background-size: cover;
        background-position: center;
        position: relative;
        display: flex;
        align-items: flex-end;
        margin-bottom: 2rem;
    }
    
    .article-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    }
    
    .article-hero-content {
        position: relative;
        z-index: 1;
        color: white;
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }
    
    .article-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem 3rem;
    }
    
    .article-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .progress-display {
        background: white;
        padding: 1rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        flex: 1;
    }
    
    .progress-bar-container {
        height: 8px;
        background: var(--gray-200);
        border-radius: 10px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    
    .progress-bar {
        height: 100%;
        background: var(--secondary-green);
        transition: width 0.3s ease;
    }
    
    .article-content {
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        line-height: 1.8;
        font-size: 1.1rem;
    }
    
    .article-content img {
        max-width: 100%;
        height: auto;
        border-radius: var(--border-radius-md);
        margin: 1.5rem 0;
    }
    
    .rating-section {
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        margin: 2rem 0;
    }
    
    .star-rating {
        display: flex;
        gap: 0.5rem;
        font-size: 2rem;
    }
    
    .star {
        cursor: pointer;
        color: var(--gray-300);
        transition: color 0.2s;
    }
    
    .star.active,
    .star:hover {
        color: var(--accent-yellow);
    }
    
    .comments-section {
        background: white;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        margin: 2rem 0;
    }
    
    .comment {
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .comment:last-child {
        border-bottom: none;
    }
    
    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    
    .comment-author {
        font-weight: 600;
        color: var(--gray-900);
    }
    
    .comment-time {
        color: var(--gray-500);
        font-size: 0.9rem;
    }
    
    .comment-form textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-size: 1rem;
        resize: vertical;
        min-height: 120px;
    }
";

include 'includes/header.php';
?>

<div class="article-hero" style="background-image: url('<?php echo htmlspecialchars($article['featured_image'] ?: 'assets/images/default-insect.jpg'); ?>');">
    <div class="article-hero-overlay"></div>
    <div class="article-hero-content">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($article['title']); ?></h1>
        <?php if ($article['scientific_name']): ?>
        <p style="font-style: italic; font-size: 1.2rem; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($article['scientific_name']); ?>
        </p>
        <?php endif; ?>
        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
            <span><i class="fas fa-clock"></i> <?php echo formatReadingTime($article['estimated_read_time']); ?></span>
            <span><i class="fas fa-eye"></i> <?php echo number_format($article['views_count']); ?> views</span>
            <?php if ($article['rating_count'] > 0): ?>
            <span><i class="fas fa-star" style="color: var(--accent-yellow);"></i> <?php echo number_format($article['avg_rating'], 1); ?> (<?php echo $article['rating_count']; ?>)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="article-container">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    </div>
    <?php endif; ?>
    
    <div class="article-actions">
        <?php if ($user_id): ?>
        <div class="progress-display">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600;">Reading Progress</span>
                <span style="font-size: 1.5rem; font-weight: bold; color: var(--secondary-green);" id="progress-percentage">
                    <?php echo round($progress); ?>%
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progress-bar" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>
        
        <button class="btn <?php echo $is_bookmarked ? 'btn-primary' : 'btn-outline'; ?>" 
                onclick="toggleBookmark(<?php echo $article_id; ?>)" 
                id="bookmark-btn">
            <i class="<?php echo $is_bookmarked ? 'fas' : 'far'; ?> fa-bookmark" id="bookmark-icon"></i>
            <span id="bookmark-text"><?php echo $is_bookmarked ? 'Bookmarked' : 'Bookmark'; ?></span>
        </button>
        <?php endif; ?>
    </div>
    
    <div class="article-content" id="article-content">
        <?php echo $article['content']; ?>
    </div>
    
    <!-- Rating Section -->
    <div class="rating-section" id="rating-section">
        <h3 style="margin-bottom: 1rem;">Rate this Article</h3>
        <?php if ($user_id): ?>
            <?php if ($user_rating): ?>
            <p style="color: var(--gray-600); margin-bottom: 1rem;">
                You rated this article <?php echo $user_rating; ?> out of 5 stars
            </p>
            <?php endif; ?>
            <form method="POST" id="rating-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="rating" id="rating-value" value="<?php echo $user_rating ?: ''; ?>">
                <div class="star-rating" id="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $user_rating && $i <= $user_rating ? 'active' : ''; ?>" 
                          data-rating="<?php echo $i; ?>"
                          onclick="setRating(<?php echo $i; ?>)">★</span>
                    <?php endfor; ?>
                </div>
                <button type="submit" name="submit_rating" class="btn btn-primary" style="margin-top: 1rem;">
                    Submit Rating
                </button>
            </form>
        <?php else: ?>
            <p><a href="login.php?redirect=article.php?id=<?php echo $article_id; ?>">Login</a> to rate this article</p>
        <?php endif; ?>
    </div>
    
    <!-- Comments Section -->
    <div class="comments-section" id="comments">
        <h3 style="margin-bottom: 1.5rem;">
            Comments (<?php echo count($comments); ?>)
        </h3>
        
        <?php if ($user_id): ?>
        <form method="POST" class="comment-form" style="margin-bottom: 2rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <textarea name="content" placeholder="Share your thoughts..." required></textarea>
            <button type="submit" name="submit_comment" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-comment"></i> Post Comment
            </button>
        </form>
        <?php else: ?>
        <p style="text-align: center; padding: 2rem; background: var(--gray-50); border-radius: var(--border-radius-md);">
            <a href="login.php?redirect=article.php?id=<?php echo $article_id; ?>">Login</a> to join the discussion
        </p>
        <?php endif; ?>
        
        <div id="comments-list">
            <?php if (empty($comments)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 2rem;">
                No comments yet. Be the first to comment!
            </p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <div class="comment-author">
                            <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                        </div>
                        <div class="comment-time">
                            <?php echo formatTimeAgo($comment['created_at']); ?>
                        </div>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const articleId = <?php echo $article_id; ?>;
const userId = <?php echo $user_id ?: 'null'; ?>;
let progressUpdateTimeout;

// Track reading progress
if (userId) {
    let lastScrollPosition = 0;
    let readingStartTime = Date.now();
    
    function updateReadingProgress() {
        const content = document.getElementById('article-content');
        const contentTop = content.offsetTop;
        const contentHeight = content.offsetHeight;
        const windowHeight = window.innerHeight;
        const scrollPosition = window.scrollY;
        
        // Calculate progress
        const progress = Math.min(100, Math.max(0, 
            ((scrollPosition + windowHeight - contentTop) / contentHeight) * 100
        ));
        
        // Update UI
        document.getElementById('progress-percentage').textContent = Math.round(progress) + '%';
        document.getElementById('progress-bar').style.width = progress + '%';
        
        // Debounce server update
        clearTimeout(progressUpdateTimeout);
        progressUpdateTimeout = setTimeout(() => {
            saveProgress(progress);
        }, 2000);
        
        lastScrollPosition = scrollPosition;
    }
    
    function saveProgress(progress) {
        const timeSpent = Math.floor((Date.now() - readingStartTime) / 1000);
        
        fetch('api/update-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                article_id: articleId,
                progress: progress,
                time_spent: timeSpent
            })
        }).catch(err => console.error('Progress update failed:', err));
    }
    
    // Track scroll
    window.addEventListener('scroll', updateReadingProgress);
    updateReadingProgress(); // Initial call
    
    // Save progress before leaving
    window.addEventListener('beforeunload', () => {
        const content = document.getElementById('article-content');
        const progress = Math.min(100, 
            ((window.scrollY + window.innerHeight - content.offsetTop) / content.offsetHeight) * 100
        );
        saveProgress(progress);
    });
}

// Bookmark functionality
function toggleBookmark(articleId) {
    if (!userId) {
        window.location.href = 'login.php?redirect=article.php?id=' + articleId;
        return;
    }
    
    fetch('api/toggle-bookmark.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ article_id: articleId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('bookmark-btn');
            const icon = document.getElementById('bookmark-icon');
            const text = document.getElementById('bookmark-text');
            
            if (data.bookmarked) {
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary');
                icon.classList.remove('far');
                icon.classList.add('fas');
                text.textContent = 'Bookmarked';
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
                icon.classList.remove('fas');
                icon.classList.add('far');
                text.textContent = 'Bookmark';
            }
        }
    })
    .catch(err => console.error('Bookmark failed:', err));
}

// Rating functionality
function setRating(rating) {
    document.getElementById('rating-value').value = rating;
    
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Star hover effect
document.querySelectorAll('.star').forEach((star, index) => {
    star.addEventListener('mouseenter', function() {
        document.querySelectorAll('.star').forEach((s, i) => {
            if (i <= index) {
                s.style.color = 'var(--accent-yellow)';
            }
        });
    });
    
    star.addEventListener('mouseleave', function() {
        const currentRating = document.getElementById('rating-value').value;
        document.querySelectorAll('.star').forEach((s, i) => {
            if (i >= currentRating) {
                s.style.color = 'var(--gray-300)';
            }
        });
    });
});


</script>


<?php
$additional_js = "
    // Add smooth scrolling to comment section
    if (window.location.hash === '#comments') {
        setTimeout(() => {
            document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
        }, 100);
    }
";

include 'includes/footer.php';
?>