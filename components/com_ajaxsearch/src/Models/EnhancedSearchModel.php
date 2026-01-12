<?php
/**
 * @package     AJAXSearch
 * @subpackage  Models
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Enhanced search model with advanced features
 */
class AjaxsearchEnhancedSearchModel
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var AjaxsearchCacheBasic Cache system
     */
    protected $cache;
    
    /**
     * @var AjaxsearchEnhancedIntegratedSearch Enhanced search engine
     */
    protected $searchEngine;
    
    /**
     * @var array Configuration
     */
    protected $config;
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
        
        // Initialize cache
        $this->cache = new AjaxsearchCacheBasic($db, $config);
        
        // Initialize enhanced search engine
        $this->searchEngine = new AjaxsearchEnhancedIntegratedSearch($db, $config);
    }
    
    /**
     * Perform enhanced search with caching
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function search($query, array $options = [])
    {
        // Generate cache key
        $cacheKey = $this->generateCacheKey($query, $options);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            $cached['cached'] = true;
            return $cached;
        }
        
        // Perform fresh search
        $results = $this->searchEngine->search($query, $options);
        
        // Cache the results
        if ($results['success']) {
            $this->cache->set($cacheKey, $results);
        }
        
        $results['cached'] = false;
        return $results;
    }
    
    /**
     * Get search suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Suggestions
     */
    public function getSuggestions($query, array $options = [])
    {
        $cacheKey = 'suggestions_' . md5($query . serialize($options));
        
        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $suggestions = $this->searchEngine->getSuggestions($query, $options);
        
        // Cache suggestions (shorter TTL)
        $this->cache->set($cacheKey, $suggestions, 60); // 1 minute TTL
        
        return $suggestions;
    }
    
    /**
     * Get search analytics
     * 
     * @param string $timeframe Timeframe
     * @return array Analytics data
     */
    public function getAnalytics($timeframe = 'week')
    {
        $cacheKey = 'analytics_' . $timeframe;
        
        // Try cache first (5 minute TTL for analytics)
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $analytics = $this->searchEngine->getAnalytics($timeframe);
        
        // Cache analytics
        $this->cache->set($cacheKey, $analytics, 300); // 5 minutes TTL
        
        return $analytics;
    }
    
    /**
     * Get trending searches
     * 
     * @param int $limit Limit
     * @param string $timeframe Timeframe
     * @return array Trending searches
     */
    public function getTrendingSearches($limit = 10, $timeframe = 'week')
    {
        $cacheKey = 'trending_' . $timeframe . '_' . $limit;
        
        // Try cache first (10 minute TTL for trending)
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $trending = $this->searchEngine->getTrendingSearches($limit, $timeframe);
        
        // Cache trending searches
        $this->cache->set($cacheKey, $trending, 600); // 10 minutes TTL
        
        return $trending;
    }
    
    /**
     * Generate cache key for search
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return string Cache key
     */
    private function generateCacheKey($query, array $options)
    {
        $user = JFactory::getUser();
        $language = JFactory::getLanguage()->getTag();
        
        $keyData = [
            'query' => $query,
            'language' => $language,
            'user_groups' => implode(',', $user->getAuthorisedGroups()),
            'options' => $options,
            'version' => '2.1' // Version for cache invalidation
        ];
        
        return md5(serialize($keyData));
    }
    
    /**
     * Get enhanced search statistics
     * 
     * @return array Search statistics
     */
    public function getStats()
    {
        $stats = [
            'cache' => $this->cache->getStats(),
            'analytics' => $this->getAnalytics('week'),
            'trending' => $this->getTrendingSearches(5, 'week')
        ];
        
        // Add configuration info
        $stats['config'] = [
            'min_query_length' => $this->searchEngine->minQueryLength,
            'max_results' => $this->searchEngine->maxResults,
            'cache_enabled' => $this->cache->isEnabled(),
            'enabled_types' => $this->searchEngine->enabledTypes,
            'enable_custom_fields' => $this->searchEngine->enableCustomFields,
            'enable_enhanced_scoring' => $this->searchEngine->enableEnhancedScoring
        ];
        
        return $stats;
    }
    
    /**
     * Clear search cache
     * 
     * @return bool Success
     */
    public function clearCache()
    {
        return $this->cache->clearAll();
    }
    
    /**
     * Clean expired cache entries
     * 
     * @return int Number of cleaned entries
     */
    public function cleanCache()
    {
        return $this->cache->cleanExpired();
    }
    
    /**
     * Clear SP Page Builder cache
     * 
     * @return bool Success
     */
    public function clearSPCache()
    {
        $parser = new AjaxsearchParserSP($this->db);
        return $parser->clearAllCache();
    }
    
    /**
     * Test enhanced search functionality
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testSearch($query = 'test')
    {
        $results = [];
        
        // Test enhanced search
        $start = microtime(true);
        $searchResults = $this->search($query, ['limit' => 5]);
        $time = microtime(true) - $start;
        
        $results['enhanced_search'] = [
            'success' => $searchResults['success'],
            'time' => round($time, 4),
            'results_count' => count($searchResults['results']),
            'total' => $searchResults['total'],
            'cached' => $searchResults['cached'],
            'features_used' => $searchResults['features_used'] ?? []
        ];
        
        // Results by type
        $typeCounts = [];
        foreach ($searchResults['results'] as $result) {
            $type = $result['type'] ?? 'unknown';
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }
        $results['results_by_type'] = $typeCounts;
        
        // Test suggestions
        $suggestions = $this->getSuggestions($query, ['limit' => 5]);
        $results['suggestions'] = [
            'count' => count($suggestions),
            'sample' => array_slice($suggestions, 0, 3)
        ];
        
        // Test cache (same query should be cached)
        $start = microtime(true);
        $cachedResults = $this->search($query, ['limit' => 5]);
        $cachedTime = microtime(true) - $start;
        
        $results['cache_test'] = [
            'success' => $cachedResults['success'],
            'time' => round($cachedTime, 4),
            'cached' => $cachedResults['cached'],
            'cache_faster' => $cachedTime < $time
        ];
        
        // Test engine directly
        $results['engine_test'] = $this->searchEngine->testEnhancedFeatures($query);
        
        // Test empty query
        $emptyResults = $this->search('a');
        $results['empty_query'] = [
            'success' => $emptyResults['success'],
            'error' => $emptyResults['error'] ?? null,
            'results_count' => count($emptyResults['results'])
        ];
        
        // Stats
        $results['stats'] = $this->getStats();
        
        return $results;
    }
    
    /**
     * Search only articles
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function searchArticles($query, array $options = [])
    {
        $options['types'] = ['article'];
        return $this->search($query, $options);
    }
    
    /**
     * Search only SP pages
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function searchSPPages($query, array $options = [])
    {
        $options['types'] = ['sppagebuilder'];
        return $this->search($query, $options);
    }
    
    /**
     * Search with custom fields only
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function searchCustomFields($query, array $options = [])
    {
        $options['types'] = ['article']; // Custom fields only work with articles
        $options['include_custom_fields'] = true;
        return $this->search($query, $options);
    }
}