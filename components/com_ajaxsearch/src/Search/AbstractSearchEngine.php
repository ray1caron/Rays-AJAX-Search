<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Abstract search engine base class
 */
abstract class AjaxsearchAbstractSearchEngine
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var array Configuration
     */
    protected $config;
    
    /**
     * @var array Search weights
     */
    protected $weights = [
        'title' => 10,
        'introtext' => 5,
        'fulltext' => 3,
        'custom_fields' => 4,
        'sppagebuilder_title' => 10,
        'sppagebuilder_content' => 5
    ];
    
    /**
     * @var int Minimum query length
     */
    protected $minQueryLength = 2;
    
    /**
     * @var int Maximum results
     */
    protected $maxResults = 50;
    
    /**
     * @var string Current language
     */
    protected $language = '*';
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration array
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
        $this->loadConfiguration();
    }
    
    /**
     * Load configuration from database
     */
    protected function loadConfiguration()
    {
        // Default configuration
        $defaults = [
            'min_query_length' => 2,
            'max_results' => 50,
            'cache_ttl' => 300,
            'enable_analytics' => true
        ];
        
        // Merge with provided config
        $this->config = array_merge($defaults, $this->config);
        
        // Set instance properties
        $this->minQueryLength = (int) $this->config['min_query_length'];
        $this->maxResults = (int) $this->config['max_results'];
        
        // Get current language
        $lang = JFactory::getLanguage();
        $this->language = $lang->getTag();
    }
    
    /**
     * Main search method
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    abstract public function search($query, array $options = []);
    
    /**
     * Validate search query
     * 
     * @param string $query Search query
     * @return bool True if valid
     */
    protected function validateQuery($query)
    {
        $query = trim($query);
        
        // Check minimum length
        if (strlen($query) < $this->minQueryLength) {
            return false;
        }
        
        // Check for SQL injection patterns
        if ($this->containsSqlInjection($query)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if string contains SQL injection patterns
     * 
     * @param string $input Input string
     * @return bool True if suspicious
     */
    protected function containsSqlInjection($input)
    {
        $patterns = [
            '/\b(?:union|select|insert|update|delete|drop|create|alter)\b/i',
            '/--/',
            '/#/',
            '/\/\*/',
            '/\*\//',
            '/;/',
            '/\b(?:or|and)\b\s+\d+\s*=\s*\d+/i',
            '/\b(?:or|and)\b\s+[\'"]\s*=\s*[\'"]/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Prepare search terms from query
     * 
     * @param string $query Search query
     * @return array Processed search terms
     */
    protected function prepareSearchTerms($query)
    {
        $query = strtolower(trim($query));
        
        // Remove extra whitespace
        $query = preg_replace('/\s+/', ' ', $query);
        
        // Split into words
        $words = explode(' ', $query);
        
        // Remove stop words
        $stopWords = $this->getStopWords();
        $words = array_diff($words, $stopWords);
        
        // Remove short words
        $words = array_filter($words, function($word) {
            return strlen($word) >= $this->minQueryLength;
        });
        
        // Apply stemming (basic)
        $words = array_map([$this, 'stemWord'], $words);
        
        return array_unique(array_values($words));
    }
    
    /**
     * Get list of stop words
     * 
     * @return array Stop words
     */
    protected function getStopWords()
    {
        // Common English stop words
        return [
            'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 
            'for', 'of', 'with', 'by', 'as', 'is', 'was', 'were', 'be', 
            'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 
            'not', 'this', 'that', 'these', 'those', 'am', 'are'
        ];
    }
    
    /**
     * Basic word stemming
     * 
     * @param string $word Word to stem
     * @return string Stemmed word
     */
    protected function stemWord($word)
    {
        // Very basic stemming - remove plural 's' and common suffixes
        $patterns = [
            '/ies$/i' => 'y',
            '/es$/i' => '',
            '/s$/i' => '',
            '/ing$/i' => '',
            '/ed$/i' => '',
            '/er$/i' => '',
            '/est$/i' => ''
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                $word = preg_replace($pattern, $replacement, $word);
                break;
            }
        }
        
        return $word;
    }
    
    /**
     * Calculate relevance score
     * 
     * @param string $text Text to search in
     * @param array $terms Search terms
     * @param int $baseWeight Base weight for this field
     * @return int Relevance score
     */
    protected function calculateRelevance($text, array $terms, $baseWeight = 1)
    {
        $text = strtolower($text);
        $score = 0;
        
        foreach ($terms as $term) {
            if (strpos($text, $term) !== false) {
                // Count occurrences
                $occurrences = substr_count($text, $term);
                
                // Add score based on occurrences (with diminishing returns)
                $score += min($occurrences * $baseWeight, $baseWeight * 5);
                
                // Bonus for exact match at beginning
                if (strpos($text, $term) === 0) {
                    $score += $baseWeight * 2;
                }
            }
        }
        
        return $score;
    }
    
    /**
     * Generate snippet from text with highlighted terms
     * 
     * @param string $text Full text
     * @param array $terms Search terms
     * @param int $length Snippet length
     * @return string Generated snippet
     */
    protected function generateSnippet($text, array $terms, $length = 200)
    {
        $text = strip_tags($text);
        $textLower = strtolower($text);
        
        // Find the best position to start snippet
        $bestPosition = 0;
        $bestScore = 0;
        
        foreach ($terms as $term) {
            $pos = strpos($textLower, $term);
            if ($pos !== false) {
                // Score based on position (earlier is better)
                $score = strlen($text) - $pos;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestPosition = $pos;
                }
            }
        }
        
        // If no term found, start from beginning
        if ($bestScore === 0) {
            $bestPosition = 0;
        }
        
        // Calculate start position (try to center on found term)
        $start = max(0, $bestPosition - ($length / 2));
        $snippet = substr($text, $start, $length);
        
        // Add ellipsis if needed
        if ($start > 0) {
            $snippet = '...' . ltrim($snippet);
        }
        
        if (strlen($text) > $start + $length) {
            $snippet = rtrim($snippet) . '...';
        }
        
        // Highlight search terms
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
     * Sanitize search query
     * 
     * @param string $query Raw query
     * @return string Sanitized query
     */
    protected function sanitizeQuery($query)
    {
        $query = trim($query);
        $query = strip_tags($query);
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        
        return $query;
    }
    
    /**
     * Get user groups for current user
     * 
     * @return string Comma-separated group IDs
     */
    protected function getUserGroups()
    {
        $user = JFactory::getUser();
        $groups = $user->getAuthorisedGroups();
        
        return implode(',', $groups);
    }
    
    /**
     * Log search analytics
     * 
     * @param string $query Search query
     * @param int $resultsCount Number of results
     * @param float $searchTime Search execution time
     * @param bool $zeroResults Whether search returned zero results
     */
    protected function logAnalytics($query, $resultsCount, $searchTime, $zeroResults = false)
    {
        if (empty($this->config['enable_analytics'])) {
            return;
        }
        
        $user = JFactory::getUser();
        $session = JFactory::getSession();
        
        $data = new stdClass();
        $data->query = substr($query, 0, 255);
        $data->results_count = $resultsCount;
        $data->user_id = $user->id ?: null;
        $data->session_id = $session->getId();
        $data->ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $data->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $data->search_time = $searchTime;
        $data->timestamp = date('Y-m-d H:i:s');
        $data->zero_results = $zeroResults ? 1 : 0;
        
        $this->db->insertObject('ajaxsearch_analytics', $data);
    }
    
    /**
     * Get cache key for query
     * 
     * @param string $query Search query
     * @param array $options Search options
     * @return string Cache key
     */
    protected function getCacheKey($query, array $options = [])
    {
        $keyData = [
            'query' => $query,
            'language' => $this->language,
            'user_groups' => $this->getUserGroups(),
            'options' => $options
        ];
        
        return md5(serialize($keyData));
    }
}