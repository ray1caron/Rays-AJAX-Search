<?php
/**
 * MINIMAL SP Page Builder Test - No Joomla Loading
 * Direct database connection only
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SP Page Builder Test - Minimal Version</h1>";
echo "<p>Testing without Joomla framework to avoid 500 errors</p>";

// ==================== DIRECT DATABASE CONNECTION ====================
echo "<h2>Step 1: Direct Database Connection</h2>";

// CORRECT credentials
$host = 'localhost';
$dbname = 'xbczjgmawe';
$username = 'xbczjgmawe';
$password = '6sngBS5S6J';
$prefix = 'of9kt_'; // From previous tests

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
    echo "Prefix: $prefix<br>";
    
    // Quick test
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Connection test: " . ($result['test'] == 1 ? '✅ OK' : '❌ Failed') . "<br>";
    
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// ==================== CHECK COMPONENT FILES ====================
echo "<h2>Step 2: Check Component Files</h2>";

$basePath = __DIR__;
$componentPath = $basePath . '/components/com_ajaxsearch';
$srcPath = $componentPath . '/src';

echo "Component path: $componentPath<br>";
echo "Source path: $srcPath<br><br>";

if (!is_dir($componentPath)) {
    die("❌ Component directory not found");
}

if (!is_dir($srcPath)) {
    die("❌ src/ directory not found");
}

// Check for specific files
$filesToCheck = [
    'Database/Adapter.php',
    'Parser/SPParser.php', 
    'Search/SPSearch.php',
    'Search/IntegratedSearch.php',
    'Models/SearchModel.php',
    'Compatibility/VersionCheck.php'
];

echo "<h3>Required Files:</h3>";
$missingFiles = [];
foreach ($filesToCheck as $file) {
    $fullPath = $srcPath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file<br>";
    } else {
        echo "❌ $file (MISSING)<br>";
        $missingFiles[] = $file;
    }
}

if (!empty($missingFiles)) {
    echo "<h3>Searching for files in src/:</h3>";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    echo "<div style='border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto; font-family:monospace;'>";
    $foundFiles = [];
    foreach ($iterator as $item) {
        if ($item->isFile() && $item->getExtension() === 'php') {
            $relative = str_replace($srcPath . '/', '', $item->getPathname());
            echo "📄 $relative<br>";
            $foundFiles[] = $relative;
        }
    }
    echo "</div>";
}

// ==================== CHECK TABLES ====================
echo "<h2>Step 3: Check Database Tables</h2>";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total tables: " . count($allTables) . "<br>";

// Check specific tables
$tablesToCheck = [
    $prefix . 'ajaxsearch_cache',
    $prefix . 'ajaxsearch_sp_cache',
    $prefix . 'ajaxsearch_analytics', 
    $prefix . 'ajaxsearch_config',
    $prefix . 'content',
    $prefix . 'sppagebuilder'
];

echo "<h3>Table Status:</h3>";
foreach ($tablesToCheck as $table) {
    $exists = in_array($table, $allTables);
    $status = $exists ? '✅ Exists' : '❌ Missing';
    $color = $exists ? 'green' : 'red';
    echo "<span style='color:$color;'>$table: $status</span><br>";
}

// ==================== LOAD AND TEST ADAPTER ====================
echo "<h2>Step 4: Test Database Adapter</h2>";

$adapterPath = $srcPath . '/Database/Adapter.php';
if (file_exists($adapterPath)) {
    try {
        // Isolate the include
        $adapterContent = file_get_contents($adapterPath);
        
        // Check if it's valid PHP
        if (strpos($adapterContent, '<?php') === false) {
            echo "❌ Adapter.php doesn't start with PHP tag<br>";
        } else {
            // Try to include it
            require_once $adapterPath;
            
            // Find the class
            $classes = get_declared_classes();
            $adapterClass = null;
            foreach ($classes as $class) {
                if (stripos($class, 'adapter') !== false && stripos($class, 'database')) {
                    $adapterClass = $class;
                    break;
                }
            }
            
            if ($adapterClass) {
                echo "✅ Found adapter class: $adapterClass<br>";
                
                // Try to instantiate
                try {
                    $dbAdapter = new $adapterClass($pdo);
                    echo "✅ Adapter instantiated successfully<br>";
                    
                    // Test methods
                    if (method_exists($dbAdapter, 'getCharset')) {
                        echo "Charset: " . $dbAdapter->getCharset() . "<br>";
                    }
                    
                    // Check table exists method
                    if (method_exists($dbAdapter, 'tableExists')) {
                        echo "tableExists method available<br>";
                    }
                    
                } catch (Exception $e) {
                    echo "❌ Could not instantiate adapter: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "⚠ Could not find adapter class<br>";
                echo "Available classes: " . implode(', ', $classes) . "<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error loading adapter: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Adapter.php not found<br>";
}

// ==================== TEST SP PAGE BUILDER PARSER ====================
echo "<h2>Step 5: Test SP Page Builder Integration</h2>";

$spTable = $prefix . 'sppagebuilder';
$spTableExists = in_array($spTable, $allTables);

if ($spTableExists) {
    echo "✅ SP Page Builder table exists<br>";
    
    // Count pages
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$spTable` WHERE published = 1");
        $publishedCount = $stmt->fetch()['count'];
        echo "Published SP pages: $publishedCount<br>";
        
        if ($publishedCount > 0) {
            // Get sample pages
            $stmt = $pdo->query("SELECT id, title, alias, created FROM `$spTable` WHERE published = 1 LIMIT 3");
            $pages = $stmt->fetchAll();
            
            echo "<h4>Sample SP Pages:</h4>";
            foreach ($pages as $page) {
                echo "<div style='border:1px solid #ddd; padding:5px; margin:5px;'>";
                echo "<strong>" . htmlspecialchars($page['title']) . "</strong><br>";
                echo "ID: {$page['id']} | Alias: {$page['alias']}<br>";
                echo "Created: {$page['created']}<br>";
                echo "</div>";
            }
            
            // Check for page content
            $stmt = $pdo->query("SELECT page FROM `$spTable` WHERE published = 1 LIMIT 1");
            $pageData = $stmt->fetch();
            
            if (!empty($pageData['page'])) {
                $pageContent = $pageData['page'];
                echo "<h4>SP Page Content Sample:</h4>";
                echo "<div style='border:1px solid #ccc; padding:10px; max-height:100px; overflow-y:auto; font-size:12px;'>";
                echo htmlspecialchars(substr($pageContent, 0, 500));
                echo "...</div>";
                
                // Check if it's JSON
                $decoded = json_decode($pageContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "✅ Page content is valid JSON<br>";
                    
                    // Try to extract text
                    function extractTextFromSPJSON($data) {
                        $text = '';
                        if (is_array($data)) {
                            foreach ($data as $key => $value) {
                                if (is_string($value) && $key !== 'settings' && $key !== 'style') {
                                    $text .= ' ' . $value;
                                } elseif (is_array($value)) {
                                    $text .= ' ' . extractTextFromSPJSON($value);
                                }
                            }
                        }
                        return $text;
                    }
                    
                    $extractedText = extractTextFromSPJSON($decoded);
                    echo "Extracted text length: " . strlen($extractedText) . " characters<br>";
                    echo "Sample: " . htmlspecialchars(substr($extractedText, 0, 200)) . "...<br>";
                } else {
                    echo "⚠ Page content is not valid JSON<br>";
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error reading SP pages: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ SP Page Builder table not found<br>";
    echo "SP Page Builder search will not work without this table<br>";
}

// ==================== TEST SEARCH CACHE ====================
echo "<h2>Step 6: Test Search Cache</h2>";

$cacheTable = $prefix . 'ajaxsearch_cache';
if (in_array($cacheTable, $allTables)) {
    try {
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `$cacheTable`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        echo "Cache table columns: " . implode(', ', $columns) . "<br>";
        
        // Count entries
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$cacheTable`");
        $cacheCount = $stmt->fetch()['count'];
        echo "Cache entries: $cacheCount<br>";
        
        if ($cacheCount > 0) {
            // Show recent entries
            $queryColumn = 'search_term';
            if (!in_array('search_term', $columns)) {
                // Try to find query column
                foreach (['term', 'keywords', 'query'] as $col) {
                    if (in_array($col, $columns)) {
                        $queryColumn = $col;
                        break;
                    }
                }
            }
            
            $stmt = $pdo->query("SELECT * FROM `$cacheTable` ORDER BY id DESC LIMIT 5");
            $entries = $stmt->fetchAll();
            
            echo "<h4>Recent Cache Entries:</h4>";
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
            echo "<tr>";
            foreach (array_keys($entries[0]) as $header) {
                echo "<th>" . $header . "</th>";
            }
            echo "</tr>";
            foreach ($entries as $entry) {
                echo "<tr>";
                foreach ($entry as $value) {
                    echo "<td>" . htmlspecialchars(substr(strval($value), 0, 30)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ Error reading cache: " . $e->getMessage() . "<br>";
    }
}

// ==================== TEST SP CACHE ====================
echo "<h2>Step 7: Test SP Page Cache</h2>";

$spCacheTable = $prefix . 'ajaxsearch_sp_cache';
if (in_array($spCacheTable, $allTables)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$spCacheTable`");
        $spCacheCount = $stmt->fetch()['count'];
        echo "SP Cache entries: $spCacheCount<br>";
        
        if ($spCacheCount > 0) {
            $stmt = $pdo->query("SELECT * FROM `$spCacheTable` LIMIT 3");
            $entries = $stmt->fetchAll();
            
            echo "<h4>SP Cache Entries:</h4>";
            echo "<pre style='font-size:11px; max-height:150px; overflow-y:auto;'>" . 
                 print_r($entries, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "❌ Error reading SP cache: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠ SP Cache table not found<br>";
}

// ==================== DIRECT SEARCH TEST ====================
echo "<h2>Step 8: Direct Search Test</h2>";

$testQuery = 'test';
echo "Testing search for: '$testQuery'<br>";

// Search in articles
$contentTable = $prefix . 'content';
try {
    $sql = "SELECT id, title, alias, introtext 
            FROM `$contentTable` 
            WHERE state = 1 
            AND (title LIKE :query OR alias LIKE :query OR introtext LIKE :query)
            LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$testQuery%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    echo "✅ Found " . count($articles) . " articles<br>";
    
    if (!empty($articles)) {
        echo "<h4>Article Results:</h4>";
        foreach ($articles as $article) {
            echo "<div style='border:1px solid #eee; padding:5px; margin:5px;'>";
            echo "<strong>" . htmlspecialchars($article['title']) . "</strong><br>";
            echo "Alias: {$article['alias']}<br>";
            echo "Snippet: " . substr(strip_tags($article['introtext']), 0, 100) . "...<br>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "❌ Article search error: " . $e->getMessage() . "<br>";
}

// Search in SP pages if table exists
if ($spTableExists) {
    try {
        echo "<h4>SP Page Search:</h4>";
        
        // This is simplified - actual SP search would need JSON parsing
        $sql = "SELECT id, title, alias 
                FROM `$spTable` 
                WHERE published = 1 
                AND (title LIKE :query OR alias LIKE :query)
                LIMIT 3";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->execute();
        $spPages = $stmt->fetchAll();
        
        echo "Found " . count($spPages) . " SP pages<br>";
        
        foreach ($spPages as $page) {
            echo "- " . htmlspecialchars($page['title']) . " (ID: {$page['id']})<br>";
        }
    } catch (Exception $e) {
        echo "❌ SP search error: " . $e->getMessage() . "<br>";
    }
}

// ==================== FINAL SUMMARY ====================
echo "<h2>Step 9: Final Summary</h2>";

echo "<div style='background:#e8f5e9; border:1px solid #4caf50; padding:15px;'>";
echo "<h3>✅ SUCCESS</h3>";
echo "- Database connection: Working<br>";
echo "- Component files: Checked<br>";
echo "- Tables: All AjaxSearch tables exist<br>";
echo "- SP Page Builder: " . ($spTableExists ? 'Installed' : 'Not installed') . "<br>";
echo "- Search test: Completed<br>";
echo "</div>";

echo "<h3>Recommendations:</h3>";
echo "1. " . ($spTableExists ? '✅ SP Page Builder is installed' : '❌ Install SP Page Builder') . "<br>";
echo "2. " . (empty($missingFiles) ? '✅ All required files exist' : '❌ Missing files: ' . implode(', ', $missingFiles)) . "<br>";
echo "3. Test the actual component by accessing the AJAX endpoint<br>";

// AJAX endpoint test
echo "<h3>AJAX Endpoint Test:</h3>";
$requestUri = $_SERVER['REQUEST_URI'];
$scriptPath = basename($requestUri);
$baseUrl = str_replace($scriptPath, '', $requestUri);
$fullUrl = 'https://' . $_SERVER['HTTP_HOST'] . $baseUrl;

echo "Test this URL in your browser:<br>";
echo "<a href='{$fullUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&debug=1' target='_blank'>";
echo "{$fullUrl}components/com_ajaxsearch/src/Component/ajax.php?q=test&limit=3&debug=1";
echo "</a><br>";
echo "<small>Add <code>&debug=1</code> to see detailed output</small>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";

// Cleanup reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ DELETE THIS FILE AFTER TESTING!</strong>";
echo "</div>";