# Build Process Documentation

**For:** GitHub Copilot (AI Assistant)  
**Purpose:** Standardized workflow for creating version releases  
**Last Updated:** 2026-01-11  
**Version:** 1.0  

---

## 📋 Overview

This document defines the exact process to follow **every time** a new build/version is created for the Rays AJAX Search project. Follow these steps in order to maintain consistency and proper versioning.

---

## 🔄 Process Flow

```
1. Prepare Code Changes
   ↓
2. Update Version Numbers
   ↓
3. Create Directory Structure
   ↓
4. Package Components
   ↓
5. Update Documentation
   ↓
6. Git Tag & Commit
   ↓
7. Verify & Cleanup
```

---

## 📝 Step 1: Prepare Code Changes

**When to Execute:** When code is ready for release

### Checklist
- [ ] All code changes completed
- [ ] Bug fixes tested
- [ ] New features working
- [ ] No uncommitted changes
- [ ] Code quality reviewed

### Files That Might Change
- `components/com_ajaxsearch/src/ajax.php`
- `mod_raysajaxsearch/mod_raysajaxsearch.php`
- `mod_raysajaxsearch/media/ajaxsearch.js`
- `mod_raysajaxsearch/helper.php`
- Other module/component files

---

## 🔢 Step 2: Update Version Numbers

**Required Changes for EACH Release**

### 2.1 Update Module Manifest
**File:** `mod_raysajaxsearch/mod_raysajaxsearch.xml`

Find and update:
```xml
<version>X.Y.Z</version>  <!-- Change this -->
```

Example: `<version>1.0.1</version>`

### 2.2 Update Component Manifest
**File:** `components/com_ajaxsearch/com_ajaxsearch.xml`

Find and update:
```xml
<version>X.Y.Z</version>  <!-- Change this -->
```

Make sure it matches the module version!

### 2.3 Version Format
Always use **MAJOR.MINOR.PATCH**:
- `1.0.0` - Initial release
- `1.0.1` - Bug fix
- `1.1.0` - New feature
- `2.0.0` - Breaking change

---

## 📁 Step 3: Create Directory Structure

**Exact Commands to Run:**

```bash
cd /home/ray/Desktop/Ray_AJAX_Search_Project

# Create version-specific directory
mkdir -p builds/releases/vX.Y.Z

# If new, create archives
mkdir -p builds/latest builds/archive/old_versions
```

**Replace `X.Y.Z` with actual version (e.g., `1.0.1`)**

---

## 📦 Step 4: Package Components

**CRITICAL: Package from the correct directory to include manifest files**

**4.1 Create Component Package**

```bash
cd /home/ray/Desktop/Ray_AJAX_Search_Project/components

zip -r ../builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip \
    com_ajaxsearch/ \
    -x "*.git*" "*.DS_Store" "*/.*"

cd ..
```

**IMPORTANT:** The manifest file `com_ajaxsearch.xml` MUST be included in the zip!

**4.2 Create Module Package**

```bash
cd /home/ray/Desktop/Ray_AJAX_Search_Project/mod_raysajaxsearch

zip -r ../builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip \
    . \
    -x "*.git*" "*.DS_Store" "*/.*"

cd ..
```

**IMPORTANT:** Package from inside the module directory (use `.` for current dir)

**4.3 Copy to Latest**

```bash
cp builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip builds/latest/com_ajaxsearch.zip
cp builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip builds/latest/mod_raysajaxsearch.zip
```

**Result Files Created:**
- `builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip` ✅ (includes manifest)
- `builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip` ✅ (includes manifest)
- `builds/latest/com_ajaxsearch.zip`
- `builds/latest/mod_raysajaxsearch.zip`

---

## 📚 Step 5: Update Documentation

**5.1 Create RELEASE_NOTES.md**

**File:** `builds/releases/vX.Y.Z/RELEASE_NOTES.md`

Template:
```markdown
# Rays AJAX Search - Release Notes vX.Y.Z

**Release Date:** [DATE]
**Status:** [Alpha/Beta/Production Ready]
**Type:** [Bug Fix/Feature/Maintenance]

## Fixed Issues
- Issue #N: [Description]

## New Features
- Feature 1
- Feature 2

## Known Limitations
- Limitation 1

## Installation
[Copy from previous release, update version numbers]

## Components
- `com_ajaxsearch_X.Y.Z.zip`
- `mod_raysajaxsearch_X.Y.Z.zip`
```

**5.2 Update VERSION_HISTORY.md**

**File:** `builds/VERSION_HISTORY.md`

Add new section at top:
```markdown
## Version X.Y.Z (YYYY-MM-DD)

**Status:** Production Release ✅
**Type:** [Bug Fix/Feature/Maintenance]

### Fixed
- Issue #N: Description

### Added
- Feature 1
- Feature 2

### Components
- `com_ajaxsearch_X.Y.Z.zip`
- `mod_raysajaxsearch_X.Y.Z.zip`
```

**5.3 Copy Installation Guides**

```bash
cp builds/INSTALLATION_GUIDE.md builds/releases/vX.Y.Z/
cp builds/INSTALLATION_QUICK_START.txt builds/releases/vX.Y.Z/
cp builds/README.md builds/releases/vX.Y.Z/
```

**5.4 Update builds/README.md**

Update the latest version table:
```markdown
| Package | Version | Size | Purpose | Status |
|---------|---------|------|---------|--------|
| **mod_raysajaxsearch.zip** | X.Y.Z | XX KB | Search Module | ✅ Ready |
| **com_ajaxsearch.zip** | X.Y.Z | X.X KB | AJAX Component | ✅ Ready |
```

---

## 🏷️ Step 6: Git Tag & Commit

**6.1 Stage Changes**

