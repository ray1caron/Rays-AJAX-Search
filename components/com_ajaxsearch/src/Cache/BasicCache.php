<?php
/**
 * @package     AJAXSearch
 * @subpackage  Cache
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Basic caching system for search results
 */
class AjaxsearchCacheBasic
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var int Default cache TTL in seconds
     */
    protected $defaultTtl = 300;
    
    /**
     * @var bool Enable cache
     */
    protected $enabled = true;
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     * @param array $config Configuration
     */
    public function __construct(AjaxsearchDatabaseAdapter $db, array $config = [])
    {
        $this->db = $db;
        
        if (isset($config['cache_ttl'])) {
            $this->defaultTtl = (int) $config['cache_ttl'];
        }
        
        if (isset($config['enable_cache'])) {
            $this->enabled = (bool) $config['enable_cache'];
        }
    }
    
    /**
     * Get cached results
     * 
     * @param string $key Cache key
     * @return mixed Cached data or false if not found
     */
    public function get($key)
    {
        if (!$this->enabled) {
            return false;
        }
        
        $query = $this->db->getQuery(true)
            ->select('results')
            ->from($this->db->quoteName('#__ajaxsearch_cache'))
            ->where($this->db->quoteName('query_hash') . ' = ' . $this->db->quote($key))
            ->where($this->db->quoteName('expires') . ' > NOW()');
        
        $this->db->setQuery($query);
        
        try {
            $result = $this->db->loadResult();
            
            if ($result) {
                // Update hit count
                $this->incrementHits($key);
                
                // Return decoded data
                return json_decode($result, true);
            }
        } catch (Exception $e) {
            error_log('AJAXSearch Cache Get Error: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Store results in cache
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool Success
     */
    public function set($key, $data, $ttl = null)
    {
        if (!$this->enabled) {
            return false;
        }
        
        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        
        $queryText = isset($data['query']) ? $data['query'] : '';
        $resultsJson = json_encode($data);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AJAXSearch Cache JSON Error: ' . json_last_error_msg());
            return false;
        }
        
        // Get user groups for cache segmentation
        $user = JFactory::getUser();
        $userGroups = implode(',', $user->getAuthorisedGroups());
        $language = JFactory::getLanguage()->getTag();
        
        // Check if cache entry already exists
        $existing = $this->getCacheEntry($key);
        
        if ($existing) {
            // Update existing entry
            $object = new stdClass();
            $object->id = $existing->id;
            $object->results = $resultsJson;
            $object->hits = $existing->hits;
            $object->expires = date('Y-m-d H:i:s', time() + $ttl);
            
            return $this->db->updateObject('#__ajaxsearch_cache', $object, 'id');
        } else {
            // Create new entry
            $object = new stdClass();
            $object->query_hash = $key;
            $object->query_text = substr($queryText, 0, 255);
            $object->results = $resultsJson;
            $object->hits = 0;
            $object->created = date('Y-m-d H:i:s');
            $object->expires = date('Y-m-d H:i:s', time() + $ttl);
            $object->language = $language;
            $object->user_group = $userGroups;
            
            return $this->db->insertObject('#__ajaxsearch_cache', $object);
        }
    }
    
    /**
     * Delete cache entry
     * 
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($key)
    {
        return $this->db->delete('ajaxsearch_cache', 
            $this->db->quoteName('query_hash') . ' = ' . $this->db->quote($key)
        );
    }
    
    /**
     * Clear all cache entries
     * 
     * @return bool Success
     */
    public function clearAll()
    {
        try {
            $query = $this->db->getQuery(true)
                ->delete($this->db->quoteName('#__ajaxsearch_cache'));
            
            $this->db->setQuery($query);
            $this->db->execute();
            
            return true;
        } catch (Exception $e) {
            error_log('AJAXSearch Cache Clear Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired cache entries
     * 
     * @return int Number of deleted entries
     */
    public function cleanExpired()
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__ajaxsearch_cache'))
            ->where($this->db->quoteName('expires') . ' <= NOW()');
        
        $this->db->setQuery($query);
        
        try {
            $this->db->execute();
            return $this->db->getAffectedRows();
        } catch (Exception $e) {
            error_log('AJAXSearch Cache Clean Error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStats()
    {
        $stats = [
            'total_entries' => 0,
            'expired_entries' => 0,
            'total_hits' => 0,
            'total_size' => 0
        ];
        
        try {
            // Total entries
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__ajaxsearch_cache'));
            $this->db->setQuery($query);
            $stats['total_entries'] = (int) $this->db->loadResult();
            
            // Expired entries
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__ajaxsearch_cache'))
                ->where($this->db->quoteName('expires') . ' <= NOW()');
            $this->db->setQuery($query);
            $stats['expired_entries'] = (int) $this->db->loadResult();
            
            // Total hits
            $query = $this->db->getQuery(true)
                ->select('SUM(hits)')
                ->from($this->db->quoteName('#__ajaxsearch_cache'));
            $this->db->setQuery($query);
            $stats['total_hits'] = (int) $this->db->loadResult();
            
            // Total size (approximate)
            $query = $this->db->getQuery(true)
                ->select('ROUND(SUM(LENGTH(results)) / 1024)')
                ->from($this->db->quoteName('#__ajaxsearch_cache'));
            $this->db->setQuery($query);
            $stats['total_size'] = (int) $this->db->loadResult() . ' KB';
            
        } catch (Exception $e) {
            error_log('AJAXSearch Cache Stats Error: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Get cache entry by key
     * 
     * @param string $key Cache key
     * @return mixed Cache entry or false
     */
    private function getCacheEntry($key)
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__ajaxsearch_cache'))
            ->where($this->db->quoteName('query_hash') . ' = ' . $this->db->quote($key));
        
        $this->db->setQuery($query);
        
        try {
            return $this->db->loadObject();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Increment hit count for cache entry
     * 
     * @param string $key Cache key
     * @return bool Success
     */
    private function incrementHits($key)
    {
        $query = 'UPDATE ' . $this->db->quoteName('#__ajaxsearch_cache') . 
                 ' SET hits = hits + 1 WHERE ' . 
                 $this->db->quoteName('query_hash') . ' = ' . $this->db->quote($key);
        
        try {
            $this->db->setQuery($query);
            return $this->db->execute();
        } catch (Exception $e) {
            error_log('AJAXSearch Cache Increment Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enable or disable cache
     * 
     * @param bool $enabled Cache enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
    }
    
    /**
     * Check if cache is enabled
     * 
     * @return bool Cache enabled
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}