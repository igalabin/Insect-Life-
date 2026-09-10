// Enhanced Article Page JavaScript
// Add this to article.php in a script tag or separate JS file

const articleId = document.querySelector('[data-article-id]')?.dataset.articleId;
const userId = document.querySelector('[data-user-id]')?.dataset.userId;

// Reading Progress Tracker
class ReadingProgressTracker {
    constructor(articleId, userId) {
        this.articleId = articleId;
        this.userId = userId;
        this.startTime = Date.now();
        this.lastUpdate = 0;
        this.updateInterval = 2000; // Update every 2 seconds
        
        if (this.userId) {
            this.init();
        }
    }
    
    init() {
        window.addEventListener('scroll', () => this.handleScroll());
        window.addEventListener('beforeunload', () => this.saveProgress());
        
        // Initial update
        this.handleScroll();
        
        // Periodic save
        setInterval(() => this.saveProgress(), 30000); // Save every 30 seconds
    }
    
    handleScroll() {
        const now = Date.now();
        if (now - this.lastUpdate < this.updateInterval) {
            return;
        }
        
        const progress = this.calculateProgress();
        this.updateUI(progress);
        this.lastUpdate = now;
        
        // Debounced server update
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => this.saveProgress(), 2000);
    }
    
    calculateProgress() {
        const content = document.getElementById('article-content');
        if (!content) return 0;
        
        const contentTop = content.offsetTop;
        const contentHeight = content.offsetHeight;
        const windowHeight = window.innerHeight;
        const scrollPosition = window.scrollY;
        
        const progress = Math.min(100, Math.max(0, 
            ((scrollPosition + windowHeight - contentTop) / contentHeight) * 100
        ));
        
        return Math.round(progress);
    }
    
    updateUI(progress) {
        const percentageEl = document.getElementById('progress-percentage');
        const barEl = document.getElementById('progress-bar');
        
        if (percentageEl) percentageEl.textContent = progress + '%';
        if (barEl) barEl.style.width = progress + '%';
    }
    
    async saveProgress() {
        const progress = this.calculateProgress();
        const timeSpent = Math.floor((Date.now() - this.startTime) / 1000);
        
        try {
            const response = await fetch('api/update-progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    article_id: this.articleId,
                    progress: progress,
                    time_spent: timeSpent
                })
            });
            
            const data = await response.json();
            if (!data.success) {
                console.error('Progress save failed:', data.error);
            }
        } catch (error) {
            console.error('Progress save error:', error);
        }
    }
}

// Bookmark Manager
class BookmarkManager {
    constructor(articleId, userId) {
        this.articleId = articleId;
        this.userId = userId;
    }
    
