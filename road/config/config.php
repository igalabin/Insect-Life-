<?php
declare(strict_types=1);

// Base configuration
define('APP_NAME', 'Road Fuel Demand System');
define('APP_ENV', getenv('APP_ENV') ?: 'local');
define('BASE_URL', getenv('BASE_URL') ?: '/road/');

// Security
define('SESSION_NAME', 'roadfuel_sess');
define('CSRF_TOKEN_KEY', 'csrf_token');

// File uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_PROFILES', UPLOAD_DIR . '/profiles');
define('UPLOAD_PAYMENTS', UPLOAD_DIR . '/payment-proofs');

// System defaults (can be overridden by DB settings)
define('DEFAULT_DELIVERY_FEE', 50.00);
define('DEFAULT_SERVICE_RADIUS_KM', 20);

// Timezone
date_default_timezone_set('Asia/Manila');


