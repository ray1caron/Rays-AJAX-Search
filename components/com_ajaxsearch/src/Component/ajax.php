<?php
/**
 * @package     AJAXSearch
 * @subpackage  Component
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Get Joomla application
    $app = JFactory::getApplication();
    
    // Include required files
    $basePath = dirname(__DIR__);
    
    // Load compatibility layer
    require_once $basePath . '/Compatibility/VersionCheck.php';
    
    // Load database adapter
    require_once $basePath . '/Database/Adapter.php';
    
    // Load cache system
    require_once $basePath . '/Cache/BasicCache.php';
    
    // Load search engines
    require_once $basePath . '/Search/AbstractSearchEngine.php';
    require_once $basePath . '/Search/ArticleSearch.php';
    require_once $basePath . '/Parser/SPParser.php';
    require_once $basePath . '/Search/SPSearch.php';
    require_once $basePath . '/Search/IntegratedSearch.php';
    require_once $basePath . '/Search/EnhancedRelevanceScorer.php';
    require_once $basePath . '/Search/CustomFieldSearch.php';
    require_once $basePath . '/Search/SuggestionsEngine.php';
    require_once $basePath . '/Search/EnhancedIntegratedSearch.php';
    
    // Load models
    require_once $basePath . '/Models/EnhancedSearchModel.php';
    
    // Initialize database adapter
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    
    // Load configuration from database
    $config = loadConfiguration($dbAdapter);
    
    // Initialize enhanced search model (Session2.1 canonical)
    $searchModel = new AjaxsearchEnhancedSearchModel($dbAdapter, $config);
    
    // Get request parameters
    $input = $app->input;
    $action = $input->getString('action', 'search');
    $query = $input->getString('q', '');
    $limit = $input->getInt('limit', 10);
    $offset = $input->getInt('offset', 0);
    $types = $input->get('types', [], 'array');
    
    // Handle different actions
    switch ($action) {
        case 'search':
            // Validate query
            if (empty($query) || strlen($query) < 2) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Query must be at least 2 characters long',
                    'results' => [],
                    'total' => 0
                ]);
                jexit();
            }
            
            // Prepare search options
            $options = [
                'limit' => $limit,
                'offset' => $offset
            ];
            
            // Add type filter if specified
            if (!empty($types)) {
                $options['types'] = $types;
            }
            
            // Perform search with caching (DB-backed via BasicCache)
            $results = $searchModel->search($query, $options);
            
            // Return JSON response
            echo json_encode($results);
            break;
            
        case 'suggest':
            // Get search suggestions
            if (strlen($query) >= 2) {
                $suggestions = $searchModel->getSuggestions($query, ['limit' => $limit]);
                echo json_encode([
                    'success' => true,
                    'suggestions' => $suggestions,
                    'query' => $query
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'suggestions' => [],
                    'query' => $query
                ]);
            }
            break;
            
        case 'trending':
            // Get trending searches
            $timeframe = $input->getString('timeframe', 'week');
            $trendingLimit = $input->getInt('trending_limit', 10);
            $trendingData = $searchModel->getTrendingSearches($trendingLimit, $timeframe);
            echo json_encode([
                'success' => true,
                'trending' => $trendingData,
                'timeframe' => $timeframe
            ]);
            break;
            
        case 'analytics':
            // Get search analytics
            $timeframe = $input->getString('timeframe', 'week');
            $analyticsData = $searchModel->getAnalytics($timeframe);
            echo json_encode([
                'success' => true,
                'analytics' => $analyticsData,
                'timeframe' => $timeframe
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action specified'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log the full error for debugging
    error_log('AJAXSearch Error: ' . $e->getMessage());
    
    // Return safe error message (no stack trace)
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your request'
    ]);
}

// Exit cleanly to avoid HTML template append
jexit();

/**
 * Load configuration from database
 * 
 * @param AjaxsearchDatabaseAdapter $db Database adapter
 * @return array Configuration array
 */
function loadConfiguration($db)
{
    $config = [
        'cache_ttl' => 300,
        'enable_cache' => true,
        'search_articles' => true,
        'search_sppages' => true,
        'search_customfields' => true,
        'enable_analytics' => true,
        'max_results' => 50
    ];
    
    try {
        $query = $db->getQuery(true)
            ->select(['param_name', 'param_value', 'param_type'])
            ->from($db->quoteName('#__ajaxsearch_config'))
            ->where($db->quoteName('component') . ' IN (' . $db->quote('global') . ', ' . $db->quote('weights') . ')');
        
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        foreach ($results as $row) {
            $value = $row->param_value;
            
            // Convert value based on type
            switch ($row->param_type) {
                case 'int':
                    $value = (int) $value;
                    break;
                case 'bool':
                    $value = (bool) $value;
                    break;
                case 'float':
                    $value = (float) $value;
                    break;
            }
            
            $config[$row->param_name] = $value;
        }
    } catch (Exception $e) {
        error_log('AJAXSearch Config Load Error: ' . $e->getMessage());
        // Use defaults on error
    }
    
    return $config;
}
