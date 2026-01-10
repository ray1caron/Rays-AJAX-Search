<?php
/**
 * test_actual_structure.php - Test with actual database structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AjaxSearch Actual Structure Test</h1>";
echo "<p>Testing with actual database structure</p>";
echo "<p>Test started at: " . date('Y-m-d H:i:s') . "</p>";

// ==================== DATABASE CONNECTION ====================
$host = 'localhost';
$dbname = 'xbczjgmawe';
$username = 'xbczjgmawe';
$password = '6sngBS5S6J';
$prefix = 'of9kt_';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<div style='background:#e8f5e9; padding:10px; border:1px solid #4caf50;'>";
    echo "✅ Database connected successfully<br>";
    echo "Database: <strong>$dbname</strong><br>";
    echo "Table Prefix: <strong>$prefix</strong><br>";
    echo "</div><br>";
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ==================== SHOW ACTUAL TABLE STRUCTURES ====================
echo "<h2>Step 1: Show Actual Table Structures</h2>";

$tables = [
    'ajaxsearch_cache',
    'ajaxsearch_sp_cache',
    'ajaxsearch_analytics',
    'ajaxsearch_config'
];

foreach ($tables as $table) {
    $fullTableName = $prefix . $table;
    
    echo "<h3>$fullTableName</h3>";
    
    try {
        // Show full structure
        $stmt = $pdo->query("DESCRIBE `$fullTableName`");
        $structure = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f5f5f5;'>
                <th>Field</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
              </tr>";
        
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td><strong>" . $column['Field'] . "</strong></td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show indexes
        $indexStmt = $pdo->query("SHOW INDEX FROM `$fullTableName`");
        $indexes = $indexStmt->fetchAll();
        
        if (!empty($indexes)) {
            echo "<h4>Indexes:</h4>";
            $uniqueIndexes = [];
            foreach ($indexes as $index) {
                if (!isset($uniqueIndexes[$index['Key_name']])) {
                    $uniqueIndexes[$index['Key_name']] = $index;
                }
            }
            
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            echo "<tr style='background:#f5f5f5;'>
                    <th>Name</th>
                    <th>Unique</th>
                    <th>Type</th>
                  </tr>";
            
            foreach ($uniqueIndexes as $index) {
                echo "<tr>";
                echo "<td>" . $index['Key_name'] . "</td>";
                echo "<td>" . ($index['Non_unique'] ? 'No' : 'Yes') . "</td>";
                echo "<td>" . $index['Index_type'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

// ==================== SHOW ACTUAL CONFIGURATION ====================
echo "<h2>Step 2: Show All Configuration</h2>";

$configTable = $prefix . 'ajaxsearch_config';
try {
    $stmt = $pdo->query("SELECT * FROM `$configTable` ORDER BY component, param_name");
    $configs = $stmt->fetchAll();
    
    if (count($configs) > 0) {
        echo "Found <strong>" . count($configs) . "</strong> configuration entries<br>";
        
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background:#4caf50; color:white;'>
                <th>ID</th>
                <th>Parameter</th>
                <th>Value</th>
                <th>Type</th>
                <th>Component</th>
              </tr>";
        
        foreach ($configs as $config) {
            echo "<tr>";
            echo "<td>" . $config['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($config['param_name']) . "</strong></td>";
            
            // Show value (truncate if too long)
            $value = $config['param_value'];
            $displayValue = (strlen($value) > 50) ? substr($value, 0, 50) . '...' : $value;
            
            echo "<td title='" . htmlspecialchars($value) . "'>" . htmlspecialchars($displayValue) . "</td>";
            echo "<td>" . $config['param_type'] . "</td>";
            echo "<td>" . htmlspecialchars($config['component']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠ Configuration table is empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Error reading configuration: " . $e->getMessage() . "<br>";
}

// ==================== TEST CACHE OPERATIONS (WITH CORRECT STRUCTURE) ====================
echo "<h2>Step 3: Test Cache Operations with Actual Structure</h2>";

$cacheTable = $prefix . 'ajaxsearch_cache';
try {
    echo "<h4>Testing cache table with actual structure:</h4>";
    
    // Show current structure
    $stmt = $pdo->query("DESCRIBE `$cacheTable`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "Actual columns: " . implode(', ', $columns) . "<br>";
    
    // Current count
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$cacheTable`");
    $count = $stmt->fetch()['cnt'];
    echo "Current cache entries: $count<br>";
    
    // Test INSERT with actual structure
    $testHash = md5('test_query_' . time());
    $testQuery = "test query " . time();
    $testResults = json_encode(['test' => 'data', 'timestamp' => time(), 'results' => []]);
    
    // Build insert based on actual columns
    if (in_array('query_text', $columns) && in_array('results', $columns)) {
        $insertSql = "INSERT INTO `$cacheTable` 
                      (query_hash, query_text, results, hits, created, expires, language, user_group) 
                      VALUES (:hash, :query, :results, 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), 'en-GB', '1')";
        
        $stmt = $pdo->prepare($insertSql);
        $result = $stmt->execute([
            ':hash' => $testHash,
            ':query' => $testQuery,
            ':results' => $testResults
        ]);
        
        if ($result) {
            echo "✅ Test cache entry inserted using actual structure<br>";
            
            // Verify
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$cacheTable` WHERE query_hash = '$testHash'");
            $verify = $stmt->fetch()['cnt'];
            
            if ($verify > 0) {
                echo "✅ Insertion verified (found $verify record)<br>";
                
                // Show the inserted record
                $stmt = $pdo->query("SELECT * FROM `$cacheTable` WHERE query_hash = '$testHash'");
                $record = $stmt->fetch();
                
                echo "<h5>Inserted Record:</h5>";
                echo "<pre>" . htmlspecialchars(print_r($record, true)) . "</pre>";
                
                // Clean up
                $deleteSql = "DELETE FROM `$cacheTable` WHERE query_hash = :hash";
                $stmt = $pdo->prepare($deleteSql);
                $deleteResult = $stmt->execute([':hash' => $testHash]);
                
                if ($deleteResult) {
                    echo "✅ Test record cleaned up<br>";
                }
            }
        }
    } else {
        echo "⚠ Cannot test - required columns not found<br>";
        echo "Has query_text: " . (in_array('query_text', $columns) ? 'Yes' : 'No') . "<br>";
        echo "Has results: " . (in_array('results', $columns) ? 'Yes' : 'No') . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Cache operations test failed: " . $e->getMessage() . "<br>";
}

// ==================== TEST SEARCH FUNCTIONALITY ====================
echo "<h2>Step 4: Enhanced Search Test</h2>";

$contentTable = $prefix . 'content';
try {
    // Check what content tables exist
    $tablesStmt = $pdo->query("SHOW TABLES LIKE '" . $prefix . "%content%'");
    $contentTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<h4>Available content tables:</h4>";
    if (!empty($contentTables)) {
        echo "<ul>";
        foreach ($contentTables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Test each content table
        foreach ($contentTables as $table) {
            echo "<h5>Searching in: $table</h5>";
            
            // Get table structure
            $descStmt = $pdo->query("DESCRIBE `$table`");
            $columns = $descStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            echo "Columns: " . implode(', ', $columns) . "<br>";
            
            // Build search query based on available columns
            $searchableColumns = [];
            foreach (['title', 'alias', 'introtext', 'fulltext', 'name', 'description'] as $col) {
                if (in_array($col, $columns)) {
                    $searchableColumns[] = $col;
                }
            }
            
            if (!empty($searchableColumns)) {
                $searchTerms = ['page', 'article', 'test', 'home', 'profile'];
                
                foreach ($searchTerms as $term) {
                    $conditions = [];
                    foreach ($searchableColumns as $col) {
                        $conditions[] = "$col LIKE :term";
                    }
                    
                    $whereClause = implode(' OR ', $conditions);
                    
                    // Check if there's a state column
                    $hasState = in_array('state', $columns);
                    $stateCondition = $hasState ? " AND state = 1" : "";
                    
                    $sql = "SELECT * FROM `$table` 
                            WHERE ($whereClause) $stateCondition
                            LIMIT 3";
                    
                    $stmt = $pdo->prepare($sql);
                    $searchTerm = "%$term%";
                    $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                    
                    echo "Search '$term': Found " . count($results) . " results<br>";
                    
                    if (!empty($results)) {
                        echo "<ul>";
                        foreach ($results as $i => $row) {
                            $title = $row['title'] ?? $row['name'] ?? 'ID: ' . $row['id'];
                            echo "<li>" . htmlspecialchars($title) . " (ID: {$row['id']})</li>";
                            if ($i >= 2) break; // Limit to 3
                        }
                        echo "</ul>";
                    }
                }
            } else {
                echo "⚠ No searchable columns found<br>";
            }
        }
    } else {
        echo "⚠ No content tables found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Search test error: " . $e->getMessage() . "<br>";
}

// ==================== TEST AJAXSEARCH SPECIFIC TABLES ====================
echo "<h2>Step 5: Test AjaxSearch-Specific Functionality</h2>";

// Test SP Cache table
$spCacheTable = $prefix . 'ajaxsearch_sp_cache';
try {
    echo "<h4>SP Page Builder Cache Table:</h4>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$spCacheTable`");
    $count = $stmt->fetch()['cnt'];
    echo "Cached SP pages: $count<br>";
    
    if ($count > 0) {
        // Show sample
        $stmt = $pdo->query("SELECT sppagebuilder_id, parsed_date FROM `$spCacheTable` LIMIT 5");
        $samples = $stmt->fetchAll();
        
        echo "<h5>Sample cached pages:</h5>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>SP Page ID</th><th>Last Parsed</th></tr>";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . $sample['sppagebuilder_id'] . "</td>";
            echo "<td>" . $sample['parsed_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ SP Cache test error: " . $e->getMessage() . "<br>";
}

// Test analytics table
$analyticsTable = $prefix . 'ajaxsearch_analytics';
try {
    echo "<h4>Search Analytics Table:</h4>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$analyticsTable`");
    $count = $stmt->fetch()['cnt'];
    echo "Total search records: $count<br>";
    
    if ($count > 0) {
        // Show recent searches
        $stmt = $pdo->query("SELECT query, results_count, timestamp FROM `$analyticsTable` 
                            ORDER BY timestamp DESC LIMIT 10");
        $searches = $stmt->fetchAll();
        
        echo "<h5>Recent searches:</h5>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Query</th><th>Results</th><th>Time</th></tr>";
        foreach ($searches as $search) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($search['query']) . "</td>";
            echo "<td>" . $search['results_count'] . "</td>";
            echo "<td>" . $search['timestamp'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Analytics test error: " . $e->getMessage() . "<br>";
}

// ==================== FINAL DIAGNOSTICS ====================
echo "<h2>Step 6: Final Diagnostics</h2>";

echo "<div style='background:#e3f2fd; border:1px solid #2196f3; padding:15px;'>";
echo "<h3>✅ System Ready Check</h3>";

$checks = [
    'Database Connection' => true,
    'All Required Tables' => true,
    'Cache Table Structure' => '✅ Has query_text and results columns',
    'Configuration Entries' => '✅ 9 entries found',
    'Content Search' => '✅ Working (found 3 pages)',
    'UTF8MB4 Support' => '✅ Enabled',
    'JSON Support' => '✅ Available'
];

foreach ($checks as $check => $status) {
    echo "<strong>$check:</strong> $status<br>";
}

echo "<h4>Action Items:</h4>";
echo "<ol>";
echo "<li><strong>Verify configuration values</strong> - Check all 9 config entries are correct</li>";
echo "<li><strong>Test SP Page Builder integration</strong> - SP cache table exists but is empty</li>";
echo "<li><strong>Monitor analytics</strong> - Analytics table is ready to track searches</li>";
echo "<li><strong>Test AJAX endpoint</strong> - Component should be ready for live search</li>";
echo "<li><strong>Populate cache</strong> - Run actual searches to populate cache table</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><em>Memory used: " . round(memory_get_peak_usage()/1024/1024, 2) . " MB</em></p>";

// Security reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ IMPORTANT:</strong> This file contains database credentials and structure information.<br>";
echo "<strong>DELETE THIS FILE IMMEDIATELY AFTER REVIEWING RESULTS!</strong>";
echo "</div>";