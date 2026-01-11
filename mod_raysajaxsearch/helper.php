<?php
/**
 * @package     RaysAjaxSearch
 * @subpackage  Module
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper class for Rays AJAX Search module
 */
class ModRaysajaxsearchHelper
{
    /**
     * Get search statistics
     * 
     * @return array Search statistics
     */
    public static function getStats()
    {
        try {
            // Include component files
            $componentPath = JPATH_SITE . '/components/com_ajaxsearch/src';
            
            if (!file_exists($componentPath . '/Database/Adapter.php')) {
                return ['error' => 'Component not installed'];
            }
            
            require_once $componentPath . '/Database/Adapter.php';
            require_once $componentPath . '/Models/SearchModel.php';
            
            $dbAdapter = new AjaxsearchDatabaseAdapter();
            $config = [];
            $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
            
            return $searchModel->getStats();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Test search functionality
     * 
     * @param string $query Test query
     * @return array Test results
     */
    public static function testSearch($query = 'test')
    {
        try {
            $componentPath = JPATH_SITE . '/components/com_ajaxsearch/src';
            
            if (!file_exists($componentPath . '/Database/Adapter.php')) {
                return ['error' => 'Component not installed'];
            }
            
            require_once $componentPath . '/Database/Adapter.php';
            require_once $componentPath . '/Models/SearchModel.php';
            
            $dbAdapter = new AjaxsearchDatabaseAdapter();
            $config = [];
            $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
            
            return $searchModel->testSearch($query);
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Clear search cache
     * 
     * @return array Result
     */
    public static function clearCache()
    {
        try {
            $componentPath = JPATH_SITE . '/components/com_ajaxsearch/src';
            
            if (!file_exists($componentPath . '/Database/Adapter.php')) {
                return ['success' => false, 'error' => 'Component not installed'];
            }
            
            require_once $componentPath . '/Database/Adapter.php';
            require_once $componentPath . '/Models/SearchModel.php';
            
            $dbAdapter = new AjaxsearchDatabaseAdapter();
            $config = [];
            $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
            
            $result = $searchModel->clearCache();
            
            return [
                'success' => $result,
                'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get module configuration
     * 
     * @param Joomla\Registry\Registry $params Module parameters
     * @return array Configuration
     */
    public static function getConfig($params)
    {
        return [
            'search_types' => $params->get('search_types', ['article', 'sppagebuilder']),
            'min_chars' => (int) $params->get('min_chars', 2),
            'typing_delay' => (int) $params->get('typing_delay', 300),
            'results_limit' => (int) $params->get('results_limit', 10),
            'show_images' => (bool) $params->get('show_images', 0),
            'show_category' => (bool) $params->get('show_category', 1),
            'show_date' => (bool) $params->get('show_date', 1),
            'show_type' => (bool) $params->get('show_type', 1)
        ];
    }
}