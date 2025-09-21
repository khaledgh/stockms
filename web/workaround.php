<?php
// This script creates a minimal working environment within the web directory
// Use this only as a temporary solution

// Create directories
$dirs = [
    __DIR__ . '/app',
    __DIR__ . '/app/config',
    __DIR__ . '/app/controllers',
    __DIR__ . '/app/models',
    __DIR__ . '/app/views',
    __DIR__ . '/app/views/site',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir<br>";
    }
}

// Create a simple config file
$configContent = <<<'EOT'
<?php
return [
    'id' => 'stockms',
    'name' => 'Stock & Sales Management System',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=stockms3',
            'username' => 'root',
            'password' => 'khaled',
            'charset' => 'utf8mb4',
        ],
    ],
];
EOT;

file_put_contents(__DIR__ . '/app/config/web.php', $configContent);
echo "Created config file<br>";

// Create a simple controller
$controllerContent = <<<'EOT'
<?php
namespace app\controllers;

class SiteController
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    public function actionLogin()
    {
        return $this->render('login');
    }
    
    public function render($view, $params = [])
    {
        extract($params);
        ob_start();
        include(__DIR__ . '/../views/site/' . $view . '.php');
        return ob_get_clean();
    }
}
EOT;

file_put_contents(__DIR__ . '/app/controllers/SiteController.php', $controllerContent);
echo "Created SiteController<br>";

// Create view files
$indexView = <<<'EOT'
<h1>Welcome to Stock & Sales Management System</h1>
<p>This is a temporary workaround due to open_basedir restrictions.</p>
<p><a href="index.php?r=site/login">Login</a></p>
EOT;

file_put_contents(__DIR__ . '/app/views/site/index.php', $indexView);
echo "Created index view<br>";

$loginView = <<<'EOT'
<h1>Login</h1>
<form method="post">
    <div>
        <label>Username:</label>
        <input type="text" name="username">
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password">
    </div>
    <div>
        <button type="submit">Login</button>
    </div>
</form>
EOT;

file_put_contents(__DIR__ . '/app/views/site/login.php', $loginView);
echo "Created login view<br>";

// Create a bootstrap file
$bootstrapContent = <<<'EOT'
<?php
// Simple router
$route = $_GET['r'] ?? 'site/index';
list($controller, $action) = explode('/', $route);

// Load controller
$controllerClass = 'app\\controllers\\' . ucfirst($controller) . 'Controller';
$actionMethod = 'action' . ucfirst($action);

if (file_exists(__DIR__ . '/app/controllers/' . ucfirst($controller) . 'Controller.php')) {
    require_once(__DIR__ . '/app/controllers/' . ucfirst($controller) . 'Controller.php');
    $controllerInstance = new $controllerClass();
    if (method_exists($controllerInstance, $actionMethod)) {
        echo $controllerInstance->$actionMethod();
    } else {
        echo "Action not found: $actionMethod";
    }
} else {
    echo "Controller not found: $controllerClass";
}
EOT;

file_put_contents(__DIR__ . '/app/bootstrap.php', $bootstrapContent);
echo "Created bootstrap file<br>";

// Create a new index.php
$indexContent = <<<'EOT'
<?php
// Minimal application entry point
define('YII_DEBUG', true);
define('YII_ENV', 'dev');

// Load configuration
$config = require(__DIR__ . '/app/config/web.php');

// Run the application
require(__DIR__ . '/app/bootstrap.php');
EOT;

file_put_contents(__DIR__ . '/minimal-index.php', $indexContent);
echo "Created minimal-index.php<br>";

echo "<p>Setup complete. You can now access the minimal application at <a href='minimal-index.php'>minimal-index.php</a></p>";
?>
