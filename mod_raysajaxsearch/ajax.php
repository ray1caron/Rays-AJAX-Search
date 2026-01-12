<?php
/**
 * Rays AJAX Search - Direct AJAX Handler
 * Path: /modules/mod_raysajaxsearch/ajax.php?q=search+term
 */

// Set JSON headers immediately BEFORE any other output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// SECURITY: Define execution constant
define('_JEXEC', 1);

// PHASE 1 TEST: Just echo to verify endpoint is reachable
echo json_encode(['success' => true, 'test' => 'Phase 1: Endpoint reachable', 'results' => []]);
exit;

// ========== BELOW IS DISABLED DURING TESTING ==========

// Calculate JPATH_BASE correctly
$parts = explode(DIRECTORY_SEPARATOR, __DIR__);
array_pop($parts); // Remove current dir
array_pop($parts); // Remove mod_raysajaxsearch
array_pop($parts); // Remove modules
define('JPATH_BASE', implode(DIRECTORY_SEPARATOR, $parts));

use Joomla\CMS\Factory;
use Joomla\CMS\Database\DatabaseInterface;
use Joomla\CMS\Uri\Uri;

try {
    require_once JPATH_BASE . '/includes/defines.php';
    require_once JPATH_BASE . '/includes/framework.php';
    
    // Get Joomla app
    $app = Factory::getApplication('site');
    $input = $app->input;

    // Get search parameters
    $query = $input->get('q', '', 'STRING');
    $limit = (int) $input->get('limit', 10, 'INT');
    $offset = (int) $input->get('offset', 0, 'INT');

    // Validate input
    if (strlen(trim($query)) < 2) {
        echo json_encode(['success' => false, 'error' => 'Query too short', 'results' => []]);
        exit;
    }

    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $user = Factory::getUser();
    $accessLevels = implode(',', $user->getAuthorisedViewLevels());

    // Search term
    $searchTerm = '%' . $db->escape($query, true) . '%';

    // Build query for articles
    $sql = $db->getQuery(true)
        ->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.title'),
            $db->quoteName('a.introtext'),
            $db->quoteName('a.fulltext'),
            $db->quoteName('a.catid'),
            $db->quoteName('a.created'),
            $db->quoteName('c.title', 'category_name')
        ])
        ->from($db->quoteName('#__content', 'a'))
        ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id'))
        ->where('(' . 
            $db->quoteName('a.title') . ' LIKE ' . $db->quote($searchTerm) . ' OR ' .
            $db->quoteName('a.introtext') . ' LIKE ' . $db->quote($searchTerm) . ' OR ' .
            $db->quoteName('a.fulltext') . ' LIKE ' . $db->quote($searchTerm) .
        ')')
        ->where($db->quoteName('a.state') . ' = 1')
        ->where($db->quoteName('a.access') . ' IN (' . $accessLevels . ')')
        ->order('a.title ASC')
        ->setLimit($limit, $offset);

    $db->setQuery($sql);
    $articles = $db->loadObjectList();

    // Format results
    $results = [];
    foreach ($articles as $article) {
        $text = !empty($article->introtext) ? $article->introtext : $article->fulltext;
        $excerpt = strip_tags($text);
        $excerpt = substr($excerpt, 0, 150);
        if (strlen($text) > 150) {
            $excerpt .= '...';
        }

        $results[] = [
            'id' => $article->id,
            'title' => htmlspecialchars($article->title),
            'excerpt' => htmlspecialchars($excerpt),
            'url' => Uri::root() . 'index.php?view=article&id=' . $article->id . '&catid=' . $article->catid,
            'category' => htmlspecialchars($article->category_name),
            'date' => $article->created,
            'type' => 'article'
        ];
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Search failed: ' . $e->getMessage(),
        'results' => []
    ]);
}

exit;
