# Rays AJAX Search - Product Requirements Document (Revision)

## Version: 1.0
**Last Updated:** January 2026
**Status:** Production-Ready Implementation

---

## Executive Summary

This document specifies the production-ready implementation of Rays AJAX Search for Joomla 5/6. The system provides:

1. **Typeahead suggestions** for instant feedback
2. **Search Results Page** for full results with SEO-friendly URLs
3. **Administrator UI** for configuration and management
4. **Multi-source search** (Joomla articles + SP Page Builder pages)
5. **DB-backed caching** for performance
6. **Analytics recording** for insights

The implementation is based on Session2.1 codebase (canonical) with full PHP 8.x compatibility.

---

## User Stories

### End User Stories

#### US-1: Search with Typeahead Suggestions
**As a** site visitor  
**I want to** see search suggestions as I type  
**So that** I can quickly find what I'm looking for

**Acceptance Criteria:**
- [ ] Suggestions appear after typing 2+ characters
- [ ] Suggestions are debounced (300ms default)
- [ ] Suggestions show up to 5 results
- [ ] Clicking a suggestion navigates to Search Results Page
- [ ] Suggestions are cached for 1 minute
- [ ] Non-JSON responses handled gracefully

#### US-2: Navigate to Search Results Page
**As a** site visitor  
**I want to** see full search results on a dedicated page  
**So that** I can browse results, share the link, and use browser history

**Acceptance Criteria:**
- [ ] Form submission navigates to results page
- [ ] URL contains query parameter (e.g., `?q=search+term`)
- [ ] Results page shows paginated results
- [ ] Results page is SEO-friendly
- [ ] Browser back/forward buttons work correctly

#### US-3: Search Multiple Content Types
**As a** site visitor  
**I want to** search both articles and SP Page Builder pages  
**So that** I can find content across the entire site

**Acceptance Criteria:**
- [ ] Search includes Joomla articles by default
- [ ] Search includes SP Page Builder pages by default
- [ ] Content types can be toggled by admin
- [ ] Results indicate content type
- [ ] Relevance scoring works across types

### Administrator Stories

#### US-4: Configure Content Sources
**As an** administrator  
**I want to** toggle which content types are searchable  
**So that** I can control what users can find

**Acceptance Criteria:**
- [ ] Toggle for Joomla articles
- [ ] Toggle for SP Page Builder pages
- [ ] Settings saved to database
- [ ] Settings respected by search engine
- [ ] UI clearly indicates current state

#### US-5: Configure Analytics Recording
**As an** administrator  
**I want to** enable/disable analytics recording  
**So that** I can comply with privacy requirements

**Acceptance Criteria:**
- [ ] Toggle for analytics recording
- [ ] Setting saved to database
- [ ] When disabled, no analytics data recorded
- [ ] When enabled, all searches tracked

#### US-6: Configure Cache Settings
**As an** administrator  
**I want to** configure cache time-to-live (TTL)  
**So that** I can balance freshness and performance

**Acceptance Criteria:**
- [ ] Input field for cache TTL (seconds)
- [ ] Default: 300 seconds (5 minutes)
- [ ] Range: 0-3600 seconds
- [ ] Setting saved to database
- [ ] Setting respected by cache system

#### US-7: Clear Search Cache
**As an** administrator  
**I want to** manually clear the search cache  
**So that** I can force fresh results immediately

**Acceptance Criteria:**
- [ ] "Clear Cache" button in admin UI
- [ ] Confirmation dialog before clearing
- [ ] All cache entries removed
- [ ] Success/error message displayed

---

## Functional Requirements

### FR-1: Frontend Module (mod_raysajaxsearch)

#### FR-1.1: Search Form
- Render accessible search form with proper ARIA labels
- Include `action` attribute pointing to Search Results Page
- Support keyboard navigation (Tab, Enter, Escape)
- Display search icon in submit button

#### FR-1.2: Typeahead Suggestions
- Call `suggestUrl` endpoint with debounced input
- Display suggestions in dropdown below search field
- Highlight query terms in suggestions
- Support keyboard navigation (Arrow keys, Enter, Escape)
- Close suggestions on blur or Escape

#### FR-1.3: Form Submission
- On Enter or submit button click, navigate to `resultsPageUrl`
- Include query parameter in URL
- Include selected content types (if filtered)
- Maintain accessibility (screen readers)

### FR-2: Backend Endpoints

