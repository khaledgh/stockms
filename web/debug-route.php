<?php
// Debug script to diagnose routing issues

// Define Yii constants
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

// Include Yii framework
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Load configuration
$config = require __DIR__ . '/../config/web.php';

// Output request information
echo "<h1>Request Information</h1>";
echo "<pre>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP Self: " . $_SERVER['PHP_SELF'] . "\n";
echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? '') . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "</pre>";

// Output URL manager configuration
echo "<h1>URL Manager Configuration</h1>";
echo "<pre>";
print_r($config['components']['urlManager']);
echo "</pre>";

// Try to parse the current URL
echo "<h1>URL Parsing Test</h1>";
try {
    // Create the application but don't run it
    $app = new yii\web\Application($config);
    
    // Get the URL manager component
    $urlManager = $app->getUrlManager();
    
    // Parse the current request
    $request = $app->getRequest();
    $pathInfo = $request->getPathInfo();
    
    echo "Path Info: " . $pathInfo . "\n";
    
    // Try to parse the URL
    $route = $urlManager->parseRequest($request);
    
    if ($route !== false) {
        echo "Parsed Route: " . print_r($route, true) . "\n";
    } else {
        echo "Failed to parse route\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Check for controller files
echo "<h1>Controller Files Check</h1>";
$controllersDir = dirname(__DIR__) . '/controllers';
$controllers = scandir($controllersDir);
echo "Controllers directory: $controllersDir\n";
echo "Available controllers:\n";
foreach ($controllers as $file) {
    if (substr($file, -14) === 'Controller.php') {
        echo "- $file\n";
    }
}

// Check for SiteController specifically
$siteControllerFile = $controllersDir . '/SiteController.php';
if (file_exists($siteControllerFile)) {
    echo "SiteController exists: Yes\n";
    echo "File size: " . filesize($siteControllerFile) . " bytes\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($siteControllerFile)), -4) . "\n";
} else {
    echo "SiteController exists: No\n";
}
?>
