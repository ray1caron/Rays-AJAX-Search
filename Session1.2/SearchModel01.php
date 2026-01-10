<?php
/**
 * @package     AJAXSearch
 * @subpackage  Models
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Search model for handling search requests
 */
class AjaxsearchModelSearch
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
     * @var AjaxsearchSearchArticle Article search engine
     */
    protected $articleSearch;
    
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
        
        // Initialize article search
        $this->articleSearch = new AjaxsearchSearchArticle($db, $config);
    }
    
    /**
     * Perform search with caching
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
        $results = $this->articleSearch->search($query, $options);
        
        // Cache the results
        if ($results['success']) {
            $this->cache->set($cacheKey, $results);
        }
        
        $results['cached'] = false;
        return $results;
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
        // Use the same method as abstract search engine
        $user = JFactory::getUser();
        $language = JFactory::getLanguage()->getTag();
        
        $keyData = [
            'query' => $query,
            'language' => $language,
            'user_groups' => implode(',', $user->getAuthorisedGroups()),
            'options' => $options,
            'type' => 'article' // Only articles in this phase
        ];
        
        return md5(serialize($keyData));
    }
    
    /**
     * Get search statistics
     * 
     * @return array Search statistics
     */
    public function getStats()
    {
        $stats = [
            'cache' => $this->cache->getStats(),
            'articles' => [
                'total' => $this->articleSearch->getTotalArticleCount()
            ]
        ];
        
        // Add configuration info
        $stats['config'] = [
            'min_query_length' => $this->articleSearch->minQueryLength,
            'max_results' => $this->articleSearch->maxResults,
            'cache_enabled' => $this->cache->isEnabled()
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
     * Test search functionality
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testSearch($query = 'test')
    {
        $results = [];
        
        // Test 1: Basic search
        $start = microtime(true);
        $searchResults = $this->search($query, ['limit' => 5]);
        $time = microtime(true) - $start;
        
        $results['basic_search'] = [
            'success' => $searchResults['success'],
            'time' => round($time, 4),
            'results_count' => count($searchResults['results']),
            'total' => $searchResults['total'],
            'cached' => $searchResults['cached']
        ];
        
        // Test 2: Cache test (same query should be cached)
        $start = microtime(true);
        $cachedResults = $this->search($query, ['limit' => 5]);
        $cachedTime = microtime(true) - $start;
        
        $results['cache_test'] = [
            'success' => $cachedResults['success'],
            'time' => round($cachedTime, 4),
            'cached' => $cachedResults['cached'],
            'cache_faster' => $cachedTime < $time
        ];
        
        // Test 3: Empty query
        $emptyResults = $this->search('a');
        $results['empty_query'] = [
            'success' => $emptyResults['success'],
            'error' => $emptyResults['error'] ?? null,
            'results_count' => count($emptyResults['results'])
        ];
        
        // Test 4: Stats
        $results['stats'] = $this->getStats();
        
        return $results;
    }
}