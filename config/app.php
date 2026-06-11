<?php
$base_url = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? "https://"
    : "http://"
) . $_SERVER['HTTP_HOST'];

$base_url .= dirname($_SERVER['SCRIPT_NAME']);

define('APP_NAME',    'YummySpot');
define('APP_URL',     $base_url);
define('APP_VERSION', '1.0.0');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ITEMS_PER_PAGE', 12);
define('FEED_PER_PAGE',  9);
