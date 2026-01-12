# Rays AJAX Search — Software Architecture (Revised v1.2)

Date: 2026-01-12  
Author: Architecture Assistant (prepared for ray1caron)  
Status: Draft — Includes Suggestion UI + Results Page + Administrator UI

## Executive Summary (updated)

Rays AJAX Search is a Joomla extension suite providing:
- Inline typeahead suggestions (low-latency) in the module UI.
- A canonical, server-rendered Search Results Page for full, paginated results (SEO and shareability).
- An Administrator UI (component backend) that allows administrators to configure content sources, exclusions, ranking weights, cache and performance settings, security options, and analytics settings.

This document defines the canonical architecture, key modules, the Admin UI surface, API contract, packaging, operational best-practices, and a roadmap to commercial viability.

---

## High-Level Architecture

Components:
- Frontend Module (site module)
  - Path: `mod_raysajaxsearch/`
  - Responsibility:
    - Present search input + accessible markup, pass configuration to JS via `addScriptOptions`.
    - Provide `suggestUrl`, `ajaxUrl`, and `resultsPageUrl`.
    - Provide non-JS fallback form action to the Search Results Page.
    - Debounce and display typeahead suggestions (inline dropdown with mobile/touch support).

- Component (server — site and admin)
  - Path: `components/com_ajaxsearch/`
    - Site-facing:
      - `src/Component/suggest.php` — Suggestion API (fast, cached).
      - `src/Component/ajax.php` — Full search JSON API.
      - `site/views/results` — Joomla view for Search Results Page (server-rendered).
    - Admin-facing:
      - `admin/controllers/*`, `admin/models/*`, `admin/views/*` — Administrator UI for configuration.
    - Libraries:
      - `src/Database/Adapter.php`
      - `src/Cache/BasicCache.php`
      - `src/Search/*` (engines)
      - `src/Parser/SPParser.php`
      - `src/Models/SearchModel.php`

- Database
  - Tables:
    - `#__ajaxsearch_cache` — cached JSON results
    - `#__ajaxsearch_sp_cache` — parsed SP Page Builder content
    - `#__ajaxsearch_analytics` — analytics records
    - `#__ajaxsearch_config` — saved administrator configuration entries
  - Notes: config entries modelled as (component, param_name, param_value, param_type) for flexibility.

- Optional infra
  - Redis/Memcached for cache, queue (Redis/RabbitMQ) for analytics ingestion at scale.

---

## Admin UI — Overview & Responsibilities

The Administrator UI (in the component admin area) is the authoritative place for configuring runtime behavior. It must include the following logical sections (each a tab/page in the admin UI):

1. General Settings
   - Site-wide enable/disable
   - Default results per page (pagination)
   - Min query length, max results
   - Default language handling (detect / force)
   - Module placement and caching defaults

2. Content Sources
   - Toggle enabled content types (checkboxes):
     - Joomla Articles (com_content)
     - SP Page Builder pages
     - Joomla core content types (Contacts, Categories, Custom Components if configured)
     - K2, VirtueMart, other third-party (optional plugin-based connectors)
   - Configure per-content-type behaviours:
     - Enable/disable custom fields indexing
     - Include/exclude unpublished content
     - Access-level filtering (respect Joomla ACL)

3. Exclusions & Inclusion Rules
   - Exclude specific menu items, URL patterns (glob/regex), Joomla folders (e.g., /administrator/), or content IDs/categories
   - Include explicit allow list (pages that should always be indexed)
   - Configure index crawler exclusions (robots-like rules)

4. Relevance & Scoring
   - Field weights (numeric sliders/input):
     - title, introtext, fulltext, custom_fields, meta_keywords, meta_description, alias, category_title, tags
   - Boost rules:
     - recency boost (age window), popularity boost (analytics-based)
     - content-type bonuses (SP page vs article)
   - Phrase/proximity settings (enable/disable)

5. Cache & Performance
   - Cache backend selection: DB (default) / Redis / Memcached
   - Cache TTLs: suggestions TTL, full-results TTL
   - Cache size caps and eviction policy (if DB, a TTL + tombstone cleanup)
   - Enable/disable cache invalidation hooks (on content save events)
   - Analytics ingestion mode: sync / queued / sampled + sampling rate

6. Security & API
   - CORS allowed origins (whitelist)
   - Rate limiting settings: requests per second (global/suggestion endpoint)
   - API key management (optional) for external access to `ajax.php` (create/regenerate API keys)
   - Debug/log verbosity toggle (admin-only)
   - CSRF and token requirements for admin actions

7. Analytics & Insights
   - Enable/disable analytics collection
   - Data retention policy (days)
   - Export analytics (CSV)
   - Trending queries configuration (timeframes)
   - Zero-results tracking and suggested content UI

