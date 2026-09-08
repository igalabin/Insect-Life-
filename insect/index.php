<?php 
session_start();
require_once 'config.php';

$page_title = "Insect Life - Explore the World of Insects";
$page_description = "Discover the amazing world of insects through our comprehensive digital library.";
$page_keywords = "insects, entomology, digital library, species, beetles, butterflies, ants";

$additional_css = "
    .stats-section {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        padding: 3rem 2rem;
        margin: 2rem 1rem;
        border-radius: var(--border-radius-xl);
        color: white;
        text-align: center;
        box-shadow: var(--shadow-lg);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 1rem;
        opacity: 0.9;
    }
    
    .featured-species {
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(10px);
        padding: 3rem 2rem;
        margin: 2rem 1rem;
        border-radius: var(--border-radius-xl);
        color: white;
        text-align: center;
        box-shadow: var(--shadow-lg);
    }
    
    .species-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        max-width: 1000px;
        margin: 2rem auto 0;
    }
    
    .species-card {
        background: rgba(255,255,255,0.1);
        padding: 1.5rem;
        border-radius: var(--border-radius-lg);
        transition: all var(--transition-normal);
        text-decoration: none;
        color: white;
        display: block;
    }
    
    .species-card:hover {
        transform: translateY(-8px);
        background: rgba(255,255,255,0.2);
        box-shadow: var(--shadow-xl);
        color: white;
    }
    
    .species-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .species-name {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .species-count {
        color: #34d399;
        font-size: 0.9rem;
        opacity: 0.9;
    }
";

include 'includes/header.php';
?>

<div class="hero">
    <h1>🐛 Discover the Amazing World of Insects</h1>
    <p>Explore thousands of species, learn fascinating facts, and earn badges</p>
    <div class="cta-buttons">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="library.php" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Continue Learning
            </a>
            <a href="species.php" class="btn btn-secondary">
                <i class="fas fa-bug"></i> Explore Species
            </a>
        <?php else: ?>
            <a href="library.php" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Start Exploring
            </a>
            <a href="register.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i> Join Community
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="features">
    <div class="feature-card">
        <div class="feature-icon">📚</div>
        <h3>Digital Library</h3>
        <p>Access comprehensive articles about insects with detailed information, stunning photographs, and scientific classifications.</p>
        <a href="library.php" style="color: var(--primary-blue); text-decoration: none; font-weight: bold;">
            Browse Library <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">🦋</div>
        <h3>Species Database</h3>
        <p>Discover detailed profiles of thousands of insect species from around the world.</p>
        <a href="species.php" style="color: var(--primary-blue); text-decoration: none; font-weight: bold;">
            Explore Species <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="feature-card">
        <div class="feature-icon">🏆</div>
        <h3>Earn Badges</h3>
        <p>Test your knowledge with interactive assessments and earn badges as you progress.</p>
        <a href="assessments.php" style="color: var(--primary-blue); text-decoration: none; font-weight: bold;">
            Take Assessments <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<!-- <div class="stats-section">
    <h2 style="margin-bottom: 2rem; font-size: 2rem;">Our Growing Community</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">2,500+</div>
            <div class="stat-label">Articles & Guides</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">1,200+</div>
            <div class="stat-label">Insect Species</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">15,000+</div>
            <div class="stat-label">Active Learners</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">75+</div>
            <div class="stat-label">Countries Reached</div>
        </div>
    </div>
</div> -->

<div class="featured-species">
    <h2 style="margin-bottom: 1rem; font-size: 2rem;">Featured Insect Orders</h2>
    <p style="opacity: 0.9; margin-bottom: 2rem;">Explore the most diverse groups</p>
    <div class="species-grid">
        <a href="species.php?order=lepidoptera" class="species-card">
            <span class="species-icon">🦋</span>
            <div class="species-name">Lepidoptera</div>
            <div class="species-count">Butterflies & Moths</div>
        </a>
        <a href="species.php?order=coleoptera" class="species-card">
            <span class="species-icon">🪲</span>
            <div class="species-name">Coleoptera</div>
            <div class="species-count">Beetles</div>
        </a>
        <a href="species.php?order=hymenoptera" class="species-card">
            <span class="species-icon">🐜</span>
            <div class="species-name">Hymenoptera</div>
            <div class="species-count">Ants, Bees & Wasps</div>
        </a>
        <a href="species.php?order=diptera" class="species-card">
            <span class="species-icon">🪰</span>
            <div class="species-name">Diptera</div>
            <div class="species-count">True Flies</div>
        </a>
    </div>
</div>

<?php
$additional_js = "
    document.addEventListener('DOMContentLoaded', function() {
        // Animate statistics
        const statNumbers = document.querySelectorAll('.stat-number');
        const options = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const finalNumber = target.textContent.replace(/[^0-9]/g, '');
                    if (finalNumber) {
                        animateNumber(target, 0, parseInt(finalNumber), 2000);
                        observer.unobserve(target);
                    }
                }
            });
        }, options);

        statNumbers.forEach(stat => observer.observe(stat));
    });
    
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const originalText = element.textContent;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + (end - start) * progress);
            
            element.textContent = current.toLocaleString() + originalText.replace(/[0-9,]/g, '');
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
";

include 'includes/footer.php';
?>
