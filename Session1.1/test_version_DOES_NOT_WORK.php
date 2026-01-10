<?php
defined('_JEXEC') or die('Restricted access');
define('_JEXEC', 1);
define('JPATH_BASE', dirname(dirname(dirname(__DIR__))));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Start Joomla
$app = JFactory::getApplication('site');

// Test version check
require_once __DIR__ . '/src/Compatibility/VersionCheck.php';
$checker = new AjaxsearchCompatibilityVersionCheck();
$results = $checker->checkCompatibility();

echo "<h2>Compatibility Check Results:</h2>";
echo "<pre>";
print_r($results);
echo "</pre>";

// Test database adapter
require_once __DIR__ . '/src/Database/Adapter.php';
$dbAdapter = new AjaxsearchDatabaseAdapter();

echo "<h2>Database Info:</h2>";
echo "Charset: " . $dbAdapter->getCharset() . "<br>";
echo "Collation: " . $dbAdapter->getCollation() . "<br>";
echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported ? 'Yes' : 'No') . "<br>";

echo "<h2>Tables Check:</h2>";
$tables = ['ajaxsearch_cache', 'ajaxsearch_sp_cache', 'ajaxsearch_analytics', 'ajaxsearch_config'];
foreach ($tables as $table) {
    echo $table . ": " . ($dbAdapter->tableExists($table) ? 'Exists' : 'Missing') . "<br>";
}
