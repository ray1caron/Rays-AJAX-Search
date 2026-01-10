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
require_once $basePath . '/Cache/BasicCache.php';
require_once $basePath . '/Models/SearchModel.php';

try {
    // Initialize database adapter
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    
    // Load configuration from database
    $config = loadConfiguration($dbAdapter);
    
    // Initialize search model
    $searchModel = new AjaxsearchModelSearch($dbAdapter, $config);
    
    // Get request parameters
    $input = $app->input;
    $query = $input->getString('q', '');
    $limit = $input->getInt('limit', 10);
    $offset = $input->getInt('offset', 0);
    $category = $input->getString('category', '');
    $type = $input->getString('type', ''); // 'article', 'sp', or empty for both
    $format = $input->getString('format', 'json');
    $debug = $input->getBool('debug', false);
    
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
        } elseif ($type === 'all') {
            $options['types'] = ['article', 'sppagebuilder'];
        }
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
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ];
    }
    
    // Return results
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Return error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'request' => [
            'query' => $query ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

jexit();

/**
 * Load configuration from database
 * 
 * @param AjaxsearchDatabaseAdapter $db Database adapter
 * @return array Configuration
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
        
        // Ensure search types are enabled
        if (!isset($config['enabled_types'])) {
            $config['enabled_types'] = ['article', 'sppagebuilder'];
        }
        
    } catch (Exception $e) {
        // Use defaults if configuration table doesn't exist
        error_log('AJAXSearch Config Load Error: ' . $e->getMessage());
        $config = [
            'cache_ttl' => 300,
            'search_articles' => true,
            'search_sppages' => true,
            'max_results' => 50,
            'enable_cache' => true,
            'enabled_types' => ['article', 'sppagebuilder']
        ];
    }
    
    return $config;
}