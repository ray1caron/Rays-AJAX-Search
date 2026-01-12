# Rays AJAX Search - Architecture Overview

## Version: 1.0
**Last Updated:** January 2026

---

## Overview

Rays AJAX Search is a production-ready, advanced search solution for Joomla 5/6 that provides typeahead suggestions, integrated search results, and comprehensive admin configuration. The system is built on the Session2.1 codebase (canonical implementation) with full PHP 8.x compatibility.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Module)                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  mod_raysajaxsearch                                   │   │
│  │  - Search form with typeahead                         │   │
│  │  - Inline suggestions dropdown                        │   │
│  │  - Navigation to Search Results Page                  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend Endpoints                         │
│  ┌──────────────────┐        ┌──────────────────────────┐   │
│  │  suggest.php     │        │     ajax.php             │   │
│  │  - Typeahead     │        │  - Full search results   │   │
│  │  - Quick results │        │  - Pagination            │   │
│  │  - Debounced     │        │  - Filtering             │   │
│  └──────────────────┘        └──────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│               Core Search Engine (Session2.1)                │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  EnhancedSearchModel                                  │   │
│  │  - Unified search interface                           │   │
│  │  - Cache management (DB-backed)                       │   │
│  │  - Analytics recording                                │   │
│  │                                                        │   │
│  │  EnhancedIntegratedSearch                             │   │
│  │  - Multi-source search (Articles + SP Pages)          │   │
│  │  - Enhanced relevance scoring                         │   │
│  │  - Custom field search                                │   │
│  │  - Suggestions engine                                 │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer                                │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Database Tables                                      │   │
│  │  - #__ajaxsearch_cache (search results cache)        │   │
│  │  - #__ajaxsearch_sp_cache (SP page content cache)    │   │
│  │  - #__ajaxsearch_analytics (search analytics)        │   │
│  │  - #__ajaxsearch_config (configuration)              │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Administrator UI                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Configuration Panel                                  │   │
│  │  - Content source toggles (Articles, SP Pages)       │   │
│  │  - Analytics recording toggle                        │   │
│  │  - Cache settings (TTL)                              │   │
│  │  - Result limits                                     │   │
│  │  - Cache management (clear cache)                    │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Component Structure

### Frontend Module (mod_raysajaxsearch)

**Location:** `/modules/mod_raysajaxsearch/`

**Files:**
- `mod_raysajaxsearch.php` - Module entry point, passes configuration to template
- `helper.php` - Module helper functions
- `tmpl/default.php` - Search form template with fallback action
- `media/ajaxsearch.js` - JavaScript for typeahead and navigation
- `media/ajaxsearch.css` - Module styles

**Responsibilities:**
1. Render accessible search form
2. Load and configure JavaScript for typeahead
3. Handle form submission (navigate to Search Results Page)
4. Pass URLs: `suggestUrl`, `ajaxUrl`, `resultsPageUrl`

### Backend Component (com_ajaxsearch)

**Location:** `/components/com_ajaxsearch/`

**Files:**
- `src/Component/ajax.php` - Full search endpoint (JSON)
- `src/Component/suggest.php` - Suggestions endpoint (JSON)
- `src/Models/EnhancedSearchModel.php` - Main search model (Session2.1)
- `src/Search/EnhancedIntegratedSearch.php` - Multi-source search engine
- `src/Search/EnhancedRelevanceScorer.php` - Advanced scoring
- `src/Search/CustomFieldSearch.php` - Custom field search
- `src/Search/SuggestionsEngine.php` - Typeahead suggestions
- `src/Cache/BasicCache.php` - DB-backed cache
- `src/Database/Adapter.php` - Database abstraction
- `src/Compatibility/VersionCheck.php` - Joomla version compatibility

**Responsibilities:**
1. Process search requests with proper validation
2. Return JSON responses with correct Content-Type
3. Use DB-backed caching (BasicCache)
4. Call `jexit()` to avoid HTML template append
5. Handle errors gracefully (no stack traces exposed)
6. Record analytics (if enabled)

### Administrator Component (administrator/components/com_ajaxsearch)

**Location:** `/administrator/components/com_ajaxsearch/`

