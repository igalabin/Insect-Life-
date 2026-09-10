</main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Stats -->
            <div class="footer-stats">
                <div class="footer-stats-grid">
                    <?php
                    try {
                        $pdo = getDBConnection();
                        
                        // Get total articles count
                        $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
                        $total_articles = $stmt->fetchColumn();
                        
                        // Get total users count
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
                        $total_users = $stmt->fetchColumn();
                        
                        // Get total species count (distinct scientific names)
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT scientific_name) FROM articles WHERE status = 'published' AND scientific_name IS NOT NULL");
                        $total_species = $stmt->fetchColumn();
                        
                        // Get total badges awarded
                        $stmt = $pdo->query("SELECT COUNT(*) FROM user_badges");
                        $total_badges = $stmt->fetchColumn();
                    } catch (Exception $e) {
                        // Fallback values if database is not available
                        $total_articles = 1250;
                        $total_users = 8500;
                        $total_species = 750;
                        $total_badges = 3200;
                    }
                    ?>
                    <div class="footer-stat">
                        <div class="footer-stat-number"><?php echo number_format($total_articles); ?>+</div>
                        <div class="footer-stat-label">Articles</div>
                    </div>
                    <div class="footer-stat">
                        <div class="footer-stat-number"><?php echo number_format($total_species); ?>+</div>
                        <div class="footer-stat-label">Species</div>
                    </div>
                    <div class="footer-stat">
                        <div class="footer-stat-number"><?php echo number_format($total_users); ?>+</div>
                        <div class="footer-stat-label">Learners</div>
                    </div>
                    <div class="footer-stat">
                        <div class="footer-stat-number"><?php echo number_format($total_badges); ?>+</div>
                        <div class="footer-stat-label">Badges Earned</div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Content -->
            <div class="footer-content">
                <!-- Footer Brand -->
                <div class="footer-section footer-brand">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="footer-logo">
                        <span class="footer-logo-icon">🐛</span>
                        <?php echo SITE_NAME; ?>
                    </a>
                    <p class="footer-description">
                        Discover the fascinating world of insects through our comprehensive digital library. 
                        Learn about different species, earn badges, and join a community of insect enthusiasts.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-section">
                    <h3 class="footer-title">Explore</h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/library.php" class="footer-link">Browse Library</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/species.php" class="footer-link">Species Database</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/assessments.php" class="footer-link">Take Assessments</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/badges.php" class="footer-link">Badge Collection</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/search.php" class="footer-link">Advanced Search</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/categories.php" class="footer-link">Browse Categories</a></li>
                    </ul>
                </div>
                
                <!-- Community -->
                <div class="footer-section">
                    <h3 class="footer-title">Community</h3>
                    <ul class="footer-links">
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="footer-link">My Dashboard</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/profile.php" class="footer-link">My Profile</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/reading-list.php" class="footer-link">Reading List</a></li>
                        <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/register.php" class="footer-link">Join Community</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/login.php" class="footer-link">Login</a></li>
                        <?php endif; ?>
                        <!-- <li><a href="<?php echo SITE_URL; ?>/forums.php" class="footer-link">Discussion Forums</a></li> -->
                        <!-- <li><a href="<?php echo SITE_URL; ?>/contributors.php" class="footer-link">Contributors</a></li> -->
                        <li><a href="<?php echo SITE_URL; ?>/leaderboard.php" class="footer-link">Leaderboard</a></li>
                        <!-- <li><a href="<?php echo SITE_URL; ?>/testimonials.php" class="footer-link">Success Stories</a></li> -->
                    </ul>
                </div>
                
                <!-- Newsletter & Support -->
                <div class="footer-section">
                    <h3 class="footer-title">Stay Updated</h3>
                    <p style="color: var(--gray-300); margin-bottom: 1rem; font-size: 0.9rem;">
                        Get the latest insect discoveries and library updates delivered to your inbox.
                    </p>
                    <form class="newsletter-form" action="<?php echo SITE_URL; ?>/newsletter-signup.php" method="POST">
                        <input type="email" class="newsletter-input" placeholder="Enter your email" name="email" required>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="newsletter-btn">Subscribe</button>
                    </form>
                    
                    <ul class="footer-links" style="margin-top: 1.5rem;">
                        <li><a href="<?php echo SITE_URL; ?>/help.php" class="footer-link">Help Center</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="footer-link">Contact Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/feedback.php" class="footer-link">Feedback</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/bug-report.php" class="footer-link">Report Bug</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-copyright">
                    © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. 
                    Made with <i class="fas fa-heart" style="color: #ef4444;"></i> for insect enthusiasts.
                </div>
                
                <ul class="footer-bottom-links">
                    <li><a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/terms-of-service.php">Terms of Service</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/cookie-policy.php">Cookie Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/accessibility.php">Accessibility</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/sitemap.xml">Sitemap</a></li>
                </ul>
            </div>
        </div>
    </footer>
    
    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scroll-top" aria-label="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- Scripts -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additional_js)): ?>
    <script>
        <?php echo $additional_js; ?>
    </script>
    <?php endif; ?>
    
    <!-- Footer JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to Top Button
            const scrollTopBtn = document.getElementById('scroll-top');
            
            if (scrollTopBtn) {
                // Show/hide scroll to top button
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrollTopBtn.classList.add('visible');
                    } else {
                        scrollTopBtn.classList.remove('visible');
                    }
                });
                
                // Scroll to top functionality
                scrollTopBtn.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
            
            // Newsletter Form Handling
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    const emailInput = this.querySelector('input[type="email"]');
                    const submitBtn = this.querySelector('.newsletter-btn');
                    
                    if (emailInput.value.trim() === '') {
                        e.preventDefault();
                        emailInput.focus();
                        return;
                    }
                    
                    // Show loading state
                    submitBtn.style.opacity = '0.7';
                    submitBtn.textContent = 'Subscribing...';
                    submitBtn.disabled = true;
                });
            }
            
            // Animate footer stats on scroll
            function animateFooterStats() {
                const footerStats = document.querySelectorAll('.footer-stat-number');
                const options = {
                    threshold: 0.5,
                    rootMargin: '0px 0px -50px 0px'
                };

                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const target = entry.target;
                            const finalNumber = target.textContent.replace(/[^0-9]/g, '');
                            animateNumber(target, 0, parseInt(finalNumber), 2000);
                            observer.unobserve(target);
                        }
                    });
                }, options);

                footerStats.forEach(stat => observer.observe(stat));
            }

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
            
            // Initialize footer animations
            animateFooterStats();
            
            // Social link tracking (if analytics is implemented)
            document.querySelectorAll('.social-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    const platform = this.getAttribute('aria-label');
                    // Track social media clicks
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'social_click', {
                            'social_platform': platform,
                            'page_url': window.location.href
                        });
                    }
                });
            });
            
            // Footer link hover effects
            document.querySelectorAll('.footer-link').forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                
                link.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
        
        // Utility function for smooth scrolling to elements
        function smoothScrollTo(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
        
        // Cookie consent (if needed)
        function showCookieConsent() {
            // Implementation for cookie consent banner
            // This would typically show a banner at the bottom of the page
        }
        
        // Check if cookies consent is needed
        if (!localStorage.getItem('cookiesAccepted')) {
            // Uncomment to enable cookie consent
            // showCookieConsent();
        }
    </script>
    
    <!-- Analytics (Google Analytics 4) - Replace with your tracking ID -->
    <?php if (defined('GA_TRACKING_ID') && !empty(GA_TRACKING_ID)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_TRACKING_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GA_TRACKING_ID; ?>', {
            page_title: '<?php echo isset($page_title) ? addslashes($page_title) : 'Insect Life'; ?>',
            page_location: window.location.href
        });
        
        // Track reading progress for articles
        if (document.body.classList.contains('article-page')) {
            let progressTracked = false;
            window.addEventListener('scroll', function() {
                const progress = Math.round((window.pageYOffset / (document.body.scrollHeight - window.innerHeight)) * 100);
                
                if (progress >= 75 && !progressTracked) {
                    gtag('event', 'article_progress_75', {
                        'article_title': document.title,
                        'page_url': window.location.href
                    });
                    progressTracked = true;
                }
            });
        }
    </script>
    <?php endif; ?>
    
</body>
</html>