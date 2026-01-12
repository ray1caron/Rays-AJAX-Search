# Versioning Guidelines

## Overview

This project uses **Semantic Versioning (SemVer)** in the format: `MAJOR.MINOR.PATCH`

Current version: **1.0.0**

---

## Version Components

### PATCH (X.Y.Z)
**Increment when:** Bug fixes, security patches, or build/deployment fixes
- Code logic changes that fix errors
- Security vulnerabilities patched
- Packaging/distribution issues fixed (before first deployment)
- Documentation corrections

**Examples:**
- `1.0.0` → `1.0.1`: Fixed SQL injection vulnerability
- `1.0.0` → `1.0.1`: Fixed search not respecting access control levels
- `1.0.0` → `1.0.1`: Fixed packaging manifest issue (before production deployment)

**Rules:**
- ✅ Increment PATCH when fixing something that was broken
- ❌ Do NOT increment PATCH for packaging issues after deployment
- ✅ Do increment PATCH for code-related bug fixes after deployment

---

### MINOR (X.Y.Z)
**Increment when:** New features, enhancements, or functionality additions
- New search filters or options
- New UI components
- Additional configuration options
- Performance improvements (backward compatible)
- Database schema additions (backward compatible)

**Examples:**
- `1.0.0` → `1.1.0`: Add advanced search filters
- `1.0.0` → `1.1.0`: Add search result highlighting
- `1.0.0` → `1.1.0`: Add caching mechanism
- `1.0.0` → `1.1.0`: Add custom field search support

**Rules:**
- ✅ Increment MINOR when adding new features
- ✅ All previous functionality must still work (backward compatible)
- ✅ Users can upgrade without breaking their sites
- ❌ Do NOT break existing configurations or APIs

---

### MAJOR (X.Y.Z)
**Increment when:** Breaking changes, major rewrites, or incompatible updates
- Removed features or endpoints
- Database schema changes (not backward compatible)
- Configuration file format changes
- PHP requirement changes
- Joomla version requirement changes
- API changes that require user action

**Examples:**
- `1.0.0` → `2.0.0`: Drop PHP 7.4 support, require PHP 8.0+
- `1.0.0` → `2.0.0`: Completely rewrite search engine
- `1.0.0` → `2.0.0`: Change configuration file format
- `1.0.0` → `2.0.0`: Merge component and module into single component

**Rules:**
- ✅ Increment MAJOR when making breaking changes
- ✅ Clearly document what broke and why
- ✅ Provide migration guides for users
- ❌ Do NOT make MAJOR changes without strong justification

---

## Decision Tree

Use this flowchart to determine which version to increment:

```
Did the code change?
  ├─ NO (packaging/docs only, pre-deployment)
  │  └─ Keep same version (1.0.0)
  │
  └─ YES
     ├─ Is it a FIX for something broken?
     │  └─ PATCH increment → 1.0.1
     │
     ├─ Is it a NEW feature (backward compatible)?
     │  └─ MINOR increment → 1.1.0
     │
     └─ Is it a BREAKING change?
        └─ MAJOR increment → 2.0.0
```

---

## Real-World Scenarios

### Scenario 1: Bug Fix Before Deployment ✅
**Situation:** Package manifest file was missing from ZIP before first user install

**Decision:** Keep as `1.0.0`
- Code never changed, just packaging
- Never reached production
- Getting it right the first time doesn't warrant a version bump
- Release notes mention "Fixed package structure"

**Action:** Update RELEASE_NOTES.md, keep v1.0.0

---

### Scenario 2: Critical Security Bug After Deployment ✅
**Situation:** SQL injection vulnerability discovered in search module after v1.0.0 installation

**Decision:** Bump to `1.0.1`
- Code changed (security fix)
- Already deployed to users
- Users need to update to get the fix
- Patch version signals "bug fix, safe to upgrade"

**Action:** 
- Fix code
- Update version in both manifest files (1.0.0 → 1.0.1)
- Run `build.sh` to create v1.0.1 package
- Create RELEASE_NOTES.md for v1.0.1

---

### Scenario 3: Add New Search Filter Option ✅
**Situation:** Add ability to search custom fields (new feature)

**Decision:** Bump to `1.1.0`
- New functionality added
- No existing features removed or changed
- Users can upgrade safely
- Will require new configuration options

