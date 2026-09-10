<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}

$page_title = "Login - Insect Life";
$page_description = "Login to your Insect Life account to access your reading progress, badges, and personalized recommendations.";
$page_keywords = "login, account, sign in, insect life";

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token mismatch. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT id, username, email, password_hash, user_type, is_active FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Update last login
                    $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$user['id']]);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Set remember me cookie
                    if ($remember_me) {
                        $cookie_token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $cookie_token, time() + (30 * 24 * 60 * 60)); // 30 days
                    }
                    
                    $_SESSION['success_message'] = 'Welcome back, ' . $user['username'] . '!';
                    
                    // Redirect to intended page or dashboard
                    $redirect_url = $_GET['redirect'] ?? 'dashboard.php';
                    redirectTo($redirect_url);
                } else {
                    $error_message = 'Invalid email or password.';
                    error_log("Failed login attempt for email: $email from IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error_message = 'An error occurred. Please try again.';
            }
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
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%) !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            margin: 0;
            font-family: var(--font-family-primary);
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            z-index: 10;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        
        .login-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius-md);
            font-size: 1rem;
            transition: all var(--transition-fast);
            background: rgba(255, 255, 255, 0.9);
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: var(--white);
        }
        
        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.1rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color var(--transition-fast);
        }
        
        .password-toggle:hover {
            color: var(--gray-600);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-blue);
        }
        
        .form-check-label {
            font-size: 0.9rem;
            color: var(--gray-700);
            cursor: pointer;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-green) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .login-links {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .login-links a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-fast);
        }
        
        .login-links a:hover {
            color: var(--primary-blue-dark);
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--gray-500);
            font-size: 0.9rem;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gray-300);
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 1rem;
        }
        
        .social-login {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius-md);
            background: var(--white);
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        
        .social-btn:hover {
            border-color: var(--gray-400);
            background: var(--gray-50);
            color: var(--gray-800);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary-green-dark);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        @media (max-width: 480px) {
            body {
                padding: 1rem 0.5rem;
            }
            
            .login-container {
                padding: 2rem 1.5rem;
                margin: 0;
                max-width: 100%;
            }
            
            .login-logo {
                font-size: 2.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .form-control {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }
            
            .social-login {
                gap: 0.5rem;
            }
            
            .social-btn {
                padding: 0.625rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">🦋</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to continue your insect learning journey</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password"
                           required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-check">
                <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                <label for="remember_me" class="form-check-label">Remember me for 30 days</label>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <!-- Social Login Options -->
        <div class="divider">
            <span>or continue with</span>
        </div>

        <div class="social-login">
            <a href="#" class="social-btn" onclick="alert('Google OAuth integration would go here')">
                <i class="fab fa-google"></i>
                Continue with Google
            </a>
            <a href="#" class="social-btn" onclick="alert('Facebook OAuth integration would go here')">
                <i class="fab fa-facebook-f"></i>
                Continue with Facebook
            </a>
        </div>

        <div class="login-links">
            <p>
                <a href="forgot-password.php">Forgot your password?</a>
            </p>
            <p style="margin-top: 1rem;">
                Don't have an account? 
                <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    Sign up here
                </a>
            </p>
            <p style="margin-top: 1rem;">
                <a href="index.php">← Back to Home</a>
            </p>
        </div>
    </div>

    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('password-toggle-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }

    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.login-form');
        const submitBtn = document.querySelector('.login-btn');
        const inputs = form.querySelectorAll('.form-control');
        
        // Add input validation styling
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    this.classList.add('has-value');
                } else {
                    this.classList.remove('has-value');
                }
            });
            
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.form-icon').style.color = 'var(--primary-blue)';
            });
            
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.parentElement.querySelector('.form-icon').style.color = 'var(--gray-400)';
                }
            });
        });
        
        // Form submission handling
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });
        
        // Auto-focus first empty input
        const firstEmptyInput = Array.from(inputs).find(input => input.value.trim() === '');
        if (firstEmptyInput) {
            firstEmptyInput.focus();
        }
    });
    </script>
</body>
</html>