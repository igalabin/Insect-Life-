<?php
session_start();
require_once 'config.php';

$page_title = "Help Center - Insect Life";
$page_description = "Find answers to frequently asked questions and get help using Insect Life.";
$page_keywords = "help, FAQ, support, guide, how-to";

$additional_css = "
    .help-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .help-header {
        text-align: center;
        margin-bottom: 3rem;
        color: white;
    }
    
    .help-search {
        max-width: 600px;
        margin: 2rem auto;
        position: relative;
    }
    
    .help-search input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: none;
        border-radius: var(--border-radius-lg);
        font-size: 1.1rem;
        box-shadow: var(--shadow-md);
    }
    
    .help-search .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-400);
    }
    
    .help-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .help-category {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        transition: all var(--transition-normal);
    }
    
    .help-category:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }
    
    .category-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        padding: 1.5rem;
        text-align: center;
    }
    
    .category-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    
    .category-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }
    
    .category-body {
        padding: 1.5rem;
    }
    
    .faq-item {
        margin-bottom: 1rem;
    }
    
    .faq-question {
        background: var(--gray-50);
        border: none;
        width: 100%;
        padding: 1rem;
        text-align: left;
        font-weight: 500;
        border-radius: var(--border-radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .faq-question:hover {
        background: var(--gray-100);
    }
    
    .faq-answer {
        padding: 1rem;
        background: white;
        border-left: 3px solid var(--primary-blue);
        margin-top: 0.5rem;
        border-radius: 0 var(--border-radius-md) var(--border-radius-md) 0;
        display: none;
    }
    
    .faq-answer.open {
        display: block;
    }
    
    .contact-section {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        text-align: center;
        margin-top: 3rem;
    }
    
    @media (max-width: 768px) {
        .help-categories {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>

<div class="help-container">
    <div class="help-header">
        <h1>Help Center</h1>
        <p>Find answers to your questions and get the most out of Insect Life</p>
    </div>

    <!-- Help Search -->
    <div class="help-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="Search for help topics..." id="help-search">
    </div>

    <!-- Help Categories -->
    <div class="help-categories">
        <!-- Getting Started -->
        <div class="help-category">
            <div class="category-header">
                <div class="category-icon">🚀</div>
                <h2 class="category-title">Getting Started</h2>
            </div>
            <div class="category-body">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do I create an account?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>To create an account, click the "Join Community" button on the homepage or navigate to the registration page. Fill in your details including name, email, and password, then verify your email address.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do I navigate the digital library?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>The digital library can be accessed from the main navigation menu. Use the search bar to find specific topics, or browse by category, difficulty level, or insect order using the filter options.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reading & Learning -->
        <div class="help-category">
            <div class="category-header">
                <div class="category-icon">📖</div>
                <h2 class="category-title">Reading & Learning</h2>
            </div>
            <div class="category-body">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How does reading progress tracking work?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Your reading progress is automatically tracked as you scroll through articles. The system monitors how much of each article you've read and saves your position for when you return.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        Can I bookmark articles for later?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! Click the bookmark icon on any article to save it to your reading list. You can access your bookmarked articles from your dashboard or profile page.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges & Assessments -->
        <div class="help-category">
            <div class="category-header">
                <div class="category-icon">🏆</div>
                <h2 class="category-title">Badges & Assessments</h2>
            </div>
            <div class="category-body">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do I earn badges?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Badges are earned by completing various activities like reading articles, passing assessments, and engaging with the community. Each badge has specific requirements that you can view on the badges page.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        What are the different badge rarities?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Badges come in five rarity levels: Common (easy to earn), Uncommon, Rare, Epic, and Legendary (most challenging). Higher rarity badges typically require more dedication and knowledge.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account & Profile -->
        <div class="help-category">
            <div class="category-header">
                <div class="category-icon">👤</div>
                <h2 class="category-title">Account & Profile</h2>
            </div>
            <div class="category-body">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do I update my profile information?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Go to Settings from your user menu (click your avatar in the top right). Here you can update your personal information, experience level, interests, and account preferences.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        How do I change my password?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>In your Settings page, scroll down to the "Change Password" section. Enter your current password and your new password twice, then click "Change Password".</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="contact-section">
        <h2>Still Need Help?</h2>
        <p>Can't find what you're looking for? Our support team is here to help!</p>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
            <a href="contact.php" class="btn btn-primary">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
            <a href="feedback.php" class="btn btn-secondary">
                <i class="fas fa-comment"></i> Send Feedback
            </a>
        </div>
    </div>
</div>

<script>
function toggleFAQ(button) {
    const answer = button.nextElementSibling;
    const icon = button.querySelector('i');
    
    // Toggle the answer visibility
    answer.classList.toggle('open');
    
    // Rotate the icon
    if (answer.classList.contains('open')) {
        icon.style.transform = 'rotate(180deg)';
    } else {
        icon.style.transform = 'rotate(0deg)';
    }
}

// Search functionality
document.getElementById('help-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = searchTerm === '' ? 'block' : 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>