    async toggle() {
        if (!this.userId) {
            window.location.href = `login.php?redirect=article.php?id=${this.articleId}`;
            return;
        }
        
        try {
            const response = await fetch('api/toggle-bookmark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: this.articleId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateUI(data.bookmarked);
                this.showNotification(
                    data.bookmarked ? 'Article bookmarked!' : 'Bookmark removed',
                    'success'
                );
            } else {
                this.showNotification('Failed to update bookmark', 'error');
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            this.showNotification('An error occurred', 'error');
        }
    }
    
    updateUI(isBookmarked) {
        const btn = document.getElementById('bookmark-btn');
        const icon = document.getElementById('bookmark-icon');
        const text = document.getElementById('bookmark-text');
        
        if (!btn || !icon || !text) return;
        
        if (isBookmarked) {
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
    
    showNotification(message, type) {
        if (typeof showNotification === 'function') {
            showNotification(message, type, 3000);
        }
    }
}

// Comment Manager
class CommentManager {
    constructor(articleId, userId) {
        this.articleId = articleId;
        this.userId = userId;
        this.form = document.getElementById('comment-form');
        this.commentsList = document.getElementById('comments-list');
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Enable AJAX submission
        this.form.dataset.ajax = 'true';
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.userId) {
            window.location.href = `login.php?redirect=article.php?id=${this.articleId}`;
            return;
        }
        
        const textarea = this.form.querySelector('textarea[name="content"]');
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const content = textarea.value.trim();
        
        if (!content) {
            this.showNotification('Please enter a comment', 'error');
            return;
        }
        
        // Disable form
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
        
        try {
            const response = await fetch('api/submit-comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    article_id: this.articleId,
                    content: content
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add comment to list
                this.addCommentToUI(data.comment);
                
                // Clear form
                textarea.value = '';
                
                // Update count
                this.updateCommentCount(1);
                
                this.showNotification('Comment posted successfully!', 'success');
            } else {
                this.showNotification(data.error || 'Failed to post comment', 'error');
            }
        } catch (error) {
            console.error('Comment submission error:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-comment"></i> Post Comment';
        }
    }
    
    addCommentToUI(comment) {
        const commentHTML = `
            <div class="comment" data-comment-id="${comment.id}">
                <div class="comment-header">
                    <div class="comment-author">
                        ${this.escapeHtml(comment.first_name + ' ' + comment.last_name)}
                    </div>
                    <div class="comment-time">
                        ${comment.time_ago}
                    </div>
                </div>
                <div class="comment-content">
                    ${this.escapeHtml(comment.content).replace(/\n/g, '<br>')}
                </div>
                ${this.userId == comment.user_id ? `
                    <button class="btn btn-sm btn-danger" onclick="deleteComment(${comment.id})" style="margin-top: 0.5rem;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ` : ''}
            </div>
        `;
        
        // Remove "no comments" message if exists
        const noComments = this.commentsList.querySelector('p');
        if (noComments) {
            noComments.remove();
        }
        
        // Add new comment at the top
        this.commentsList.insertAdjacentHTML('afterbegin', commentHTML);
        
        // Animate new comment
        const newComment = this.commentsList.firstElementChild;
        newComment.style.animation = 'fadeInUp 0.5s ease-out';
    }
    
    async deleteComment(commentId) {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        
        try {
            const response = await fetch('api/delete-comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove comment from UI
                const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentEl) {
                    commentEl.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => commentEl.remove(), 300);
                }
                
                this.updateCommentCount(-1);
                this.showNotification('Comment deleted', 'success');
            } else {
                this.showNotification(data.error || 'Failed to delete comment', 'error');
            }
        } catch (error) {
            console.error('Delete comment error:', error);
            this.showNotification('An error occurred', 'error');
        }
    }
    
    updateCommentCount(change) {
        const countEl = document.querySelector('#comments h3');
        if (countEl) {
            const match = countEl.textContent.match(/\((\d+)\)/);
            if (match) {
                const currentCount = parseInt(match[1]);
                const newCount = Math.max(0, currentCount + change);
                countEl.textContent = `Comments (${newCount})`;
            }
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showNotification(message, type) {
        if (typeof showNotification === 'function') {
            showNotification(message, type, 3000);
        } else {
            alert(message);
        }
    }
}

// Rating Manager
class RatingManager {
    constructor() {
        this.form = document.getElementById('rating-form');
        this.stars = document.querySelectorAll('.star');
        this.ratingInput = document.getElementById('rating-value');
        
        if (this.stars.length > 0) {
            this.init();
        }
    }
    
    init() {
        this.stars.forEach((star, index) => {
            star.addEventListener('click', () => this.setRating(index + 1));
            star.addEventListener('mouseenter', () => this.highlightStars(index + 1));
            star.addEventListener('mouseleave', () => this.resetHighlight());
        });
    }
    
    setRating(rating) {
        this.ratingInput.value = rating;
        this.updateStars(rating);
    }
    
    highlightStars(rating) {
        this.stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = 'var(--accent-yellow)';
            } else {
                star.style.color = 'var(--gray-300)';
            }
        });
    }
    
    resetHighlight() {
        const currentRating = parseInt(this.ratingInput.value) || 0;
        this.updateStars(currentRating);
    }
    
    updateStars(rating) {
        this.stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
                star.style.color = 'var(--accent-yellow)';
            } else {
                star.classList.remove('active');
                star.style.color = 'var(--gray-300)';
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    if (articleId && userId) {
        // Initialize progress tracker
        window.progressTracker = new ReadingProgressTracker(articleId, userId);
        
        // Initialize bookmark manager
        window.bookmarkManager = new BookmarkManager(articleId, userId);
        
        // Initialize comment manager
        window.commentManager = new CommentManager(articleId, userId);
        
        // Initialize rating manager
        window.ratingManager = new RatingManager();
    }
    
    // Scroll to comments if hash is present
    if (window.location.hash === '#comments') {
        setTimeout(() => {
            document.getElementById('comments')?.scrollIntoView({ behavior: 'smooth' });
        }, 100);
    }
});

// Global functions for inline event handlers
function toggleBookmark(articleId) {
    if (window.bookmarkManager) {
        window.bookmarkManager.toggle();
    }
}

function deleteComment(commentId) {
    if (window.commentManager) {
        window.commentManager.deleteComment(commentId);
    }
}

function setRating(rating) {
    if (window.ratingManager) {
        window.ratingManager.setRating(rating);
    }
}

// Add fadeOut animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(-20px);
        }
    }
`;
document.head.appendChild(style);