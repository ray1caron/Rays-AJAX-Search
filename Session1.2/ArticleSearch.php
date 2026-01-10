<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Article search engine for regular Joomla articles
 */
class AjaxsearchSearchArticle extends AjaxsearchAbstractSearchEngine
{
    /**
     * @var array Article fields to search
     */
    protected $searchFields = [
        'title',
        'introtext', 
        'fulltext',
        'alias',
        'metakey',
        'metadesc'
    ];
    
    /**
     * @var array Article fields to return in results
     */
    protected $returnFields = [
        'id',
        'title',
        'alias',
        'introtext',
        'fulltext',
        'catid',
        'created',
        'modified',
        'publish_up',
        'metakey',
        'metadesc',
        'language',
        'access'
    ];
    
    /**
     * Search for articles
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
            'access' => true
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Perform search
        $articles = $this->searchArticles($terms, $options);
        
        // Calculate relevance scores
        $scoredArticles = $this->scoreArticles($articles, $terms);
        
        // Sort by relevance
        usort($scoredArticles, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        // Apply limit and offset
        $total = count($scoredArticles);
        $results = array_slice($scoredArticles, $options['offset'], $options['limit']);
        
        // Generate snippets
        foreach ($results as &$result) {
            $result['snippet'] = $this->generateSnippet(
                $result['introtext'] . ' ' . $result['fulltext'],
                $terms
            );
            $result['url'] = $this->getArticleUrl($result['id'], $result['alias'], $result['catid']);
            $result['type'] = 'article';
            $result['type_label'] = 'Article';
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
     * Search articles in database
     * 
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Found articles
     */
    private function searchArticles(array $terms, array $options)
    {
        $query = $this->db->getQuery(true);
        
        // Select fields
        $query->select($this->db->quoteName($this->returnFields))
              ->from($this->db->quoteName('#__content', 'c'))
              ->where($this->db->quoteName('c.state') . ' = 1'); // Published articles
        
        // Build WHERE clause for search terms
        $whereClauses = [];
        foreach ($terms as $term) {
            $termWhere = [];
            
            foreach ($this->searchFields as $field) {
                $termWhere[] = $this->db->quoteName('c.' . $field) . ' LIKE ' . 
                               $this->db->quote('%' . $this->db->escape($term, true) . '%');
            }
            
            $whereClauses[] = '(' . implode(' OR ', $termWhere) . ')';
        }
        
        if (!empty($whereClauses)) {
            $query->where('(' . implode(' AND ', $whereClauses) . ')');
        }
        
        // Language filter
        if ($options['language'] !== '*') {
            $query->where($this->db->quoteName('c.language') . ' IN (' . 
                   $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        // Category filter
        if (!empty($options['category'])) {
            if (is_array($options['category'])) {
                $query->where($this->db->quoteName('c.catid') . ' IN (' . 
                       implode(',', array_map('intval', $options['category'])) . ')');
            } else {
                $query->where($this->db->quoteName('c.catid') . ' = ' . (int)$options['category']);
            }
        }
        
        // Access filter
        if ($options['access']) {
            $user = JFactory::getUser();
            $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
            $query->where($this->db->quoteName('c.access') . ' IN (' . $groups . ')');
        }
        
        // Order by publish date (will be re-sorted by relevance later)
        $query->order($this->db->quoteName('c.publish_up') . ' DESC');
        
        // Execute query
        $this->db->setQuery($query);
        
        try {
            return $this->db->loadObjectList() ?: [];
        } catch (Exception $e) {
            error_log('AJAXSearch Article Search Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate relevance scores for articles
     * 
     * @param array $articles Articles to score
     * @param array $terms Search terms
     * @return array Articles with relevance scores
     */
    private function scoreArticles(array $articles, array $terms)
    {
        $scoredArticles = [];
        
        foreach ($articles as $article) {
            $relevance = 0;
            
            // Score each field with appropriate weight
            $relevance += $this->calculateRelevance(
                $article->title, 
                $terms, 
                $this->weights['title']
            );
            
            $relevance += $this->calculateRelevance(
                $article->introtext,
                $terms,
                $this->weights['introtext']
            );
            
            $relevance += $this->calculateRelevance(
                $article->fulltext,
                $terms,
                $this->weights['fulltext']
            );
            
            $relevance += $this->calculateRelevance(
                $article->metakey . ' ' . $article->metadesc,
                $terms,
                $this->weights['title'] / 2 // Half weight for meta fields
            );
            
            // Convert to array and add relevance
            $articleArray = (array) $article;
            $articleArray['relevance'] = $relevance;
            
            $scoredArticles[] = $articleArray;
        }
        
        return $scoredArticles;
    }
    
    /**
     * Get article URL
     * 
     * @param int $id Article ID
     * @param string $alias Article alias
     * @param int $catid Category ID
     * @return string Article URL
     */
    private function getArticleUrl($id, $alias, $catid)
    {
        // Use Joomla router to generate URL
        $slug = $id . ':' . $alias;
        $catslug = $catid ? $catid . ':' . $this->getCategoryAlias($catid) : '';
        
        $url = 'index.php?option=com_content&view=article';
        
        if (version_compare(JVERSION, '4.0', '>=')) {
            // Joomla 4+ URL
            $url .= '&id=' . $slug;
            if ($catslug) {
                $url .= '&catid=' . $catslug;
            }
        } else {
            // Joomla 3.x URL
            $url .= '&id=' . $slug;
        }
        
        // Route the URL
        if (class_exists('JRoute')) {
            return JRoute::_($url);
        }
        
        return $url;
    }
    
    /**
     * Get category alias
     * 
     * @param int $catid Category ID
     * @return string Category alias
     */
    private function getCategoryAlias($catid)
    {
        static $aliases = [];
        
        if (!isset($aliases[$catid])) {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__categories'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$catid);
            
            $this->db->setQuery($query);
            $aliases[$catid] = $this->db->loadResult() ?: '';
        }
        
        return $aliases[$catid];
    }
    
    /**
     * Get total article count (for statistics)
     * 
     * @return int Total published articles
     */
    public function getTotalArticleCount()
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__content'))
            ->where($this->db->quoteName('state') . ' = 1');
        
        $this->db->setQuery($query);
        
        return (int) $this->db->loadResult();
    }
}