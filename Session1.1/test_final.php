<?php
/**
 * Development Test Script for AjaxSearch
 * CORRECTED PATH VERSION
 */

// ==================== BOOTSTRAP ====================
define('_JEXEC', 1);

// CORRECT PATH (from your discovery):
define('JPATH_BASE', '/home/1208205.cloudwaysapps.com/xbczjgmawe/public_html');

// Or dynamically (if you want flexibility):
// define('JPATH_BASE', dirname(dirname(__DIR__)));

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify path
echo "<h2>Path Verification:</h2>";
echo "JPATH_BASE: " . JPATH_BASE . "<br>";
echo "defines.php exists: " . (file_exists(JPATH_BASE . '/includes/defines.php') ? '✅ YES' : '❌ NO') . "<br>";

// Load Joomla framework
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Bootstrap Joomla
try {
    $app = JFactory::getApplication('site');
    echo "<h2>✅ Joomla Loaded Successfully!</h2>";
    echo "Joomla Version: " . JVERSION . "<br>";
    echo "PHP Version: " . PHP_VERSION . "<br><hr>";
} catch (Exception $e) {
    die('Failed to initialize Joomla: ' . $e->getMessage());
}

// ==================== YOUR TESTS ====================

echo "<h1>AjaxSearch Development Tests</h1>";

// Test 1: Check if your class files exist
$versionCheckPath = __DIR__ . '/src/Compatibility/VersionCheck.php';
$adapterPath = __DIR__ . '/src/Database/Adapter.php';

echo "<h3>File Check:</h3>";
echo "VersionCheck.php: " . (file_exists($versionCheckPath) ? '✅ Found' : '❌ Missing') . "<br>";
echo "Adapter.php: " . (file_exists($adapterPath) ? '✅ Found' : '❌ Missing') . "<br>";

if (!file_exists($versionCheckPath) || !file_exists($adapterPath)) {
    echo "<p style='color:red;'>Missing required files. Check your component structure.</p>";
    echo "Current directory: " . __DIR__ . "<br>";
    
    // Show directory contents for debugging
    echo "<h4>Directory listing of " . __DIR__ . ":</h4>";
    if (is_dir(__DIR__)) {
        $files = scandir(__DIR__);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $type = is_dir(__DIR__ . '/' . $file) ? '[DIR]' : '[FILE]';
                echo "- $type $file<br>";
            }
        }
    }
    die();
}

// Test 2: Version Compatibility Check
echo "<hr><h2>1. Compatibility Check</h2>";
try {
    require_once $versionCheckPath;
    
    // Check class name - might need namespace
    if (class_exists('AjaxsearchCompatibilityVersionCheck')) {
        $checker = new AjaxsearchCompatibilityVersionCheck();
        $results = $checker->checkCompatibility();
        
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } elseif (class_exists('Compatibility\VersionCheck')) {
        $checker = new Compatibility\VersionCheck();
        $results = $checker->checkCompatibility();
        
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } else {
        echo "❌ Could not find VersionCheck class. Available classes:<br>";
        $declared = get_declared_classes();
        foreach ($declared as $class) {
            if (stripos($class, 'version') !== false || stripos($class, 'ajaxsearch') !== false) {
                echo "- $class<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}

// Test 3: Database Adapter Check
echo "<hr><h2>2. Database Adapter Check</h2>";
try {
    require_once $adapterPath;
    
    // Get database instance for comparison
    $db = JFactory::getDbo();
    echo "Joomla DB Prefix: " . $db->getPrefix() . "<br>";
    
    if (class_exists('AjaxsearchDatabaseAdapter')) {
        $dbAdapter = new AjaxsearchDatabaseAdapter();
        
        echo "<h3>Adapter Methods:</h3>";
        $methods = get_class_methods($dbAdapter);
        echo "Available methods: " . implode(', ', $methods) . "<br><br>";
        
        // Try to call methods safely
        if (method_exists($dbAdapter, 'getCharset')) {
            echo "Charset: " . $dbAdapter->getCharset() . "<br>";
        }
        
        if (method_exists($dbAdapter, 'getCollation')) {
            echo "Collation: " . $dbAdapter->getCollation() . "<br>";
        }
        
        // Check utf8mb4 support
        if (property_exists($dbAdapter, 'utf8mb4Supported')) {
            echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported ? 'Yes' : 'No') . "<br>";
        } elseif (method_exists($dbAdapter, 'utf8mb4Supported')) {
            echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported() ? 'Yes' : 'No') . "<br>";
        }
        
        echo "<h3>Table Check:</h3>";
        $tables = ['ajaxsearch_cache', 'ajaxsearch_sp_cache', 'ajaxsearch_analytics', 'ajaxsearch_config'];
        
        foreach ($tables as $table) {
            $fullTableName = $db->getPrefix() . $table;
            if (method_exists($dbAdapter, 'tableExists')) {
                $exists = $dbAdapter->tableExists($table);
                echo $table . " (" . $fullTableName . "): " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            } else {
                // Direct check
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from('information_schema.tables')
                    ->where('table_schema = DATABASE()')
                    ->where('table_name = ' . $db->quote($fullTableName));
                $db->setQuery($query);
                $exists = (int)$db->loadResult() > 0;
                echo $table . " (" . $fullTableName . "): " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            }
        }
    } else {
        echo "❌ AjaxsearchDatabaseAdapter class not found.<br>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}

echo "<hr><h2>✅ Test Complete</h2>";
echo "<p>All tests finished. Check results above.</p>";

// Optional: Show more debug info
echo "<h3>Debug Info:</h3>";
echo "Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
echo "Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB<br>";
?>