<?php
/**
 * Test script for SP Page Builder Search
 * Place this in your Joomla root, test, then delete
 */

define('_JEXEC', 1);
define('JPATH_BASE', __DIR__);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Start Joomla
$app = JFactory::getApplication('site');

echo "<h1>AJAX Search - Session 1.3 Test (SP Page Builder)</h1>";

// Path to our component
$componentPath = JPATH_BASE . '/components/com_ajaxsearch/src';

// Check if files exist
$files = [
    'Parser/SPParser.php',
    'Search/SPSearch.php',
    'Search/IntegratedSearch.php'
];

echo "<h2>New Files Check:</h2>";
foreach ($files as $file) {
    $fullPath = $componentPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ " . $file . "<br>";
    } else {
        echo "✗ " . $file . " (MISSING)<br>";
    }
}

// Test SP Page Builder table exists
echo "<h2>SP Page Builder Check:</h2>";
try {
    $db = JFactory::getDbo();
    $tables = $db->getTableList();
    $prefix = $db->getPrefix();
    $spTable = $prefix . 'sppagebuilder';
    
    if (in_array($spTable, $tables)) {
        echo "✓ SP Page Builder table exists<br>";
        
        // Count SP pages
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($spTable)
            ->where('published = 1');
        $db->setQuery($query);
        $count = $db->loadResult();
        echo "✓ Published SP pages: " . $count . "<br>";
    } else {
        echo "✗ SP Page Builder table not found<br>";
        echo "Note: SP Page Builder search will not work without the table<br>";
    }
} catch (Exception $e) {
    echo "✗ Error checking SP Page Builder: " . $e->getMessage() . "<br>";
}

// Test parser
echo "<h2>Parser Test:</h2>";
try {
    require_once $componentPath . '/Database/Adapter.php';
    require_once $componentPath . '/Parser/SPParser.php';
    
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $parser = new AjaxsearchParserSP($dbAdapter);
    
    echo "✓ Parser created successfully<br>";
    
    // Test with sample JSON
    $testResults = $parser->testParser();
    echo "✓ Parser test completed<br>";
    echo "JSON valid: " . ($testResults['json_valid'] ? 'Yes' : 'No') . "<br>";
    echo "Parse time: " . round($testResults['parse_time'], 4) . " seconds<br>";
    echo "Extracted length: " . $testResults['extracted_length'] . " characters<br>";
    echo "Sample text: " . htmlspecialchars(substr($testResults['extracted_text'], 0, 200)) . "...<br>";
    
    // Test cache stats
    $cacheStats = $parser->getCacheStats();
    echo "SP Cache pages: " . $cacheStats['total_pages'] . "<br>";
    echo "SP Cache size: " . $cacheStats['total_size'] . "<br>";
    
} catch (Exception $e) {
    echo "✗ Parser test error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test integrated search
echo "<h2>Integrated Search Test:</h2>";
try {
    require_once $componentPath . '/Models/SearchModel.php';
    
    $config = [];
    $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
    
    echo "✓ Search model created<br>";
    
    // Test search
    $testQuery = 'test';
    $results = $searchModel->search($testQuery, ['limit' => 5]);
    
    if ($results['success']) {
        echo "✓ Integrated search successful<br>";
        echo "Query: '{$testQuery}'<br>";
        echo "Found: {$results['total']} total results<br>";
        echo "Returned: " . count($results['results']) . " results<br>";
        echo "Search time: {$results['search_time']} seconds<br>";
        echo "Cached: " . ($results['cached'] ? 'Yes' : 'No') . "<br>";
        echo "Types searched: " . implode(', ', $results['types_searched'] ?? []) . "<br>";
        
        // Count by type
        $typeCounts = [];
        foreach ($results['results'] as $result) {
            $type = $result['type'] ?? 'unknown';
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }
        echo "Results by type: ";
        foreach ($typeCounts as $type => $count) {
            echo "{$type}: {$count} ";
        }
        echo "<br>";
        
        if (!empty($results['results'])) {
            echo "<h3>Sample Results:</h3>";
            foreach ($results['results'] as $index => $result) {
                echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
                echo "<strong>#" . ($index + 1) . " [" . ($result['type'] ?? 'unknown') . "]:</strong> {$result['title']}<br>";
                echo "Relevance: {$result['relevance']}<br>";
                echo "URL: {$result['url']}<br>";
                echo "Snippet: " . substr(strip_tags($result['snippet']), 0, 100) . "...<br>";
                echo "</div>";
            }
        }
    } else {
        echo "✗ Search failed: " . ($results['error'] ?? 'Unknown error') . "<br>";
    }
    
    // Test search by type
    echo "<h3>Type-Specific Search:</h3>";
    
    // Articles only
    $articleResults = $searchModel->searchArticles($testQuery, ['limit' => 2]);
    echo "Articles only: " . count($articleResults['results']) . " results<br>";
    
    // SP pages only (if SP Page Builder exists)
    if ($db->tableExists($spTable)) {
        $spResults = $searchModel->searchSPPages($testQuery, ['limit' => 2]);
        echo "SP pages only: " . count($spResults['results']) . " results<br>";
    }
    
    // Run comprehensive test
    echo "<h3>Comprehensive Test:</h3>";
    $testResults = $searchModel->testSearch($testQuery);
    echo "<pre>" . print_r($testResults, true) . "</pre>";
    
} catch (Exception $e) {
    echo "✗ Integrated search error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test AJAX endpoint
echo "<h2>AJAX Endpoint Test:</h2>";
$baseUrl = JUri::base();
echo "Test URLs:<br>";
echo "1. <a href='{$baseUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3' target='_blank'>All content</a><br>";
echo "2. <a href='{$baseUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&type=article' target='_blank'>Articles only</a><br>";
if ($db->tableExists($spTable)) {
    echo "3. <a href='{$baseUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&type=sp' target='_blank'>SP pages only</a><br>";
}
echo "4. <a href='{$baseUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&debug=1' target='_blank'>With debug info</a><br>";

// Cleanup recommendation
echo "<h2>Next Steps:</h2>";
echo "1. Test the AJAX endpoint URLs above<br>";
echo "2. Verify SP Page Builder content is being searched<br>";
echo "3. Check that results from articles and SP pages are mixed correctly<br>";
echo "4. If everything works, delete this test file<br>";
echo "5. Proceed to Session 1.4<br>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";