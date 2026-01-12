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
    
    // Load suggestions engine
    require_once $basePath . '/Search/SuggestionsEngine.php';
    
    // Initialize database adapter
    $dbAdapter = new AjaxsearchDatabaseAdapter();
    
    // Initialize suggestions engine
    $suggestionsEngine = new AjaxsearchSuggestionsEngine($dbAdapter);
    
    // Get request parameters
    $input = $app->input;
    $query = $input->getString('q', '');
    $limit = $input->getInt('limit', 5);
    
    // Validate query
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'suggestions' => [],
            'query' => $query
        ]);
        jexit();
    }
    
    // Generate cache key
    $cacheKey = 'suggest_' . md5($query . '_' . $limit);
    
    // Try to get from cache first
    $cache = new AjaxsearchCacheBasic($dbAdapter, ['cache_ttl' => 60]); // 1 minute TTL for suggestions
    $cached = $cache->get($cacheKey);
    
    if ($cached !== false) {
        echo json_encode([
            'success' => true,
            'suggestions' => $cached,
            'query' => $query,
            'cached' => true
        ]);
        jexit();
    }
    
    // Get suggestions from engine
    $suggestions = $suggestionsEngine->getSuggestions($query, ['limit' => $limit]);
    
    // Cache the suggestions
    $cache->set($cacheKey, $suggestions, 60);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'query' => $query,
        'cached' => false
    ]);
    
} catch (Exception $e) {
    // Log the full error for debugging
    error_log('AJAXSearch Suggestions Error: ' . $e->getMessage());
    
    // Return safe error message (no stack trace)
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while processing your request'
    ]);
}

// Exit cleanly to avoid HTML template append
jexit();
