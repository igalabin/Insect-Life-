<?php
session_start();
require_once 'config.php';

// Log the logout activity before clearing session
if (isLoggedIn()) {
    try {
        logActivity('user_logout', [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'logout_time' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Error logging logout activity: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Start a new session to show logout message
session_start();
$_SESSION['success_message'] = 'You have been successfully logged out. Thank you for visiting Insect Life!';

// Redirect to home page
redirectTo('index.php');
?>