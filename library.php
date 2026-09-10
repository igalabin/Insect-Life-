<?php 
session_start();
require_once 'config.php';

$page_title = "Digital Library - Insect Life";
$page_description = "Browse our comprehensive digital library of insect articles, guides, and research papers. Filter by difficulty, category, and reading progress.";
$page_keywords = "insect library, articles, guides, entomology, reading, education";

// Get filter parameters
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$difficulty_filter = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : '';
$order_filter = isset($_GET['order']) ? sanitizeInput($_GET['order']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'published_at';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;

$additional_css = "
    body {
        padding-top: 0;
    }
    
    .library-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 2rem 0;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .library-header h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: white;
    }
    
    .library-search {
        max-width: 600px;
        margin: 2rem auto 0;
        position: relative;
    }
    
    .library-search input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        box-shadow: var(--shadow-lg);
    }
    
    .library-search .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        color: var(--gray-400);
    }
    
    .filters-section {
        background: white;
        padding: 2rem;
        margin: 0 1rem 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--gray-700);
    }
    
    .filter-select {
        padding: 0.75rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-size: 1rem;
        transition: border-color var(--transition-fast);
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--primary-blue);
    }
    
    .filter-actions {
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    .results-count {
        color: var(--gray-600);
        font-weight: 500;
    }
    
    .clear-filters {
        background: none;
        border: 2px solid var(--gray-300);
        color: var(--gray-600);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .clear-filters:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }
    
    .articles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        padding: 0 1rem;
        margin-bottom: 3rem;
    }
    
    .article-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
        position: relative;
    }
    
    .article-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }
    
    .article-image {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, var(--gray-200) 0%, var(--gray-300) 100%);
        position: relative;
        overflow: hidden;
    }
    
    .article-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform var(--transition-normal);
    }
    
    .article-card:hover .article-image img {
        transform: scale(1.05);
    }
    
    .difficulty-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .difficulty-beginner {
        background: var(--secondary-green);
        color: white;
    }
    
    .difficulty-intermediate {
        background: var(--accent-orange);
        color: white;
    }
    
    .difficulty-advanced {
        background: #ef4444;
        color: white;
    }
    
    .article-content {
        padding: 1.5rem;
    }
    
    .article-category {
        color: var(--primary-blue);
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .article-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: var(--gray-900);
        line-height: 1.3;
    }
    
    .article-scientific {
        font-style: italic;
        color: var(--gray-600);
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }
    
    .article-excerpt {
        color: var(--gray-600);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .article-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--gray-200);
        font-size: 0.9rem;
        color: var(--gray-500);
    }
    
    .read-time {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .article-rating {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .stars {
        color: var(--accent-yellow);
    }
    
    .progress-indicator {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 4px;
        background: var(--secondary-green);
        transition: width var(--transition-normal);
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        margin: 3rem 0;
    }
    
    .pagination a, .pagination span {
        padding: 0.75rem 1rem;
        border-radius: var(--border-radius-md);
        text-decoration: none;
        transition: all var(--transition-fast);
    }
    
    .pagination a {
        background: white;
        color: var(--gray-700);
        border: 2px solid var(--gray-300);
    }
    
    .pagination a:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }
    
    .pagination .current {
        background: var(--primary-blue);
        color: white;
        border: 2px solid var(--primary-blue);
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--gray-500);
    }
    
    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .library-header h1 {
            font-size: 2rem;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .articles-grid {
            grid-template-columns: 1fr;
            padding: 0 0.5rem;
        }
    }
";

include 'includes/header.php';

