<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Enhanced integrated search engine with advanced features
 */
class AjaxsearchEnhancedIntegratedSearch extends AjaxsearchSearchIntegrated
{
    /**
     * @var AjaxsearchEnhancedRelevanceScorer Enhanced relevance scorer
     */
    protected $relevanceScorer;
    
    /**
     * @var AjaxsearchCustomFieldSearch Custom field search
     */
    protected $customFieldSearch;
    
    /**
     * @var AjaxsearchSuggestionsEngine Suggestions engine
     */
    protected $suggestionsEngine;
    
    /**
     * @var bool Enable custom field search
     */
    protected $enableCustomFields = true;
    
    /**
     * @var bool Enable enhanced relevance scoring
     */
    protected $enableEnhancedScoring = true;
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        parent::__construct($db, $config);
        
        // Initialize enhanced components
        $this->relevanceScorer = new AjaxsearchEnhancedRelevanceScorer();
        $this->customFieldSearch = new AjaxsearchCustomFieldSearch($db);
        $this->suggestionsEngine = new AjaxsearchSuggestionsEngine($db);
        
        // Load configuration
        if (isset($config['enable_custom_fields'])) {
            $this->enableCustomFields = (bool) $config['enable_custom_fields'];
        }
        
        if (isset($config['enable_enhanced_scoring'])) {
            $this->enableEnhancedScoring = (bool) $config['enable_enhanced_scoring'];
        }
        
