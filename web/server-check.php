<?php
// Check server configuration
echo "<h1>Server Configuration Check</h1>";

// Check if the document root is correct
echo "<h2>Document Root</h2>";
echo "Current script path: " . __FILE__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Check if important directories exist
echo "<h2>Directory Structure</h2>";
$baseDir = dirname(dirname(__FILE__));
$dirs = [
    'web' => $baseDir . '/web',
    'controllers' => $baseDir . '/controllers',
    'models' => $baseDir . '/models',
    'views' => $baseDir . '/views',
    'config' => $baseDir . '/config',
    'vendor' => $baseDir . '/vendor',
];

foreach ($dirs as $name => $path) {
    echo "$name: " . (is_dir($path) ? "✅ Exists" : "❌ Missing") . " ($path)<br>";
}

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Check if important PHP extensions are loaded
echo "<h2>PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'intl', 'gd', 'fileinfo'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✅ Loaded" : "❌ Not loaded") . "<br>";
}

// Check if Yii is accessible
echo "<h2>Yii Framework</h2>";
if (file_exists($baseDir . '/vendor/yiisoft/yii2/Yii.php')) {
    echo "Yii framework file: ✅ Exists<br>";
} else {
    echo "Yii framework file: ❌ Missing<br>";
}

// Check permissions
echo "<h2>Directory Permissions</h2>";
$writableDirs = [
    'runtime' => $baseDir . '/runtime',
    'web/assets' => $baseDir . '/web/assets',
];

foreach ($writableDirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    echo "$name: " . ($exists ? "✅ Exists" : "❌ Missing") . 
         ($exists ? ($writable ? ", ✅ Writable" : ", ❌ Not writable") : "") . 
         " ($path)<br>";
}

// Output server variables
echo "<h2>Server Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>
