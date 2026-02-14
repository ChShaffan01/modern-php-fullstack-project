<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bakery_pos');
define('BASE_URL', 'http://localhost/bakery-pos-system/');
define('SITE_NAME', 'Bakery POS System');

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('SESSION_TIMEOUT', 1800); // 30 minutes