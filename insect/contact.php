<?php
session_start();
require_once 'config.php';

$page_title = "Contact Us - Insect Life";
$page_description = "Get in touch with the Insect Life team. We'd love to hear from you!";
$page_keywords = "contact, support, feedback, help";

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch. Please try again.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error_message = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Here you would typically send an email or store in database
            // For now, we'll just show a success message
            $success_message = 'Thank you for your message! We\'ll get back to you soon.';
        }
    }
}

$additional_css = "
    .contact-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .contact-header {
        text-align: center;
        margin-bottom: 3rem;
        color: white;
    }
    
    .contact-form {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .contact-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .info-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        text-align: center;
        transition: all var(--transition-normal);
    }
    
    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }
    
    .info-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: var(--primary-blue);
    }
    
    .info-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--gray-900);
    }
    
    .info-details {
        color: var(--gray-600);
        line-height: 1.6;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius-md);
        font-size: 1rem;
        transition: border-color var(--transition-fast);
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }
    
    .submit-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        color: white;
        border: none;
        border-radius: var(--border-radius-md);
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .contact-info {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>

<div class="contact-container">
    <div class="contact-header">
        <h1>Contact Us</h1>
        <p>Have a question, suggestion, or just want to say hello? We'd love to hear from you!</p>
    </div>

    <!-- Contact Information Cards -->
    <div class="contact-info">
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3 class="info-title">Email Support</h3>
            <div class="info-details">
                <p>For general inquiries and support:</p>
                <p><strong>support@insectlife.com</strong></p>
                <p>We typically respond within 24 hours</p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-bug"></i>
            </div>
            <h3 class="info-title">Scientific Inquiries</h3>
            <div class="info-details">
                <p>For questions about insect biology and taxonomy:</p>
                <p><strong>science@insectlife.com</strong></p>
                <p>Reviewed by our team of entomologists</p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="info-title">Community</h3>
            <div class="info-details">
                <p>Join our community discussions:</p>
                <p><strong>Discussion Forums</strong></p>
                <p>Connect with other insect enthusiasts</p>
            </div>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="contact-form">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Send us a Message</h2>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="subject" class="form-label">Subject</label>
                <select id="subject" name="subject" class="form-control" required>
                    <option value="">Select a subject</option>
                    <option value="general" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                    <option value="technical" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                    <option value="content" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'content') ? 'selected' : ''; ?>>Content Question</option>
                    <option value="bug" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'bug') ? 'selected' : ''; ?>>Bug Report</option>
                    <option value="suggestion" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'suggestion') ? 'selected' : ''; ?>>Suggestion</option>
                    <option value="partnership" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'partnership') ? 'selected' : ''; ?>>Partnership</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message" class="form-label">Message</label>
                <textarea id="message" name="message" class="form-control" 
                          placeholder="Tell us how we can help..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.submit-btn');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    });
});
</script>

<?php include 'includes/footer.php'; ?>