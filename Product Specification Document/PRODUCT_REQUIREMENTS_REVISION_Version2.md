# Product Requirements Document — Revised (with Admin UI)

Revision: 2.3  
Date: 2026-01-12  
Owner: ray1caron

## Summary of changes

This revision extends the PRD to require a comprehensive Administrator UI in the Joomla component backend. The admin UI controls runtime behavior, content sources, exclusions, scoring weights, cache/backends, analytics behavior, security, and developer options. The UI is the authoritative configuration surface for the app.

---

## Admin UI — Features & Options (detailed)

### 1. Pages / Tabs in Admin UI
- Dashboard / Summary
  - Health checks, cache stats, sample queries, recent errors
- General Settings
  - Enable/Disable component
  - Default results per page
  - Min query length, max results
  - Module default options
- Content Sources
  - Multi-select enabled types (Articles, SP Page Builder, Contacts, K2, VirtueMart, Other)
  - Per-type toggles (index custom fields, include images, include drafts)
- Exclusions & Inclusion
  - Exclude menu items (multi-select)
  - URL patterns (glob/regex) exclude list
  - Category and content ID exclusion lists
  - Inclusion allowlist
- Scoring & Relevance
  - Field weights (title, introtext, fulltext, meta, custom fields, tags)
  - Boost rules: recency (days), popularity multiplier
  - Phrase and proximity toggles
- Cache & Performance
  - Cache backend (DB/Redis/Memcached)
  - Suggestion TTL, Results TTL
  - Cache size estimates + cleanup controls
  - Caching on save (hook enable/disable)
- Analytics
  - Enable/disable analytics
  - Sampling rate (0–100%)
  - Data retention days
  - Export CSV and clear analytics button
- Security & API
  - CORS whitelist
  - Rate limiting settings (suggestion and search endpoints)
  - API key management for external calls
  - Debug log level (production default = off)
- Advanced / Developer
  - Stop words list
  - Tokenizer/stemmer settings
  - Import/Export configuration (JSON)
  - Re-index (full/incremental)
  - Diagnostics (clear caches, run test query)
- Maintenance
  - Clear caches (all / results / suggestions / SP cache)
  - Re-index queue controls
  - Backup & Restore configs

### 2. Schema & Persistence
- Store admin settings in `#__ajaxsearch_config`:
  - `component` (e.g., 'global' or 'analytics')
  - `param_name`
  - `param_value`
  - `param_type` (int|bool|array|string|json)
- Provide a config model that loads and validates these values and caches them in memory for request runtime.

### 3. UX & Controls
- Use Joomla form fields for:
  - Multi-select (enabled content types)
  - Repeater fields for exclusions
  - Sliders for weights
  - Numeric inputs for TTLs and sampling
  - Textareas for regex/stop-words
  - Buttons for actions (Clear Cache, Re-index)
- Provide in-page help tooltips and examples for regex/URL pattern usage.

### 4. Access Control
- UI accessible only to users with `core.manage` for `com_ajaxsearch`.
- Sensitive actions (re-index, export, clear analytics) limited to `core.admin` or `super user`.

### 5. Validation & Safety
- Validate regex patterns server-side and do not accept invalid regex.
- Provide preview results for exclusion rules (search test tool).
- Require confirmation modal for destructive actions (clear caches, re-index full).

---

## Acceptance Criteria (Admin UI)

- Admin can toggle content types on/off and changes are respected by subsequent searches.
- Admin can add an exclusion pattern and the pattern prevents results from showing for a matching page.
- Admin can set field weights and see changed ordering in sample queries (test runner).
- Admin can switch cache backend (if Redis available) via dropdown and the component uses it.
- Admin can set analytics sampling rate and observe that recorded analytics reflect the rate.
- UI actions (clear cache, re-index) run and show success/failure messages.

---

## Non-Functional Requirements (re-iterated)

- Admin UI must be responsive and accessible (keyboard friendly).
- Large actions must be asynchronous and show progress (via AJAX in admin panel).
- Audit log: admin actions should be logged with user, timestamp, and action details.

---

## Implementation Notes & Dependencies

- Admin UI is implemented using Joomla administrator MVC (administrator/components/com_ajaxsearch/).
- Use Joomla Form XML for forms and validation.
- UI should re-use the same models/providers as the site component to avoid code duplication.
- Provide helper endpoints in admin to run test queries against current config.

---

## Next Steps

- Implement Admin UI skeleton in `administrator/components/com_ajaxsearch/`.
- Migrate and centralize settings reading/writing into `src/Models/ConfigModel.php`.
- Wire module to read effective runtime config and expose to JS.
- Build acceptance tests around admin operations.

---

End of PRD revision (Admin UI included).