<?php
// Base URL Configuration
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // If we're in the root directory
    if ($script_name === '/' || $script_name === '\\') {
        return '/';
    }
    
    // If we're in a subdirectory
    $base_url = dirname($script_name);
    if ($base_url !== '/') {
        $base_url .= '/';
    }
    
    return $base_url;
}

// Define URL constants
$base_url = getBaseUrl();
define('BASE_URL', $base_url);
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . BASE_URL);

// Application Constants
define('APP_NAME', 'BCP Graduation Pictorial');
define('FAVICON_PATH', 'includes/bcp_logo.png');

// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . BASE_URL . 'uploads/');
define('PROFILE_PICS_PATH', UPLOAD_PATH . 'profiles/');
define('GALLERY_PATH', UPLOAD_PATH . 'gallery/');

// Ensure upload directories exist
$directories = [UPLOAD_PATH, PROFILE_PICS_PATH, GALLERY_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
