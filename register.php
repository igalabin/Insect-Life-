<?php
session_start();
require_once 'config.php';

if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$page_title = "Join Insect Life - Create Account";
$page_description = "Join our community of insect enthusiasts.";
$page_keywords = "register, signup, create account";

$error_message = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch. Please try again.';
    } else {
        $form_data = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'experience_level' => sanitizeInput($_POST['experience_level'] ?? ''),
            'interests' => $_POST['interests'] ?? []
        ];
        
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms_accepted = isset($_POST['terms_accepted']);
        $newsletter_subscribe = isset($_POST['newsletter_subscribe']);
        
        $errors = [];
        
        if (empty($form_data['username'])) {
            $errors[] = 'Username is required.';
        } elseif (strlen($form_data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        if (empty($form_data['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($form_data['first_name'])) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($form_data['last_name'])) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!$terms_accepted) {
            $errors[] = 'You must accept the Terms of Service.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$form_data['username']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Username is already taken.';
                }
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$form_data['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email is already registered.';
                }
                
                if (empty($errors)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $verification_token = bin2hex(random_bytes(32));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                        (username, email, password_hash, first_name, last_name, experience_level, 
                         newsletter_subscribed, email_verification_token, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $form_data['username'],
                        $form_data['email'],
                        $password_hash,
                        $form_data['first_name'],
                        $form_data['last_name'],
                        $form_data['experience_level'],
                        $newsletter_subscribe ? 1 : 0,
                        $verification_token
                    ]);
                    
                    $user_id = $pdo->lastInsertId();
                    
                    if (!empty($form_data['users'])) {
                        $stmt = $pdo->prepare("INSERT INTO users (user_id, interest) VALUES (?, ?)");
                        foreach ($form_data['users'] as $interest) {
                            $stmt->execute([$user_id, sanitizeInput($interest)]);
                        }
                    }
                    
                    $_SESSION['success_message'] = 'Account created successfully!';
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $form_data['username'];
                    $_SESSION['email'] = $form_data['email'];
                    $_SESSION['user_type'] = 'user';
                    redirectTo('dashboard.php');
                }
                
            } catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    $errors[] = 'Database error: ' . $e->getMessage();
}
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            margin: 0;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 600px;
            margin: 2rem auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .register-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
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
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .interests-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .interest-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .interest-option:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }
        
        .interest-option input[type="checkbox"] {
            cursor: pointer;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        
        .form-check label {
            cursor: pointer;
            user-select: none;
        }
        
        .register-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .register-links {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 1.5rem;
        }
        
        .register-links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }
            
            .register-container {
                padding: 2rem 1.5rem;
            }
            
            .form-row, .interests-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">🦋</div>
            <h1 class="register-title">Join Our Community</h1>
            <p style="color: #6b7280;">Start your insect learning journey today</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" 
                           placeholder="Jay" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" 
                           placeholder="Bond" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" 
                       placeholder="jaybond123" required>
                <small style="color: #6b7280; font-size: 0.875rem;">Letters, numbers, and underscores only</small>
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                       placeholder="jaybond123@example.com" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Min. 8 characters" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="Re-enter password" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Experience Level *</label>
                <select name="experience_level" class="form-control" required>
                    <option value="">Select your level</option>
                    <option value="beginner" <?php echo ($form_data['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner - New to entomology</option>
                    <option value="intermediate" <?php echo ($form_data['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate - Some knowledge</option>
                    <option value="advanced" <?php echo ($form_data['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced - Experienced hobbyist</option>
                    <option value="expert" <?php echo ($form_data['experience_level'] ?? '') === 'expert' ? 'selected' : ''; ?>>Expert - Professional/Academic</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">What interests you? (Optional)</label>
                <div class="interests-grid">
                    <label class="interest-option">
                        <input type="checkbox" name="interests[]" value="butterflies" 
                               <?php echo in_array('butterflies', $form_data['interests'] ?? []) ? 'checked' : ''; ?>>
                        <span>🦋 Butterflies</span>
                    </label>
                    <label class="interest-option">
                        <input type="checkbox" name="interests[]" value="beetles" 
                               <?php echo in_array('beetles', $form_data['interests'] ?? []) ? 'checked' : ''; ?>>
                        <span>🪲 Beetles</span>
                    </label>
                    <label class="interest-option">
                        <input type="checkbox" name="interests[]" value="ants" 
                               <?php echo in_array('ants', $form_data['interests'] ?? []) ? 'checked' : ''; ?>>
                        <span>🐜 Ants</span>
                    </label>
                    <label class="interest-option">
                        <input type="checkbox" name="interests[]" value="bees" 
                               <?php echo in_array('bees', $form_data['interests'] ?? []) ? 'checked' : ''; ?>>
                        <span>🐝 Bees</span>
                    </label>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" id="newsletter" name="newsletter_subscribe">
                <label for="newsletter">Send me newsletter updates and learning tips</label>
            </div>

            <div class="form-check">
                <input type="checkbox" id="terms" name="terms_accepted" required>
                <label for="terms">I agree to the <a href="#" style="color: #3b82f6;">Terms of Service</a> *</label>
            </div>

            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="register-links">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.php">← Back to Home</a></p>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
        
        // Password strength indicator
        const passwordInput = document.querySelector('input[name="password"]');
        passwordInput.addEventListener('input', function() {
            const strength = this.value.length >= 12 ? 'Strong' : 
                           this.value.length >= 8 ? 'Good' : 'Weak';
            const color = this.value.length >= 12 ? '#10b981' : 
                         this.value.length >= 8 ? '#f59e0b' : '#ef4444';
            
            this.style.borderColor = color;
        });
    </script>
</body>
</html>