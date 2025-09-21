<?php
// This file should be placed in the root directory of your server
// to help diagnose 404 errors

// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>404 Error Debugging</h1>";

// Check server environment
echo "<h2>Server Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Current Script: " . __FILE__ . "<br>";

// Check directory structure
echo "<h2>Directory Structure</h2>";
$currentDir = dirname(__FILE__);
echo "Current Directory: $currentDir<br>";

// List all directories and files in the current directory
echo "<h3>Files in current directory:</h3>";
$files = scandir($currentDir);
echo "<ul>";
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        $path = $currentDir . '/' . $file;
        $type = is_dir($path) ? "Directory" : "File";
        $size = is_file($path) ? filesize($path) . " bytes" : "-";
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "<li>$file ($type, $size, permissions: $perms)</li>";
    }
}
echo "</ul>";

// Check if web directory exists
$webDir = $currentDir . '/web';
echo "<h3>Web directory check:</h3>";
if (is_dir($webDir)) {
    echo "Web directory exists at: $webDir<br>";
    
    // List files in web directory
    echo "<h4>Files in web directory:</h4>";
    $webFiles = scandir($webDir);
    echo "<ul>";
    foreach ($webFiles as $file) {
        if ($file != "." && $file != "..") {
            $path = $webDir . '/' . $file;
            $type = is_dir($path) ? "Directory" : "File";
            $size = is_file($path) ? filesize($path) . " bytes" : "-";
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            echo "<li>$file ($type, $size, permissions: $perms)</li>";
        }
    }
    echo "</ul>";
    
    // Check for index.php
    if (file_exists($webDir . '/index.php')) {
        echo "index.php exists in web directory ✅<br>";
    } else {
        echo "index.php does NOT exist in web directory ❌<br>";
    }
} else {
    echo "Web directory does NOT exist at: $webDir ❌<br>";
}

// Check for Yii framework
$vendorDir = $currentDir . '/vendor';
$yiiFile = $vendorDir . '/yiisoft/yii2/Yii.php';
echo "<h3>Yii framework check:</h3>";
if (file_exists($yiiFile)) {
    echo "Yii.php exists at: $yiiFile ✅<br>";
} else {
    echo "Yii.php does NOT exist at: $yiiFile ❌<br>";
    
    if (is_dir($vendorDir)) {
        echo "Vendor directory exists, but Yii framework might not be installed correctly.<br>";
    } else {
        echo "Vendor directory does NOT exist. You may need to run 'composer install'.<br>";
    }
}

// Check for configuration files
$configDir = $currentDir . '/config';
echo "<h3>Configuration files check:</h3>";
if (is_dir($configDir)) {
    echo "Config directory exists at: $configDir ✅<br>";
    
    $configFiles = ['web.php', 'db.php', 'params.php'];
    foreach ($configFiles as $file) {
        $path = $configDir . '/' . $file;
        if (file_exists($path)) {
            echo "$file exists ✅<br>";
        } else {
            echo "$file does NOT exist ❌<br>";
        }
    }
} else {
    echo "Config directory does NOT exist at: $configDir ❌<br>";
}

// Check for PHP-FPM socket
echo "<h2>PHP-FPM Socket Check</h2>";
$socketPaths = [
    '/tmp/php-cgi-74.sock',
    '/var/run/php/php7.4-fpm.sock',
    '/var/run/php-fpm.sock',
    '/run/php/php7.4-fpm.sock'
];

foreach ($socketPaths as $socketPath) {
    if (file_exists($socketPath)) {
        echo "$socketPath exists ✅<br>";
    } else {
        echo "$socketPath does NOT exist ❌<br>";
    }
}

// Output server variables
echo "<h2>Server Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>
