# 🤖 Instructions for GitHub Copilot AI Assistant

**Purpose:** Standardized instructions for building and releasing Rays AJAX Search  
**For:** GitHub Copilot  
**Effective:** 2026-01-11  

---

## 📌 IMPORTANT: READ BEFORE EVERY BUILD

**ALL code must follow [JOOMLA_CODING_STANDARDS.md](JOOMLA_CODING_STANDARDS.md)**

When the user asks to create a new build or release, **ALWAYS** follow the process in [BUILD_PROCESS.md](BUILD_PROCESS.md).

Do NOT skip steps or deviate from the process.

---

## 🎯 Quick Reference

### User Says: "Create a new build" or "Release version X.Y.Z"

**Respond with:**
1. Confirm the version number (must be in X.Y.Z format)
2. **FIRST: Check [VERSIONING_GUIDELINES.md](VERSIONING_GUIDELINES.md) to determine if version should be incremented**
3. **BEFORE BUILDING: Check all code files for syntax errors** (see Syntax Validation below)
4. Follow the exact steps in BUILD_PROCESS.md
5. Complete all checklist items
6. Verify all files are created
7. Provide user with status summary

### Key Files to Reference
- **[JOOMLA_CODING_STANDARDS.md](JOOMLA_CODING_STANDARDS.md)** ← Mandatory coding guide
- **[VERSIONING_GUIDELINES.md](VERSIONING_GUIDELINES.md)** ← When to increment version
- **[BUILD_PROCESS.md](BUILD_PROCESS.md)** ← THE SOURCE OF TRUTH for build steps
- **[build.sh](build.sh)** ← Automated build script (optional helper)
- **[builds/VERSION_HISTORY.md](builds/VERSION_HISTORY.md)** ← Version tracking

---

## 🔄 The Process (Summary)

### ALWAYS Follow These 7 Steps:

**Step 1: Prepare Code**
- Confirm code is ready
- All changes committed

**Step 2: Update Version Numbers**
- [ ] mod_raysajaxsearch.xml → `<version>X.Y.Z</version>`
- [ ] com_ajaxsearch.xml → `<version>X.Y.Z</version>`

**Step 3: Create Directories**
```bash
mkdir -p builds/releases/vX.Y.Z
```

**Step 4: Package Files**
```bash
# Component
zip -r builds/releases/vX.Y.Z/com_ajaxsearch_X.Y.Z.zip components/com_ajaxsearch/ -x "*.git*" "*.DS_Store" "*/.*"

# Module
zip -r builds/releases/vX.Y.Z/mod_raysajaxsearch_X.Y.Z.zip mod_raysajaxsearch/ -x "*.git*" "*.DS_Store" "*/.*"

# Copy to Latest
cp builds/releases/vX.Y.Z/*.zip builds/latest/
```

**Step 5: Update Documentation**
- [ ] Create `RELEASE_NOTES.md` in releases/vX.Y.Z/
- [ ] Update `builds/VERSION_HISTORY.md`
- [ ] Copy installation guides to release folder

**Step 6: Git Operations**
```bash
git add components/com_ajaxsearch/com_ajaxsearch.xml mod_raysajaxsearch/mod_raysajaxsearch.xml builds/
git commit -m "Release version X.Y.Z - [description]"
git tag -a vX.Y.Z -m "Release version X.Y.Z"
git push origin main vX.Y.Z
```

**Step 7: Verify**
- [ ] All files exist
- [ ] Versions are consistent
- [ ] Documentation complete
- [ ] Git tags pushed

---

## ✅ Syntax Validation (CRITICAL BEFORE EVERY BUILD)

**ALWAYS check code for syntax errors BEFORE creating packages:**

### JavaScript Files
```bash
# Check for syntax errors in JavaScript
node -c /path/to/file.js
```
- Check: `/mod_raysajaxsearch/media/ajaxsearch.js`
- Look for: Missing braces, mismatched parentheses, incomplete if/else blocks
- Common errors: Unfinished function calls, missing commas, orphaned `else` statements