8. Advanced / Developer
   - Custom stop words list
   - Custom tokenization/stemming settings
   - Thresholds for query validation / sanitization
   - Import/Export configuration (JSON)
   - Reset to defaults

9. Maintenance & Tools
   - Clear cache buttons (suggestions cache, results cache, SP parsed cache)
   - Re-index trigger (full or incremental)
   - Diagnostics page (log viewer, DB checks, sample queries)
   - Health checks & endpoint tests

Access control:
- Only administrators with `core.manage` for `com_ajaxsearch` should see admin UI.
- Some functions (e.g., view logs, run re-index) can be restricted to `super user` only.

Persistence:
- Store configuration in `#__ajaxsearch_config` with `component='global'` or per-component sections. Use Joomla's config/factory where appropriate.

---

## Data Flow & Sequence (updated for admin)

- Admin config changes update `#__ajaxsearch_config` (write-through).
- Component reads config at runtime (SearchModel loads config on construct).
- Module and client JS pick up module-level options via `addScriptOptions` and inline data-attributes.
- Config changes that affect cache/invalidation:
  - Admin triggers “Clear Cache” or “Re-index” which cause cache entries to be invalidated.

---

## UI Pattern Recommendation (typeahead + results page)

- Typeahead (inline) — shows completions; includes “See all results” link.
- Separate Search Results Page (server-rendered) — canonical, SEO friendly, shareable.
- Rationale:
  - SEO: A server-rendered /search page can be crawled and indexed.
  - Accessibility: Page navigation provides stable focus and semantics.
  - Mobile UX: Separate page avoids modal complexities on small screens; easy back-button behavior.
  - Progressive enhancement: Works even without JS.

---

## API Contract (summary)

- `suggest.php` (GET)
  - Input: `q`, `limit`, `type`
  - Output: `{ success: true, suggestions: [ { term, count? } ... ] }`

- `ajax.php` (GET/POST)
  - Input: `q`, `limit`, `offset`, `type`, `debug`
  - Output: `{ success, total, results[], search_time, cached, request }`

- `view=results` (HTML)
  - Query parameters as above; server-rendered result list; SEO metadata.

All endpoints must:
- Return appropriate HTTP status codes.
- Return `Content-Type: application/json` for API endpoints.
- Not return HTML from API endpoints.
- Require auth for admin/debug endpoints.

---

## Caching, Analytics & Performance Notes

- Suggestion cache: short TTL, heavy caching, likely Redis in production.
- Full results cache: configurable TTL; consider storing only summary/ids to reduce DB size.
- Analytics: default sync for low-traffic; queued ingestion for high-volume sites.
- Rate-limit suggestions endpoint at app level; suggest Cloud/host-level protection in addition.

---

## Packaging & Admin Integration

- Admin views and controllers must be part of the component admin area: `administrator/components/com_ajaxsearch/`.
- Admin UI should be built using Joomla MVC patterns and respect Joomla ACL.
- Provide import/export of configuration as JSON with versioning.

---

## Roadmap & Next Steps

- Phase 1: Implement admin UI skeleton (settings pages), canonical Search Results Page view, Suggest and Ajax endpoints as stubs, and update module to use `suggestUrl`, `ajaxUrl`, `resultsPageUrl`.
- Phase 2: Implement config-driven indexing (enable/disable content types), caching backend abstraction, and analytics queue.
- Phase 3: Harden security, CI, performance tuning, QA, packaging.

---

## Appendix: Admin UI Options Recommendation (concise)

1. Toggles / Multi-selects:
   - Enable content types: Articles, SP Page Builder, Contacts, K2, VirtueMart, Other Components via plugin.
   - Enable custom fields indexing: (checkbox per content type).
   - Expose per-content-type access-level configuration.

2. Inclusion / Exclusion:
   - Exclude menu items (multi-select)
   - Exclude URL patterns (allow glob & regex)
   - Exclude categories or content IDs

3. Scoring & Weights:
   - Numeric input or slider for: title, introtext, fulltext, custom_fields, meta_keywords, meta_description, alias, category_title, tags
   - Recency weight, popularity weight
   - Enable proximity scoring

4. Cache & Performance:
   - Cache backend (DB/Redis/Memcached)
   - TTLs for suggestions and results
   - Cache purging controls & manual purge buttons

5. Analytics & Privacy:
   - Enable analytics (checkbox)
   - Sampling rate (0-100%)
   - Auto-delete analytics older than N days
   - Export CSV button

6. Security:
   - Allowed CORS origins (multi-line)
   - Rate limit (requests/min) for suggestion & search endpoints
   - API keys management (create/regenerate)
   - Debug logging toggle

7. Advanced:
   - Stop words list (textarea)
   - Additional tokenizer/stemming toggles
   - Import/export config (JSON)
   - Re-index options (full/incremental)

---

End of Architecture (Admin UI integrated).