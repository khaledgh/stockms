<?php
// This is a modified index.php that works with open_basedir restrictions
// The key is to have all required files within the web directory

// Define application paths
define('YII_DEBUG', true);
define('YII_ENV', 'dev');

// Create a simple router
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Handle static files
if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
    return false; // Let the web server handle it
}

// Simple routing
if ($path === '/' || $path === '/index.php') {
    echo "<h1>Welcome to Stock & Sales Management System</h1>";
    echo "<p>Your PHP is working correctly, but there's an open_basedir restriction that prevents accessing files outside the web directory.</p>";
    echo "<p>To fix this, you need to modify your PHP configuration to allow access to the parent directory.</p>";
    
    echo "<h2>Server Information</h2>";
    echo "<ul>";
    echo "<li>PHP Version: " . phpversion() . "</li>";
    echo "<li>open_basedir: " . ini_get('open_basedir') . "</li>";
    echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
    echo "<li>Current Script: " . __FILE__ . "</li>";
    echo "</ul>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>Contact your hosting provider and ask them to modify the open_basedir setting to include:</p>";
    echo "<pre>/www/wwwroot/stock.linksbridge.top/:/tmp/</pre>";
    echo "<p>Or move your entire Yii2 application into the web directory (not recommended).</p>";
} elseif ($path === '/site/login' || $path === '/login') {
    echo "<h1>Login Page</h1>";
    echo "<p>This is a placeholder for the login page.</p>";
    echo "<p>The actual login functionality cannot be implemented until the open_basedir restriction is fixed.</p>";
} else {
    // 404 page
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "<p>The requested URL $path was not found on this server.</p>";
}
?>