**Action:**
- Add feature code
- Update version in both manifest files (1.0.0 → 1.1.0)
- Add configuration option to XML manifest
- Run `build.sh` to create v1.1.0 package
- Document new feature in RELEASE_NOTES.md

---

### Scenario 4: Drop PHP 7.4 Support ✅
**Situation:** Refactor to use PHP 8.0+ only features for better performance

**Decision:** Bump to `2.0.0`
- Breaking change: PHP version requirement changed
- Users on PHP 7.4 can't upgrade
- Needs prominent documentation
- Users must plan migration

**Action:**
- Update minimum PHP version in manifest (7.4 → 8.0)
- Update all code to use PHP 8.0+ syntax
- Create MIGRATION_GUIDE.md explaining breaking change
- Update version to 2.0.0 in both manifest files
- Run `build.sh` to create v2.0.0 package
- Create prominent RELEASE_NOTES.md entry

---

## Version Numbering Rules

### When to Increment
| Type | Trigger | Current → Next | Safe to Upgrade |
|------|---------|-----------------|----------------|
| PATCH | Bug fix, security fix | 1.0.0 → 1.0.1 | ✅ Yes, always |
| MINOR | New feature (backward compatible) | 1.0.0 → 1.1.0 | ✅ Yes, always |
| MAJOR | Breaking change | 1.0.0 → 2.0.0 | ❌ No, requires planning |

### When NOT to Increment
| Scenario | Example | Version |
|----------|---------|---------|
| Pre-deployment bug fix | Missing manifest file in ZIP | Keep v1.0.0 |
| Documentation updates | Fix typo in README | Keep v1.0.0 |
| Code comments | Clarify code intent | Keep v1.0.0 |
| Local development | Testing new features locally | Keep v1.0.0 |

---

## Implementation Checklist

When creating a new version, follow this checklist:

### Pre-Release
- [ ] Code changes completed and tested
- [ ] Determine version increment using Decision Tree
- [ ] Update version in `/mod_raysajaxsearch/mod_raysajaxsearch.xml`
- [ ] Update version in `/components/com_ajaxsearch/com_ajaxsearch.xml` (if applicable)
- [ ] Update version in `/package.json`
- [ ] Create/update release notes in `builds/releases/vX.Y.Z/RELEASE_NOTES.md`

### Build
- [ ] Run `./build.sh` to create packages
- [ ] Verify packages created successfully
- [ ] Verify manifest files included in packages
- [ ] Copy to `builds/latest/` for easy access

### Release
- [ ] Tag in Git: `git tag vX.Y.Z`
- [ ] Push to GitHub: `git push origin vX.Y.Z`
- [ ] Create GitHub Release page
- [ ] Upload packages to release

### Post-Release
- [ ] Update `builds/VERSION_HISTORY.md`
- [ ] Announce changes to users
- [ ] Monitor for bug reports

---

## Special Cases

### Pre-Release Versions (0.x.x)
- Until reaching 1.0.0, all versions are considered pre-release
- Breaking changes do NOT require MAJOR increment
- Example: 0.9.0 → 0.9.1 → 0.10.0 → 1.0.0

### Hotfixes
- Urgent critical bug: Use PATCH increment immediately
- Example: If v1.0.0 has critical bug, release 1.0.1 ASAP
- Do NOT wait for next planned release

### Release Candidates
- Optional: Use pre-release notation for testing
- Format: `1.0.0-rc1` (release candidate 1)
- Example flow: 1.0.0-rc1 → 1.0.0-rc2 → 1.0.0 (final)
- Not currently used in this project

---

## Version History

| Version | Release Date | Status | Notes |
|---------|-------------|--------|-------|
| 1.0.0 | Jan 11, 2026 | Stable | Initial release with AJAX search functionality |
| 0.9.0 | Dec 2025 | Deprecated | Module only, no component |

---

## Questions to Ask When Determining Version

1. **Did any code change?**
   - If NO → Don't increment
   - If YES → Continue to #2

2. **Is this a fix for something broken?**
   - If YES → PATCH increment
   - If NO → Continue to #3

3. **Is this a new feature?**
   - If YES → MINOR increment
   - If NO → Continue to #4

4. **Is something broken or incompatible?**
   - If YES → MAJOR increment
   - If NO → MINOR increment (default for new features)

---

## References

- [Semantic Versioning Specification](https://semver.org/)
- [SemVer Guidelines](https://semver.org/#summary)

---

## Last Updated
January 11, 2026

## Maintained By
AI Assistant (GitHub Copilot)
