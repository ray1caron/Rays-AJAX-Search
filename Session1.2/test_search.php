<?php
/**
 * Test script for AJAX Search
 * Place this in your Joomla root, test, then delete
 */

define('_JEXEC', 1);
define('JPATH_BASE', __DIR__);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Start Joomla
$app = JFactory::getApplication('site');

echo "<h1>AJAX Search - Session 1.2 Test</h1>";

// Path to our component
$componentPath = JPATH_BASE . '/components/com_ajaxsearch/src';

// Check if files exist
$files = [
    'Compatibility/VersionCheck.php',
    'Database/Adapter.php', 
    'Search/AbstractSearchEngine.php',
    'Search/ArticleSearch.php',
    'Cache/BasicCache.php',
    'Models/SearchModel.php'
];

echo "<h2>File Check:</h2>";
foreach ($files as $file) {
    $fullPath = $componentPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ " . $file . "<br>";
    } else {
        echo "✗ " . $file . " (MISSING)<br>";
    }
}

// Test database connection
echo "<h2>Database Test:</h2>";
try {
    require_once $componentPath . '/Compatibility/VersionCheck.php';
    require_once $componentPath . '/Database/Adapter.php';
    
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    echo "✓ Database adapter created successfully<br>";
    
    // Test table existence
    $tables = ['ajaxsearch_cache', 'ajaxsearch_config'];
    foreach ($tables as $table) {
        if ($dbAdapter->tableExists($table)) {
            echo "✓ Table {$table} exists<br>";
        } else {
            echo "✗ Table {$table} missing<br>";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test search functionality
echo "<h2>Search Test:</h2>";
try {
    require_once $componentPath . '/Search/ArticleSearch.php';
    require_once $componentPath . '/Cache/BasicCache.php';
    require_once $componentPath . '/Models/SearchModel.php';
    
    // Load configuration
    $config = [];
    $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
    
    echo "✓ Search model created<br>";
    
    // Test search with a common word
    $testQuery = 'article';
    $results = $searchModel->search($testQuery, ['limit' => 3]);
    
    if ($results['success']) {
        echo "✓ Search successful<br>";
        echo "Query: '{$testQuery}'<br>";
        echo "Found: {$results['total']} results<br>";
        echo "Returned: " . count($results['results']) . " results<br>";
        echo "Search time: {$results['search_time']} seconds<br>";
        echo "Cached: " . ($results['cached'] ? 'Yes' : 'No') . "<br>";
        
        if (!empty($results['results'])) {
            echo "<h3>Sample Results:</h3>";
            foreach ($results['results'] as $index => $result) {
                echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
                echo "<strong>#" . ($index + 1) . ":</strong> {$result['title']}<br>";
                echo "Relevance: {$result['relevance']}<br>";
                echo "URL: {$result['url']}<br>";
                echo "Snippet: " . substr(strip_tags($result['snippet']), 0, 100) . "...<br>";
                echo "</div>";
            }
        }
    } else {
        echo "✗ Search failed: " . ($results['error'] ?? 'Unknown error') . "<br>";
    }
    
    // Test cache
    echo "<h2>Cache Test:</h2>";
    $cacheStats = $searchModel->getStats();
    echo "Cache entries: " . $cacheStats['cache']['total_entries'] . "<br>";
    echo "Cache hits: " . $cacheStats['cache']['total_hits'] . "<br>";
    echo "Cache size: " . $cacheStats['cache']['total_size'] . "<br>";
    
    // Run comprehensive test
    echo "<h2>Comprehensive Test:</h2>";
    $testResults = $searchModel->testSearch('test');
    echo "<pre>" . print_r($testResults, true) . "</pre>";
    
} catch (Exception $e) {
    echo "✗ Search test error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test AJAX endpoint
echo "<h2>AJAX Endpoint Test:</h2>";
$ajaxUrl = JUri::base() . 'components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=2';
echo "AJAX URL: <a href='{$ajaxUrl}' target='_blank'>{$ajaxUrl}</a><br>";
echo "Open this URL to test JSON response<br>";

// Cleanup recommendation
echo "<h2>Next Steps:</h2>";
echo "1. Test the AJAX endpoint URL above<br>";
echo "2. If everything works, delete this test file<br>";
echo "3. Proceed to Session 1.3<br>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";