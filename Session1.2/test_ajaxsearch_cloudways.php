<?php
/**
 * AjaxSearch Test with Cloudways Database Credentials
 * Using your actual Cloudways database info
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AjaxSearch Test - Cloudways Database</h1>";

// ==================== CLOUDWAYS DATABASE CREDENTIALS ====================
// From your Cloudways panel:
$host = 'localhost';                     // Cloudways typically uses localhost
$dbname = 'xbczjgmawe';                  // Your database name (note: you wrote "xbczjgmawewe" but earlier logs show "xbczjgmawe")
$username = 'xbczjgmawe';                // Your username
$password = '6sngB558J';                 // Your password

// Try both database name possibilities
$dbname_variants = ['xbczjgmawe', 'xbczjgmawewe'];

echo "<h2>Step 1: Database Connection Test</h2>";
echo "Host: $host<br>";
echo "Username: $username<br>";
echo "Password: ********<br>";

$pdo = null;
$selected_db = '';

foreach ($dbname_variants as $test_db) {
    echo "Trying database: $test_db... ";
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$test_db;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $selected_db = $test_db;
        echo "✅ SUCCESS<br>";
        break;
    } catch (PDOException $e) {
        echo "❌ FAILED: " . $e->getMessage() . "<br>";
    }
}

if (!$pdo) {
    // Try without database first
    echo "<br>Trying connection without database... ";
    try {
        $pdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        echo "✅ Connected to server<br>";
        
        // Show available databases
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Available Databases:</h3>";
        echo "<div style='border:1px solid #ccc; padding:10px; max-height:200px; overflow-y:auto;'>";
        foreach ($databases as $db) {
            echo "- $db<br>";
        }
        echo "</div>";
        
        die("Please check which database is correct and update the script.");
    } catch (PDOException $e) {
        die("❌ All connection attempts failed. Last error: " . $e->getMessage());
    }
}

echo "✅ Connected to database: <strong>$selected_db</strong><br>";

// Get database info
$stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db, USER() as user");
$info = $stmt->fetch();
echo "MySQL Version: " . $info['version'] . "<br>";
echo "Current User: " . $info['user'] . "<br>";

// ==================== CHECK FOR JOOMLA TABLES ====================
echo "<h2>Step 2: Check for Joomla Tables</h2>";

$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total tables in database: " . count($allTables) . "<br>";

// Look for Joomla prefix
$prefixes = [];
foreach ($allTables as $table) {
    if (preg_match('/^([a-z0-9]+_)/', $table, $matches)) {
        $prefixes[$matches[1]] = ($prefixes[$matches[1]] ?? 0) + 1;
    }
}

if (!empty($prefixes)) {
    arsort($prefixes);
    $likelyPrefix = key($prefixes);
    echo "Likely Joomla prefix: <strong>$likelyPrefix</strong> (used on " . current($prefixes) . " tables)<br>";
    $prefix = $likelyPrefix;
} else {
    echo "⚠ No clear table prefix found<br>";
    $prefix = '';
}

// Check for common Joomla tables
$joomlaTables = [
    'content',
    'categories', 
    'users',
    'menu',
    'modules',
    'extensions'
];

echo "<h3>Joomla Core Tables:</h3>";
foreach ($joomlaTables as $table) {
    $fullTable = $prefix . $table;
    $exists = in_array($fullTable, $allTables);
    echo "$fullTable: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
}

// ==================== CHECK FOR AJAXSEARCH TABLES ====================
echo "<h2>Step 3: Check for AjaxSearch Tables</h2>";

$ajaxsearchTables = [
    'ajaxsearch_cache',
    'ajaxsearch_sp_cache',
    'ajaxsearch_analytics',
    'ajaxsearch_config'
];

$foundTables = [];
foreach ($ajaxsearchTables as $table) {
    $fullTable = $prefix . $table;
    $exists = in_array($fullTable, $allTables);
    echo "$fullTable: " . ($exists ? '✅ Exists' : '❌ Missing') . "<br>";
    if ($exists) {
        $foundTables[] = $fullTable;
    }
}

// Show data from found tables
if (!empty($foundTables)) {
    echo "<h3>Data from AjaxSearch Tables:</h3>";
    foreach ($foundTables as $table) {
        echo "<h4>$table:</h4>";
        try {
            $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 5");
            $rows = $stmt->fetchAll();
            
            if (count($rows) > 0) {
                echo "<div style='border:1px solid #ccc; padding:10px; max-height:150px; overflow-y:auto;'>";
                echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
                // Headers
                echo "<tr>";
                foreach (array_keys($rows[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                // Data
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars(substr(strval($cell), 0, 50)) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
            } else {
                echo "Table is empty<br>";
            }
        } catch (Exception $e) {
            echo "Error reading table: " . $e->getMessage() . "<br>";
        }
    }
}

// ==================== CHECK FOR COMPONENT FILES ====================
echo "<h2>Step 4: Check for Component Files</h2>";

$basePath = __DIR__;
$componentPaths = [
    $basePath . '/components/com_ajaxsearch',
    $basePath . '/administrator/components/com_ajaxsearch',
    dirname($basePath) . '/components/com_ajaxsearch'
];

$componentFound = false;
foreach ($componentPaths as $path) {
    echo "Checking: $path<br>";
    if (is_dir($path)) {
        echo "✅ Component directory found!<br>";
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
                
                // If it's src directory, show contents
                if ($file == 'src' && is_dir($fullFile)) {
                    echo "<div style='margin-left:20px;'>";
                    $srcFiles = scandir($fullFile);
                    foreach ($srcFiles as $srcFile) {
                        if ($srcFile != '.' && $srcFile != '..') {
                            $type = is_dir($fullFile . '/' . $srcFile) ? '📁' : '📄';
                            echo "$type $srcFile<br>";
                        }
                    }
                    echo "</div>";
                }
            }
        }
        echo "</div>";
        break;
    }
}

if (!$componentFound) {
    echo "❌ Component directory not found in common locations<br>";
}

// ==================== TEST SEARCH FUNCTIONALITY ====================
echo "<h2>Step 5: Test Search Functionality</h2>";

if ($componentFound && !empty($foundTables)) {
    echo "Component and tables found. Testing search...<br>";
    
    // Check if we can search articles
    $contentTable = $prefix . 'content';
    if (in_array($contentTable, $allTables)) {
        echo "✅ Found articles table: $contentTable<br>";
        
        // Count published articles
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$contentTable` WHERE state = 1");
            $count = $stmt->fetch()['count'];
            echo "Published articles: $count<br>";
            
            if ($count > 0) {
                // Get sample articles
                $stmt = $pdo->query("SELECT id, title, alias, created FROM `$contentTable` WHERE state = 1 ORDER BY created DESC LIMIT 3");
                $articles = $stmt->fetchAll();
                
                echo "<h3>Sample Articles:</h3>";
                foreach ($articles as $article) {
                    echo "<div style='border:1px solid #eee; padding:5px; margin:5px;'>";
                    echo "<strong>" . htmlspecialchars($article['title']) . "</strong><br>";
                    echo "ID: {$article['id']} | Created: {$article['created']}<br>";
                    echo "</div>";
                }
            }
        } catch (Exception $e) {
            echo "❌ Error reading articles: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test ajaxsearch_cache table
    $cacheTable = $prefix . 'ajaxsearch_cache';
    if (in_array($cacheTable, $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$cacheTable`");
            $cacheCount = $stmt->fetch()['count'];
            echo "Cache entries: $cacheCount<br>";
            
            if ($cacheCount > 0) {
                $stmt = $pdo->query("SELECT query, results_count, created FROM `$cacheTable` ORDER BY created DESC LIMIT 3");
                $cacheEntries = $stmt->fetchAll();
                
                echo "<h3>Recent Cache Entries:</h3>";
                foreach ($cacheEntries as $entry) {
                    echo "<div style='border:1px solid #ddd; padding:5px; margin:5px; background:#f9f9f9;'>";
                    echo "Query: " . htmlspecialchars($entry['query']) . "<br>";
                    echo "Results: {$entry['results_count']} | Created: {$entry['created']}<br>";
                    echo "</div>";
                }
            }
        } catch (Exception $e) {
            echo "❌ Error reading cache: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "⚠ Cannot test search - component or tables missing<br>";
}

// ==================== FINAL SUMMARY ====================
echo "<h2>Step 6: Final Summary</h2>";

echo "<div style='background:#e8f5e9; border:1px solid #4caf50; padding:15px;'>";
echo "<strong>✅ Database Connection:</strong> SUCCESS<br>";
echo "<strong>✅ Database:</strong> $selected_db<br>";
echo "<strong>✅ Joomla Prefix:</strong> " . ($prefix ? $prefix : 'Not detected') . "<br>";
echo "<strong>✅ AjaxSearch Tables:</strong> " . count($foundTables) . " of " . count($ajaxsearchTables) . " found<br>";
echo "<strong>✅ Component Files:</strong> " . ($componentFound ? 'Found' : 'Not found') . "<br>";
echo "</div>";

echo "<h3>Recommendations:</h3>";
echo "1. " . (count($foundTables) == count($ajaxsearchTables) ? 
    "✅ All AjaxSearch tables exist" : 
    "⚠ Some tables missing - may need to install component") . "<br>";
echo "2. " . ($componentFound ? 
    "✅ Component files found" : 
    "❌ Component files not found - install via Joomla admin") . "<br>";
echo "3. Next: Test the actual search functionality<br>";

echo "<hr><em>Test completed at: " . date('Y-m-d H:i:s') . "</em>";

// Cleanup reminder
echo "<div style='background:#ffebee; border:2px solid #f44336; padding:15px; margin-top:20px;'>";
echo "<strong>⚠ IMPORTANT:</strong> Delete this file after testing!";
echo "</div>";