<?php
/**
 * Development Test Script for AjaxSearch
 * Run directly to test compatibility and database
 */

// ==================== BOOTSTRAP ====================
// Define _JEXEC FIRST before checking it
define('_JEXEC', 1);

// Define base path - using __DIR__ is more reliable
define('JPATH_BASE', dirname(dirname(dirname(__DIR__))));

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Joomla framework
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Bootstrap Joomla application
try {
    $app = JFactory::getApplication('site');
    // Optional: If you need session, user, etc.
    // $app->initialise();
} catch (Exception $e) {
    die('Failed to initialize Joomla: ' . $e->getMessage());
}

// ==================== YOUR TEST CODE ====================
echo "<h1>AjaxSearch Development Test</h1>";
echo "Joomla Version: " . JVERSION . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br><hr>";

try {
    // Test version check
    $versionCheckPath = __DIR__ . '/src/Compatibility/VersionCheck.php';
    
    if (!file_exists($versionCheckPath)) {
        throw new Exception("VersionCheck.php not found at: " . $versionCheckPath);
    }
    
    require_once $versionCheckPath;
    
    // Check if class exists
    if (!class_exists('AjaxsearchCompatibilityVersionCheck')) {
        throw new Exception("Class AjaxsearchCompatibilityVersionCheck not found");
    }
    
    $checker = new AjaxsearchCompatibilityVersionCheck();
    $results = $checker->checkCompatibility();
    
    echo "<h2>Compatibility Check Results:</h2>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'><h3>Error in Version Check:</h3>";
    echo $e->getMessage() . "</div><br>";
}

echo "<hr>";

try {
    // Test database adapter
    $adapterPath = __DIR__ . '/src/Database/Adapter.php';
    
    if (!file_exists($adapterPath)) {
        throw new Exception("Adapter.php not found at: " . $adapterPath);
    }
    
    require_once $adapterPath;
    
    if (!class_exists('AjaxsearchDatabaseAdapter')) {
        throw new Exception("Class AjaxsearchDatabaseAdapter not found");
    }
    
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    
    echo "<h2>Database Info:</h2>";
    echo "Charset: " . (method_exists($dbAdapter, 'getCharset') ? $dbAdapter->getCharset() : 'Method not found') . "<br>";
    echo "Collation: " . (method_exists($dbAdapter, 'getCollation') ? $dbAdapter->getCollation() : 'Method not found') . "<br>";
    
    // Check for property vs method
    if (property_exists($dbAdapter, 'utf8mb4Supported')) {
        echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported ? 'Yes' : 'No') . "<br>";
    } elseif (method_exists($dbAdapter, 'utf8mb4Supported')) {
        echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported() ? 'Yes' : 'No') . "<br>";
    } else {
        echo "UTF8MB4 Supported: Property/method not found<br>";
    }
    
    echo "<h2>Tables Check:</h2>";
    $tables = ['ajaxsearch_cache', 'ajaxsearch_sp_cache', 'ajaxsearch_analytics', 'ajaxsearch_config'];
    
    if (method_exists($dbAdapter, 'tableExists')) {
        foreach ($tables as $table) {
            // Joomla tables have prefix
            $fullTableName = $app->get('dbprefix') . $table;
            echo $table . " (" . $fullTableName . "): " . 
                 ($dbAdapter->tableExists($table) ? 'Exists' : 'Missing') . "<br>";
        }
    } else {
        echo "tableExists method not found in adapter<br>";
        
        // Alternative direct database check
        $db = JFactory::getDbo();
        foreach ($tables as $table) {
            $fullTableName = $db->getPrefix() . $table;
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('information_schema.tables')
                ->where('table_schema = ' . $db->quote($db->getName()))
                ->where('table_name = ' . $db->quote($fullTableName));
            $db->setQuery($query);
            $exists = (int)$db->loadResult() > 0;
            echo $table . " (" . $fullTableName . "): " . ($exists ? 'Exists' : 'Missing') . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'><h3>Error in Database Adapter Test:</h3>";
    echo $e->getMessage() . "</div><br>";
}

echo "<hr>";
echo "<h3>Test completed!</h3>";

// Optional: Debug information
echo "<h4>Debug Info:</h4>";
echo "Current Dir: " . __DIR__ . "<br>";
echo "JPATH_BASE: " . JPATH_BASE . "<br>";
echo "Files checked:<br>";
echo "- " . $versionCheckPath . " (" . (file_exists($versionCheckPath) ? 'Exists' : 'Missing') . ")<br>";
echo "- " . $adapterPath . " (" . (file_exists($adapterPath) ? 'Exists' : 'Missing') . ")<br>";
?>