#### FR-2.1: ajax.php (Full Search)
- Accept query parameter `q` (required, min 2 chars)
- Accept `limit` parameter (default: 10, max: 100)
- Accept `offset` parameter (default: 0)
- Accept `types` parameter (array of content types)
- Accept `action` parameter (search, trending, analytics)
- Return JSON with proper Content-Type header
- Include response schema:
  ```json
  {
    "success": true|false,
    "results": [...],
    "total": 123,
    "query": "sanitized query",
    "cached": true|false,
    "search_time": 0.45,
    "error": "error message if failed"
  }
  ```
- Call `jexit()` to prevent HTML template append
- Handle errors gracefully (no stack traces)

#### FR-2.2: suggest.php (Typeahead Suggestions)
- Accept query parameter `q` (required, min 2 chars)
- Accept `limit` parameter (default: 5, max: 10)
- Return JSON with proper Content-Type header
- Include response schema:
  ```json
  {
    "success": true|false,
    "suggestions": [
      {"text": "suggestion 1", "count": 10},
      {"text": "suggestion 2", "count": 8}
    ],
    "query": "sanitized query",
    "cached": true|false,
    "error": "error message if failed"
  }
  ```
- Use 1-minute cache TTL
- Call `jexit()` to prevent HTML template append

### FR-3: Core Search Engine (Session2.1)

#### FR-3.1: EnhancedSearchModel
- Implement `search($query, $options)` method
- Implement `getSuggestions($query, $options)` method
- Implement cache key generation (user-aware, language-aware)
- Check cache before performing search
- Store results in cache after search
- Support pagination (limit, offset)
- Support type filtering

#### FR-3.2: EnhancedIntegratedSearch
- Search Joomla articles (if enabled)
- Search SP Page Builder pages (if enabled)
- Merge and sort results by relevance
- Apply enhanced relevance scoring
- Support custom field search (articles)
- Generate suggestions based on search history

#### FR-3.3: Cache (BasicCache)
- Use `#__ajaxsearch_cache` table
- Implement `get($key)` method (check expiration)
- Implement `set($key, $data, $ttl)` method
- Implement `clearAll()` method
- Implement `cleanExpired()` method
- Track cache hits for statistics

### FR-4: Administrator UI

#### FR-4.1: Configuration View
- Display current configuration from database
- Show toggles for content sources (articles, SP pages)
- Show toggle for analytics recording
- Show input for cache TTL (seconds)
- Show input for max results
- Provide "Save" button
- Provide "Clear Cache" button

#### FR-4.2: Configuration Controller
- Handle form submission
- Validate input (TTL range, max results)
- Update/insert configuration to database
- Display success/error messages
- Redirect back to config view after save

#### FR-4.3: Configuration Model
- Implement `getConfig()` method (read from database)
- Implement `saveConfig($data)` method (update/insert)
- Implement `clearCache()` method (delete all cache entries)
- Handle database errors gracefully

---

## Non-Functional Requirements

### NFR-1: Performance
- Search results returned in < 500ms (cached)
- Search results returned in < 2s (uncached)
- Suggestions returned in < 200ms
- Support for 100+ concurrent searches
- Cache hit rate > 70%

### NFR-2: Scalability
- Support databases with 10,000+ articles
- Support databases with 1,000+ SP Page Builder pages
- Support 1,000+ searches per day
- Cache table size < 100MB

### NFR-3: Compatibility
- Joomla 5.x, 6.x
- PHP 8.0, 8.1, 8.2, 8.3
- MySQL 5.7+, 8.0+
- MariaDB 10.2+

### NFR-4: Security
- SQL injection prevention (prepared statements)
- XSS prevention (output escaping)
- CSRF protection (form tokens)
- No stack traces in production
- Input validation and sanitization

### NFR-5: Accessibility
- WCAG 2.1 Level AA compliance
- Keyboard navigation support
- Screen reader support (ARIA labels)
- Focus management
- High contrast support

### NFR-6: Maintainability
- PSR-12 code style (PHP)
- ESLint + Prettier (JavaScript)
- Inline documentation (PHPDoc)
- Separation of concerns (MVC)
- Unit test coverage > 70% (future)

---

## Data Requirements

### DR-1: Database Tables

#### Table: #__ajaxsearch_cache
- `id` (INT, PK, AUTO_INCREMENT)
- `query_hash` (CHAR(32), UNIQUE with language/user_group)
- `query_text` (VARCHAR(255))
- `results` (MEDIUMTEXT, JSON)
- `hits` (INT, default 0)
- `created` (DATETIME)
- `expires` (DATETIME)
- `language` (CHAR(7))
- `user_group` (VARCHAR(255))