### PHP Files
```bash
# Check for syntax errors in PHP  
php -l /path/to/file.php
```
- Check: `/mod_raysajaxsearch/mod_raysajaxsearch.php`
- Check: `/mod_raysajaxsearch/ajax.php`
- Check: `/components/com_ajaxsearch/` files
- Look for: Mismatched quotes, missing semicolons, unclosed tags

### What to Do If Syntax Errors Found:
1. **STOP** - Do NOT create packages
2. **FIX** - Correct the syntax error
3. **VERIFY** - Re-run syntax check to confirm fix
4. **TEST** - Run the corrected code
5. **THEN** - Proceed with build

### Why This Matters:
- Syntax errors cause JavaScript to stop executing (no error dialog, just silent failure)
- They prevent modules/components from loading correctly
- Users will see "Search Error" without understanding why
- These errors are preventable with basic validation

---

## 📋 DO's and DON'Ts

### DO:
✅ Follow BUILD_PROCESS.md exactly  
✅ **CHECK SYNTAX BEFORE EVERY BUILD** (Critical!)
✅ Update BOTH manifest files (module + component)  
✅ Create RELEASE_NOTES.md every time  
✅ Update VERSION_HISTORY.md  
✅ Verify all files are in correct directories  
✅ Use semantic versioning (X.Y.Z)  
✅ Push git tags to GitHub  
✅ Keep old versions in archive for reference  

### DON'T:
❌ Skip any steps  
❌ Create builds without updating version numbers  
❌ Forget documentation files  
❌ Commit build files without releasing properly  
❌ Use incorrect version formats (e.g., "1.0" instead of "1.0.0")  
❌ Mix versions between module and component  
❌ Delete old versions (archive them instead)  
❌ Increment version for pre-deployment bug fixes (packaging, docs, code not yet deployed)

---

## 🔢 Version Increment Decision Guide

**BEFORE updating version numbers, answer these questions:**

1. **Did the code change?**
   - NO → Keep same version (e.g., stay at 1.0.0)
   - YES → Go to question 2

2. **Is this a FIX for something broken?**
   - YES, and code was already deployed → PATCH (1.0.0 → 1.0.1)
   - YES, but not deployed yet → Keep same version
   - NO → Go to question 3

3. **Is this a NEW feature (backward compatible)?**
   - YES → MINOR (1.0.0 → 1.1.0)
   - NO → Go to question 4

4. **Is this a BREAKING change?**
   - YES → MAJOR (1.0.0 → 2.0.0)
   - NO → MINOR (1.0.0 → 1.1.0)

**See [VERSIONING_GUIDELINES.md](VERSIONING_GUIDELINES.md) for detailed examples and decision tree.**

---

## ✅ Pre-Build Checklist: Determine Correct Version

When user says "create v1.0.1" or "release new build":
- [ ] Ask: "What changes are in this release?" (if not obvious)
- [ ] Cross-check against VERSIONING_GUIDELINES.md
- [ ] Confirm version increment is appropriate
- [ ] Proceed with BUILD_PROCESS.md steps

### Examples:
- User: "Fix the search bug" → Check if deployed → If yes, PATCH (1.0.1)
- User: "Add search highlighting" → NEW feature → MINOR (1.1.0)
- User: "Drop PHP 7.4 support" → BREAKING change → MAJOR (2.0.0)

---

## 🚀 Automated Build Helper

### Optional: Use build.sh for faster packaging
```bash
./build.sh 1.0.1
```

This script:
- ✅ Creates directory structure
- ✅ Packages component & module
- ✅ Copies to latest/
- ✅ Copies documentation
- ✅ Verifies packages
- ✅ Displays summary

**Note:** Still need to manually create RELEASE_NOTES and update VERSION_HISTORY

---

## 📁 Directory Structure (After Release)

```
builds/
├── releases/
│   └── vX.Y.Z/
│       ├── com_ajaxsearch_X.Y.Z.zip
│       ├── mod_raysajaxsearch_X.Y.Z.zip
│       ├── RELEASE_NOTES.md
│       ├── INSTALLATION_GUIDE.md
│       ├── INSTALLATION_QUICK_START.txt
│       └── README.md
├── latest/
│   ├── com_ajaxsearch.zip (current)
│   └── mod_raysajaxsearch.zip (current)
├── archive/
│   └── old_versions/
│       └── [old versions...]
├── VERSION_HISTORY.md
└── README.md
```

