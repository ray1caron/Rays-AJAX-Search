/**
 * Rays AJAX Search Module - Main JavaScript
 */

(function() {
    'use strict';
    
    // Module configuration
    const config = {
        minChars: 2,
        typingDelay: 300,
        resultsLimit: 10,
        searchTypes: ['article', 'sppagebuilder'],
        ajaxUrl: '',
        currentPage: 1,
        totalPages: 1,
        totalResults: 0,
        currentQuery: '',
        selectedResult: -1,
        typingTimeout: null,
        isLoading: false,
        keyboardNavigation: true
    };
    
    // DOM elements cache
    let elements = {};
    
    // Initialize module
    function init(moduleId, moduleConfig) {
        // Merge config
        Object.assign(config, moduleConfig);
        
        // Cache DOM elements
        cacheElements(moduleId);
        
        // Set up event listeners
        setupEventListeners();
        
        // Check for initial search query in URL
        checkInitialSearch();
        
        // Enable keyboard navigation
        if (config.keyboardNavigation) {
            enableKeyboardNavigation();
        }
        
        console.log('Rays AJAX Search module initialized:', moduleId);
    }
    
    // Cache DOM elements
    function cacheElements(moduleId) {
        const container = document.getElementById(moduleId);
        if (!container) return;
        
        elements = {
            container,
            form: container.querySelector('.raysajaxsearch-form'),
            input: container.querySelector('.raysajaxsearch-input'),
            button: container.querySelector('.raysajaxsearch-button'),
            resultsContainer: container.querySelector('.raysajaxsearch-results-container'),
            results: container.querySelector('.raysajaxsearch-results'),
            resultsHeader: container.querySelector('.raysajaxsearch-results-header'),
            resultsCount: container.querySelector('.raysajaxsearch-results-count'),
            resultsTime: container.querySelector('.raysajaxsearch-results-time'),
            clearButton: container.querySelector('.raysajaxsearch-clear'),
            loading: container.querySelector('.raysajaxsearch-loading'),
            noResults: container.querySelector('.raysajaxsearch-no-results'),
            error: container.querySelector('.raysajaxsearch-error'),
            errorMessage: container.querySelector('.raysajaxsearch-error-message'),
            pagination: container.querySelector('.raysajaxsearch-pagination'),
            keyboardHint: container.querySelector('.raysajaxsearch-keyboard-hint'),
            typeFilters: container.querySelectorAll('.raysajaxsearch-type-filter')
        };
    }
    
    // Set up event listeners
    function setupEventListeners() {
        if (!elements.form || !elements.input) return;
        
        // Form submission
        elements.form.addEventListener('submit', handleFormSubmit);
        
        // Input typing with debounce
        elements.input.addEventListener('input', handleInput);
        
        // Input focus/blur
        elements.input.addEventListener('focus', showKeyboardHint);
        elements.input.addEventListener('blur', hideKeyboardHint);
        
        // Clear button
        if (elements.clearButton) {
            elements.clearButton.addEventListener('click', clearSearch);
        }
        
        // Type filter changes
        if (elements.typeFilters.length > 0) {
            elements.typeFilters.forEach(filter => {
                filter.addEventListener('change', handleTypeFilterChange);
            });
        }
    }
    
    // Enable keyboard navigation
    function enableKeyboardNavigation() {
        document.addEventListener('keydown', handleKeyboardNavigation);
    }
    
    // Handle form submission
    function handleFormSubmit(e) {
        e.preventDefault();
        const query = elements.input.value.trim();
        
        if (query.length >= config.minChars) {
            search(query, 1);
            elements.input.blur(); // Remove focus after search
        } else {
            showError(`Please enter at least ${config.minChars} characters`);
        }
    }
    
    // Handle input with debounce
    function handleInput(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        if (config.typingTimeout) {
            clearTimeout(config.typingTimeout);
        }
        
        // Hide results if query is too short
        if (query.length < config.minChars) {
            hideResults();
            return;
        }
        
        // Set new timeout for debounced search
        config.typingTimeout = setTimeout(() => {
            if (query.length >= config.minChars) {
                search(query, 1);
            }
        }, config.typingDelay);
    }
    
    // Handle type filter change
    function handleTypeFilterChange() {
        const query = elements.input.value.trim();
        if (query.length >= config.minChars) {
            search(query, 1);
        }
    }
    
    // Handle keyboard navigation
    function handleKeyboardNavigation(e) {
        if (!elements.results || elements.results.style.display === 'none') return;
        
        const results = elements.results.querySelectorAll('.raysajaxsearch-result');
        if (results.length === 0) return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                navigateResults(1, results);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                navigateResults(-1, results);
                break;
                
            case 'Enter':
                if (config.selectedResult >= 0 && config.selectedResult < results.length) {
                    e.preventDefault();
                    const link = results[config.selectedResult].querySelector('a');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                clearSearch();
                break;
                
            case '/':
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    elements.input.focus();
                }
                break;
        }
    }
    
    // Navigate results with arrow keys
    function navigateResults(direction, results) {
        // Remove active class from current selection
        if (config.selectedResult >= 0 && config.selectedResult < results.length) {
            results[config.selectedResult].classList.remove('active');
        }
        
        // Calculate new selection
        config.selectedResult += direction;
        
        // Loop around
        if (config.selectedResult < 0) {
            config.selectedResult = results.length - 1;
        } else if (config.selectedResult >= results.length) {
            config.selectedResult = 0;
        }
        
        // Add active class to new selection
        results[config.selectedResult].classList.add('active');
        
        // Scroll into view if needed
        results[config.selectedResult].scrollIntoView({
            block: 'nearest',
            behavior: 'smooth'
        });
    }
    
    // Check for initial search query in URL
    function checkInitialSearch() {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('q');
        
        if (query && query.length >= config.minChars) {
            elements.input.value = query;
            search(query, 1);
        }
    }
    
    // Show keyboard hint
    function showKeyboardHint() {
        if (elements.keyboardHint) {
            elements.keyboardHint.style.display = 'block';
        }
    }
    
    // Hide keyboard hint
    function hideKeyboardHint() {
        if (elements.keyboardHint) {
            elements.keyboardHint.style.display = 'none';
        }
    }
    
    // Perform search
    async function search(query, page = 1) {
        if (config.isLoading) return;
        
        // Update state
        config.currentQuery = query;
        config.currentPage = page;
        config.selectedResult = -1;
        
        // Show loading state
        showLoading();
        
        // Get selected types
        const selectedTypes = getSelectedTypes();
        
        // Build API URL
        const url = new URL(config.ajaxUrl);
        url.searchParams.append('q', query);
        url.searchParams.append('limit', config.resultsLimit);
        url.searchParams.append('offset', (page - 1) * config.resultsLimit);
        
        if (selectedTypes.length > 0) {
            if (selectedTypes.length === 1) {
                url.searchParams.append('type', selectedTypes[0]);
            } else if (selectedTypes.length < config.searchTypes.length) {
                // If not all types selected, specify them
                url.searchParams.append('type', selectedTypes.join(','));
            }
            // If all types selected, don't send type parameter (defaults to all)
        }
        
        try {
            config.isLoading = true;
            const response = await fetch(url.toString());
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                displayResults(data);
            } else {
                showError(data.error || 'Search failed');
            }
            
        } catch (error) {
            console.error('Search error:', error);
            showError('Search failed. Please try again.');
            
        } finally {
            config.isLoading = false;
            hideLoading();
        }
    }
    
    // Get selected types from checkboxes
    function getSelectedTypes() {
        if (!elements.typeFilters || elements.typeFilters.length === 0) {
            return config.searchTypes;
        }
        
        const selected = [];
        elements.typeFilters.forEach(checkbox => {
            if (checkbox.checked) {
                selected.push(checkbox.value);
            }
        });
        
        return selected;
    }
    
    // Display search results
    function displayResults(data) {
        // Update config
        config.totalResults = data.total || 0;
        config.totalPages = Math.ceil(config.totalResults / config.resultsLimit);
        
        // Clear previous results
        clearResults();
        
        // Show/hide elements based on results
        if (config.totalResults > 0) {
            // Show results header
            if (elements.resultsHeader) {
                elements.resultsHeader.style.display = 'flex';
            }
            
            // Update results count
            if (elements.resultsCount) {
                const start = ((config.currentPage - 1) * config.resultsLimit) + 1;
                const end = Math.min(config.currentPage * config.resultsLimit, config.totalResults);
                elements.resultsCount.textContent = 
                    `Showing ${start}-${end} of ${config.totalResults} results`;
            }
            
            // Update search time
            if (elements.resultsTime && data.search_time) {
                elements.resultsTime.textContent = 
                    `in ${data.search_time.toFixed(2)} seconds`;
            }
            
            // Show clear button
            if (elements.clearButton) {
                elements.clearButton.style.display = 'block';
            }
            
            // Show results container
            if (elements.results) {
                elements.results.style.display = 'flex';
            }
            
            // Render results
            renderResults(data.results);
            
            // Render pagination if needed
            if (config.totalPages > 1) {
                renderPagination();
            } else {
                hidePagination();
            }
            
            // Hide no results and error
            hideNoResults();
            hideError();
            
        } else {
            // No results
            showNoResults();
            hideResultsHeader();
            hidePagination();
        }
        
        // Update URL without page reload
        updateUrl();
    }
    
    // Render results
    function renderResults(results) {
        if (!elements.results || !results || results.length === 0) return;
        
        const template = document.getElementById('raysajaxsearch-result-template');
        if (!template) return;
        
        results.forEach((result, index) => {
            const clone = template.content.cloneNode(true);
            const resultElement = clone.querySelector('.raysajaxsearch-result');
            
            // Set result attributes
            resultElement.setAttribute('data-id', result.id || index);
            resultElement.setAttribute('data-type', result.type || 'unknown');
            
            // Fill in content
            const link = clone.querySelector('.raysajaxsearch-result-link');
            if (link && result.url) {
                link.href = result.url;
                link.setAttribute('title', result.title || '');
            }
            
            const title = clone.querySelector('.raysajaxsearch-result-title-text');
            if (title) {
                title.textContent = result.title || 'Untitled';
            }
            
            const type = clone.querySelector('.raysajaxsearch-result-type');
            if (type) {
                type.textContent = result.type_label || result.type || '';
            }
            
            const snippet = clone.querySelector('.raysajaxsearch-result-snippet');
            if (snippet && result.snippet) {
                snippet.innerHTML = result.snippet;
            }
            
            const category = clone.querySelector('.raysajaxsearch-result-category');
            if (category && result.category) {
                category.textContent = result.category;
                category.style.display = 'inline';
            }
            
            const date = clone.querySelector('.raysajaxsearch-result-date');
            if (date && result.created) {
                const dateObj = new Date(result.created);
                date.textContent = dateObj.toLocaleDateString();
                date.style.display = 'inline';
            }
            
            const relevance = clone.querySelector('.raysajaxsearch-result-relevance');
            if (relevance && result.relevance !== undefined) {
                relevance.textContent = `Relevance: ${Math.round(result.relevance)}%`;
                relevance.style.display = 'inline';
            }
            
            // Add click handler
            resultElement.addEventListener('click', (e) => {
                if (!e.target.closest('a')) {
                    const link = resultElement.querySelector('a');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
            });
            
            elements.results.appendChild(clone);
        });
    }
    
    // Render pagination
    function renderPagination() {
        if (!elements.pagination || config.totalPages <= 1) return;
        
        const template = document.getElementById('raysajaxsearch-pagination-template');
        if (!template) return;
        
        // Clear existing pagination
        elements.pagination.innerHTML = '';
        elements.pagination.style.display = 'block';
        
        const clone = template.content.cloneNode(true);
        const container = clone.querySelector('.raysajaxsearch-pagination-container');
        const prevButton = clone.querySelector('.raysajaxsearch-pagination-prev');
        const nextButton = clone.querySelector('.raysajaxsearch-pagination-next');
        const pagesContainer = clone.querySelector('.raysajaxsearch-pagination-pages');
        
        // Previous button
        if (prevButton) {
            prevButton.disabled = config.currentPage === 1;
            prevButton.addEventListener('click', () => {
                if (config.currentPage > 1) {
                    search(config.currentQuery, config.currentPage - 1);
                }
            });
        }
        
        // Next button
        if (nextButton) {
            nextButton.disabled = config.currentPage === config.totalPages;
            nextButton.addEventListener('click', () => {
                if (config.currentPage < config.totalPages) {
                    search(config.currentQuery, config.currentPage + 1);
                }
            });
        }
        
        // Page numbers
        if (pagesContainer) {
            // Calculate range of pages to show
            let startPage = Math.max(1, config.currentPage - 2);
            let endPage = Math.min(config.totalPages, startPage + 4);
            
            // Adjust start if we're near the end
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            // Create page buttons
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.className = 'raysajaxsearch-pagination-page';
                pageButton.textContent = i;
                pageButton.type = 'button';
                
                if (i === config.currentPage) {
                    pageButton.classList.add('active');
                }
                
                pageButton.addEventListener('click', () => {
                    if (i !== config.currentPage) {
                        search(config.currentQuery, i);
                    }
                });
                
                pagesContainer.appendChild(pageButton);
            }
        }
        
        elements.pagination.appendChild(clone);
    }
    
    // Clear search
    function clearSearch() {
        if (elements.input) {
            elements.input.value = '';
            elements.input.focus();
        }
        
        hideResults();
        hideResultsHeader();
        hidePagination();
        hideNoResults();
        hideError();
        
        // Clear URL parameter
        updateUrl(true);
    }
    
    // Clear results
    function clearResults() {
        if (elements.results) {
            elements.results.innerHTML = '';
        }
    }
    
    // Show loading state
    function showLoading() {
        if (elements.loading) {
            elements.loading.style.display = 'flex';
        }
        
        hideResults();
        hideNoResults();
        hideError();
    }
    
    // Hide loading state
    function hideLoading() {
        if (elements.loading) {
            elements.loading.style.display = 'none';
        }
    }
    
    // Show results
    function showResults() {
        if (elements.results) {
            elements.results.style.display = 'flex';
        }
        
        if (elements.resultsHeader) {
            elements.resultsHeader.style.display = 'flex';
        }
    }
    
    // Hide results
    function hideResults() {
        if (elements.results) {
            elements.results.style.display = 'none';
        }
    }
    
    // Hide results header
    function hideResultsHeader() {
        if (elements.resultsHeader) {
            elements.resultsHeader.style.display = 'none';
        }
        
        if (elements.clearButton) {
            elements.clearButton.style.display = 'none';
        }
    }
    
    // Show no results
    function showNoResults() {
        if (elements.noResults) {
            elements.noResults.style.display = 'block';
        }
    }
    
    // Hide no results
    function hideNoResults() {
        if (elements.noResults) {
            elements.noResults.style.display = 'none';
        }
    }
    
    // Show error
    function showError(message) {
        if (elements.error && elements.errorMessage) {
            elements.errorMessage.textContent = message;
            elements.error.style.display = 'block';
        }
    }
    
    // Hide error
    function hideError() {
        if (elements.error) {
            elements.error.style.display = 'none';
        }
    }
    
    // Show pagination
    function showPagination() {
        if (elements.pagination) {
            elements.pagination.style.display = 'block';
        }
    }
    
    // Hide pagination
    function hidePagination() {
        if (elements.pagination) {
            elements.pagination.style.display = 'none';
        }
    }
    
    // Update URL with search query
    function updateUrl(clear = false) {
        if (!window.history || !window.history.replaceState) return;
        
        const url = new URL(window.location);
        
        if (clear || !config.currentQuery) {
            url.searchParams.delete('q');
        } else {
            url.searchParams.set('q', config.currentQuery);
        }
        
        // Update URL without reload
        window.history.replaceState({}, '', url.toString());
    }
    
    // Get module configuration from Joomla
    function getModuleConfig() {
        // Try to get config from Joomla script options
        const moduleId = Object.keys(Joomla.getOptions() || {})
            .find(key => key.startsWith('mod_raysajaxsearch_'));
        
        if (moduleId) {
            return Joomla.getOptions(moduleId);
        }
        
        // Fallback to data attributes
        const moduleElement = document.querySelector('[data-module-id]');
        if (moduleElement) {
            return {
                id: moduleElement.dataset.moduleId,
                params: {
                    search_types: moduleElement.dataset.searchTypes 
                        ? moduleElement.dataset.searchTypes.split(',') 
                        : ['article', 'sppagebuilder'],
                    min_chars: parseInt(moduleElement.dataset.minChars) || 2,
                    typing_delay: parseInt(moduleElement.dataset.typingDelay) || 300,
                    results_limit: parseInt(moduleElement.dataset.resultsLimit) || 10,
                    ajax_url: moduleElement.dataset.ajaxUrl || 
                        window.location.origin + '/components/com_ajaxsearch/src/Component/ajax.php'
                }
            };
        }
        
        return null;
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const config = getModuleConfig();
        if (config) {
            init('raysajaxsearch-module-' + config.id, config.params);
        }
    });
    
    // Export for debugging
    window.RaysAJAXSearch = {
        init,
        search,
        clearSearch,
        getModuleConfig
    };
    
})();