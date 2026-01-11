<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Required extensions:\n";
$exts = ['json', 'mbstring', 'xml', 'curl', 'mysqli', 'gd', 'xdebug'];
foreach ($exts as $ext) {
    echo "  $ext: " . (extension_loaded($ext) ? '✓' : '✗') . "\n";
}