try {
    $pdo = getDBConnection();
    
    // Build query with filters
    $where_conditions = ["articles.status = 'published'"];
    $params = [];
    
    if ($search_query) {
        $where_conditions[] = "(articles.title LIKE :search OR articles.content LIKE :search OR articles.scientific_name LIKE :search)";
        $params['search'] = "%$search_query%";
    }
    
    if ($category_filter) {
        $where_conditions[] = "articles.category = :category";
        $params['category'] = $category_filter;
    }
    
    if ($difficulty_filter) {
        $where_conditions[] = "articles.difficulty_level = :difficulty";
        $params['difficulty'] = $difficulty_filter;
    }
    
    if ($order_filter) {
        $where_conditions[] = "articles.insect_order = :order";
        $params['order'] = $order_filter;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM articles $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_articles = $count_stmt->fetchColumn();
    
    // Get articles with pagination
    $offset = ($page - 1) * $per_page;
    $order_clause = "ORDER BY articles.$sort_by DESC";
    
    $main_query = "
        SELECT 
            articles.*,
            users.username as author_name,
            AVG(article_ratings.rating) as avg_rating,
            COUNT(article_ratings.id) as rating_count
        FROM articles 
        LEFT JOIN users ON articles.author_id = users.id
        LEFT JOIN article_ratings ON articles.id = article_ratings.article_id
        $where_clause
        GROUP BY articles.id
        $order_clause
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($main_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    // Get reading progress for logged-in users
    $reading_progress = [];
    if (isLoggedIn()) {
        $progress_stmt = $pdo->prepare("SELECT article_id, progress_percentage FROM reading_progress WHERE user_id = ?");
        $progress_stmt->execute([$_SESSION['user_id']]);
        $reading_progress = $progress_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
} catch (Exception $e) {
    error_log("Library page error: " . $e->getMessage());
    $articles = [];
    $total_articles = 0;
}

$total_pages = ceil($total_articles / $per_page);
?>

<div class="library-header">
    <div class="container">
        <h1><i class="fas fa-book-open"></i> Digital Library</h1>
        <p>Explore our comprehensive collection of insect articles and guides</p>
        
        <div class="library-search">
            <i class="fas fa-search search-icon"></i>
            <form method="GET" style="margin: 0;">
                <input type="text" name="search" placeholder="Search articles, species, or topics..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       onchange="this.form.submit()">
                <!-- Preserve other filters -->
                <?php if ($category_filter): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>"><?php endif; ?>
                <?php if ($difficulty_filter): ?><input type="hidden" name="difficulty" value="<?php echo htmlspecialchars($difficulty_filter); ?>"><?php endif; ?>
                <?php if ($order_filter): ?><input type="hidden" name="order" value="<?php echo htmlspecialchars($order_filter); ?>"><?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="container">
    <div class="filters-section">
        <form method="GET" id="filters-form">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select name="category" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="">All Categories</option>
                        <option value="anatomy" <?php echo $category_filter === 'anatomy' ? 'selected' : ''; ?>>Anatomy</option>
                        <option value="behavior" <?php echo $category_filter === 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                        <option value="ecology" <?php echo $category_filter === 'ecology' ? 'selected' : ''; ?>>Ecology</option>
                        <option value="conservation" <?php echo $category_filter === 'conservation' ? 'selected' : ''; ?>>Conservation</option>
                        <option value="identification" <?php echo $category_filter === 'identification' ? 'selected' : ''; ?>>Identification</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Difficulty</label>
                    <select name="difficulty" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="">All Levels</option>
                        <option value="beginner" <?php echo $difficulty_filter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo $difficulty_filter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo $difficulty_filter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Insect Order</label>
                    <select name="order" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="">All Orders</option>
                        <option value="lepidoptera" <?php echo $order_filter === 'lepidoptera' ? 'selected' : ''; ?>>Lepidoptera</option>
                        <option value="coleoptera" <?php echo $order_filter === 'coleoptera' ? 'selected' : ''; ?>>Coleoptera</option>
                        <option value="hymenoptera" <?php echo $order_filter === 'hymenoptera' ? 'selected' : ''; ?>>Hymenoptera</option>
                        <option value="diptera" <?php echo $order_filter === 'diptera' ? 'selected' : ''; ?>>Diptera</option>
                        <option value="hemiptera" <?php echo $order_filter === 'hemiptera' ? 'selected' : ''; ?>>Hemiptera</option>
                        <option value="orthoptera" <?php echo $order_filter === 'orthoptera' ? 'selected' : ''; ?>>Orthoptera</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="published_at" <?php echo $sort_by === 'published_at' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="estimated_read_time" <?php echo $sort_by === 'estimated_read_time' ? 'selected' : ''; ?>>Reading Time</option>
                    </select>
                </div>
            </div>
            
            <!-- Preserve search query -->
            <?php if ($search_query): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
            
            <div class="filter-actions">
                <div class="results-count">
                    Showing <?php echo number_format($total_articles); ?> article<?php echo $total_articles !== 1 ? 's' : ''; ?>
                </div>
                
                <?php if ($search_query || $category_filter || $difficulty_filter || $order_filter): ?>
                <button type="button" class="clear-filters" onclick="window.location.href='library.php'">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Articles Grid -->
<?php if (empty($articles)): ?>
<div class="container">
    <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3>No articles found</h3>
        <p>Try adjusting your search criteria or <a href="library.php">browse all articles</a></p>
    </div>
</div>
<?php else: ?>
<div class="container">
    <div class="articles-grid">
        <?php foreach ($articles as $article): ?>
        <article class="article-card">
            <a href="article.php?id=<?php echo $article['id']; ?>" style="text-decoration: none; color: inherit;">
                <div class="article-image">
                    <?php 
                    $image_path = !empty($article['featured_image']) ? $article['featured_image'] : 'assets/images/default-insect.jpg';
                    ?>
                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         loading="lazy">
                    
                    <div class="difficulty-badge difficulty-<?php echo $article['difficulty_level']; ?>">
                        <?php echo ucfirst($article['difficulty_level']); ?>
                    </div>
                    
                    <?php if (isset($reading_progress[$article['id']]) && $reading_progress[$article['id']] > 0): ?>
                    <div class="progress-indicator" 
                         style="width: <?php echo $reading_progress[$article['id']]; ?>%"></div>
                    <?php endif; ?>
                </div>
                
                <div class="article-content">
                    <div class="article-category">
                        <?php echo ucfirst($article['insect_order'] ?: 'General'); ?>
                    </div>
                    
                    <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                    
                    <?php if ($article['scientific_name']): ?>
                    <div class="article-scientific">
                        <?php echo htmlspecialchars($article['scientific_name']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="article-excerpt">
                        <?php 
                        $excerpt = strip_tags($article['content']);
                        echo htmlspecialchars(substr($excerpt, 0, 150)) . (strlen($excerpt) > 150 ? '...' : '');
                        ?>
                    </div>
                    
                    <div class="article-meta">
                        <div class="read-time">
                            <i class="fas fa-clock"></i>
                            <?php echo formatReadingTime($article['estimated_read_time']); ?>
                        </div>
                        
                        <?php if ($article['rating_count'] > 0): ?>
                        <div class="article-rating">
                            <span class="stars">
                                <?php 
                                $rating = round($article['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <span>(<?php echo $article['rating_count']; ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </article>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'category' => $category_filter, 'difficulty' => $difficulty_filter, 'order' => $order_filter, 'sort' => $sort_by])); ?>">
        <i class="fas fa-chevron-left"></i> Previous
    </a>
    <?php endif; ?>
    
    <?php
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++):
    ?>
    <?php if ($i === $page): ?>
        <span class="current"><?php echo $i; ?></span>
    <?php else: ?>
        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'category' => $category_filter, 'difficulty' => $difficulty_filter, 'order' => $order_filter, 'sort' => $sort_by])); ?>">
            <?php echo $i; ?>
        </a>
    <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'category' => $category_filter, 'difficulty' => $difficulty_filter, 'order' => $order_filter, 'sort' => $sort_by])); ?>">
        Next <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$additional_js = "
    // Auto-submit search form with debouncing
    let searchTimeout;
    document.querySelector('input[name=\"search\"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const form = this.closest('form');
        searchTimeout = setTimeout(() => {
            form.submit();
        }, 500);
    });
    
    // Add loading states to article cards
    document.querySelectorAll('.article-card a').forEach(link => {
        link.addEventListener('click', function() {
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
        });
    });
    
    // Intersection Observer for card animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                cardObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.article-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = (index * 0.1) + 's';
        cardObserver.observe(card);
    });
";

include 'includes/footer.php';
?>