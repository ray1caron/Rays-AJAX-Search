<?php
/**
 * Simplified AjaxSearch Test
 * Loads Joomla framework WITHOUT full application
 */

// ==================== BOOTSTRAP ====================
define('_JEXEC', 1);
define('JPATH_BASE', '/home/1208205.cloudwaysapps.com/xbczjgmawe/public_html');

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AjaxSearch Test (Simplified)</h1>";

// Load JUST the framework (no application)
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Import the JLoader class
jimport('joomla.filesystem.path');
jimport('joomla.factory');

echo "<h2>✅ Joomla Framework Loaded</h2>";
echo "JVersion: " . (class_exists('JVersion') ? JVERSION : 'Not loaded') . "<br>";
echo "PHP: " . PHP_VERSION . "<br><hr>";

// ==================== DIRECT DATABASE TEST ====================
echo "<h2>Database Connection Test</h2>";

try {
    // Get database directly from configuration
    $config = new JConfig();
    $host = $config->host;
    $user = $config->user;
    $password = $config->password;
    $dbname = $config->db;
    $dbprefix = $config->dbprefix;
    
    echo "Database: $dbname<br>";
    echo "Prefix: $dbprefix<br>";
    
    // Create direct PDO connection (bypass Joomla DB layer)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Direct database connection successful<br>";
    
    // Check your tables
    $tables = [
        $dbprefix . 'ajaxsearch_cache',
        $dbprefix . 'ajaxsearch_sp_cache', 
        $dbprefix . 'ajaxsearch_analytics',
        $dbprefix . 'ajaxsearch_config'
    ];
    
    echo "<h3>Table Check:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "$table: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
    }
    
    echo "<hr>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// ==================== CHECK YOUR FILES ====================
echo "<h2>Component File Check</h2>";

$componentPath = __DIR__;
echo "Component path: $componentPath<br>";

// List all files in component directory
if (is_dir($componentPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($componentPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    echo "<h3>Component Structure:</h3>";
    echo "<div style='font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;'>";
    foreach ($iterator as $item) {
        $depth = $iterator->getDepth();
        $prefix = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
        if ($item->isDir()) {
            echo $prefix . "📁 " . $item->getFilename() . "<br>";
        } else {
            echo $prefix . "📄 " . $item->getFilename() . "<br>";
        }
    }
    echo "</div>";
}

// ==================== LOAD YOUR CLASSES MANUALLY ====================
echo "<hr><h2>Manual Class Load Test</h2>";

// First, let's see what files we have
$srcDir = __DIR__ . '/src';
if (is_dir($srcDir)) {
    echo "Looking for classes in src directory...<br>";
    
    // Try to find and include PHP files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($componentPath . '/', '', $file->getPathname());
            echo "Found: $relativePath<br>";
            
            // Try to include it
            try {
                require_once $file->getPathname();
                echo "&nbsp;&nbsp;✅ Included successfully<br>";
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;❌ Error: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Now check what classes are loaded
    echo "<h3>Loaded Classes (filtered):</h3>";
    $allClasses = get_declared_classes();
    $ajaxsearchClasses = array_filter($allClasses, function($class) {
        return stripos($class, 'ajaxsearch') !== false || 
               stripos($class, 'Ajaxsearch') !== false;
    });
    
    if (count($ajaxsearchClasses) > 0) {
        foreach ($ajaxsearchClasses as $class) {
            echo "- $class<br>";
        }
    } else {
        echo "No AjaxSearch-related classes found.<br>";
    }
} else {
    echo "❌ src directory not found at: $srcDir<br>";
}

echo "<hr><h2>✅ Test Complete</h2>";
?>