<?php
/**
 * @package     AJAXSearch
 * @subpackage  Parser
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * SP Page Builder JSON content parser
 * Extracts searchable text from SP Page Builder JSON structure
 */
class AjaxsearchParserSP
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var array Addon parsers
     */
    protected $addonParsers = [];
    
    /**
     * @var bool Debug mode
     */
    protected $debug = false;
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     */
    public function __construct(AjaxsearchDatabaseAdapter $db = null)
    {
        $this->db = $db ?: new AjaxsearchDatabaseAdapter();
        
        // Initialize addon parsers
        $this->initializeAddonParsers();
    }
    
    /**
     * Initialize addon parsers
     */
    protected function initializeAddonParsers()
    {
        // Core addon parsers - will be extended in future sessions
        $this->addonParsers = [
            'text_block' => [$this, 'parseTextBlock'],
            'text' => [$this, 'parseTextBlock'], // Alias for text_block
            'heading' => [$this, 'parseHeading'],
            'addon_standard' => [$this, 'parseStandardAddon']
        ];
    }
    
    /**
     * Parse SP Page Builder JSON content
     * 
     * @param string $jsonContent JSON content from SP Page Builder
     * @param int $pageId SP Page Builder page ID (for caching)
     * @return string Extracted text content
     */
    public function parse($jsonContent, $pageId = null)
    {
        if (empty($jsonContent)) {
            return '';
        }
        
        // Check cache first
        if ($pageId !== null) {
            $cached = $this->getCachedContent($pageId, $jsonContent);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $extractedText = '';
        
        try {
            // Decode JSON
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (!is_array($data)) {
                return '';
            }
            
            // Parse the JSON structure
            $extractedText = $this->parseData($data);
            
            // Cache the result
            if ($pageId !== null && !empty($extractedText)) {
                $this->cacheContent($pageId, $jsonContent, $extractedText);
            }
            
        } catch (Exception $e) {
            error_log('AJAXSearch SP Parser Error: ' . $e->getMessage());
            
            if ($this->debug) {
                $extractedText .= ' [Parser Error: ' . $e->getMessage() . ']';
            }
        }
        
        return trim($extractedText);
    }
    
    /**
     * Parse SP Page Builder data structure
     * 
     * @param array $data SP Page Builder data
     * @return string Extracted text
     */
    protected function parseData(array $data)
    {
        $text = '';
        
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            
            // Parse rows
            if (isset($item['type']) && $item['type'] === 'row') {
                $text .= $this->parseRow($item);
            }
            // Legacy structure or direct addons
            elseif (isset($item['columns']) || isset($item['addons'])) {
                $text .= $this->parseRow($item);
            }
            // Direct addon (single addon without row/column)
            elseif (isset($item['type']) && isset($this->addonParsers[$item['type']])) {
                $text .= $this->parseAddon($item);
            }
        }
        
        return $text;
    }
    
    /**
     * Parse a row
     * 
     * @param array $row Row data
     * @return string Extracted text from row
     */
    protected function parseRow(array $row)
    {
        $text = '';
        
        // Parse columns
        if (isset($row['columns']) && is_array($row['columns'])) {
            foreach ($row['columns'] as $column) {
                $text .= $this->parseColumn($column);
            }
        }
        
        // Parse addons directly in row (legacy structure)
        if (isset($row['addons']) && is_array($row['addons'])) {
            foreach ($row['addons'] as $addon) {
                $text .= $this->parseAddon($addon);
            }
        }
        
        return $text;
    }
    
    /**
     * Parse a column
     * 
     * @param array $column Column data
     * @return string Extracted text from column
     */
    protected function parseColumn(array $column)
    {
        $text = '';
        
        if (isset($column['addons']) && is_array($column['addons'])) {
            foreach ($column['addons'] as $addon) {
                $text .= $this->parseAddon($addon);
            }
        }
        
        return $text;
    }
    
    /**
     * Parse an addon
     * 
     * @param array $addon Addon data
     * @return string Extracted text from addon
     */
    protected function parseAddon(array $addon)
    {
        $text = '';
        
        if (!isset($addon['type'])) {
            return $text;
        }
        
        $type = $addon['type'];
        
        // Check if we have a parser for this addon type
        if (isset($this->addonParsers[$type])) {
            $text .= call_user_func($this->addonParsers[$type], $addon);
        } else {
            // Try standard addon parser as fallback
            $text .= $this->parseStandardAddon($addon);
        }
        
        // Parse nested addons (for addons that contain other addons)
        if (isset($addon['addons']) && is_array($addon['addons'])) {
            foreach ($addon['addons'] as $nestedAddon) {
                $text .= $this->parseAddon($nestedAddon);
            }
        }
        
        return $text;
    }
    
    /**
     * Parse text_block addon
     * 
     * @param array $addon Addon data
     * @return string Extracted text
     */
    protected function parseTextBlock(array $addon)
    {
        $text = '';
        
        if (isset($addon['settings']['text'])) {
            $content = $addon['settings']['text'];
            $text .= ' ' . strip_tags($content);
        }
        
        return $text;
    }
    
    /**
     * Parse heading addon
     * 
     * @param array $addon Addon data
     * @return string Extracted text
     */
    protected function parseHeading(array $addon)
    {
        $text = '';
        
        if (isset($addon['settings']['title'])) {
            $title = $addon['settings']['title'];
            $text .= ' ' . strip_tags($title);
        }
        
        // Subtitle if exists
        if (isset($addon['settings']['subtitle'])) {
            $subtitle = $addon['settings']['subtitle'];
            $text .= ' ' . strip_tags($subtitle);
        }
        
        return $text;
    }
    
    /**
     * Parse standard addon (fallback for unknown types)
     * 
     * @param array $addon Addon data
     * @return string Extracted text
     */
    protected function parseStandardAddon(array $addon)
    {
        $text = '';
        
        if (!isset($addon['settings']) || !is_array($addon['settings'])) {
            return $text;
        }
        
        // Extract all text values from settings
        foreach ($addon['settings'] as $value) {
            if (is_string($value) && !empty($value)) {
                // Skip URLs and HTML
                if (!filter_var($value, FILTER_VALIDATE_URL) && 
                    !preg_match('/^<[^>]+>$/', $value)) {
                    $text .= ' ' . strip_tags($value);
                }
            } elseif (is_array($value)) {
                // Recursively parse array values
                $text .= $this->extractTextFromArray($value);
            }
        }
        
        return $text;
    }
    
    /**
     * Extract text from array recursively
     * 
     * @param array $array Array to extract from
     * @return string Extracted text
     */
    protected function extractTextFromArray(array $array)
    {
        $text = '';
        
        foreach ($array as $value) {
            if (is_string($value) && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_URL) && 
                    !preg_match('/^<[^>]+>$/', $value)) {
                    $text .= ' ' . strip_tags($value);
                }
            } elseif (is_array($value)) {
                $text .= $this->extractTextFromArray($value);
            }
        }
        
        return $text;
    }
    
    /**
     * Get cached parsed content
     * 
     * @param int $pageId SP Page Builder page ID
     * @param string $jsonContent Current JSON content (for hash comparison)
     * @return string|false Cached content or false if not found/stale
     */
    protected function getCachedContent($pageId, $jsonContent)
    {
        try {
            $contentHash = md5($jsonContent);
            
            $query = $this->db->getQuery(true)
                ->select('parsed_content')
                ->from($this->db->quoteName('#__ajaxsearch_sp_cache'))
                ->where($this->db->quoteName('sppagebuilder_id') . ' = ' . (int)$pageId)
                ->where($this->db->quoteName('content_hash') . ' = ' . $this->db->quote($contentHash));
            
            $this->db->setQuery($query);
            $cached = $this->db->loadResult();
            
            return $cached !== null ? $cached : false;
            
        } catch (Exception $e) {
            error_log('AJAXSearch SP Cache Get Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache parsed content
     * 
     * @param int $pageId SP Page Builder page ID
     * @param string $jsonContent Original JSON content
     * @param string $parsedContent Parsed text content
     * @return bool Success
     */
    protected function cacheContent($pageId, $jsonContent, $parsedContent)
    {
        try {
            $contentHash = md5($jsonContent);
            
            // Check if entry exists
            $query = $this->db->getQuery(true)
                ->select('id')
                ->from($this->db->quoteName('#__ajaxsearch_sp_cache'))
                ->where($this->db->quoteName('sppagebuilder_id') . ' = ' . (int)$pageId);
            
            $this->db->setQuery($query);
            $existingId = $this->db->loadResult();
            
            if ($existingId) {
                // Update existing
                $object = new stdClass();
                $object->id = $existingId;
                $object->parsed_content = $parsedContent;
                $object->content_hash = $contentHash;
                $object->parsed_date = date('Y-m-d H:i:s');
                
                return $this->db->updateObject('#__ajaxsearch_sp_cache', $object, 'id');
            } else {
                // Insert new
                $object = new stdClass();
                $object->sppagebuilder_id = $pageId;
                $object->parsed_content = $parsedContent;
                $object->content_hash = $contentHash;
                $object->parsed_date = date('Y-m-d H:i:s');
                $object->version = '1.0'; // SP Page Builder version can be detected later
                
                return $this->db->insertObject('#__ajaxsearch_sp_cache', $object);
            }
            
        } catch (Exception $e) {
            error_log('AJAXSearch SP Cache Set Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear cache for specific page
     * 
     * @param int $pageId SP Page Builder page ID
     * @return bool Success
     */
    public function clearPageCache($pageId)
    {
        return $this->db->delete('ajaxsearch_sp_cache', 
            $this->db->quoteName('sppagebuilder_id') . ' = ' . (int)$pageId
        );
    }
    
    /**
     * Clear all SP cache
     * 
     * @return bool Success
     */
    public function clearAllCache()
    {
        return $this->db->execute('TRUNCATE TABLE ' . $this->db->quoteName('#__ajaxsearch_sp_cache'));
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getCacheStats()
    {
        $stats = [
            'total_pages' => 0,
            'total_size' => 0
        ];
        
        try {
            // Total cached pages
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__ajaxsearch_sp_cache'));
            $this->db->setQuery($query);
            $stats['total_pages'] = (int) $this->db->loadResult();
            
            // Total size (approximate)
            $query = $this->db->getQuery(true)
                ->select('ROUND(SUM(LENGTH(parsed_content)) / 1024)')
                ->from($this->db->quoteName('#__ajaxsearch_sp_cache'));
            $this->db->setQuery($query);
            $stats['total_size'] = (int) $this->db->loadResult() . ' KB';
            
        } catch (Exception $e) {
            error_log('AJAXSearch SP Cache Stats Error: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Enable debug mode
     * 
     * @param bool $debug Debug mode
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
    }
    
    /**
     * Test parser with sample JSON
     * 
     * @param string $json Sample JSON
     * @return array Test results
     */
    public function testParser($json = null)
    {
        if ($json === null) {
            // Create sample SP Page Builder JSON
            $json = $this->getSampleJson();
        }
        
        $results = [
            'json_valid' => json_last_error() === JSON_ERROR_NONE,
            'json_size' => strlen($json),
            'parse_time' => 0,
            'extracted_text' => '',
            'extracted_length' => 0
        ];
        
        $start = microtime(true);
        $extracted = $this->parse($json, 9999); // Test ID
        $results['parse_time'] = microtime(true) - $start;
        
        $results['extracted_text'] = substr($extracted, 0, 500) . (strlen($extracted) > 500 ? '...' : '');
        $results['extracted_length'] = strlen($extracted);
        
        return $results;
    }
    
    /**
     * Get sample JSON for testing
     * 
     * @return string Sample JSON
     */
    protected function getSampleJson()
    {
        return '[
            {
                "type": "row",
                "columns": [
                    {
                        "addons": [
                            {
                                "type": "heading",
                                "settings": {
                                    "title": "Welcome to Our Website",
                                    "subtitle": "Learn more about our services"
                                }
                            },
                            {
                                "type": "text_block",
                                "settings": {
                                    "text": "<p>This is a sample paragraph from SP Page Builder. It contains searchable text that should be indexed by our search system.</p>"
                                }
                            }
                        ]
                    }
                ]
            }
        ]';
    }
}