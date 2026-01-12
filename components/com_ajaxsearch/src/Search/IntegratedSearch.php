<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Integrated search engine combining articles and SP pages
 */
class AjaxsearchSearchIntegrated extends AjaxsearchAbstractSearchEngine
{
    /**
     * @var AjaxsearchSearchArticle Article search
     */
    protected $articleSearch;
    
    /**
     * @var AjaxsearchSearchSP SP page search
     */
    protected $spSearch;
    
    /**
     * @var array Enabled search types
     */
    protected $enabledTypes = ['article', 'sppagebuilder'];
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        parent::__construct($db, $config);
        
        // Initialize search engines
        $this->articleSearch = new AjaxsearchSearchArticle($db, $config);
        $this->spSearch = new AjaxsearchSearchSP($db, $config);
        
        // Load enabled types from config
        if (isset($config['enabled_types'])) {
            $this->enabledTypes = (array) $config['enabled_types'];
        }
    }
    
    /**
     * Perform integrated search
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function search($query, array $options = [])
    {
        $startTime = microtime(true);
        
        // Validate query
        if (!$this->validateQuery($query)) {
            return [
                'success' => false,
                'error' => 'Invalid search query',
                'results' => [],
                'total' => 0,
                'query' => $this->sanitizeQuery($query)
            ];
        }
        
        // Prepare search terms
        $terms = $this->prepareSearchTerms($query);
        if (empty($terms)) {
            return [
                'success' => true,
                'results' => [],
                'total' => 0,
                'query' => $this->sanitizeQuery($query)
            ];
        }
        
        // Merge options with defaults
        $defaultOptions = [
            'limit' => $this->maxResults,
            'offset' => 0,
            'category' => null,
            'language' => $this->language,
            'access' => true,
            'types' => $this->enabledTypes,
            'parse_sp_content' => true
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Collect results from all enabled types
        $allResults = [];
        
        // Search articles
        if (in_array('article', $options['types'])) {
            $articleOptions = $options;
            unset($articleOptions['types']);
            unset($articleOptions['parse_sp_content']);
            
            $articleResults = $this->articleSearch->search($query, $articleOptions);
            
            if ($articleResults['success']) {
                $allResults = array_merge($allResults, $articleResults['results']);
            }
        }
        
        // Search SP pages
        if (in_array('sppagebuilder', $options['types'])) {
            $spOptions = $options;
            unset($spOptions['types']);
            $spOptions['parse_content'] = $options['parse_sp_content'];
            
            $spResults = $this->spSearch->search($query, $spOptions);
            
            if ($spResults['success']) {
                $allResults = array_merge($allResults, $spResults['results']);
            }
        }
        
        // Sort all results by relevance
        usort($allResults, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        // Apply limit and offset
        $total = count($allResults);
        $results = array_slice($allResults, $options['offset'], $options['limit']);
        
        $searchTime = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'results' => $results,
            'total' => $total,
            'query' => $this->sanitizeQuery($query),
            'search_time' => round($searchTime, 4),
            'types_searched' => $options['types']
        ];
    }
    
    /**
     * Set enabled search types
     * 
     * @param array $types Enabled types
     */
    public function setEnabledTypes(array $types)
    {
        $this->enabledTypes = $types;
    }
    
    /**
     * Get search statistics
     * 
     * @return array Statistics
     */
    public function getStats()
    {
        return [
            'articles' => [
                'total' => $this->articleSearch->getTotalArticleCount()
            ],
            'sppages' => [
                'total' => $this->spSearch->getTotalSPCount()
            ],
            'enabled_types' => $this->enabledTypes
        ];
    }
    
    /**
     * Test integrated search
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testSearch($query = 'test')
    {
        $results = [];
        
        // Test integrated search
        $start = microtime(true);
        $searchResults = $this->search($query, ['limit' => 5]);
        $time = microtime(true) - $start;
        
        $results['integrated_search'] = [
            'success' => $searchResults['success'],
            'time' => round($time, 4),
            'results_count' => count($searchResults['results']),
            'total' => $searchResults['total'],
            'types_searched' => $searchResults['types_searched']
        ];
        
        // Count results by type
        $typeCounts = [];
        foreach ($searchResults['results'] as $result) {
            $type = $result['type'] ?? 'unknown';
            $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
        }
        $results['results_by_type'] = $typeCounts;
        
        // Test individual engines
        $results['article_engine'] = $this->articleSearch->testSearch($query);
        $results['sp_engine'] = $this->spSearch->testSearch($query);
        
        return $results;
    }
}