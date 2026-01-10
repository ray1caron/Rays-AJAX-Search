<?php
/**
 * @package     AJAXSearch
 * @subpackage  Component
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Get Joomla application
$app = JFactory::getApplication();

// Check if it's an AJAX request
if ($app->input->server->get('HTTP_X_REQUESTED_WITH', '') !== 'xmlhttprequest') {
    // Not an AJAX request, return error
    echo json_encode([
        'success' => false,
        'error' => 'Direct access not allowed'
    ]);
    jexit();
}

// Include required files
$basePath = dirname(__DIR__);

require_once $basePath . '/Compatibility/VersionCheck.php';
require_once $basePath . '/Database/Adapter.php';
require_once $basePath . '/Search/AbstractSearchEngine.php';
require_once $basePath . '/Search/ArticleSearch.php';
require_once $basePath . '/Parser/SPParser.php';
require_once $basePath . '/Search/SPSearch.php';
require_once $basePath . '/Search/IntegratedSearch.php';
require_once $basePath . '/Search/EnhancedRelevanceScorer.php';
require_once $basePath . '/Search/CustomFieldSearch.php';
require_once $basePath . '/Search/SuggestionsEngine.php';
require_once $basePath . '/Search/EnhancedIntegratedSearch.php';
require_once $basePath . '/Cache/BasicCache.php';
require_once $basePath . '/Models/EnhancedSearchModel.php';

try {
    // Initialize database adapter
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    
    // Load configuration from database
    $config = loadConfiguration($dbAdapter);
    
    // Initialize enhanced search model
    $searchModel = new AjaxsearchEnhancedSearchModel($dbAdapter, $config);
    
    // Get request parameters
    $input = $app->input;
    $action = $input->getString('action', 'search');
    $query = $input->getString('q', '');
    $limit = $input->getInt('limit', 10);
    $offset = $input->getInt('offset', 0);
    $category = $input->getString('category', '');
    $type = $input->getString('type', ''); // 'article', 'sp', 'custom', or empty for all
    $suggest = $input->getBool('suggest', false);
    $trending = $input->getBool('trending', false);
    $analytics = $input->getBool('analytics', false);
    $debug = $input->getBool('debug', false);
    
    // Handle different actions
    switch ($action) {
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
            
        case 'stats':
            // Get search statistics
            $stats = $searchModel->getStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'clear_cache':
            // Clear search cache (admin only)
            $user = JFactory::getUser();
            if ($user->authorise('core.manage', 'com_ajaxsearch')) {
                $result = $searchModel->clearCache();
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Not authorized'
                ]);
            }
            break;
            
        case 'search':
        default:
            // Perform search
            handleSearch($searchModel, $input, $config, $debug);
            break;
    }
    
} catch (Exception $e) {
    // Return error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

jexit();

/**
 * Handle search request
 */
function handleSearch($searchModel, $input, $config, $debug)
{
    $query = $input->getString('q', '');
    $limit = $input->getInt('limit', 10);
    $offset = $input->getInt('offset', 0);
    $category = $input->getString('category', '');
    $type = $input->getString('type', '');
    
    // Validate parameters
    if (empty($query)) {
        throw new Exception('Search query is required');
    }
    
    if ($limit < 1 || $limit > 100) {
        $limit = 10;
    }
    
    if ($offset < 0) {
        $offset = 0;
    }
    
    // Prepare search options
    $options = [
        'limit' => $limit,
        'offset' => $offset
    ];
    
    // Add category filter if provided
    if (!empty($category)) {
        $categories = array_map('intval', explode(',', $category));
        $options['category'] = $categories;
    }
    
    // Add type filter if provided
    if (!empty($type)) {
        if ($type === 'article') {
            $options['types'] = ['article'];
        } elseif ($type === 'sp') {
            $options['types'] = ['sppagebuilder'];
        } elseif ($type === 'custom') {
            $options['types'] = ['article'];
            $options['include_custom_fields'] = true;
            $options['enhanced_scoring'] = true;
        } elseif ($type === 'all') {
            $options['types'] = ['article', 'sppagebuilder'];
            $options['include_custom_fields'] = true;
            $options['enhanced_scoring'] = true;
        }
    } else {
        // Default: all types with enhanced features
        $options['types'] = ['article', 'sppagebuilder'];
        $options['include_custom_fields'] = $config['enable_custom_fields'] ?? true;
        $options['enhanced_scoring'] = $config['enable_enhanced_scoring'] ?? true;
    }
    
    // Perform search
    $results = $searchModel->search($query, $options);
    
    // Add request info
    $results['request'] = [
        'query' => $query,
        'limit' => $limit,
        'offset' => $offset,
        'category' => $category,
        'type' => $type,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Add debug info if requested
    if ($debug) {
        $results['debug'] = [
            'config' => $config,
            'options' => $options,
            'php_version' => PHP_VERSION,
            'joomla_version' => JVERSION,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    // Return results
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Load configuration from database
 */
function loadConfiguration($db)
{
    $config = [];
    
    try {
        $query = $db->getQuery()
            ->select('param_name, param_value, param_type')
            ->from($db->quoteName('#__ajaxsearch_config'))
            ->where($db->quoteName('component') . ' = ' . $db->quote('global'));
        
        $db->setQuery($query);
        $rows = $db->loadResults('object');
        
        foreach ($rows as $row) {
            switch ($row->param_type) {
                case 'int':
                    $config[$row->param_name] = (int) $row->param_value;
                    break;
                case 'bool':
                    $config[$row->param_name] = (bool) $row->param_value;
                    break;
                case 'float':
                    $config[$row->param_name] = (float) $row->param_value;
                    break;
                case 'array':
                    $config[$row->param_name] = json_decode($row->param_value, true) ?: [];
                    break;
                default:
                    $config[$row->param_name] = $row->param_value;
            }
        }
        
        // Ensure enhanced features are enabled by default
        if (!isset($config['enable_custom_fields'])) {
            $config['enable_custom_fields'] = true;
        }
        if (!isset($config['enable_enhanced_scoring'])) {
            $config['enable_enhanced_scoring'] = true;
        }
        if (!isset($config['enabled_types'])) {
            $config['enabled_types'] = ['article', 'sppagebuilder'];
        }
        
        // Custom field weights configuration
        if (!isset($config['custom_field_weights'])) {
            $config['custom_field_weights'] = [
                'custom_fields' => 4,
                'meta_keywords' => 8,
                'meta_description' => 6
            ];
        }
        
    } catch (Exception $e) {
        // Use defaults if configuration table doesn't exist
        error_log('AJAXSearch Config Load Error: ' . $e->getMessage());
        $config = [
            'cache_ttl' => 300,
            'search_articles' => true,
            'search_sppages' => true,
            'enable_custom_fields' => true,
            'enable_enhanced_scoring' => true,
            'max_results' => 50,
            'enable_cache' => true,
            'enabled_types' => ['article', 'sppagebuilder'],
            'custom_field_weights' => [
                'custom_fields' => 4,
                'meta_keywords' => 8,
                'meta_description' => 6
            ]
        ];
    }
    
    return $config;
}