<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Search suggestions and autocomplete engine
 */
class AjaxsearchSuggestionsEngine
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var int Maximum suggestions to return
     */
    protected $maxSuggestions = 10;
    
    /**
     * @var int Minimum query length for suggestions
     */
    protected $minQueryLength = 2;
    
    /**
     * @var array Suggestion sources
     */
    protected $sources = [
        'popular_queries' => true,
        'article_titles' => true,
        'article_content' => false,
        'categories' => true,
        'tags' => true,
        'custom_fields' => false
    ];
    
    /**
     * @var array Stop words
     */
    protected $stopWords = [
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to'
    ];
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     */
    public function __construct(AjaxsearchDatabaseAdapter $db)
    {
        $this->db = $db;
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
        $query = trim($query);
        
        if (strlen($query) < $this->minQueryLength) {
            return [];
        }
        
        $defaultOptions = [
            'limit' => $this->maxSuggestions,
            'sources' => $this->sources,
            'language' => '*',
            'include_query' => true
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $allSuggestions = [];
        
        // Get suggestions from each enabled source
        foreach ($options['sources'] as $source => $enabled) {
            if ($enabled) {
                $sourceSuggestions = $this->getSourceSuggestions($source, $query, $options);
                $allSuggestions = array_merge($allSuggestions, $sourceSuggestions);
            }
        }
        
        // Add the original query as first suggestion if requested
        if ($options['include_query'] && !empty($query)) {
            array_unshift($allSuggestions, [
                'text' => $query,
                'type' => 'query',
                'relevance' => 100,
                'source' => 'current_query'
            ]);
        }
        
        // Sort by relevance
        usort($allSuggestions, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        // Remove duplicates (same text)
        $uniqueSuggestions = [];
        $seenTexts = [];
        
        foreach ($allSuggestions as $suggestion) {
            $text = strtolower($suggestion['text']);
            if (!in_array($text, $seenTexts)) {
                $seenTexts[] = $text;
                $uniqueSuggestions[] = $suggestion;
            }
        }
        
        // Limit results
        return array_slice($uniqueSuggestions, 0, $options['limit']);
    }
    
    /**
     * Get suggestions from a specific source
     * 
     * @param string $source Source name
     * @param string $query Partial query
     * @param array $options Options
     * @return array Suggestions from source
     */
    protected function getSourceSuggestions($source, $query, array $options)
    {
        switch ($source) {
            case 'popular_queries':
                return $this->getPopularQueries($query, $options);
                
            case 'article_titles':
                return $this->getArticleTitles($query, $options);
                
            case 'article_content':
                return $this->getArticleContent($query, $options);
                
            case 'categories':
                return $this->getCategories($query, $options);
                
            case 'tags':
                return $this->getTags($query, $options);
                
            case 'custom_fields':
                return $this->getCustomFields($query, $options);
                
            default:
                return [];
        }
    }
    
    /**
     * Get popular search queries
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Popular query suggestions
     */
    protected function getPopularQueries($query, array $options)
    {
        // Check if analytics table exists
        if (!$this->db->tableExists('ajaxsearch_analytics')) {
            return [];
        }
        
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'query as text',
            "'popular_query' as type",
            'COUNT(*) as frequency',
            'AVG(results_count) as avg_results',
            'CASE WHEN query LIKE ' . $this->db->quote($escapedQuery) . ' THEN 90 ELSE 70 END as relevance'
        ])
        ->from($this->db->quoteName('#__ajaxsearch_analytics'))
        ->where('query LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%'))
        ->where('LENGTH(query) >= ' . $this->minQueryLength)
        ->where('zero_results = 0')
        ->group('query')
        ->order('frequency DESC, relevance DESC')
        ->setLimit($options['limit']);
        
        // Language filter
        if ($options['language'] !== '*') {
            // Language not stored in analytics yet, skip for now
        }
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                $suggestions[] = [
                    'text' => $result->text,
                    'type' => $result->type,
                    'relevance' => (int) $result->relevance,
                    'frequency' => (int) $result->frequency,
                    'avg_results' => (int) $result->avg_results,
                    'source' => 'popular_queries'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Popular Queries Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get article title suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Article title suggestions
     */
    protected function getArticleTitles($query, array $options)
    {
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'title as text',
            "'article_title' as type",
            'id',
            'alias',
            'catid',
            'CASE WHEN title LIKE ' . $this->db->quote($escapedQuery) . ' THEN 95 ELSE 80 END as relevance'
        ])
        ->from($this->db->quoteName('#__content'))
        ->where('state = 1')
        ->where('(title LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . ')')
        ->order('relevance DESC, created DESC')
        ->setLimit($options['limit']);
        
        // Language filter
        if ($options['language'] !== '*') {
            $queryObj->where('language IN (' . $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Access control
        $user = JFactory::getUser();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        $queryObj->where('access IN (' . $groups . ')');
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                $suggestions[] = [
                    'text' => $result->text,
                    'type' => $result->type,
                    'relevance' => (int) $result->relevance,
                    'item_id' => (int) $result->id,
                    'alias' => $result->alias,
                    'catid' => (int) $result->catid,
                    'source' => 'article_titles'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Article Titles Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get article content suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Article content suggestions
     */
    protected function getArticleContent($query, array $options)
    {
        // This is more expensive, so we limit it more
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'SUBSTRING(introtext, 1, 100) as text',
            "'article_content' as type",
            'id',
            'title',
            'alias',
            'catid',
            'CASE WHEN introtext LIKE ' . $this->db->quote($escapedQuery) . ' OR fulltext LIKE ' . $this->db->quote($escapedQuery) . ' THEN 85 ELSE 70 END as relevance'
        ])
        ->from($this->db->quoteName('#__content'))
        ->where('state = 1')
        ->where('(introtext LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . 
                ' OR fulltext LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . ')')
        ->order('relevance DESC, created DESC')
        ->setLimit(min(5, $options['limit']));
        
        // Language filter
        if ($options['language'] !== '*') {
            $queryObj->where('language IN (' . $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Access control
        $user = JFactory::getUser();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        $queryObj->where('access IN (' . $groups . ')');
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                // Clean up the text snippet
                $text = strip_tags($result->text);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                
                if (!empty($text)) {
                    $suggestions[] = [
                        'text' => $text . '...',
                        'type' => $result->type,
                        'relevance' => (int) $result->relevance,
                        'item_id' => (int) $result->id,
                        'title' => $result->title,
                        'alias' => $result->alias,
                        'catid' => (int) $result->catid,
                        'source' => 'article_content'
                    ];
                }
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Article Content Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Category suggestions
     */
    protected function getCategories($query, array $options)
    {
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'title as text',
            "'category' as type",
            'id',
            'alias',
            'path',
            'CASE WHEN title LIKE ' . $this->db->quote($escapedQuery) . ' THEN 90 ELSE 75 END as relevance'
        ])
        ->from($this->db->quoteName('#__categories'))
        ->where('published = 1')
        ->where('extension = ' . $this->db->quote('com_content'))
        ->where('(title LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . 
                ' OR alias LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . 
                ' OR description LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . ')')
        ->order('relevance DESC, lft ASC')
        ->setLimit($options['limit']);
        
        // Language filter
        if ($options['language'] !== '*') {
            $queryObj->where('language IN (' . $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Access control
        $user = JFactory::getUser();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        $queryObj->where('access IN (' . $groups . ')');
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                $suggestions[] = [
                    'text' => $result->text,
                    'type' => $result->type,
                    'relevance' => (int) $result->relevance,
                    'category_id' => (int) $result->id,
                    'alias' => $result->alias,
                    'path' => $result->path,
                    'source' => 'categories'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Categories Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get tag suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Tag suggestions
     */
    protected function getTags($query, array $options)
    {
        // Check if tags table exists (Joomla 3.1+)
        if (!$this->db->tableExists('tags')) {
            return [];
        }
        
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'title as text',
            "'tag' as type",
            'id',
            'alias',
            'CASE WHEN title LIKE ' . $this->db->quote($escapedQuery) . ' THEN 88 ELSE 72 END as relevance'
        ])
        ->from($this->db->quoteName('#__tags'))
        ->where('published = 1')
        ->where('(title LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . 
                ' OR alias LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%') . ')')
        ->order('relevance DESC, title ASC')
        ->setLimit($options['limit']);
        
        // Language filter
        if ($options['language'] !== '*') {
            $queryObj->where('language IN (' . $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Access control
        $user = JFactory::getUser();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        $queryObj->where('access IN (' . $groups . ')');
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                $suggestions[] = [
                    'text' => $result->text,
                    'type' => $result->type,
                    'relevance' => (int) $result->relevance,
                    'tag_id' => (int) $result->id,
                    'alias' => $result->alias,
                    'source' => 'tags'
                ];
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Tags Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get custom field suggestions
     * 
     * @param string $query Partial query
     * @param array $options Options
     * @return array Custom field suggestions
     */
    protected function getCustomFields($query, array $options)
    {
        // Check if fields tables exist
        if (!$this->db->tableExists('fields') || !$this->db->tableExists('fields_values')) {
            return [];
        }
        
        $escapedQuery = $this->db->escape($query, true) . '%';
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'DISTINCT v.value as text',
            "'custom_field' as type",
            'f.title as field_title',
            'f.name as field_name',
            'CASE WHEN v.value LIKE ' . $this->db->quote($escapedQuery) . ' THEN 82 ELSE 65 END as relevance'
        ])
        ->from($this->db->quoteName('#__fields_values', 'v'))
        ->innerJoin($this->db->quoteName('#__fields', 'f') . ' ON f.id = v.field_id')
        ->where('f.state = 1')
        ->where('f.type IN ("text", "textarea", "editor", "list", "radio")')
        ->where('v.value LIKE ' . $this->db->quote('%' . $this->db->escape($query, true) . '%'))
        ->where('LENGTH(v.value) <= 100') // Limit to reasonable length values
        ->order('relevance DESC, f.title ASC')
        ->setLimit(min(5, $options['limit']));
        
        $this->db->setQuery($queryObj);
        
        try {
            $results = $this->db->loadObjectList();
            
            $suggestions = [];
            foreach ($results as $result) {
                $text = strip_tags($result->text);
                $text = trim($text);
                
                if (!empty($text)) {
                    $suggestions[] = [
                        'text' => $text,
                        'type' => $result->type,
                        'relevance' => (int) $result->relevance,
                        'field_title' => $result->field_title,
                        'field_name' => $result->field_name,
                        'source' => 'custom_fields'
                    ];
                }
            }
            
            return $suggestions;
        } catch (Exception $e) {
            error_log('AJAXSearch Custom Fields Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending searches
     * 
     * @param int $limit Number of trending searches
     * @param string $timeframe Timeframe (today, week, month)
     * @return array Trending searches
     */
    public function getTrendingSearches($limit = 10, $timeframe = 'week')
    {
        if (!$this->db->tableExists('ajaxsearch_analytics')) {
            return [];
        }
        
        $timeConditions = [
            'today' => 'timestamp >= CURDATE()',
            'week' => 'timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
            'month' => 'timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'
        ];
        
        $timeCondition = $timeConditions[$timeframe] ?? $timeConditions['week'];
        
        $queryObj = $this->db->getQuery(true);
        $queryObj->select([
            'query',
            'COUNT(*) as searches',
            'AVG(results_count) as avg_results',
            'SUM(zero_results) as zero_result_searches'
        ])
        ->from($this->db->quoteName('#__ajaxsearch_analytics'))
        ->where($timeCondition)
        ->where('LENGTH(query) >= ' . $this->minQueryLength)
        ->group('query')
        ->order('searches DESC')
        ->setLimit($limit);
        
        $this->db->setQuery($queryObj);
        
        try {
            return $this->db->loadObjectList() ?: [];
        } catch (Exception $e) {
            error_log('AJAXSearch Trending Searches Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Record a search query for analytics
     * 
     * @param string $query Search query
     * @param int $resultsCount Number of results
     * @param bool $zeroResults Whether search returned zero results
     * @return bool Success
     */
    public function recordSearch($query, $resultsCount = 0, $zeroResults = false)
    {
        if (!$this->db->tableExists('ajaxsearch_analytics') || empty($query)) {
            return false;
        }
        
        $query = substr(trim($query), 0, 255);
        
        if (strlen($query) < $this->minQueryLength) {
            return false;
        }
        
        $data = new stdClass();
        $data->query = $query;
        $data->results_count = (int) $resultsCount;
        $data->user_id = JFactory::getUser()->id ?: null;
        $data->session_id = JFactory::getSession()->getId();
        $data->ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $data->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $data->search_time = null; // Would be populated by search engine
        $data->timestamp = date('Y-m-d H:i:s');
        $data->zero_results = $zeroResults ? 1 : 0;
        
        try {
            return $this->db->insertObject('#__ajaxsearch_analytics', $data);
        } catch (Exception $e) {
            error_log('AJAXSearch Record Search Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get search analytics summary
     * 
     * @param string $timeframe Timeframe (today, week, month, all)
     * @return array Analytics summary
     */
    public function getAnalyticsSummary($timeframe = 'week')
    {
        if (!$this->db->tableExists('ajaxsearch_analytics')) {
            return [];
        }
        
        $timeConditions = [
            'today' => 'timestamp >= CURDATE()',
            'week' => 'timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
            'month' => 'timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            'all' => '1=1'
        ];
        
        $timeCondition = $timeConditions[$timeframe] ?? $timeConditions['week'];
        
        $summary = [];
        
        try {
            // Total searches
            $queryObj = $this->db->getQuery(true);
            $queryObj->select('COUNT(*) as total_searches')
                     ->from($this->db->quoteName('#__ajaxsearch_analytics'))
                     ->where($timeCondition);
            $this->db->setQuery($queryObj);
            $summary['total_searches'] = (int) $this->db->loadResult();
            
            // Unique searches
            $queryObj = $this->db->getQuery(true);
            $queryObj->select('COUNT(DISTINCT query) as unique_searches')
                     ->from($this->db->quoteName('#__ajaxsearch_analytics'))
                     ->where($timeCondition);
            $this->db->setQuery($queryObj);
            $summary['unique_searches'] = (int) $this->db->loadResult();
            
            // Zero result searches
            $queryObj = $this->db->getQuery(true);
            $queryObj->select('COUNT(*) as zero_result_searches')
                     ->from($this->db->quoteName('#__ajaxsearch_analytics'))
                     ->where($timeCondition)
                     ->where('zero_results = 1');
            $this->db->setQuery($queryObj);
            $summary['zero_result_searches'] = (int) $this->db->loadResult();
            
            // Average results per search
            $queryObj = $this->db->getQuery(true);
            $queryObj->select('AVG(results_count) as avg_results')
                     ->from($this->db->quoteName('#__ajaxsearch_analytics'))
                     ->where($timeCondition)
                     ->where('zero_results = 0');
            $this->db->setQuery($queryObj);
            $summary['avg_results'] = round($this->db->loadResult(), 1);
            
            // Most popular searches
            $summary['popular_searches'] = $this->getTrendingSearches(5, $timeframe);
            
            // Zero result rate
            if ($summary['total_searches'] > 0) {
                $summary['zero_result_rate'] = round(($summary['zero_result_searches'] / $summary['total_searches']) * 100, 1) . '%';
            } else {
                $summary['zero_result_rate'] = '0%';
            }
            
        } catch (Exception $e) {
            error_log('AJAXSearch Analytics Summary Error: ' . $e->getMessage());
            $summary['error'] = $e->getMessage();
        }
        
        return $summary;
    }
    
    /**
     * Set suggestion sources
     * 
     * @param array $sources Suggestion sources
     */
    public function setSources(array $sources)
    {
        $this->sources = array_merge($this->sources, $sources);
    }
    
    /**
     * Test suggestions
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testSuggestions($query = 'test')
    {
        return [
            'query' => $query,
            'suggestions' => $this->getSuggestions($query),
            'trending' => $this->getTrendingSearches(5),
            'analytics' => $this->getAnalyticsSummary('week')
        ];
    }
}