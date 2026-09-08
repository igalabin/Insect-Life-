<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default values if not set
$page_title = $page_title ?? 'Insect Life - Digital Library';
$page_description = $page_description ?? 'Explore the fascinating world of insects';
$page_keywords = $page_keywords ?? 'insects, entomology, digital library';
$additional_css = $additional_css ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    
    <!-- Additional CSS -->
    <?php if ($additional_css): ?>
    <style><?php echo $additional_css; ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress"></div>
    
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <!-- Logo -->
            <a href="../index.php" class="logo">
                <span class="logo-icon">🦋</span>
                <span class="logo-text">Insect Life</span>
            </a>
            
            <!-- Navigation -->
            <nav class="nav">
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation Menu -->
                <ul class="nav-menu" id="nav-menu">

                     <!-- Search Bar -->
                    <li class="nav-item search-container">
                        <form action="../library.php" method="GET" style="margin: 0;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search articles...">
                        </form>
                    </li>

                    
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../library.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../library.php' ? 'active' : ''; ?>">
                            <i class="fas fa-book-open"></i> Library
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../species.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../species.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bug"></i> Species
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../assessments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === '../assessments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-graduation-cap"></i> Assessments
                        </a>
                    </li>
                    
                   
                    
                    <!-- User Menu -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item user-menu">
                        <div class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="user-dropdown">
                            <a href="../dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="../profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="../reading-list.php" class="dropdown-item">
                                <i class="fas fa-bookmark"></i> Reading List
                            </a>
                            <a href="../badges.php" class="dropdown-item">
                                <i class="fas fa-trophy"></i> Badges
                            </a>
                            <a href="../settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="../logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a href="../login.php" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a href="../register.php" class="nav-link">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li> -->
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Main Content Area -->
    <main class="main-content"><?php
?>