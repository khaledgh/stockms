<?php
// Define path to the Yii framework
$yiiPath = dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

echo "<h1>Yii2 Framework Check</h1>";

// Check if Yii.php exists
if (file_exists($yiiPath)) {
    echo "✅ Yii framework file found at: $yiiPath<br>";
    
    // Try to include Yii.php
    try {
        require_once($yiiPath);
        echo "✅ Successfully included Yii.php<br>";
        echo "✅ Yii version: " . Yii::getVersion() . "<br>";
    } catch (Exception $e) {
        echo "❌ Error including Yii.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Yii framework file NOT found at: $yiiPath<br>";
}

// Check if config files exist
$configPath = dirname(__DIR__) . '/config/web.php';
if (file_exists($configPath)) {
    echo "✅ Config file found at: $configPath<br>";
    
    // Try to load the config
    try {
        $config = require($configPath);
        echo "✅ Successfully loaded config file<br>";
        
        // Check if essential components are configured
        $components = isset($config['components']) ? $config['components'] : [];
        echo "<h2>Configured Components:</h2>";
        echo "<ul>";
        foreach ($components as $name => $component) {
            echo "<li>$name</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "❌ Error loading config file: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Config file NOT found at: $configPath<br>";
}

// Check if the application can be initialized
if (class_exists('Yii')) {
    try {
        // Try to create the application but don't run it
        $app = new yii\web\Application($config);
        echo "✅ Successfully created Yii application instance<br>";
    } catch (Exception $e) {
        echo "❌ Error creating Yii application: " . $e->getMessage() . "<br>";
    }
}

// Display PHP info
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Loaded Extensions:<br>";
echo "<ul>";
foreach (get_loaded_extensions() as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";
?>
