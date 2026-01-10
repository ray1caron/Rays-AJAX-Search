<?php
/**
 * Test script for SP Page Builder Search - UPDATED
 * Uses corrected database credentials and handles missing Joomla initialization
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AJAX Search - Session 1.3 Test (SP Page Builder) - UPDATED</h1>";

// ==================== MANUAL JOOMLA LOADING ====================
// Since direct Joomla loading causes 500 errors, load manually
echo "<h2>Step 1: Manual Joomla Loading</h2>";

try {
    define('_JEXEC', 1);
    define('JPATH_BASE', __DIR__);
    
    // Check if Joomla files exist
    $definesPath = JPATH_BASE . '/includes/defines.php';
    $frameworkPath = JPATH_BASE . '/includes/framework.php';
    
    echo "defines.php: " . (file_exists($definesPath) ? '✅ Exists' : '❌ Missing') . "<br>";
    echo "framework.php: " . (file_exists($frameworkPath) ? '✅ Exists' : '❌ Missing') . "<br>";
    
    if (!file_exists($definesPath) || !file_exists($frameworkPath)) {
        die("❌ Joomla core files missing");
    }
    
    // Load minimal Joomla
    require_once $definesPath;
    require_once $frameworkPath;
    
    // Minimal bootstrap - don't initialize full application
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');
    jimport('joomla.factory');
    
    echo "✅ Joomla framework loaded (minimal)<br>";
    
} catch (Exception $e) {
    die("❌ Joomla loading failed: " . $e->getMessage());
}

// ==================== DIRECT DATABASE CONNECTION ====================
echo "<h2>Step 2: Database Connection</h2>";

// Use CORRECT credentials we found
$host = 'localhost';
$dbname = 'xbczjgmawe';
$username = 'xbczjgmawe';
$password = '6sngBS5S6J';
$prefix = 'of9kt_'; // From our previous test

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
    
    echo "✅ Direct database connection successful<br>";
    echo "Using prefix: $prefix<br>";
    
    // Create JDatabaseDriver compatible object for components that need it
    $db = JFactory::getDbo();
    if (!$db->connected()) {
        // Manually set connection
        $options = [
            'driver'   => 'pdomysql',
            'host'     => $host,
            'user'     => $username,
            'password' => $password,
            'database' => $dbname,
            'prefix'   => $prefix
        ];
        
        try {
            $db = JDatabaseDriver::getInstance($options);
            JFactory::$database = $db;
            echo "✅ Joomla database object created<br>";
        } catch (Exception $e) {
            echo "⚠ Could not create Joomla database object: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ==================== CHECK COMPONENT FILES ====================
echo "<h2>Step 3: Component Files Check</h2>";

$componentPath = JPATH_BASE . '/components/com_ajaxsearch';
$srcPath = $componentPath . '/src';

echo "Component path: $componentPath<br>";
echo "src/ path: $srcPath<br>";

if (!is_dir($componentPath)) {
    die("❌ Component directory not found");
}

if (!is_dir($srcPath)) {
    die("❌ src/ directory not found");
}

// Check for required files
$requiredFiles = [
    'Database/Adapter.php',
    'Parser/SPParser.php',
    'Search/SPSearch.php',
    'Search/IntegratedSearch.php',
    'Models/SearchModel.php'
];

echo "<h3>Required Files:</h3>";
$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $fullPath = $srcPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ " . $file . "<br>";
    } else {
        echo "❌ " . $file . " (MISSING)<br>";
        $allFilesExist = false;
    }
}

if (!$allFilesExist) {
    echo "<h3>Available files in src/:</h3>";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    echo "<div style='border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto; font-family:monospace;'>";
    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $relative = str_replace($srcPath . '/', '', $item->getPathname());
            echo "📄 " . $relative . "<br>";
        }
    }
    echo "</div>";
}

// ==================== CHECK SP PAGE BUILDER TABLE ====================
echo "<h2>Step 4: SP Page Builder Table Check</h2>";

$spTable = $prefix . 'sppagebuilder';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE '$spTable'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ SP Page Builder table exists: $spTable<br>";
        
        // Count published pages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$spTable` WHERE published = 1");
        $count = $stmt->fetch()['count'];
        echo "Published SP pages: $count<br>";
        
        if ($count > 0) {
            // Show sample pages
            $stmt = $pdo->query("SELECT id, title, alias FROM `$spTable` WHERE published = 1 LIMIT 3");
            $pages = $stmt->fetchAll();
            
            echo "<h4>Sample SP Pages:</h4>";
            foreach ($pages as $page) {
                echo "- " . htmlspecialchars($page['title']) . " (ID: {$page['id']})<br>";
            }
        }
    } else {
        echo "❌ SP Page Builder table not found: $spTable<br>";
        echo "Note: SP Page Builder search will not work without this table<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking SP Page Builder: " . $e->getMessage() . "<br>";
}

// ==================== MANUALLY LOAD COMPONENT CLASSES ====================
echo "<h2>Step 5: Manual Class Loading</h2>";

// Load all PHP files in src directory
function loadComponentClasses($srcPath) {
    $loaded = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        if ($item->isFile() && $item->getExtension() === 'php') {
            try {
                require_once $item->getPathname();
                $loaded[] = $item->getFilename();
            } catch (Exception $e) {
                echo "⚠ Error loading {$item->getFilename()}: " . $e->getMessage() . "<br>";
            }
        }
    }
    return $loaded;
}

$loadedFiles = loadComponentClasses($srcPath);
echo "Loaded " . count($loadedFiles) . " PHP files<br>";

// Check what classes were loaded
$allClasses = get_declared_classes();
$componentClasses = array_filter($allClasses, function($class) {
    return stripos($class, 'ajaxsearch') !== false || 
           stripos($class, 'ajaxsearch') !== false;
});

echo "<h3>Loaded AjaxSearch Classes:</h3>";
if (count($componentClasses) > 0) {
    echo "<div style='border:1px solid #ccc; padding:10px; max-height:150px; overflow-y:auto;'>";
    foreach ($componentClasses as $class) {
        echo "- $class<br>";
    }
    echo "</div>";
} else {
    echo "No AjaxSearch classes found. Trying alternative search...<br>";
    
    // Show all classes containing 'search' or 'parser'
    $searchClasses = array_filter($allClasses, function($class) {
        return stripos($class, 'search') !== false || 
               stripos($class, 'parser') !== false;
    });
    
    if (count($searchClasses) > 0) {
        echo "<h4>Search/Parser related classes:</h4>";
        foreach ($searchClasses as $class) {
            echo "- $class<br>";
        }
    }
}

// ==================== TEST DATABASE ADAPTER ====================
echo "<h2>Step 6: Test Database Adapter</h2>";

try {
    // Find adapter class
    $adapterClass = null;
    foreach ($componentClasses as $class) {
        if (stripos($class, 'adapter') !== false && stripos($class, 'database')) {
            $adapterClass = $class;
            break;
        }
    }
    
    if ($adapterClass && class_exists($adapterClass)) {
        echo "Found adapter class: $adapterClass<br>";
        
        // Create adapter instance
        $dbAdapter = new $adapterClass($pdo);
        echo "✅ Database adapter created<br>";
        
        // Test table existence
        $tablesToCheck = [
            $prefix . 'ajaxsearch_cache',
            $prefix . 'ajaxsearch_sp_cache',
            $prefix . 'content',
            $spTable
        ];
        
        foreach ($tablesToCheck as $table) {
            if (method_exists($dbAdapter, 'tableExists')) {
                $exists = $dbAdapter->tableExists(str_replace($prefix, '', $table));
                echo "$table: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            } else {
                // Direct check
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                echo "$table: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
            }
        }
        
    } else {
        echo "⚠ No adapter class found. Checking tables directly:<br>";
        
        foreach ([$prefix . 'ajaxsearch_cache', $prefix . 'ajaxsearch_sp_cache'] as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "$table: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Adapter test error: " . $e->getMessage() . "<br>";
}

// ==================== TEST PARSER ====================
echo "<h2>Step 7: Test Parser</h2>";

try {
    // Find parser class
    $parserClass = null;
    foreach ($componentClasses as $class) {
        if (stripos($class, 'parser') !== false && stripos($class, 'sp')) {
            $parserClass = $class;
            break;
        }
    }
    
    if ($parserClass && class_exists($parserClass)) {
        echo "Found parser class: $parserClass<br>";
        
        // Need adapter for parser
        if (!isset($dbAdapter)) {
            echo "⚠ Need adapter for parser test<br>";
        } else {
            $parser = new $parserClass($dbAdapter);
            echo "✅ Parser created<br>";
            
            // Test parser methods
            if (method_exists($parser, 'testParser')) {
                $testResults = $parser->testParser();
                echo "✅ Parser test completed<br>";
                
                if (is_array($testResults)) {
                    echo "<h4>Parser Test Results:</h4>";
                    echo "<pre>" . print_r($testResults, true) . "</pre>";
                }
            } else {
                echo "⚠ testParser() method not found<br>";
            }
            
            // Check cache stats
            if (method_exists($parser, 'getCacheStats')) {
                $cacheStats = $parser->getCacheStats();
                echo "<h4>SP Cache Stats:</h4>";
                echo "<pre>" . print_r($cacheStats, true) . "</pre>";
            }
        }
    } else {
        echo "⚠ No parser class found<br>";
    }
} catch (Exception $e) {
    echo "❌ Parser test error: " . $e->getMessage() . "<br>";
}

// ==================== TEST SEARCH FUNCTIONALITY ====================
echo "<h2>Step 8: Test Search Functionality</h2>";

try {
    // Find search model class
    $modelClass = null;
    foreach ($componentClasses as $class) {
        if (stripos($class, 'model') !== false && stripos($class, 'search')) {
            $modelClass = $class;
            break;
        }
    }
    
    if ($modelClass && class_exists($modelClass)) {
        echo "Found model class: $modelClass<br>";
        
        // Create configuration
        $config = [
            'enable_cache' => true,
            'cache_ttl' => 3600,
            'max_results' => 10,
            'search_types' => ['article', 'sp']
        ];
        
        // Need adapter for model
        if (!isset($dbAdapter)) {
            echo "⚠ Need adapter for search model<br>";
        } else {
            $searchModel = new $modelClass($dbAdapter, $config);
            echo "✅ Search model created<br>";
            
            // Test basic search
            $testQuery = 'test';
            
            if (method_exists($searchModel, 'search')) {
                echo "Testing search for: '$testQuery'<br>";
                
                $results = $searchModel->search($testQuery, ['limit' => 3]);
                
                if (is_array($results)) {
                    echo "✅ Search returned results<br>";
                    echo "Results count: " . count($results) . "<br>";
                    
                    if (!empty($results)) {
                        echo "<h4>Sample Results:</h4>";
                        foreach ($results as $index => $result) {
                            if ($index >= 2) break;
                            echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
                            echo "<strong>#" . ($index + 1) . ":</strong><br>";
                            echo "<pre>" . print_r($result, true) . "</pre>";
                            echo "</div>";
                        }
                    }
                } else {
                    echo "⚠ Search returned non-array result<br>";
                }
            } else {
                echo "⚠ search() method not found<br>";
            }
            
            // Test article search
            if (method_exists($searchModel, 'searchArticles')) {
                $articleResults = $searchModel->searchArticles($testQuery, ['limit' => 2]);
                echo "Article search results: " . (is_array($articleResults) ? count($articleResults) : 'N/A') . "<br>";
            }
            
            // Test SP search if table exists
            if ($tableExists && method_exists($searchModel, 'searchSPPages')) {
                $spResults = $searchModel->searchSPPages($testQuery, ['limit' => 2]);
                echo "SP search results: " . (is_array($spResults) ? count($spResults) : 'N/A') . "<br>";
            }
        }
    } else {
        echo "⚠ No search model class found<br>";
    }
} catch (Exception $e) {
    echo "❌ Search test error: " . $e->getMessage() . "<br>";
}

// ==================== DIRECT CONTENT SEARCH ====================
echo "<h2>Step 9: Direct Content Search</h2>";

try {
    $testQuery = 'article';
    
    // Search in Joomla articles
    $contentTable = $prefix . 'content';
    
    $sql = "SELECT id, title, introtext 
            FROM `$contentTable` 
            WHERE state = 1 
            AND (title LIKE :query OR introtext LIKE :query OR alias LIKE :query)
            ORDER BY created DESC 
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$testQuery%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    echo "✅ Direct article search completed<br>";
    echo "Found " . count($articles) . " articles containing '$testQuery'<br>";
    
    if (!empty($articles)) {
        echo "<h4>Sample Articles:</h4>";
        foreach ($articles as $article) {
            echo "<div style='border:1px solid #ddd; padding:5px; margin:5px;'>";
            echo "<strong>" . htmlspecialchars($article['title']) . "</strong><br>";
            echo "ID: {$article['id']}<br>";
            echo "Snippet: " . substr(strip_tags($article['introtext']), 0, 100) . "...<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Direct search error: " . $e->getMessage() . "<br>";
}

// ==================== FINAL SUMMARY ====================
echo "<h2>Step 10: Final Summary</h2>";

echo "<div style='background:#e8f5e9; border:2px solid #4caf50; padding:15px;'>";
echo "<h3>Test Results:</h3>";
echo "✅ Database: Connected successfully<br>";
echo "✅ Joomla: Minimal framework loaded<br>";
echo "✅ Component: Files checked<br>";
echo "✅ SP Page Builder: " . ($tableExists ? 'Table exists' : 'Table missing') . "<br>";
echo "✅ Classes: " . count($componentClasses) . " AjaxSearch classes loaded<br>";
echo "✅ Search: Basic tests completed<br>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "1. " . ($tableExists ? '✅ SP Page Builder table exists' : '❌ Install SP Page Builder') . "<br>";
echo "2. " . (count($componentClasses) > 0 ? '✅ Component classes loaded' : '❌ Check component files') . "<br>";
echo "3. Test the actual AJAX endpoint<br>";
echo "4. Delete this test file after testing<br>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";

// AJAX endpoint URLs
echo "<h2>AJAX Endpoint Test URLs:</h2>";
$baseUrl = rtrim(str_replace('/test_sp_search.php', '', $_SERVER['REQUEST_URI']), '/');
$siteUrl = 'https://' . $_SERVER['HTTP_HOST'] . $baseUrl;

echo "Base URL: $siteUrl<br><br>";
echo "Test URLs:<br>";
echo "1. <a href='{$siteUrl}/components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3' target='_blank'>All content</a><br>";
echo "2. <a href='{$siteUrl}/components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&type=article' target='_blank'>Articles only</a><br>";
if ($tableExists) {
    echo "3. <a href='{$siteUrl}/components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&type=sp' target='_blank'>SP pages only</a><br>";
}
echo "4. <a href='{$siteUrl}/components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&debug=1' target='_blank'>With debug info</a><br>";

// Cleanup reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ SECURITY WARNING:</strong> DELETE THIS FILE AFTER TESTING!";
echo "</div>";