<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


/**
 * WordPress View Bootstrapper
 */
define('WP_USE_THEMES', true);
require __DIR__ . '/wp/wp-blog-header.php';