**Files:**
- `controllers/config.php` - Configuration controller
- `models/config.php` - Configuration model (CRUD for #__ajaxsearch_config)
- `views/config/tmpl/default.php` - Configuration form
- `language/en-GB/en-GB.com_ajaxsearch.ini` - Language strings

**Responsibilities:**
1. Provide UI for configuration management
2. Toggle content sources (Joomla articles, SP Page Builder pages)
3. Toggle analytics recording
4. Configure cache settings (TTL)
5. Set result limits
6. Clear cache on demand

## Data Flow

### Typeahead Suggestions Flow

1. User types in search field (debounced)
2. JavaScript calls `suggest.php` with query parameter
3. `suggest.php` checks cache first
4. If not cached, calls `SuggestionsEngine`
5. Returns JSON with suggestions array
6. JavaScript displays suggestions inline
7. User selects suggestion or presses Enter
8. Form navigates to Search Results Page

### Full Search Flow

1. User submits search form
2. Browser navigates to `resultsPageUrl` with query parameter
3. Search Results Page (or AJAX endpoint) calls `ajax.php`
4. `ajax.php` validates query
5. Loads configuration from `#__ajaxsearch_config`
6. Instantiates `EnhancedSearchModel` (Session2.1)
7. Calls `search($query, $options)` with cache check
8. Returns JSON with results array
9. Records analytics (if enabled)

### Admin Configuration Flow

1. Admin navigates to com_ajaxsearch config
2. Loads current settings from `#__ajaxsearch_config`
3. Admin makes changes
4. Saves configuration (updates/inserts to database)
5. Cache can be cleared manually

## Key Design Decisions

### 1. Session2.1 as Canonical Implementation
- **Rationale:** Session2.1 provides the most complete, tested search functionality
- **Benefits:** Enhanced relevance scoring, custom field search, suggestions engine
- **Implementation:** All classes copied from Session2.1 to component structure

### 2. DB-Backed Cache
- **Rationale:** Simple, reliable, no external dependencies
- **Benefits:** Easy to manage, clear from admin UI, works on all hosting
- **Table:** `#__ajaxsearch_cache` with TTL-based expiration

### 3. Separate Endpoints (suggest.php vs ajax.php)
- **Rationale:** Different performance characteristics, caching strategies
- **suggest.php:** Lightweight, 1-minute cache, returns 5 results
- **ajax.php:** Full search, 5-minute cache, pagination support

### 4. Navigation to Results Page (Not Inline)
- **Rationale:** SEO-friendly, shareable URLs, proper browser history
- **Benefits:** Better UX for full results, allows deep linking
- **Fallback:** Form has `action` attribute pointing to results page

### 5. Defensive Error Handling
- **Rationale:** Production-ready requires graceful degradation
- **Benefits:** No stack traces exposed, proper HTTP status codes
- **Implementation:** Try-catch blocks, JSON Content-Type checks

## Security Considerations

1. **Input Validation:** All queries sanitized before database use
2. **SQL Injection Prevention:** Uses prepared statements via DatabaseAdapter
3. **XSS Prevention:** All output escaped in templates
4. **Error Handling:** No stack traces in production responses
5. **Access Control:** Admin UI restricted to authorized users

## Performance Optimizations

1. **Caching Strategy:**
   - Suggestions: 1-minute TTL (frequently changing)
   - Full results: 5-minute TTL (balance freshness/performance)
   - SP Page content: Cached until page modified

2. **Debouncing:**
   - Typeahead: 300ms default
   - Prevents excessive API calls

3. **Pagination:**
   - Default: 10 results per page
   - Configurable via admin UI

4. **Database Indexes:**
   - All cache tables have proper indexes
   - Analytics table indexed for common queries

## Compatibility

- **Joomla:** 5.x, 6.x
- **PHP:** 8.0+
- **MySQL:** 5.7+, 8.0+
- **MariaDB:** 10.2+

## Extension Points

1. **Custom Content Sources:** Add new search engines implementing `AbstractSearchEngine`
2. **Custom Scoring:** Extend `EnhancedRelevanceScorer`
3. **Custom Suggestions:** Extend `SuggestionsEngine`
4. **Custom Cache:** Implement cache interface for Redis, Memcached, etc.

## Monitoring & Analytics

- **Search Analytics Table:** Tracks all searches, results count, zero-result searches
- **Cache Stats:** Hit rates, expiration patterns
- **Trending Searches:** Most popular queries by timeframe

## Future Enhancements

See [ENHANCEMENT_BACKLOG.md](../ENHANCEMENT_BACKLOG.md) for planned features.

---

**Document Version:** 1.0  
**Effective Date:** January 2026  
**Review Date:** February 2026