#### Table: #__ajaxsearch_sp_cache
- `id` (INT, PK, AUTO_INCREMENT)
- `sppagebuilder_id` (INT, UNIQUE)
- `parsed_content` (MEDIUMTEXT)
- `content_hash` (VARCHAR(32))
- `parsed_date` (DATETIME)
- `version` (VARCHAR(20))

#### Table: #__ajaxsearch_analytics
- `id` (INT, PK, AUTO_INCREMENT)
- `query` (VARCHAR(255), INDEXED)
- `results_count` (INT)
- `user_id` (INT, NULLABLE)
- `session_id` (VARCHAR(128))
- `ip_address` (VARCHAR(45))
- `user_agent` (TEXT)
- `search_time` (FLOAT)
- `timestamp` (DATETIME, INDEXED)
- `zero_results` (TINYINT, default 0)

#### Table: #__ajaxsearch_config
- `id` (INT, PK, AUTO_INCREMENT)
- `param_name` (VARCHAR(100), UNIQUE with component)
- `param_value` (TEXT)
- `param_type` (VARCHAR(20), enum: string|int|bool|float)
- `component` (VARCHAR(50), default 'global')

### DR-2: Configuration Parameters

Default configuration in `#__ajaxsearch_config`:
- `cache_ttl`: 300 (int)
- `search_articles`: 1 (bool)
- `search_sppages`: 1 (bool)
- `search_customfields`: 1 (bool)
- `title_weight`: 10 (int)
- `content_weight`: 3 (int)
- `max_results`: 50 (int)
- `enable_analytics`: 1 (bool)
- `version`: 1.0.0 (string)

---

## API Specifications

### Endpoint: suggest.php

**Method:** GET  
**URL:** `/components/com_ajaxsearch/src/Component/suggest.php`

**Query Parameters:**
- `q` (string, required): Search query (min 2 chars)
- `limit` (int, optional): Max suggestions (default: 5, max: 10)

**Response (Success):**
```json
{
  "success": true,
  "suggestions": [
    {"text": "example search", "count": 15},
    {"text": "example article", "count": 10}
  ],
  "query": "example",
  "cached": false
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Query must be at least 2 characters long"
}
```

### Endpoint: ajax.php

**Method:** GET  
**URL:** `/components/com_ajaxsearch/src/Component/ajax.php`

**Query Parameters:**
- `q` (string, required): Search query (min 2 chars)
- `limit` (int, optional): Results per page (default: 10, max: 100)
- `offset` (int, optional): Offset for pagination (default: 0)
- `types` (array, optional): Content types to search (default: all)
- `action` (string, optional): Action type (default: search)

**Response (Success):**
```json
{
  "success": true,
  "results": [
    {
      "id": 123,
      "title": "Example Article",
      "snippet": "This is an example...",
      "url": "/example-article",
      "type": "article",
      "category": "News",
      "date": "2026-01-12",
      "relevance": 0.95
    }
  ],
  "total": 45,
  "query": "example",
  "cached": true,
  "search_time": 0.42
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "An error occurred while processing your request"
}
```

---

## Testing Requirements

### Unit Tests (Future)
- Search model methods
- Cache get/set/clear
- Configuration CRUD
- Relevance scoring

### Integration Tests (Future)
- Full search flow (query → results)
- Suggestions flow
- Admin configuration save
- Cache expiration

### Manual Testing (Required)
- [ ] Typeahead suggestions appear correctly
- [ ] Form submission navigates to results page
- [ ] Search results are accurate
- [ ] Admin UI saves configuration
- [ ] Cache clear button works
- [ ] Analytics recording works (when enabled)
- [ ] Non-JSON responses handled gracefully
- [ ] Error messages display correctly

---

## Deployment Requirements

### Installation Steps
1. Install component via Joomla installer
2. Database tables created automatically
3. Default configuration inserted
4. Enable module (mod_raysajaxsearch)
5. Configure content sources in admin UI
6. Test search functionality

### Configuration Checklist
- [ ] Content sources enabled (articles, SP pages)
- [ ] Analytics recording enabled/disabled
- [ ] Cache TTL set appropriately
- [ ] Max results configured
- [ ] Module positioned correctly
- [ ] Search Results Page menu item created

---

## Success Metrics

- **User Engagement:** Increased search usage by 30%
- **Performance:** 90% of searches return in < 500ms
- **Accuracy:** User satisfaction > 80%
- **Reliability:** Uptime > 99.9%
- **Cache Efficiency:** Hit rate > 70%

---

**Document Version:** 1.0  
**Effective Date:** January 2026  
**Review Date:** February 2026
