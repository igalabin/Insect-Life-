<?php
session_start();
require_once 'config.php';

$page_title = 'News - ' . SITE_NAME;
$page_description = 'Latest updates and announcements about Insect Life.';

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 1rem;">
    <div class="card">
        <h1>News</h1>
        <p>Stay tuned! News and updates will appear here soon.</p>
        <p><a class="btn btn-primary" href="index.php">Back to Home</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>



