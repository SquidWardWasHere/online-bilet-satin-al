<?php
// Security configurations
define('DB_PATH', __DIR__ . '/../database/bilet_satin_alma.db');
define('BASE_URL', 'http://localhost:8080');
define('SITE_NAME', 'Bussio - Bilet Satın Alma Platformu');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Istanbul');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_COMPANY_ADMIN', 'company_admin');
define('ROLE_USER', 'user');

// Default balance for new users
define('DEFAULT_USER_BALANCE', 5000);

// Ticket cancellation time limit (in hours)
define('TICKET_CANCEL_TIME_LIMIT', 1);
