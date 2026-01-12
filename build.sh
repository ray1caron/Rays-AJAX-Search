#!/bin/bash

# =============================================================================
# Rays AJAX Search - Automated Build Script
# =============================================================================
# Usage: ./build.sh <version>
# Example: ./build.sh 1.0.0
# =============================================================================

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# =============================================================================
# CONFIGURATION
# =============================================================================

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="$PROJECT_ROOT/builds"

# =============================================================================
# FUNCTIONS
# =============================================================================

print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# =============================================================================
# VALIDATION
# =============================================================================

if [ -z "$1" ]; then
    print_error "Version number required"
    echo "Usage: ./build.sh <version>"
    echo "Example: ./build.sh 1.0.0"
    exit 1
fi

VERSION=$1

# Validate version format (X.Y.Z)
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_error "Invalid version format: $VERSION"
    echo "Use format: X.Y.Z (e.g., 1.0.0)"
    exit 1
fi

# Check if project root exists
if [ ! -d "$PROJECT_ROOT" ]; then
    print_error "Project root not found: $PROJECT_ROOT"
    exit 1
fi

# =============================================================================
# START BUILD
# =============================================================================

print_header "Building Rays AJAX Search v$VERSION"

# Step 1: Create directory structure
print_info "Step 1: Creating directory structure..."
mkdir -p "$BUILD_DIR/releases/v$VERSION"
mkdir -p "$BUILD_DIR/latest"
mkdir -p "$BUILD_DIR/archive/old_versions"
print_success "Directory structure created"

# Step 2: Create component package
print_info "Step 2: Creating component package..."
cd "$PROJECT_ROOT/components"
zip -r "$BUILD_DIR/releases/v$VERSION/com_ajaxsearch_${VERSION}.zip" \
    com_ajaxsearch/ \
    -x "*.git*" "*.DS_Store" "*/.*" > /dev/null 2>&1
cd "$PROJECT_ROOT"

if [ $? -eq 0 ]; then
    print_success "Component package created: com_ajaxsearch_${VERSION}.zip"
else
    print_error "Failed to create component package"
    exit 1
fi

# Step 3: Create module package
print_info "Step 3: Creating module package..."
cd "$PROJECT_ROOT/mod_raysajaxsearch"
zip -r "$BUILD_DIR/releases/v$VERSION/mod_raysajaxsearch_${VERSION}.zip" \
    . \
    -x "*.git*" "*.DS_Store" "*/.*" > /dev/null 2>&1
cd "$PROJECT_ROOT"

if [ $? -eq 0 ]; then
    print_success "Module package created: mod_raysajaxsearch_${VERSION}.zip"
else
    print_error "Failed to create module package"
    exit 1
fi

# Step 4: Copy to latest
print_info "Step 4: Copying packages to latest..."
cp "$BUILD_DIR/releases/v$VERSION/com_ajaxsearch_${VERSION}.zip" "$BUILD_DIR/latest/com_ajaxsearch.zip"
cp "$BUILD_DIR/releases/v$VERSION/mod_raysajaxsearch_${VERSION}.zip" "$BUILD_DIR/latest/mod_raysajaxsearch.zip"
print_success "Copied to latest folder"

# Step 5: Copy documentation
print_info "Step 5: Copying documentation..."
if [ -f "$BUILD_DIR/INSTALLATION_GUIDE.md" ]; then
    cp "$BUILD_DIR/INSTALLATION_GUIDE.md" "$BUILD_DIR/releases/v$VERSION/"
    print_success "Copied INSTALLATION_GUIDE.md"
fi

if [ -f "$BUILD_DIR/INSTALLATION_QUICK_START.txt" ]; then
    cp "$BUILD_DIR/INSTALLATION_QUICK_START.txt" "$BUILD_DIR/releases/v$VERSION/"
    print_success "Copied INSTALLATION_QUICK_START.txt"
fi

if [ -f "$BUILD_DIR/README.md" ]; then
    cp "$BUILD_DIR/README.md" "$BUILD_DIR/releases/v$VERSION/"
    print_success "Copied README.md"
fi

# Step 6: Verify packages
print_info "Step 6: Verifying packages..."
COMP_SIZE=$(ls -lh "$BUILD_DIR/releases/v$VERSION/com_ajaxsearch_${VERSION}.zip" | awk '{print $5}')
MOD_SIZE=$(ls -lh "$BUILD_DIR/releases/v$VERSION/mod_raysajaxsearch_${VERSION}.zip" | awk '{print $5}')
print_success "Component package size: $COMP_SIZE"
print_success "Module package size: $MOD_SIZE"

# Step 7: Display summary
print_header "Build Complete!"

echo ""
echo "Release Information:"
echo "  Version: $VERSION"
echo "  Location: $BUILD_DIR/releases/v$VERSION/"
echo ""
echo "Files Created:"
echo "  - com_ajaxsearch_${VERSION}.zip ($COMP_SIZE)"
echo "  - mod_raysajaxsearch_${VERSION}.zip ($MOD_SIZE)"
echo "  - Documentation files"
echo ""
echo "Latest Symlinks:"
echo "  - $BUILD_DIR/latest/com_ajaxsearch.zip"
echo "  - $BUILD_DIR/latest/mod_raysajaxsearch.zip"
echo ""
echo "Next Steps:"
echo "  1. Update VERSION_HISTORY.md with release details"
echo "  2. Create RELEASE_NOTES.md in releases/v$VERSION/"
echo "  3. Commit changes: git add builds/ && git commit -m \"Release v$VERSION\""
echo "  4. Tag release: git tag -a v$VERSION -m \"Release v$VERSION\""
echo "  5. Push to GitHub: git push origin main && git push origin v$VERSION"
echo ""
print_success "Ready for release!"
echo ""
