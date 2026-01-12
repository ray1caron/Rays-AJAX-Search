// Module frontend script (simplified)
// Expects data attributes on module element:
// data-ajax-url, data-suggest-url, data-results-page-url, data-min-chars, data-results-limit
(function () {
    'use strict';

    // Simple debounce helper
    function debounce(fn, wait) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    // Safe JSON parse with fallback logging
    async function fetchJson(url) {
        const res = await fetch(url, { credentials: 'same-origin' });
        const contentType = res.headers.get('content-type') || '';
        const text = await res.text();
        if (!contentType.includes('application/json')) {
            console.error('Non-JSON response from', url, 'response snippet:', text.slice(0, 300));
            throw new Error('Server returned non-JSON response');
        }
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON from', url, 'text:', text.slice(0, 1000));
            throw e;
        }
    }

    function createSuggestionItem(term, container, onSelect) {
        const el = document.createElement('div');
        el.className = 'ajaxsearch-suggestion';
        el.setAttribute('role', 'option');
        el.tabIndex = 0;
        el.textContent = term;
        el.addEventListener('click', () => onSelect(term));
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                onSelect(term);
            }
        });
        container.appendChild(el);
    }

    function initModule(moduleEl) {
        const moduleId = moduleEl.dataset.moduleId;
        const ajaxUrl = moduleEl.dataset.ajaxUrl;
        const suggestUrl = moduleEl.dataset.suggestUrl;
        const resultsPageUrl = moduleEl.dataset.resultsPageUrl;
        const minChars = parseInt(moduleEl.dataset.minChars || '2', 10);
        const resultsLimit = parseInt(moduleEl.dataset.resultsLimit || '10', 10);

        const input = moduleEl.querySelector('input[type="search"]');
        const suggestionsContainer = document.getElementById('ajaxsearch-suggestions-' + moduleId);

        if (!input || !suggestionsContainer) return;

        function hideSuggestions() {
            suggestionsContainer.style.display = 'none';
            suggestionsContainer.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        }

        function showSuggestions() {
            suggestionsContainer.style.display = 'block';
            input.setAttribute('aria-expanded', 'true');
        }

        async function onSelect(term) {
            // when selecting a suggestion, navigate to results page for that term
            const url = new URL(resultsPageUrl, window.location.origin);
            url.searchParams.set('q', term);
            window.location.href = url.toString();
        }

        async function fetchAndShowSuggestions(query) {
            if (!query || query.length < minChars) {
                hideSuggestions();
                return;
            }
            const url = new URL(suggestUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', Math.min(10, resultsLimit));
            try {
                const data = await fetchJson(url.toString());
                if (!data || !Array.isArray(data.suggestions)) {
                    hideSuggestions();
                    return;
                }
                suggestionsContainer.innerHTML = '';
                const suggestions = data.suggestions.map(s => s.term || s);
                suggestions.forEach(s => createSuggestionItem(s, suggestionsContainer, onSelect));
                // "See all results" link
                const seeAll = document.createElement('div');
                seeAll.className = 'ajaxsearch-suggestion see-all';
                seeAll.textContent = 'See all results';
                seeAll.addEventListener('click', () => {
                    const url = new URL(resultsPageUrl, window.location.origin);
                    url.searchParams.set('q', query);
                    window.location.href = url.toString();
                });
                suggestionsContainer.appendChild(seeAll);
                showSuggestions();
            } catch (e) {
                console.error('Suggestion fetch failed', e);
                hideSuggestions();
            }
        }

        const debouncedSuggest = debounce((q) => {
            fetchAndShowSuggestions(q);
        }, 220);

        input.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            if (q.length >= minChars) {
                debouncedSuggest(q);
            } else {
                hideSuggestions();
            }
        });

        // Submit behavior: navigate to results page
        moduleEl.addEventListener('submit', (ev) => {
            ev.preventDefault();
            const q = input.value.trim();
            if (!q) {
                return;
            }
            const url = new URL(resultsPageUrl, window.location.origin);
            url.searchParams.set('q', q);
            // Keep limit/offset if needed
            window.location.href = url.toString();
        });

        // hide suggestions on outside click
        document.addEventListener('click', (e) => {
            if (!moduleEl.contains(e.target)) {
                hideSuggestions();
            }
        });
    }

    // Initialize modules on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        const modules = document.querySelectorAll('[data-module-id]');
        modules.forEach(initModule);
    });
})();