```bash
cd /home/ray/Desktop/Ray_AJAX_Search_Project

git add \
    components/com_ajaxsearch/com_ajaxsearch.xml \
    mod_raysajaxsearch/mod_raysajaxsearch.xml \
    builds/
```

**6.2 Commit**

```bash
git commit -m "Release version X.Y.Z - [Brief description]"

# Examples:
# git commit -m "Release version 1.0.0 - Initial release with Issue #9 fix"
# git commit -m "Release version 1.0.1 - Bug fixes and improvements"
```

**6.3 Create Tag**

```bash
git tag -a vX.Y.Z -m "Release version X.Y.Z"

# Example:
# git tag -a v1.0.0 -m "Release version 1.0.0"
```

**6.4 Push to GitHub**

```bash
git push origin main
git push origin vX.Y.Z
```

---

## ✅ Step 7: Verify & Cleanup

**7.1 Verify Files Exist**

Check that all files are in correct locations:
```
builds/
├── releases/vX.Y.Z/
│   ├── com_ajaxsearch_X.Y.Z.zip
│   ├── mod_raysajaxsearch_X.Y.Z.zip
│   ├── RELEASE_NOTES.md
│   ├── INSTALLATION_GUIDE.md
│   └── INSTALLATION_QUICK_START.txt
├── latest/
│   ├── com_ajaxsearch.zip
│   └── mod_raysajaxsearch.zip
└── VERSION_HISTORY.md
```

**7.2 List Files**

```bash
echo "=== Release vX.Y.Z Files ===" && \
ls -lh builds/releases/vX.Y.Z/ && \
echo "" && \
echo "=== Latest Files ===" && \
ls -lh builds/latest/
```

**7.3 Verify Zip Contents**

```bash
unzip -l builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip
unzip -l builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip
```

**7.4 Cleanup**

Remove any temporary or old files not needed:
```bash
# Remove old versions if archiving older releases
# But KEEP version history for reference
```

---

## 🎯 Complete Checklist

Use this checklist for EVERY release:

### Before Building
- [ ] Code changes completed and tested
- [ ] All bugs fixed
- [ ] No uncommitted changes
- [ ] Code quality reviewed

### Building
- [ ] Update `mod_raysajaxsearch.xml` version
- [ ] Update `com_ajaxsearch.xml` version
- [ ] Create `builds/releases/vX.Y.Z/` directory
- [ ] Create component .zip file
- [ ] Create module .zip file
- [ ] Copy to `builds/latest/`
- [ ] Create RELEASE_NOTES.md
- [ ] Update VERSION_HISTORY.md
- [ ] Copy installation guides

### Documentation
- [ ] RELEASE_NOTES.md created
- [ ] VERSION_HISTORY.md updated
- [ ] README.md version updated
- [ ] All files in correct directories

### Git
- [ ] Changes staged (`git add`)
- [ ] Committed (`git commit`)
- [ ] Tagged (`git tag`)
- [ ] Pushed to GitHub (`git push`)

### Verification
- [ ] Files exist in correct locations
- [ ] Zip contents verified
- [ ] Version numbers consistent
- [ ] Documentation complete

---

## 📊 Version Increment Guide

### When to Increment What

**PATCH (X.Y.Z → X.Y.Z+1)**
- Bug fixes only
- No new features
- Backward compatible
- Example: 1.0.0 → 1.0.1

**MINOR (X.Y.Z → X.Y+1.0)**
- New features
- Backward compatible
- Fixes may be included
- Example: 1.0.0 → 1.1.0

**MAJOR (X.Y.Z → X+1.0.0)**
- Breaking changes
- New major functionality
- API changes
- Example: 1.9.5 → 2.0.0

---

## 🛠️ Troubleshooting

### Issue: Zip file missing contents
**Solution:** Check the zip command excludes patterns match actual files

### Issue: Version numbers don't match
**Solution:** Search both module and component XML files

### Issue: Git tag not showing
**Solution:** Use `git push origin vX.Y.Z` to push tags

### Issue: Files not in latest folder
**Solution:** Ensure `cp` command completes before verifying

---

## 📋 Quick Reference

### Core Commands (in order)
```bash
# 1. Create directory
mkdir -p builds/releases/vX.Y.Z

# 2. Create packages
zip -r builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip components/com_ajaxsearch/ -x "*.git*" "*.DS_Store"
zip -r builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip mod_raysajaxsearch/ -x "*.git*" "*.DS_Store"

# 3. Copy to latest
cp builds/releases/vX.Y.Z/*.zip builds/latest/

# 4. Git operations
git add builds/ components/com_ajaxsearch/com_ajaxsearch.xml mod_raysajaxsearch/mod_raysajaxsearch.xml
git commit -m "Release version X.Y.Z"
git tag -a vX.Y.Z -m "Release version X.Y.Z"
git push origin main vX.Y.Z
```

---

## 🔄 Repeating This Process

**Every time a new version is ready:**

1. Follow Steps 1-7 above
2. Use this checklist to verify nothing is missed
3. Update only the version number (everything else is the same)
4. Create release notes highlighting changes
5. Push to GitHub with clear commit messages

---

## 📞 Questions?

Refer to:
- [builds/README.md](builds/README.md) - Overview
- [builds/releases/vX.Y.Z/RELEASE_NOTES.md](builds/releases/vX.Y.Z/RELEASE_NOTES.md) - Release details
- [builds/VERSION_HISTORY.md](builds/VERSION_HISTORY.md) - All versions

---

**This document is the source of truth for all build operations.**

**Update this document if the process changes.**

**Follow this process for every release to maintain consistency.**

---

**Version:** 1.0  
**Effective Date:** 2026-01-11  
**Next Review:** 2026-02-11