        // Set custom field weights if provided
        if (isset($config['custom_field_weights'])) {
            $this->relevanceScorer->setWeights($config['custom_field_weights']);
        }
    }
    
    /**
     * Perform enhanced integrated search
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
            'parse_sp_content' => true,
            'include_custom_fields' => $this->enableCustomFields,
            'enhanced_scoring' => $this->enableEnhancedScoring
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Collect results from all enabled types
        $allResults = [];
        
        // Search articles
        if (in_array('article', $options['types'])) {
            $articleResults = $this->searchArticlesEnhanced($query, $terms, $options);
            $allResults = array_merge($allResults, $articleResults);
        }
        
        // Search SP pages
        if (in_array('sppagebuilder', $options['types'])) {
            $spResults = $this->searchSPPagesEnhanced($query, $terms, $options);
            $allResults = array_merge($allResults, $spResults);
        }
        
        // Search custom fields if enabled
        if ($options['include_custom_fields']) {
            $customFieldResults = $this->searchCustomFields($terms, $options);
            $allResults = array_merge($allResults, $customFieldResults);
        }
        
        // Apply enhanced scoring if enabled
        if ($options['enhanced_scoring']) {
            $allResults = $this->applyEnhancedScoring($allResults, $terms);
        } else {
            // Use basic scoring
            usort($allResults, function($a, $b) {
                return $b['relevance'] <=> $a['relevance'];
            });
        }
        
        // Remove duplicates (same item ID and type)
        $allResults = $this->removeDuplicates($allResults);
        
        // Apply limit and offset
        $total = count($allResults);
        $results = array_slice($allResults, $options['offset'], $options['limit']);
        
        // Generate enhanced snippets
        foreach ($results as &$result) {
            $result = $this->enhanceResult($result, $terms);
        }
        
        $searchTime = microtime(true) - $startTime;
        
        // Record search for analytics
        $this->suggestionsEngine->recordSearch($query, $total, $total === 0);
        
        return [
            'success' => true,
            'results' => $results,
            'total' => $total,
            'query' => $this->sanitizeQuery($query),
            'search_time' => round($searchTime, 4),
            'types_searched' => $options['types'],
            'features_used' => [
                'enhanced_scoring' => $options['enhanced_scoring'],
                'custom_fields' => $options['include_custom_fields']
            ]
        ];
    }
    
    /**
     * Search articles with enhanced features
     * 
     * @param string $query Original query
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Article results
     */
    protected function searchArticlesEnhanced($query, array $terms, array $options)
    {
        $articleOptions = $options;
        unset($articleOptions['types']);
        unset($articleOptions['parse_sp_content']);
        unset($articleOptions['include_custom_fields']);
        unset($articleOptions['enhanced_scoring']);
        
        $articleResults = $this->articleSearch->search($query, $articleOptions);
        
        if (!$articleResults['success']) {
            return [];
        }
        
        $enhancedResults = [];
        foreach ($articleResults['results'] as $result) {
            // Add custom field data if enabled
            if ($options['include_custom_fields']) {
                $customFields = $this->customFieldSearch->getItemCustomFields($result['id']);
                $result['custom_fields'] = $customFields;
                
                // Extract custom field text for scoring
                $customFieldText = '';
                foreach ($customFields as $field) {
                    if (in_array($field->type, ['text', 'textarea', 'editor', 'list'])) {
                        $customFieldText .= ' ' . $field->value;
                    }
                }
                $result['custom_fields_text'] = trim($customFieldText);
            }
            
            $enhancedResults[] = $result;
        }
        
        return $enhancedResults;
    }
    
    /**
     * Search SP pages with enhanced features
     * 
     * @param string $query Original query
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array SP page results
     */
    protected function searchSPPagesEnhanced($query, array $terms, array $options)
    {
        $spOptions = $options;
        unset($spOptions['types']);
        $spOptions['parse_content'] = $options['parse_sp_content'];
        unset($spOptions['include_custom_fields']);
        unset($spOptions['enhanced_scoring']);
        
        $spResults = $this->spSearch->search($query, $spOptions);
        
        if (!$spResults['success']) {
            return [];
        }
        
        return $spResults['results'];
    }
    
    /**
     * Search custom fields
     * 
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Custom field results
     */
    protected function searchCustomFields(array $terms, array $options)
    {
        $cfOptions = [
            'content_type' => 'com_content.article',
            'limit' => $options['limit'] * 2, // Get more to account for duplicates
            'offset' => 0,
            'language' => $options['language'],
            'min_relevance' => 1
        ];
        
        $cfResults = $this->customFieldSearch->search($terms, $cfOptions);
        
        $enhancedResults = [];
        foreach ($cfResults as $result) {
            // Convert to standard result format
            $enhancedResult = [
                'id' => $result->item_id,
                'title' => $result->content_title,
                'alias' => $result->content_alias,
                'catid' => $result->catid,
                'created' => $result->created,
                'type' => 'article',
                'type_label' => 'Article',
                'relevance' => $result->relevance,
                'custom_field_match' => true,
                'matched_fields' => $result->matched_fields,
                'field_match_count' => $result->field_match_count
            ];
            
            // Get URL
            $enhancedResult['url'] = $this->getArticleUrl(
                $result->item_id,
                $result->content_alias,
                $result->catid
            );
            
            $enhancedResults[] = $enhancedResult;
        }
        
        return $enhancedResults;
    }
    
    /**
     * Apply enhanced scoring to results
     * 
     * @param array $results Search results
     * @param array $terms Search terms
     * @return array Results with enhanced scoring
     */
    protected function applyEnhancedScoring(array $results, array $terms)
    {
        foreach ($results as &$result) {
            // Prepare data for scoring
            $scoreData = [
                'title' => $result['title'] ?? '',
                'introtext' => $result['introtext'] ?? '',
                'fulltext' => $result['fulltext'] ?? '',
                'content_text' => $result['content_text'] ?? '',
                'alias' => $result['alias'] ?? '',
                'meta_keywords' => $result['metakey'] ?? '',
                'meta_description' => $result['metadesc'] ?? '',
                'custom_fields' => $result['custom_fields_text'] ?? ''
            ];
            
            // Add category title if available
            if (isset($result['catid']) && $result['catid']) {
                $categoryTitle = $this->getCategoryTitle($result['catid']);
                $scoreData['category_title'] = $categoryTitle;
            }
            
            // Calculate enhanced score
            $contentType = $result['type'] ?? 'article';
            $enhancedScore = $this->relevanceScorer->calculateScore($scoreData, $terms, $contentType);
            
            // Combine with existing relevance (weighted average)
            $existingScore = $result['relevance'] ?? 0;
            $result['relevance'] = (int) round(($enhancedScore * 0.7) + ($existingScore * 0.3));
            $result['enhanced_score'] = $enhancedScore;
            $result['original_score'] = $existingScore;
        }
        
        // Sort by enhanced relevance
        usort($results, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return $results;
    }
    
    /**
     * Remove duplicate results
     * 
     * @param array $results Search results
     * @return array Unique results
     */
    protected function removeDuplicates(array $results)
    {
        $uniqueResults = [];
        $seenItems = [];
        
        foreach ($results as $result) {
            $key = $result['type'] . '_' . $result['id'];
            
            if (!isset($seenItems[$key])) {
                $seenItems[$key] = true;
                $uniqueResults[] = $result;
            } else {
                // Item already seen, keep the one with higher relevance
                foreach ($uniqueResults as &$uniqueResult) {
                    if ($uniqueResult['type'] === $result['type'] && $uniqueResult['id'] === $result['id']) {
                        if ($result['relevance'] > $uniqueResult['relevance']) {
                            $uniqueResult = $result;
                        }
                        break;
                    }
                }
            }
        }
        
        return $uniqueResults;
    }
    
    /**
     * Enhance a result with additional data
     * 
     * @param array $result Result data
     * @param array $terms Search terms
     * @return array Enhanced result
     */
    protected function enhanceResult(array $result, array $terms)
    {
        // Generate better snippet
        $snippetSource = '';
        if (!empty($result['introtext'])) {
            $snippetSource .= ' ' . $result['introtext'];
        }
        if (!empty($result['fulltext'])) {
            $snippetSource .= ' ' . $result['fulltext'];
        }
        if (!empty($result['content_text'])) {
            $snippetSource .= ' ' . $result['content_text'];
        }
        if (!empty($result['custom_fields_text'])) {
            $snippetSource .= ' ' . $result['custom_fields_text'];
        }
        
        if (!empty($snippetSource)) {
            $result['snippet'] = $this->generateEnhancedSnippet($snippetSource, $terms);
        }
        
        // Add custom field info if available
        if (isset($result['custom_field_match']) && $result['custom_field_match']) {
            $result['type_label'] = 'Article (Custom Field)';
            if (!empty($result['matched_fields'])) {
                $result['snippet'] = 'Matches in custom fields: ' . $result['matched_fields'] . '. ' . ($result['snippet'] ?? '');
            }
        }
        
        // Add scoring info for debugging
        if (isset($result['enhanced_score'])) {
            $result['score_breakdown'] = [
                'enhanced' => $result['enhanced_score'],
                'original' => $result['original_score'] ?? 0,
                'final' => $result['relevance']
            ];
        }
        
        return $result;
    }
    
    /**
     * Generate enhanced snippet
     * 
     * @param string $text Full text
     * @param array $terms Search terms
     * @param int $length Snippet length
     * @return string Enhanced snippet
     */
    protected function generateEnhancedSnippet($text, array $terms, $length = 250)
    {
        $text = strip_tags($text);
        $textLower = strtolower($text);
        
        // Find the best segment containing most terms
        $bestSegment = '';
        $bestScore = 0;
        
        // Split text into sentences or segments
        $segments = preg_split('/[.!?]+/', $text);
        
        foreach ($segments as $segment) {
            if (strlen(trim($segment)) < 20) {
                continue; // Skip very short segments
            }
            
            $segmentLower = strtolower($segment);
            $segmentScore = 0;
            
            foreach ($terms as $term) {
                if (strpos($segmentLower, $term) !== false) {
                    $segmentScore += 10;
                    // Bonus for term at beginning of segment
                    if (strpos($segmentLower, $term) < 10) {
                        $segmentScore += 5;
                    }
                }
            }
            
            if ($segmentScore > $bestScore) {
                $bestScore = $segmentScore;
                $bestSegment = $segment;
            }
        }
        
        // If we found a good segment, use it
        if (!empty($bestSegment) && $bestScore > 0) {
            $snippet = trim($bestSegment);
            if (strlen($snippet) > $length) {
                $snippet = substr($snippet, 0, $length) . '...';
            }
        } else {
            // Fall back to basic snippet
            $snippet = parent::generateSnippet($text, $terms, $length);
        }
        
        // Highlight terms
        foreach ($terms as $term) {
            $snippet = preg_replace(
                '/(' . preg_quote($term, '/') . ')/i',
                '<mark>$1</mark>',
                $snippet
            );
        }
        
        return $snippet;
    }
    
    /**
     * Get category title
     * 
     * @param int $catid Category ID
     * @return string Category title
     */
    protected function getCategoryTitle($catid)
    {
        static $categoryCache = [];
        
        if (!isset($categoryCache[$catid])) {
            $query = $this->db->getQuery(true);
            $query->select('title')
                  ->from($this->db->quoteName('#__categories'))
                  ->where('id = ' . (int) $catid);
            
            $this->db->setQuery($query);
            $categoryCache[$catid] = $this->db->loadResult() ?: '';
        }
        
        return $categoryCache[$catid];
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
        return $this->suggestionsEngine->getSuggestions($query, $options);
    }
    
    /**
     * Get search analytics
     * 
     * @param string $timeframe Timeframe
     * @return array Analytics data
     */
    public function getAnalytics($timeframe = 'week')
    {
        return $this->suggestionsEngine->getAnalyticsSummary($timeframe);
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
        return $this->suggestionsEngine->getTrendingSearches($limit, $timeframe);
    }
    
    /**
     * Test enhanced search features
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public function testEnhancedFeatures($query = 'test')
    {
        $terms = $this->prepareSearchTerms($query);
        
        return [
            'query' => $query,
            'terms' => $terms,
            'suggestions' => $this->getSuggestions($query, ['limit' => 5]),
            'trending' => $this->getTrendingSearches(5),
            'analytics' => $this->getAnalytics('week'),
            'scorer_test' => $this->relevanceScorer->testScoring([
                'title' => 'Test Article Title',
                'introtext' => 'This is a test article with some content for testing search functionality.',
                'fulltext' => 'More detailed content about testing and search algorithms.',
                'alias' => 'test-article',
                'created' => date('Y-m-d H:i:s')
            ], $query)
        ];
    }
}