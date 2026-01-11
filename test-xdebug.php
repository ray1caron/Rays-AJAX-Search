<?php
echo "Testing Xdebug...\n";
if (extension_loaded('xdebug')) {
    echo "Xdebug is loaded!\n";
    echo "Version: " . phpversion('xdebug') . "\n";
} else {
    echo "Xdebug NOT loaded.\n";
}
