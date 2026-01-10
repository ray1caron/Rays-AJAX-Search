<?php
/**
 * AjaxSearch Test with CORRECTED Credentials
 * Password: 6sngBS5S6J (not 6sngB558J)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AjaxSearch Test - CORRECTED Credentials</h1>";

// ==================== CORRECTED CREDENTIALS ====================
$host = 'localhost';
$dbname = 'xbczjgmawe';      // Database name
$username = 'xbczjgmawe';    // Username  
$password = '6sngBS5S6J';    // CORRECTED PASSWORD

echo "<h2>Step 1: Database Connection Test</h2>";
echo "Host: $host<br>";
echo "Username: $username<br>";
echo "Password: ********<br>";
echo "Database: $dbname<br><br>";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
    echo "✅ <span style='color:green; font-size:1.2em;'>DATABASE CONNECTION SUCCESSFUL!</span><br><br>";
    
    // Get database info
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db, USER() as user");
    $info = $stmt->fetch();
    echo "MySQL Version: " . $info['version'] . "<br>";
    echo "Connected to: " . $info['db'] . "<br>";
    echo "As user: " . $info['user'] . "<br>";
    
} catch (PDOException $e) {
    die("❌ <span style='color:red; font-size:1.2em;'>Connection failed: " . $e->getMessage() . "</span>");
}

// ==================== LIST ALL TABLES ====================
echo "<h2>Step 2: List All Database Tables</h2>";

$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total tables: " . count($allTables) . "<br>";
echo "<div style='border:1px solid #ccc; padding:10px; max-height:300px; overflow-y:auto; font-family:monospace;'>";
foreach ($allTables as $table) {
    echo "- $table<br>";
}
echo "</div>";

// ==================== DETECT JOOMLA PREFIX ====================
echo "<h2>Step 3: Detect Joomla Table Prefix</h2>";

$prefixes = [];
foreach ($allTables as $table) {
    if (preg_match('/^([a-z0-9]+_)/', $table, $matches)) {
        $prefix = $matches[1];
        $prefixes[$prefix] = ($prefixes[$prefix] ?? 0) + 1;
    }
}

if (!empty($prefixes)) {
    arsort($prefixes);
    $likelyPrefix = key($prefixes);
    $prefixCount = current($prefixes);
    echo "Detected prefix: <strong>$likelyPrefix</strong> (used on $prefixCount tables)<br>";
    $prefix = $likelyPrefix;
} else {
    echo "⚠ No table prefix detected<br>";
    $prefix = '';
}

// ==================== CHECK AJAXSEARCH TABLES ====================
echo "<h2>Step 4: Check AjaxSearch Tables</h2>";

$ajaxsearchTables = [
    'ajaxsearch_cache',
    'ajaxsearch_sp_cache', 
    'ajaxsearch_analytics',
    'ajaxsearch_config'
];

$foundAjaxTables = [];
foreach ($ajaxsearchTables as $table) {
    $fullTable = $prefix . $table;
    $exists = in_array($fullTable, $allTables);
    echo "$fullTable: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
    if ($exists) {
        $foundAjaxTables[] = $fullTable;
    }
}

// Show data from found AjaxSearch tables
if (!empty($foundAjaxTables)) {
    echo "<h3>Data in AjaxSearch Tables:</h3>";
    foreach ($foundAjaxTables as $table) {
        echo "<h4>$table:</h4>";
        try {
            // Get row count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "Rows: $count<br>";
            
            if ($count > 0) {
                // Get table structure
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "Columns: " . implode(', ', $columns) . "<br>";
                
                // Get sample data
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                $rows = $stmt->fetchAll();
                
                echo "<div style='border:1px solid #ccc; padding:10px; max-height:150px; overflow-y:auto; font-size:12px;'>";
                echo "<pre>" . print_r($rows, true) . "</pre>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "<br>";
        }
    }
}

// ==================== CHECK COMPONENT INSTALLATION ====================
echo "<h2>Step 5: Check AjaxSearch Component Installation</h2>";

// Check extensions table
$extensionsTable = $prefix . 'extensions';
if (in_array($extensionsTable, $allTables)) {
    try {
        $query = "SELECT * FROM `$extensionsTable` 
                  WHERE `type` = 'component' 
                  AND (`element` = 'com_ajaxsearch' OR `name` LIKE '%ajaxsearch%')
                  LIMIT 5";
        $stmt = $pdo->query($query);
        $components = $stmt->fetchAll();
        
        if (count($components) > 0) {
            echo "✅ AjaxSearch component is installed in Joomla<br>";
            foreach ($components as $component) {
                echo "<div style='border:1px solid #ddd; padding:10px; margin:5px;'>";
                echo "Name: " . htmlspecialchars($component['name'] ?? 'N/A') . "<br>";
                echo "Element: " . htmlspecialchars($component['element'] ?? 'N/A') . "<br>";
                echo "Enabled: " . ($component['enabled'] ? 'Yes' : 'No') . "<br>";
                echo "Manifest: <pre style='font-size:10px;'>" . 
                     print_r(json_decode($component['manifest_cache'] ?? '{}', true), true) . 
                     "</pre>";
                echo "</div>";
            }
        } else {
            echo "⚠ AjaxSearch component not found in extensions table<br>";
        }
    } catch (Exception $e) {
        echo "Error checking extensions: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Extensions table not found: $extensionsTable<br>";
}

// ==================== CHECK FOR COMPONENT FILES ====================
echo "<h2>Step 6: Check for Component Files</h2>";

$basePath = __DIR__;
$componentPaths = [
    $basePath . '/components/com_ajaxsearch',
    $basePath . '/administrator/components/com_ajaxsearch',
];

$componentFound = false;
foreach ($componentPaths as $path) {
    if (is_dir($path)) {
        echo "✅ Found component at: $path<br>";
        $componentFound = true;
        $componentPath = $path;
        
        // List files
        echo "<h3>Files in component directory:</h3>";
        $files = scandir($path);
        echo "<div style='border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto; font-family:monospace;'>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $fullFile = $path . '/' . $file;
                $type = is_dir($fullFile) ? '📁' : '📄';
                echo "$type $file<br>";
            }
        }
        echo "</div>";
        break;
    }
}

if (!$componentFound) {
    echo "❌ Component directory not found<br>";
    echo "Searched in:<br>";
    foreach ($componentPaths as $path) {
        echo "- $path<br>";
    }
}

// ==================== TEST SEARCH FUNCTIONALITY ====================
echo "<h2>Step 7: Test Basic Search Functionality</h2>";

if (!empty($foundAjaxTables)) {
    // Test ajaxsearch_cache table
    $cacheTable = $prefix . 'ajaxsearch_cache';
    try {
        $stmt = $pdo->query("SELECT query, results_count, created, hits FROM `$cacheTable` ORDER BY created DESC LIMIT 5");
        $cacheEntries = $stmt->fetchAll();
        
        if (count($cacheEntries) > 0) {
            echo "✅ Cache table contains data<br>";
            echo "<h3>Recent search cache entries:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
            echo "<tr><th>Query</th><th>Results</th><th>Created</th><th>Hits</th></tr>";
            foreach ($cacheEntries as $entry) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($entry['query']) . "</td>";
                echo "<td>" . $entry['results_count'] . "</td>";
                echo "<td>" . $entry['created'] . "</td>";
                echo "<td>" . $entry['hits'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠ Cache table exists but is empty<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error reading cache table: " . $e->getMessage() . "<br>";
    }
    
    // Test ajaxsearch_analytics table
    $analyticsTable = $prefix . 'ajaxsearch_analytics';
    if (in_array($analyticsTable, $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$analyticsTable`");
            $analyticsCount = $stmt->fetch()['count'];
            echo "Analytics entries: $analyticsCount<br>";
        } catch (Exception $e) {
            echo "Error reading analytics: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "⚠ Cannot test search functionality - AjaxSearch tables not found<br>";
}

// ==================== TEST CONTENT SEARCH ====================
echo "<h2>Step 8: Test Content Search</h2>";

$contentTable = $prefix . 'content';
if (in_array($contentTable, $allTables)) {
    try {
        // Count articles
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN state = 1 THEN 1 END) as published FROM `$contentTable`");
        $counts = $stmt->fetch();
        
        echo "Total articles: " . $counts['total'] . "<br>";
        echo "Published articles: " . $counts['published'] . "<br>";
        
        if ($counts['published'] > 0) {
            // Simple search test
            $searchTerm = 'article';
            $query = "SELECT id, title, alias, created 
                      FROM `$contentTable` 
                      WHERE state = 1 
                      AND (title LIKE :term OR alias LIKE :term)
                      ORDER BY created DESC 
                      LIMIT 5";
            
            $stmt = $pdo->prepare($query);
            $term = "%$searchTerm%";
            $stmt->bindParam(':term', $term, PDO::PARAM_STR);
            $stmt->execute();
            $articles = $stmt->fetchAll();
            
            if (count($articles) > 0) {
                echo "<h3>Sample articles containing '$searchTerm':</h3>";
                foreach ($articles as $article) {
                    echo "<div style='border:1px solid #eee; padding:5px; margin:5px;'>";
                    echo "<strong>" . htmlspecialchars($article['title']) . "</strong><br>";
                    echo "ID: {$article['id']} | Created: {$article['created']}<br>";
                    echo "</div>";
                }
            } else {
                echo "No articles found containing '$searchTerm'<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error searching content: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Content table not found: $contentTable<br>";
}

// ==================== FINAL SUMMARY ====================
echo "<h2>Step 9: Final Summary</h2>";

$joomlaDetected = in_array($prefix . 'content', $allTables);
$ajaxsearchTablesDetected = count($foundAjaxTables) > 0;
$componentFilesDetected = $componentFound;

echo "<div style='background:#e8f5e9; border:2px solid #4caf50; padding:15px;'>";
echo "<h3>✅ Database Connection: SUCCESS</h3>";
echo "Using CORRECTED credentials<br>";
echo "Database: $dbname<br>";
echo "Prefix: $prefix<br>";
echo "Total tables: " . count($allTables) . "<br><br>";

echo "<h3>AjaxSearch Status:</h3>";
echo "Tables found: " . count($foundAjaxTables) . " of " . count($ajaxsearchTables) . "<br>";
echo "Component files: " . ($componentFilesDetected ? '✅ Found' : '❌ Not found') . "<br>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
if (count($foundAjaxTables) == count($ajaxsearchTables)) {
    echo "1. ✅ All AjaxSearch tables exist<br>";
    echo "2. " . ($componentFilesDetected ? '✅ Component files exist' : '❌ Upload component files') . "<br>";
    echo "3. Test the search functionality<br>";
    echo "4. Create your test_version.php using these database credentials<br>";
} else {
    echo "1. ⚠ Some AjaxSearch tables missing<br>";
    echo "2. Install/reinstall the AjaxSearch component<br>";
    echo "3. Check component files<br>";
}

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";

// Provide sample code for test_version.php
echo "<h2>Sample Code for Your test_version.php:</h2>";
echo "<div style='background:#f1f1f1; border:1px solid #ccc; padding:15px; font-family:monospace;'>";
echo "&lt;?php<br>";
echo "// Database configuration<br>";
echo "\$host = 'localhost';<br>";
echo "\$dbname = 'xbczjgmawe';<br>";
echo "\$username = 'xbczjgmawe';<br>";
echo "\$password = '6sngBS5S6J';<br>";
echo "\$prefix = '$prefix';<br><br>";
echo "try {<br>";
echo "    \$pdo = new PDO(<br>";
echo "        \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\",<br>";
echo "        \$username,<br>";
echo "        \$password,<br>";
echo "        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]<br>";
echo "    );<br>";
echo "    echo \"✅ Database connected successfully\\n\";<br>";
echo "    // Your test code here...<br>";
echo "} catch (PDOException \$e) {<br>";
echo "    die(\"❌ Connection failed: \" . \$e->getMessage());<br>";
echo "}<br>";
echo "?&gt;";
echo "</div>";

// Cleanup reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ SECURITY WARNING:</strong> Delete this test file after use!";
echo "</div>";