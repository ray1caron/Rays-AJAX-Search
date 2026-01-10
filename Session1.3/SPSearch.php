<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * SP Page Builder search engine
 */
class AjaxsearchSearchSP extends AjaxsearchAbstractSearchEngine
{
    /**
     * @var AjaxsearchParserSP SP parser
     */
    protected $parser;
    
    /**
     * @var array SP page fields to search
     */
    protected $searchFields = [
        'title',
        'text' // JSON content will be parsed
    ];
    
    /**
     * @var array SP page fields to return in results
     */
    protected $returnFields = [
        'id',
        'title',
        'alias',
        'text',
        'catid',
        'created_on as created',
        'modified_on as modified',
        'language',
        'access',
        'hits'
    ];
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        parent::__construct($db, $config);
        
        // Initialize parser
        $this->parser = new AjaxsearchParserSP($db);
    }
    
    /**
     * Search SP Page Builder pages
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
            'parse_content' => true // Whether to parse JSON content
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Get SP pages
        $pages = $this->getSPPages($options);
        
        if (empty($pages)) {
            $searchTime = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'results' => [],
                'total' => 0,
                'query' => $this->sanitizeQuery($query),
                'search_time' => round($searchTime, 4)
            ];
        }
        
        // Parse and search content
        $matchedPages = $this->searchInPages($pages, $terms, $options);
        
        // Sort by relevance
        usort($matchedPages, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        // Apply limit and offset
        $total = count($matchedPages);
        $results = array_slice($matchedPages, $options['offset'], $options['limit']);
        
        // Generate snippets and URLs
        foreach ($results as &$result) {
            $result['snippet'] = $this->generateSnippet($result['content_text'], $terms);
            $result['url'] = $this->getPageUrl($result['id'], $result['alias']);
            $result['type'] = 'sppagebuilder';
            $result['type_label'] = 'SP Page';
            $result['hits'] = isset($result['hits']) ? $result['hits'] : 0;
        }
        
        $searchTime = microtime(true) - $startTime;
        
        // Log analytics
        $this->logAnalytics($query, $total, $searchTime, $total === 0);
        
        return [
            'success' => true,
            'results' => $results,
            'total' => $total,
            'query' => $this->sanitizeQuery($query),
            'search_time' => round($searchTime, 4)
        ];
    }
    
    /**
     * Get SP Page Builder pages from database
     * 
     * @param array $options Search options
     * @return array SP pages
     */
    private function getSPPages(array $options)
    {
        // Check if SP Page Builder table exists
        if (!$this->db->tableExists('sppagebuilder')) {
            return [];
        }
        
        $query = $this->db->getQuery(true);
        
        // Select fields
        $query->select($this->db->quoteName($this->returnFields))
              ->from($this->db->quoteName('#__sppagebuilder', 'p'))
              ->where($this->db->quoteName('p.published') . ' = 1');
        
        // Language filter
        if ($options['language'] !== '*') {
            $query->where($this->db->quoteName('p.language') . ' IN (' . 
                   $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Category filter
        if (!empty($options['category'])) {
            if (is_array($options['category'])) {
                $query->where($this->db->quoteName('p.catid') . ' IN (' . 
                       implode(',', array_map('intval', $options['category'])) . ')');
            } else {
                $query->where($this->db->quoteName('p.catid') . ' = ' . (int)$options['category']);
            }
        }
        
        // Access filter
        if ($options['access']) {
            $user = JFactory::getUser();
            $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
            $query->where($this->db->quoteName('p.access') . ' IN (' . $groups . ')');
        }
        
        // Order by creation date
        $query->order($this->db->quoteName('p.created_on') . ' DESC');
        
        // Execute query
        $this->db->setQuery($query);
        
        try {
            return $this->db->loadObjectList() ?: [];
        } catch (Exception $e) {
            error_log('AJAXSearch SP Pages Query Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search for terms in SP pages
     * 
     * @param array $pages SP pages
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Matched pages with relevance scores
     */
    private function searchInPages(array $pages, array $terms, array $options)
    {
        $matchedPages = [];
        
        foreach ($pages as $page) {
            $relevance = 0;
            $contentText = '';
            
            // Score title
            $relevance += $this->calculateRelevance(
                $page->title,
                $terms,
                $this->weights['sppagebuilder_title']
            );
            
            // Parse and score content if enabled
            if ($options['parse_content'] && !empty($page->text)) {
                $contentText = $this->parser->parse($page->text, $page->id);
                
                $relevance += $this->calculateRelevance(
                    $contentText,
                    $terms,
                    $this->weights['sppagebuilder_content']
                );
            }
            
            // Only include pages with matches
            if ($relevance > 0) {
                $pageArray = (array) $page;
                $pageArray['relevance'] = $relevance;
                $pageArray['content_text'] = $contentText;
                $pageArray['title'] = $page->title; // Ensure title is set
                
                $matchedPages[] = $pageArray;
            }
        }
        
        return $matchedPages;
    }
    
    /**
     * Get page URL
     * 
     * @param int $id Page ID
     * @param string $alias Page alias
     * @return string Page URL
     */
    private function getPageUrl($id, $alias)
    {
        // SP Page Builder URL structure
        $slug = $id . ':' . $alias;
        $url = 'index.php?option=com_sppagebuilder&view=page&id=' . $slug;
        
        // Route the URL
        if (class_exists('JRoute')) {
            return JRoute::_($url);
        }
        
        return $url;
    }
    
    /**
     * Get total SP page count
     * 
     * @return int Total published SP pages
     */
    public function getTotalSPCount()
    {
        // Check if table exists
        if (!$this->db->tableExists('sppagebuilder')) {
            return 0;
        }
        
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__sppagebuilder'))
            ->where($this->db->quoteName('published') . ' = 1');
        
        $this->db->setQuery($query);
        
        try {
            return (int) $this->db->loadResult();
        } catch (Exception $e) {
            error_log('AJAXSearch SP Count Error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Test SP search functionality
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testSearch($query = 'sample')
    {
        $results = [];
        
        // Test 1: Basic search
        $start = microtime(true);
        $searchResults = $this->search($query, ['limit' => 3, 'parse_content' => true]);
        $time = microtime(true) - $start;
        
        $results['basic_search'] = [
            'success' => $searchResults['success'],
            'time' => round($time, 4),
            'results_count' => count($searchResults['results']),
            'total' => $searchResults['total']
        ];
        
        // Test 2: Parser test
        if ($this->db->tableExists('sppagebuilder')) {
            $query = $this->db->getQuery(true)
                ->select('text')
                ->from($this->db->quoteName('#__sppagebuilder'))
                ->where($this->db->quoteName('published') . ' = 1')
                ->setLimit(1);
            
            $this->db->setQuery($query);
            $sampleJson = $this->db->loadResult();
            
            if ($sampleJson) {
                $parserTest = $this->parser->testParser($sampleJson);
                $results['parser_test'] = $parserTest;
            }
        }
        
        // Test 3: Cache stats
        $results['cache_stats'] = $this->parser->getCacheStats();
        
        return $results;
    }
}