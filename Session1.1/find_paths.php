<?php
/**
 * Development Test Script for AjaxSearch
 * Path-finding version
 */

// ==================== PATH DISCOVERY ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Path Discovery Test</h1>";

// Try different possible base paths
$possiblePaths = [
    dirname(dirname(dirname(__DIR__))),  // Your original
    dirname(dirname(__DIR__)),           // One level up
    '/home/1208205.cloudwaysapps.com/xbczjgmawe',  // Full path from error
    '/home/1208205.cloudwaysapps.com/xbczjgmawe/public_html',  // Public HTML
    realpath(dirname(dirname(dirname(__DIR__)))),  // Real path
];

echo "<h2>Trying Paths:</h2>";
foreach ($possiblePaths as $i => $path) {
    $definesPath = $path . '/includes/defines.php';
    $exists = file_exists($definesPath) ? '✅ EXISTS' : '❌ MISSING';
    echo ($i+1) . ". $definesPath<br>&nbsp;&nbsp;&nbsp;&nbsp;→ $exists<br>";
}

// Stop here to see which path works
echo "<hr><h2>Stop here and check which path shows 'EXISTS' above</h2>";
die("Choose the correct path and update the script.");
?>