#!/bin/bash

# Rays AJAX Search - Setup and Test Script
# This script sets up the component structure and tests the AJAX endpoint

echo "================================"
echo "Rays AJAX Search - Setup Script"
echo "================================"
echo ""

# Get the project root
PROJECT_ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Project Root: $PROJECT_ROOT"

# Create component directory structure
echo ""
echo "Creating component directory structure..."
mkdir -p "$PROJECT_ROOT/components/com_ajaxsearch/src/Database"
mkdir -p "$PROJECT_ROOT/components/com_ajaxsearch/src/Search"
mkdir -p "$PROJECT_ROOT/components/com_ajaxsearch/src/Cache"
mkdir -p "$PROJECT_ROOT/components/com_ajaxsearch/src/Compatibility"

# Copy core files to component (if they exist)
echo ""
echo "Copying core files to component..."

if [ -f "$PROJECT_ROOT/src/Database/Adapter.php" ]; then
    cp "$PROJECT_ROOT/src/Database/Adapter.php" "$PROJECT_ROOT/components/com_ajaxsearch/src/Database/"
    echo "✓ Copied Database/Adapter.php"
fi

if [ -f "$PROJECT_ROOT/src/Search/AbstractSearchEngine.php" ]; then
    cp "$PROJECT_ROOT/src/Search/AbstractSearchEngine.php" "$PROJECT_ROOT/components/com_ajaxsearch/src/Search/"
    echo "✓ Copied Search/AbstractSearchEngine.php"
fi

if [ -f "$PROJECT_ROOT/src/Cache/BasicCache.php" ]; then
    cp "$PROJECT_ROOT/src/Cache/BasicCache.php" "$PROJECT_ROOT/components/com_ajaxsearch/src/Cache/"
    echo "✓ Copied Cache/BasicCache.php"
fi

if [ -f "$PROJECT_ROOT/src/Compatibility/VersionCheck.php" ]; then
    cp "$PROJECT_ROOT/src/Compatibility/VersionCheck.php" "$PROJECT_ROOT/components/com_ajaxsearch/src/Compatibility/"
    echo "✓ Copied Compatibility/VersionCheck.php"
fi

echo ""
echo "================================"
echo "Setup Complete!"
echo "================================"
echo ""
echo "Next steps:"
echo "1. Ensure Joomla is installed in your development environment"
echo "2. Test the AJAX endpoint at:"
echo "   http://localhost/components/com_ajaxsearch/src/ajax.php?q=test"
echo "3. The module should now return search results"
echo ""