---

## 🎯 Common Scenarios

### Scenario 1: Bug Fix Release (1.0.0 → 1.0.1)
1. Fix bug in code
2. Update manifests to 1.0.1
3. Create RELEASE_NOTES highlighting the fix
4. Run build process
5. Git tag as v1.0.1

### Scenario 2: Feature Release (1.0.1 → 1.1.0)
1. Add new features
2. Test thoroughly
3. Update manifests to 1.1.0
4. Create RELEASE_NOTES with features listed
5. Run build process
6. Git tag as v1.1.0

### Scenario 3: Major Release (1.9.9 → 2.0.0)
1. Major refactoring or breaking changes
2. Update manifests to 2.0.0
3. Clear release notes about breaking changes
4. Run build process
5. Possible migration guide in RELEASE_NOTES
6. Git tag as v2.0.0

---

## ✅ Pre-Release Checklist

Before starting build process, verify:

- [ ] Code changes are complete
- [ ] All bugs are fixed
- [ ] No uncommitted changes exist
- [ ] Tests are passing (if applicable)
- [ ] Version number is decided (X.Y.Z format)
- [ ] Release notes outline is prepared
- [ ] No merge conflicts
- [ ] Latest from GitHub is pulled

---

## 📝 RELEASE_NOTES.md Template

```markdown
# Rays AJAX Search - Release Notes vX.Y.Z

**Release Date:** YYYY-MM-DD
**Status:** [Alpha/Beta/Production]
**Type:** [Bug Fix/Feature/Maintenance]

## Fixed Issues
- Issue #N: Description
- Issue #M: Description

## New Features
- Feature 1: Description
- Feature 2: Description

## Components
- com_ajaxsearch_X.Y.Z.zip
- mod_raysajaxsearch_X.Y.Z.zip

## Installation
[See INSTALLATION_QUICK_START.txt]

## Known Limitations
- Item 1
- Item 2
```

---

## 🔗 Related Files

| File | Purpose |
|------|---------|
| [BUILD_PROCESS.md](BUILD_PROCESS.md) | Detailed step-by-step process |
| [build.sh](build.sh) | Automated packaging script |
| [builds/VERSION_HISTORY.md](builds/VERSION_HISTORY.md) | All version history |
| [builds/releases/](builds/releases/) | Released versions |
| [builds/latest/](builds/latest/) | Current production version |

---

## 🆘 If Something Goes Wrong

### Issue: Wrong version number
**Fix:** Update manifest files immediately and rebuild

### Issue: Missing files
**Fix:** Check file locations against checklist

### Issue: Git tag failed
**Fix:** Verify git is working with `git status`

### Issue: Zip files corrupt
**Fix:** Delete and recreate with zip command

---

## 📞 Questions?

1. Check [BUILD_PROCESS.md](BUILD_PROCESS.md) first
2. Review [builds/VERSION_HISTORY.md](builds/VERSION_HISTORY.md) for examples
3. Look at previous release folders for patterns

---

## 🎓 Learning from Examples

Study the v1.0.0 release:
```
builds/releases/v1.0.0/
├── com_ajaxsearch_1.0.0.zip
├── mod_raysajaxsearch_1.0.0.zip
├── RELEASE_NOTES.md ← Example of good release notes
├── INSTALLATION_GUIDE.md
├── INSTALLATION_QUICK_START.txt
└── README.md
```

Use this as a template for future releases.

---

## 🔄 After Release

User may ask about:
- Moving to next issue → Review [../GitHub](../GitHub) issues
- Testing the release → Follow installation guide
- Creating next version → Use this same process

---

**REMEMBER:**
- Always reference BUILD_PROCESS.md
- Follow the 7-step process every time
- Update documentation every release
- Use semantic versioning
- Keep history for reference
- Verify everything before considering done

---

**Last Updated:** 2026-01-11  
**Version:** 1.0  
**Review Date:** 2026-02-11
