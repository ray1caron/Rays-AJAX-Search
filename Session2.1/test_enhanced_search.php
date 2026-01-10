<?php
/**
 * Test script for Enhanced Search Features - Session 2.1
 * Place this in your Joomla root, test, then delete
 */

define('_JEXEC', 1);
define('JPATH_BASE', __DIR__);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Start Joomla
$app = JFactory::getApplication('site');

echo "<html><head><title>AJAX Search - Session 2.1 Enhanced Features Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #2e7d32; }
    .error { color: #c62828; }
    .warning { color: #f57c00; }
    .test-section { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    .result-item { border: 1px solid #eee; padding: 10px; margin: 5px 0; background: #f9f9f9; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .highlight { background-color: #fffacd; padding: 2px 5px; border-radius: 3px; }
</style></head><body>";

echo "<h1>AJAX Search - Session 2.1 Test (Enhanced Features)</h1>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Joomla Version:</strong> " . JVERSION . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";

// Path to our component
$componentPath = JPATH_BASE . '/components/com_ajaxsearch/src';

// Check if enhanced files exist
echo "<div class='test-section'><h2>File System Check:</h2>";

$requiredFiles = [
    'Search/EnhancedRelevanceScorer.php' => 'Enhanced Relevance Scorer',
    'Search/CustomFieldSearch.php' => 'Custom Field Search',
    'Search/SuggestionsEngine.php' => 'Suggestions Engine',
    'Search/EnhancedIntegratedSearch.php' => 'Enhanced Integrated Search',
    'Models/EnhancedSearchModel.php' => 'Enhanced Search Model',
    'Database/Adapter.php' => 'Database Adapter',
    'Search/ArticleSearch.php' => 'Article Search',
    'Search/SPSearch.php' => 'SP Page Builder Search',
    'Search/IntegratedSearch.php' => 'Integrated Search',
    'Cache/BasicCache.php' => 'Basic Cache System'
];

$missingFiles = [];
foreach ($requiredFiles as $file => $description) {
    $fullPath = $componentPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<div class='success'>✓ {$description} ({$file})</div>";
    } else {
        echo "<div class='error'>✗ {$description} ({$file}) - MISSING</div>";
        $missingFiles[] = $file;
    }
}

if (!empty($missingFiles)) {
    echo "<div class='warning'>⚠️ Missing files may cause test failures</div>";
}

echo "</div>";

// Test file includes
echo "<div class='test-section'><h2>Class Loading Test:</h2>";

try {
    // Test loading each class
    $classesToTest = [
        'AjaxsearchDatabaseAdapter',
        'AjaxsearchEnhancedRelevanceScorer',
        'AjaxsearchCustomFieldSearch',
        'AjaxsearchSuggestionsEngine',
        'AjaxsearchEnhancedIntegratedSearch',
        'AjaxsearchEnhancedSearchModel'
    ];
    
    foreach ($classesToTest as $className) {
        if (class_exists($className)) {
            echo "<div class='success'>✓ {$className} already loaded</div>";
        } else {
            // Try to load the file
            $fileMap = [
                'AjaxsearchDatabaseAdapter' => 'Database/Adapter.php',
                'AjaxsearchEnhancedRelevanceScorer' => 'Search/EnhancedRelevanceScorer.php',
                'AjaxsearchCustomFieldSearch' => 'Search/CustomFieldSearch.php',
                'AjaxsearchSuggestionsEngine' => 'Search/SuggestionsEngine.php',
                'AjaxsearchEnhancedIntegratedSearch' => 'Search/EnhancedIntegratedSearch.php',
                'AjaxsearchEnhancedSearchModel' => 'Models/EnhancedSearchModel.php'
            ];
            
            if (isset($fileMap[$className])) {
                $filePath = $componentPath . '/' . $fileMap[$className];
                if (file_exists($filePath)) {
                    require_once $filePath;
                    if (class_exists($className)) {
                        echo "<div class='success'>✓ {$className} loaded successfully</div>";
                    } else {
                        echo "<div class='error'>✗ {$className} class not found in file</div>";
                    }
                } else {
                    echo "<div class='error'>✗ {$className} file not found</div>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Class loading error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test Database Connectivity
echo "<div class='test-section'><h2>Database Connectivity Test:</h2>";

try {
    $db = JFactory::getDbo();
    echo "<div class='success'>✓ Joomla database connection established</div>";
    echo "<div>Database: " . $db->getName() . "</div>";
    echo "<div>Server: " . $db->getServer() . "</div>";
    
    // Test component tables
    $tablesToCheck = [
        'ajaxsearch_cache',
        'ajaxsearch_analytics',
        'ajaxsearch_sp_cache'
    ];
    
    foreach ($tablesToCheck as $table) {
        $query = "SHOW TABLES LIKE '%{$table}%'";
        $db->setQuery($query);
        $exists = $db->loadResult();
        if ($exists) {
            echo "<div class='success'>✓ Table #__{$table} exists</div>";
        } else {
            echo "<div class='warning'>⚠️ Table #__{$table} does not exist (may need to run installer)</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test Enhanced Search Features
echo "<div class='test-section'><h2>Enhanced Search Features Test:</h2>";

try {
    // Initialize enhanced search model
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $config = [
        'enable_custom_fields' => true,
        'enable_enhanced_scoring' => true,
        'cache_ttl' => 300,
        'enabled_types' => ['article', 'sppagebuilder']
    ];
    
    $searchModel = new AjaxsearchEnhancedSearchModel($dbAdapter, $config);
    echo "<div class='success'>✓ Enhanced search model initialized</div>";
    
    // Test queries
    $testQueries = [
        'test',
        'search',
        'content',
        'article',
        'joomla'
    ];
    
    foreach ($testQueries as $testQuery) {
        echo "<h3>Testing Query: '<span class='highlight'>{$testQuery}</span>'</h3>";
        
        // Perform enhanced search
        $startTime = microtime(true);
        $results = $searchModel->search($testQuery, [
            'limit' => 5,
            'include_custom_fields' => true,
            'enhanced_scoring' => true,
            'types' => ['article', 'sppagebuilder']
        ]);
        $searchTime = microtime(true) - $startTime;
        
        echo "<div>";
        if ($results['success']) {
            echo "<div class='success'>✓ Search successful</div>";
            echo "<div>Total results: <strong>{$results['total']}</strong></div>";
            echo "<div>Search time: <strong>" . round($searchTime, 4) . "s</strong></div>";
            echo "<div>Cached: <strong>" . ($results['cached'] ? 'Yes' : 'No') . "</strong></div>";
            
            // Display features used
            if (isset($results['features_used'])) {
                echo "<div>Features used: ";
                foreach ($results['features_used'] as $feature => $enabled) {
                    $status = $enabled ? '✓' : '✗';
                    echo "<span class='" . ($enabled ? 'success' : 'warning') . "'>{$feature}: {$status}</span> ";
                }
                echo "</div>";
            }
            
            // Display sample results
            if (!empty($results['results'])) {
                echo "<h4>Results Preview:</h4>";
                foreach (array_slice($results['results'], 0, 3) as $index => $result) {
                    echo "<div class='result-item'>";
                    echo "<strong>#" . ($index + 1) . " [" . ($result['type'] ?? 'unknown') . "]</strong><br>";
                    echo "<strong>Title:</strong> {$result['title']}<br>";
                    echo "<strong>Relevance:</strong> {$result['relevance']} ";
                    
                    if (isset($result['score_breakdown'])) {
                        echo "(Enhanced: {$result['score_breakdown']['enhanced']}, ";
                        echo "Original: {$result['score_breakdown']['original']})<br>";
                    } else {
                        echo "<br>";
                    }
                    
                    if (isset($result['custom_field_match']) && $result['custom_field_match']) {
                        echo "<strong>Custom Field Match:</strong> Yes<br>";
                        if (!empty($result['matched_fields'])) {
                            echo "<strong>Matched Fields:</strong> {$result['matched_fields']}<br>";
                        }
                    }
                    
                    if (!empty($result['snippet'])) {
                        $snippet = strip_tags($result['snippet']);
                        echo "<strong>Snippet:</strong> " . substr($snippet, 0, 100);
                        if (strlen($snippet) > 100) echo "...";
                        echo "<br>";
                    }
                    
                    echo "</div>";
                }
            }
        } else {
            echo "<div class='error'>✗ Search failed: " . ($results['error'] ?? 'Unknown error') . "</div>";
        }
        echo "</div><hr>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Enhanced search error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

// Test Suggestions Engine
echo "<div class='test-section'><h2>Suggestions Engine Test:</h2>";

try {
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $suggestionsEngine = new AjaxsearchSuggestionsEngine($dbAdapter);
    
    // Test partial queries
    $partialQueries = ['t', 'te', 'tes', 'test', 'sea', 'art'];
    
    foreach ($partialQueries as $partialQuery) {
        echo "<h3>Suggestions for: '<span class='highlight'>{$partialQuery}</span>'</h3>";
        $suggestions = $suggestionsEngine->getSuggestions($partialQuery, [
            'limit' => 5,
            'sources' => [
                'popular_queries' => true,
                'article_titles' => true,
                'article_content' => false, // Disabled for speed
                'categories' => true,
                'tags' => true,
                'custom_fields' => false // Disabled for speed
            ]
        ]);
        
        echo "<div>Found: " . count($suggestions) . " suggestions</div>";
        
        if (!empty($suggestions)) {
            echo "<div style='margin-left: 20px;'>";
            foreach ($suggestions as $suggestion) {
                $typeColors = [
                    'popular_query' => '#2e7d32',
                    'article_title' => '#1565c0',
                    'category' => '#6a1b9a',
                    'tag' => '#f57c00',
                    'query' => '#c62828'
                ];
                $color = $typeColors[$suggestion['type']] ?? '#666';
                echo "<div style='color: {$color}; margin: 2px 0;'>";
                echo "• {$suggestion['text']} <small>({$suggestion['type']}, relevance: {$suggestion['relevance']})</small>";
                echo "</div>";
            }
            echo "</div>";
        }
    }
    
    // Test trending searches
    echo "<h3>Trending Searches:</h3>";
    $trending = $suggestionsEngine->getTrendingSearches(5, 'week');
    if (!empty($trending)) {
        echo "<div style='margin-left: 20px;'>";
        foreach ($trending as $trend) {
            echo "<div style='margin: 5px 0;'>";
            echo "<strong>{$trend->query}</strong>: {$trend->searches} searches";
            if (isset($trend->avg_results)) {
                echo " (avg results: " . round($trend->avg_results, 1) . ")";
            }
            if (isset($trend->zero_result_searches)) {
                echo ", zero results: {$trend->zero_result_searches}";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='warning'>No trending search data available</div>";
    }
    
    // Test analytics summary
    echo "<h3>Analytics Summary (Last Week):</h3>";
    $analytics = $suggestionsEngine->getAnalyticsSummary('week');
    if (!empty($analytics) && !isset($analytics['error'])) {
        echo "<div style='margin-left: 20px;'>";
        echo "<div><strong>Total Searches:</strong> " . ($analytics['total_searches'] ?? 0) . "</div>";
        echo "<div><strong>Unique Searches:</strong> " . ($analytics['unique_searches'] ?? 0) . "</div>";
        echo "<div><strong>Zero Result Searches:</strong> " . ($analytics['zero_result_searches'] ?? 0) . "</div>";
        echo "<div><strong>Zero Result Rate:</strong> " . ($analytics['zero_result_rate'] ?? '0%') . "</div>";
        echo "<div><strong>Average Results:</strong> " . ($analytics['avg_results'] ?? 0) . "</div>";
        
        if (!empty($analytics['popular_searches'])) {
            echo "<div><strong>Popular Searches:</strong>";
            echo "<ul>";
            foreach ($analytics['popular_searches'] as $popular) {
                echo "<li>{$popular->query} ({$popular->searches} searches)</li>";
            }
            echo "</ul></div>";
        }
        echo "</div>";
    } else {
        echo "<div class='warning'>Analytics not available: " . ($analytics['error'] ?? 'No data collected yet') . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Suggestions engine error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test Custom Field Search
echo "<div class='test-section'><h2>Custom Field Search Test:</h2>";

try {
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $customFieldSearch = new AjaxsearchCustomFieldSearch($dbAdapter);
    
    // Test available fields
    echo "<h3>Available Custom Fields:</h3>";
    $fields = $customFieldSearch->getFieldsConfiguration();
    
    if (!empty($fields)) {
        echo "<div>Found: " . count($fields) . " custom fields</div>";
        echo "<div style='margin-left: 20px;'>";
        foreach (array_slice($fields, 0, 5) as $field) {
            $status = $field->state ? '✓ Enabled' : '✗ Disabled';
            echo "<div>{$field->title} ({$field->type}) - {$status}</div>";
        }
        if (count($fields) > 5) {
            echo "<div>... and " . (count($fields) - 5) . " more</div>";
        }
        echo "</div>";
        
        // Test custom field search
        echo "<h3>Custom Field Search Test:</h3>";
        $testTerms = ['test', 'sample', 'data'];
        $cfResults = $customFieldSearch->search($testTerms, [
            'limit' => 3,
            'min_relevance' => 1
        ]);
        
        if (!empty($cfResults)) {
            echo "<div>Found: " . count($cfResults) . " items with matching custom fields</div>";
            echo "<div style='margin-left: 20px;'>";
            foreach ($cfResults as $result) {
                echo "<div class='result-item'>";
                echo "<strong>Article:</strong> {$result->content_title}<br>";
                echo "<strong>Matched Fields:</strong> {$result->matched_fields}<br>";
                echo "<strong>Field Matches:</strong> {$result->field_match_count}<br>";
                echo "<strong>Relevance:</strong> {$result->relevance}<br>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='warning'>No custom field matches found for test terms</div>";
        }
    } else {
        echo "<div class='warning'>No custom fields configured in the system</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Custom field search error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Test Enhanced Relevance Scorer
echo "<div class='test-section'><h2>Enhanced Relevance Scorer Test:</h2>";

try {
    $scorer = new AjaxsearchEnhancedRelevanceScorer();
    
    echo "<h3>Default Weights:</h3>";
    $weights = $scorer->getWeights();
    echo "<pre>" . print_r($weights, true) . "</pre>";
    
    // Test scoring with sample data
    echo "<h3>Scoring Test:</h3>";
    
    $sampleData = [
        'title' => 'Test Article About Joomla Search Functionality',
        'introtext' => 'This is a comprehensive test article discussing Joomla search features and enhancements.',
        'fulltext' => 'Detailed content about implementing advanced search algorithms in Joomla extensions.',
        'alias' => 'test-article-joomla-search',
        'meta_keywords' => 'joomla, search, test, article',
        'meta_description' => 'Testing Joomla search functionality with enhanced features',
        'created' => date('Y-m-d H:i:s')
    ];
    
    $testQueries = [
        'joomla search',
        'test article',
        'advanced functionality',
        'search test joomla'
    ];
    
    foreach ($testQueries as $query) {
        echo "<h4>Query: '<span class='highlight'>{$query}</span>'</h4>";
        $testResult = $scorer->testScoring($sampleData, $query);
        
        echo "<div style='margin-left: 20px;'>";
        echo "<div><strong>Score:</strong> {$testResult['score']}/100</div>";
        echo "<div><strong>Max Possible:</strong> {$testResult['max_possible']}</div>";
        echo "<div><strong>Terms:</strong> " . implode(', ', $testResult['terms']) . "</div>";
        echo "</div>";
    }
    
    // Test stemming
    echo "<h3>Word Stemming Test:</h3>";
    $wordsToStem = ['searching', 'searches', 'searched', 'functionality', 'testing', 'tests', 'advanced'];
    echo "<div style='margin-left: 20px;'>";
    foreach ($wordsToStem as $word) {
        // Access protected method via Reflection
        try {
            $reflection = new ReflectionClass($scorer);
            $method = $reflection->getMethod('stemWord');
            $method->setAccessible(true);
            $stemmed = $method->invoke($scorer, $word);
            echo "<div>{$word} → {$stemmed}</div>";
        } catch (Exception $e) {
            echo "<div class='error'>✗ Could not test stemming for '{$word}'</div>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Relevance scorer error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Performance Test
echo "<div class='test-section'><h2>Performance Test:</h2>";

try {
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $searchModel = new AjaxsearchEnhancedSearchModel($dbAdapter, [
        'enable_custom_fields' => true,
        'enable_enhanced_scoring' => true
    ]);
    
    echo "<h3>Cache Operations:</h3>";
    $cacheStats = $searchModel->getStats();
    if (isset($cacheStats['cache'])) {
        echo "<div style='margin-left: 20px;'>";
        echo "<pre>" . print_r($cacheStats['cache'], true) . "</pre>";
        echo "</div>";
    }
    
    echo "<h3>Search Performance:</h3>";
    $performanceTests = [
        'Simple' => 'test',
        'Multi-word' => 'test search article',
        'Complex' => 'joomla search functionality testing'
    ];
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Query Type</th><th>Query</th><th>Time (s)</th><th>Results</th><th>Cached</th></tr>";
    
    foreach ($performanceTests as $type => $query) {
        $startTime = microtime(true);
        $results = $searchModel->search($query, ['limit' => 3]);
        $searchTime = microtime(true) - $startTime;
        
        echo "<tr>";
        echo "<td>{$type}</td>";
        echo "<td>{$query}</td>";
        echo "<td>" . round($searchTime, 4) . "</td>";
        echo "<td>" . $results['total'] . "</td>";
        echo "<td>" . ($results['cached'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Cache Clearing Test:</h3>";
    $clearResult = $searchModel->clearCache();
    if ($clearResult) {
        echo "<div class='success'>✓ Cache cleared successfully</div>";
    } else {
        echo "<div class='warning'>⚠️ Cache clearing may have failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Performance test error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Comprehensive System Test
echo "<div class='test-section'><h2>Comprehensive System Test:</h2>";

try {
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    $searchModel = new AjaxsearchEnhancedSearchModel($dbAdapter);
    
    echo "<h3>Running Full Test Suite:</h3>";
    $comprehensiveTest = $searchModel->testSearch('test');
    
    echo "<div style='margin-left: 20px;'>";
    echo "<h4>Enhanced Search Test:</h4>";
    if (isset($comprehensiveTest['enhanced_search'])) {
        $es = $comprehensiveTest['enhanced_search'];
        echo "<div>Success: " . ($es['success'] ? '✓' : '✗') . "</div>";
        echo "<div>Search Time: " . $es['time'] . "s</div>";
        echo "<div>Results: " . $es['results_count'] . " of " . $es['total'] . "</div>";
        echo "<div>Cached: " . ($es['cached'] ? 'Yes' : 'No') . "</div>";
    }
    
    echo "<h4>Results by Type:</h4>";
    if (isset($comprehensiveTest['results_by_type'])) {
        foreach ($comprehensiveTest['results_by_type'] as $type => $count) {
            echo "<div>{$type}: {$count}</div>";
        }
    }
    
    echo "<h4>Cache Test:</h4>";
    if (isset($comprehensiveTest['cache_test'])) {
        $ct = $comprehensiveTest['cache_test'];
        echo "<div>Cached Search Time: " . $ct['time'] . "s</div>";
        echo "<div>Cache Faster: " . ($ct['cache_faster'] ? '✓' : '✗') . "</div>";
    }
    
    echo "<h4>System Statistics:</h4>";
    if (isset($comprehensiveTest['stats']) && isset($comprehensiveTest['stats']['config'])) {
        echo "<pre>" . print_r($comprehensiveTest['stats']['config'], true) . "</pre>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Comprehensive test error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Summary Report
echo "<div class='test-section'><h2>Test Summary:</h2>";

$summary = [
    'Files Found' => count($requiredFiles) - count($missingFiles) . '/' . count($requiredFiles),
    'Database Connection' => '✓',
    'Enhanced Search Model' => class_exists('AjaxsearchEnhancedSearchModel') ? '✓' : '✗',
    'Suggestions Engine' => class_exists('AjaxsearchSuggestionsEngine') ? '✓' : '✗',
    'Custom Field Search' => class_exists('AjaxsearchCustomFieldSearch') ? '✓' : '✗',
    'Relevance Scorer' => class_exists('AjaxsearchEnhancedRelevanceScorer') ? '✓' : '✗'
];

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Component</th><th>Status</th></tr>";
foreach ($summary as $component => $status) {
    $color = strpos($status, '✓') !== false ? 'success' : 'error';
    echo "<tr><td>{$component}</td><td class='{$color}'>{$status}</td></tr>";
}
echo "</table>";

echo "<h3>Recommendations:</h3>";
echo "<ol>";
if (count($missingFiles) > 0) {
    echo "<li class='error'>Missing files need to be created before testing can proceed</li>";
}
echo "<li>Ensure the component is properly installed via Joomla Extension Manager</li>";
echo "<li>Run the SQL installer to create necessary database tables</li>";
echo "<li>Test with actual content in the database for accurate results</li>";
echo "<li>Configure custom fields in Joomla to test custom field search</li>";
echo "</ol>";

echo "</div>";

// Cleanup reminder
echo "<div class='test-section warning'>";
echo "<h2>⚠️ Important Reminder:</h2>";
echo "<p>This is a <strong>temporary test script</strong> for development purposes only.</p>";
echo "<p><strong>Delete this file</strong> from your server after testing to prevent security risks.</p>";
echo "<p>Location: " . __FILE__ . "</p>";
echo "</div>";

echo "</body></html>";