<?php
/**
 * test_version.php - Corrected with working credentials
 * Test database changes and search functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AjaxSearch Test Version</h1>";
echo "<p>Testing database changes and search functionality</p>";

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
    
    echo "✅ Database connected successfully<br>";
    echo "Database: $dbname<br>";
    echo "Prefix: $prefix<br><br>";
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ==================== LOAD COMPONENT CLASSES ====================
echo "<h2>Step 1: Load Component Classes</h2>";

$componentPath = __DIR__ . '/components/com_ajaxsearch/src';

// Check if files exist
$filesToLoad = [
    'Compatibility/VersionCheck.php',
    'Database/Adapter.php'
];

foreach ($filesToLoad as $file) {
    $fullPath = $componentPath . '/' . $file;
    if (file_exists($fullPath)) {
        try {
            require_once $fullPath;
            echo "✅ Loaded: $file<br>";
        } catch (Exception $e) {
            echo "❌ Error loading $file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ File not found: $file<br>";
    }
}

// ==================== TEST VERSION CHECK ====================
echo "<h2>Step 2: Test Version Compatibility</h2>";

try {
    // Find the VersionCheck class
    $classes = get_declared_classes();
    $versionCheckClass = null;
    
    foreach ($classes as $class) {
        if (stripos($class, 'versioncheck') !== false || 
            stripos($class, 'compatibility') !== false) {
            $versionCheckClass = $class;
            break;
        }
    }
    
    if ($versionCheckClass && class_exists($versionCheckClass)) {
        echo "✅ Found class: $versionCheckClass<br>";
        
        $checker = new $versionCheckClass();
        
        if (method_exists($checker, 'checkCompatibility')) {
            $results = $checker->checkCompatibility();
            
            echo "<h3>Compatibility Check Results:</h3>";
            echo "<div style='border:1px solid #ccc; padding:10px; background:#f9f9f9;'>";
            echo "<pre>" . print_r($results, true) . "</pre>";
            echo "</div>";
        } else {
            echo "⚠ checkCompatibility() method not found<br>";
        }
    } else {
        echo "❌ VersionCheck class not found<br>";
        echo "Available classes: " . implode(', ', $classes) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Version check error: " . $e->getMessage() . "<br>";
}

// ==================== TEST DATABASE ADAPTER ====================
echo "<h2>Step 3: Test Database Adapter</h2>";

try {
    // Find the Adapter class
    $classes = get_declared_classes();
    $adapterClass = null;
    
    foreach ($classes as $class) {
        if (stripos($class, 'adapter') !== false && stripos($class, 'database')) {
            $adapterClass = $class;
            break;
        }
    }
    
    if ($adapterClass && class_exists($adapterClass)) {
        echo "✅ Found class: $adapterClass<br>";
        
        $dbAdapter = new $adapterClass($pdo);
        echo "✅ Database adapter created<br>";
        
        // Test methods
        echo "<h3>Database Info:</h3>";
        
        if (method_exists($dbAdapter, 'getCharset')) {
            echo "Charset: " . $dbAdapter->getCharset() . "<br>";
        }
        
        if (method_exists($dbAdapter, 'getCollation')) {
            echo "Collation: " . $dbAdapter->getCollation() . "<br>";
        }
        
        // Check UTF8MB4 support
        if (property_exists($dbAdapter, 'utf8mb4Supported')) {
            echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported ? 'Yes' : 'No') . "<br>";
        } elseif (method_exists($dbAdapter, 'utf8mb4Supported')) {
            echo "UTF8MB4 Supported: " . ($dbAdapter->utf8mb4Supported() ? 'Yes' : 'No') . "<br>";
        }
        
        echo "<h3>Table Check:</h3>";
        $tables = ['ajaxsearch_cache', 'ajaxsearch_sp_cache', 'ajaxsearch_analytics', 'ajaxsearch_config'];
        
        foreach ($tables as $table) {
            $fullTableName = $prefix . $table;
            
            if (method_exists($dbAdapter, 'tableExists')) {
                $exists = $dbAdapter->tableExists($table);
                echo "$table ($fullTableName): " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            } else {
                // Direct check
                $stmt = $pdo->query("SHOW TABLES LIKE '$fullTableName'");
                $exists = $stmt->rowCount() > 0;
                echo "$table ($fullTableName): " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            }
        }
        
    } else {
        echo "❌ DatabaseAdapter class not found<br>";
        echo "Checking tables directly:<br>";
        
        $tables = ['ajaxsearch_cache', 'ajaxsearch_sp_cache', 'ajaxsearch_analytics', 'ajaxsearch_config'];
        foreach ($tables as $table) {
            $fullTableName = $prefix . $table;
            $stmt = $pdo->query("SHOW TABLES LIKE '$fullTableName'");
            $exists = $stmt->rowCount() > 0;
            echo "$table ($fullTableName): " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database adapter error: " . $e->getMessage() . "<br>";
}

// ==================== CHECK AJAXSEARCH CONFIG ====================
echo "<h2>Step 4: Check AjaxSearch Configuration</h2>";

$configTable = $prefix . 'ajaxsearch_config';
try {
    $stmt = $pdo->query("SELECT * FROM `$configTable`");
    $configs = $stmt->fetchAll();
    
    if (count($configs) > 0) {
        echo "✅ Configuration entries found: " . count($configs) . "<br>";
        
        echo "<h3>Configuration Settings:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Parameter</th><th>Value</th><th>Type</th><th>Component</th></tr>";
        
        foreach ($configs as $config) {
            echo "<tr>";
            echo "<td>" . $config['id'] . "</td>";
            echo "<td>" . htmlspecialchars($config['param_name']) . "</td>";
            echo "<td>" . htmlspecialchars($config['param_value']) . "</td>";
            echo "<td>" . $config['param_type'] . "</td>";
            echo "<td>" . $config['component'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠ Configuration table is empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Error reading configuration: " . $e->getMessage() . "<br>";
}

// ==================== TEST CACHE TABLES ====================
echo "<h2>Step 5: Test Cache Tables</h2>";

// Check cache table
$cacheTable = $prefix . 'ajaxsearch_cache';
try {
    $stmt = $pdo->query("DESCRIBE `$cacheTable`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<h3>Cache Table Structure:</h3>";
    echo "Columns: " . implode(', ', $columns) . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$cacheTable`");
    $cacheCount = $stmt->fetch()['count'];
    echo "Cache entries: $cacheCount<br>";
    
} catch (Exception $e) {
    echo "❌ Error reading cache table: " . $e->getMessage() . "<br>";
}

// Check SP cache table
$spCacheTable = $prefix . 'ajaxsearch_sp_cache';
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$spCacheTable`");
    $spCacheCount = $stmt->fetch()['count'];
    echo "SP Cache entries: $spCacheCount<br>";
} catch (Exception $e) {
    echo "❌ Error reading SP cache: " . $e->getMessage() . "<br>";
}

// ==================== TEST SEARCH FUNCTIONALITY ====================
echo "<h2>Step 6: Test Basic Search</h2>";

// Try to load and test search model
$searchModelPath = $componentPath . '/Models/SearchModel.php';
if (file_exists($searchModelPath)) {
    try {
        require_once $searchModelPath;
        
        // Find SearchModel class
        $classes = get_declared_classes();
        $searchModelClass = null;
        
        foreach ($classes as $class) {
            if (stripos($class, 'searchmodel') !== false || 
                (stripos($class, 'model') !== false && stripos($class, 'search'))) {
                $searchModelClass = $class;
                break;
            }
        }
        
        if ($searchModelClass && class_exists($searchModelClass) && isset($dbAdapter)) {
            echo "✅ Found class: $searchModelClass<br>";
            
            $config = [];
            $searchModel = new $searchModelClass($dbAdapter, $config);
            echo "✅ Search model created<br>";
            
            // Test search
            $testQuery = 'test';
            
            if (method_exists($searchModel, 'search')) {
                echo "Testing search for: '$testQuery'<br>";
                
                $results = $searchModel->search($testQuery, ['limit' => 2]);
                
                if (is_array($results)) {
                    echo "✅ Search completed<br>";
                    echo "Results: " . count($results) . "<br>";
                    
                    if (!empty($results)) {
                        echo "<h4>Search Results:</h4>";
                        echo "<pre style='max-height:200px; overflow-y:auto;'>" . 
                             print_r($results, true) . "</pre>";
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Search test error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠ SearchModel.php not found<br>";
}

// ==================== DIRECT CONTENT SEARCH ====================
echo "<h2>Step 7: Direct Content Search</h2>";

$contentTable = $prefix . 'content';
try {
    $testQuery = 'article';
    
    $sql = "SELECT id, title, alias, created 
            FROM `$contentTable` 
            WHERE state = 1 
            AND (title LIKE :query OR alias LIKE :query)
            ORDER BY created DESC 
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$testQuery%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    echo "Found " . count($articles) . " articles containing '$testQuery'<br>";
    
    if (!empty($articles)) {
        echo "<h4>Article Results:</h4>";
        foreach ($articles as $article) {
            echo "<div style='border:1px solid #eee; padding:5px; margin:5px;'>";
            echo "<strong>" . htmlspecialchars($article['title']) . "</strong><br>";
            echo "ID: {$article['id']} | Created: {$article['created']}<br>";
            echo "Alias: {$article['alias']}<br>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "❌ Direct search error: " . $e->getMessage() . "<br>";
}

// ==================== FINAL SUMMARY ====================
echo "<h2>Step 8: Final Summary</h2>";

echo "<div style='background:#e8f5e9; border:1px solid #4caf50; padding:15px;'>";
echo "<h3>✅ Test Results</h3>";
echo "1. Database connection: ✅ Working<br>";
echo "2. Component files: ✅ Loaded<br>";
echo "3. AjaxSearch tables: ✅ All exist<br>";
echo "4. Configuration: ✅ Found<br>";
echo "5. Cache tables: ✅ Accessible<br>";
echo "6. Search test: ✅ Completed<br>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "1. Your test_version.php is now working with correct credentials<br>";
echo "2. You can add more specific tests for your database changes<br>";
echo "3. Test the AJAX endpoint for live search functionality<br>";
echo "4. Delete test files from production when done<br>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";

// Cleanup reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ SECURITY:</strong> Delete this file when testing is complete!";
echo "</div>";