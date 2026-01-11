#!/bin/bash
echo "=== Ray AJAX Search Development Server ==="
echo ""
echo "Starting PHP development server on port 8080..."
echo "Press Ctrl+C to stop"
echo ""
echo "Available test files:"
echo "  http://localhost:8080/Session1.3/test_sp_search.php"
echo "  http://localhost:8080/Session1.3/ajax.php"
echo "  http://localhost:8080/Session2.1/enhanced_ajax.php"
echo ""
echo "With Xdebug: Add ?XDEBUG_SESSION_START=VSCODE to URL"
echo ""
php -S localhost:8080 -t .
