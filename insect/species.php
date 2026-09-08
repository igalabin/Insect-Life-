<?php
session_start();
require_once 'config.php';

$page_title = "Species Database - Insect Life";
$page_description = "Browse our comprehensive database of insect species from around the world. Discover detailed profiles, photos, and fascinating facts.";
$page_keywords = "insect species, database, identification, taxonomy, beetles, butterflies, ants, entomology";

// Get filter parameters
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$order_filter = isset($_GET['order']) ? sanitizeInput($_GET['order']) : '';
$family_filter = isset($_GET['family']) ? sanitizeInput($_GET['family']) : '';
$sort_by = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'title';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 16;

$additional_css = "
    .species-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 3rem 0;
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .species-header h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: white;
    }
    
    .species-search {
        max-width: 600px;
        margin: 2rem auto 0;
        position: relative;
    }
    
    .species-search input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        box-shadow: var(--shadow-lg);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .species-search .search-icon {
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
        gap: 1.5rem;
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
    
    .order-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }
    
    .order-tab {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: white;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-lg);
        text-decoration: none;
        color: var(--gray-700);
        font-weight: 500;
        transition: all var(--transition-fast);
        box-shadow: var(--shadow-sm);
    }
    
    .order-tab:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .order-tab.active {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        border-color: var(--primary-blue);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .order-icon {
        font-size: 1.5rem;
    }
    
    .species-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        padding: 0 1rem;
        margin-bottom: 3rem;
    }
    
    .species-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
        position: relative;
    }
    
    .species-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
    }
    
    .species-image {
        height: 200px;
        background: linear-gradient(135deg, var(--gray-200) 0%, var(--gray-300) 100%);
        position: relative;
        overflow: hidden;
    }
    
    .species-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform var(--transition-normal);
    }
    
    .species-card:hover .species-image img {
        transform: scale(1.05);
    }
    
    .species-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .species-content {
        padding: 1.5rem;
    }
    
    .species-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    
    .species-scientific {
        font-style: italic;
        color: var(--gray-600);
        margin-bottom: 1rem;
        font-size: 1rem;
    }
    
    .species-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--gray-200);
        font-size: 0.9rem;
        color: var(--gray-500);
    }
    
    .species-order {
        background: var(--gray-100);
        color: var(--gray-700);
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 500;
    }
    
    .species-family {
        font-weight: 500;
    }
    
    .stats-section {
        background: white;
        margin: 0 1rem 2rem;
        padding: 2rem;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        text-align: center;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 2rem;
        margin-top: 1.5rem;
    }
    
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
        font-weight: bold;
        color: var(--gray-900);
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-size: 0.9rem;
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
    
    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .species-header h1 {
            font-size: 2.5rem;
        }
        
        .order-tabs {
            justify-content: center;
        }
        
        .order-tab {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .species-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            padding: 0 0.5rem;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .species-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';

try {
    $pdo = getDBConnection();
    
    // Get species data from articles with featured images and scientific names
    $where_conditions = ["articles.status = 'published'", "articles.scientific_name IS NOT NULL"];
    $params = [];
    
    if ($search_query) {
        $where_conditions[] = "(articles.title LIKE :search OR articles.scientific_name LIKE :search OR articles.content LIKE :search)";
        $params['search'] = "%$search_query%";
    }
    
    if ($order_filter) {
        $where_conditions[] = "articles.insect_order = :order";
        $params['order'] = $order_filter;
    }
    
    if ($family_filter) {
        $where_conditions[] = "articles.insect_family = :family";
        $params['family'] = $family_filter;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count
    $count_query = "SELECT COUNT(DISTINCT articles.scientific_name) FROM articles $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_species = $count_stmt->fetchColumn();
    
    // Get species data
    $offset = ($page - 1) * $per_page;
    $order_clause = "ORDER BY articles.$sort_by";
    
    $main_query = "
        SELECT 
            articles.id,
            articles.title,
            articles.scientific_name,
            articles.featured_image,
            articles.insect_order,
            articles.insect_family,
            articles.difficulty_level,
            articles.excerpt,
            articles.published_at,
            COUNT(rp.user_id) as reader_count
        FROM articles 
        LEFT JOIN reading_progress rp ON articles.id = rp.article_id AND rp.progress_percentage > 0
        $where_clause
        GROUP BY articles.scientific_name
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
    $species = $stmt->fetchAll();
    
    // Get order statistics
    $order_stats_query = "
        SELECT 
            insect_order,
            COUNT(DISTINCT scientific_name) as species_count
        FROM articles 
        WHERE status = 'published' AND scientific_name IS NOT NULL
        GROUP BY insect_order
        ORDER BY species_count DESC
    ";
    $order_stats = $pdo->query($order_stats_query)->fetchAll();
    
    // Get total statistics
    $total_articles = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
    $total_scientific_names = $pdo->query("SELECT COUNT(DISTINCT scientific_name) FROM articles WHERE status = 'published' AND scientific_name IS NOT NULL")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(DISTINCT insect_order) FROM articles WHERE status = 'published' AND insect_order IS NOT NULL")->fetchColumn();
    $total_families = $pdo->query("SELECT COUNT(DISTINCT insect_family) FROM articles WHERE status = 'published' AND insect_family IS NOT NULL")->fetchColumn();

} catch (Exception $e) {
    error_log("Species page error: " . $e->getMessage());
    $species = [];
    $total_species = 0;
    $order_stats = [];
    $total_articles = 0;
    $total_scientific_names = 0;
    $total_orders = 0;
    $total_families = 0;
}

$total_pages = ceil($total_species / $per_page);

// Define insect orders with icons and descriptions
$insect_orders = [
    'lepidoptera' => ['icon' => '🦋', 'name' => 'Lepidoptera', 'desc' => 'Butterflies & Moths'],
    'coleoptera' => ['icon' => '🪲', 'name' => 'Coleoptera', 'desc' => 'Beetles'],
    'hymenoptera' => ['icon' => '🐜', 'name' => 'Hymenoptera', 'desc' => 'Ants, Bees & Wasps'],
    'diptera' => ['icon' => '🪰', 'name' => 'Diptera', 'desc' => 'True Flies'],
    'hemiptera' => ['icon' => '🐛', 'name' => 'Hemiptera', 'desc' => 'True Bugs'],
    'orthoptera' => ['icon' => '🦗', 'name' => 'Orthoptera', 'desc' => 'Grasshoppers & Crickets'],
    'odonata' => ['icon' => '🕷️', 'name' => 'Odonata', 'desc' => 'Dragonflies & Damselflies'],
    'neuroptera' => ['icon' => '🦟', 'name' => 'Neuroptera', 'desc' => 'Lacewings']
];
?>

<div class="species-header">
    <div class="container">
        <h1>Species Database</h1>
        <p>Discover and explore insect species from around the world</p>
        
        <div class="species-search">
            <i class="fas fa-search search-icon"></i>
            <form method="GET" style="margin: 0;">
                <input type="text" name="search" placeholder="Search species by name, scientific name, or characteristics..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       onchange="this.form.submit()">
                <!-- Preserve other filters -->
                <?php if ($order_filter): ?><input type="hidden" name="order" value="<?php echo htmlspecialchars($order_filter); ?>"><?php endif; ?>
                <?php if ($family_filter): ?><input type="hidden" name="family" value="<?php echo htmlspecialchars($family_filter); ?>"><?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="container">
    <div class="stats-section">
        <h2>Database Statistics</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo number_format($total_articles); ?></div>
                <div class="stat-label">Total Articles</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">🔬</div>
                <div class="stat-number"><?php echo number_format($total_scientific_names); ?></div>
                <div class="stat-label">Species Documented</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">📋</div>
                <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                <div class="stat-label">Insect Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">🌳</div>
                <div class="stat-number"><?php echo number_format($total_families); ?></div>
                <div class="stat-label">Families</div>
            </div>
        </div>
    </div>
</div>

<!-- Order Filter Tabs -->
<div class="order-tabs">
    <a href="species.php" class="order-tab <?php echo empty($order_filter) ? 'active' : ''; ?>">
        <span class="order-icon">🌍</span>
        <span>All Orders</span>
    </a>
    
    <?php foreach ($insect_orders as $order_key => $order_info): ?>
        <?php $count = 0; ?>
        <?php foreach ($order_stats as $stat): ?>
            <?php if ($stat['insect_order'] === $order_key): ?>
                <?php $count = $stat['species_count']; break; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if ($count > 0): ?>
        <a href="species.php?order=<?php echo $order_key; ?>" 
           class="order-tab <?php echo $order_filter === $order_key ? 'active' : ''; ?>">
            <span class="order-icon"><?php echo $order_info['icon']; ?></span>
            <div>
                <div><?php echo $order_info['name']; ?></div>
                <div style="font-size: 0.8em; opacity: 0.8;"><?php echo $count; ?> species</div>
            </div>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- Filters Section -->
<div class="container">
    <div class="filters-section">
        <form method="GET" id="filters-form">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Insect Order</label>
                    <select name="order" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="">All Orders</option>
                        <?php foreach ($insect_orders as $order_key => $order_info): ?>
                            <option value="<?php echo $order_key; ?>" <?php echo $order_filter === $order_key ? 'selected' : ''; ?>>
                                <?php echo $order_info['name']; ?> - <?php echo $order_info['desc']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="filter-select" onchange="document.getElementById('filters-form').submit()">
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Common Name</option>
                        <option value="scientific_name" <?php echo $sort_by === 'scientific_name' ? 'selected' : ''; ?>>Scientific Name</option>
                        <option value="published_at" <?php echo $sort_by === 'published_at' ? 'selected' : ''; ?>>Recently Added</option>
                        <option value="views_count" <?php echo $sort_by === 'views_count' ? 'selected' : ''; ?>>Most Popular</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Results</label>
                    <div style="padding: 0.75rem 0; color: var(--gray-600); font-weight: 500;">
                        Showing <?php echo number_format($total_species); ?> species
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Actions</label>
                    <?php if ($search_query || $order_filter || $family_filter): ?>
                    <button type="button" class="filter-select" onclick="window.location.href='species.php'" 
                            style="background: var(--gray-100); border-color: var(--gray-400); cursor: pointer;">
                        Clear Filters
                    </button>
                    <?php else: ?>
                    <div style="padding: 0.75rem 0; color: var(--gray-400);">No filters active</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Preserve search query -->
            <?php if ($search_query): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>"><?php endif; ?>
        </form>
    </div>
</div>

<!-- Species Grid -->
<?php if (empty($species)): ?>
<div class="container">
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>No species found</h3>
        <p>Try adjusting your search criteria or <a href="species.php">browse all species</a></p>
    </div>
</div>
<?php else: ?>
<div class="container">
    <div class="species-grid">
        <?php foreach ($species as $item): ?>
        <div class="species-card">
            <a href="article.php?id=<?php echo $item['id']; ?>" style="text-decoration: none; color: inherit;">
                <div class="species-image">
                    <?php if ($item['featured_image']): ?>
                        <img src="<?php echo htmlspecialchars($item['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--gray-300); font-size: 3rem;">
                            <?php echo $insect_orders[$item['insect_order']]['icon'] ?? '🐛'; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($item['difficulty_level']): ?>
                    <div class="species-badge">
                        <?php echo ucfirst($item['difficulty_level']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="species-content">
                    <h3 class="species-name"><?php echo htmlspecialchars($item['title']); ?></h3>
                    <div class="species-scientific"><?php echo htmlspecialchars($item['scientific_name']); ?></div>
                    
                    <?php if ($item['excerpt']): ?>
                    <p style="color: var(--gray-600); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars(substr(strip_tags($item['excerpt']), 0, 100)); ?>...
                    </p>
                    <?php endif; ?>
                    
                    <div class="species-meta">
                        <div class="species-order">
                            <?php echo ucfirst($item['insect_order'] ?: 'Unknown'); ?>
                        </div>
                        
                        <div class="species-family">
                            <?php if ($item['reader_count'] > 0): ?>
                                <?php echo $item['reader_count']; ?> reader<?php echo $item['reader_count'] !== 1 ? 's' : ''; ?>
                            <?php else: ?>
                                New species
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'order' => $order_filter, 'family' => $family_filter, 'sort' => $sort_by])); ?>">
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
        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'order' => $order_filter, 'family' => $family_filter, 'sort' => $sort_by])); ?>">
            <?php echo $i; ?>
        </a>
    <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $total_pages): ?>
    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter(['search' => $search_query, 'order' => $order_filter, 'family' => $family_filter, 'sort' => $sort_by])); ?>">
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
    
    // Add loading states to species cards
    document.querySelectorAll('.species-card a').forEach(link => {
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
    
    document.querySelectorAll('.species-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = (index * 0.1) + 's';
        cardObserver.observe(card);
    });
    
    // Animate statistics on page load
    document.addEventListener('DOMContentLoaded', function() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalNumber = parseInt(stat.textContent.replace(/,/g, ''));
            if (finalNumber > 0) {
                animateNumber(stat, 0, finalNumber, 1500);
            }
        });
    });
    
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const originalText = element.textContent;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + (end - start) * progress);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = originalText;
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
";

include 'includes/footer.php';
?>