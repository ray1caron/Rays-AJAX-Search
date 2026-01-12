<?php
/**
 * @package     RaysAjaxSearch
 * @subpackage  Module
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

// Include module helper
require_once __DIR__ . '/helper.php';

// Get module parameters
$searchTypes = $params->get('search_types', ['article', 'sppagebuilder']);
$minChars = (int) $params->get('min_chars', 2);
$delay = (int) $params->get('typing_delay', 300);
$limit = (int) $params->get('results_limit', 10);
$showImages = (bool) $params->get('show_images', 0);
$moduleClassSfx = htmlspecialchars($params->get('moduleclass_sfx', ''));

// Convert search types to array if string
if (is_string($searchTypes)) {
    $searchTypes = explode(',', $searchTypes);
}

// Prepare URLs for endpoints
$baseUrl = Uri::root();
$suggestUrl = $baseUrl . 'components/com_ajaxsearch/src/Component/suggest.php';
$ajaxUrl = $baseUrl . 'components/com_ajaxsearch/src/Component/ajax.php';
$resultsPageUrl = $baseUrl . 'index.php?option=com_ajaxsearch&view=results';

// Prepare module data
$moduleData = [
    'id' => $module->id,
    'title' => $module->title,
    'position' => $module->position,
    'params' => [
        'search_types' => $searchTypes,
        'min_chars' => $minChars,
        'typing_delay' => $delay,
        'results_limit' => $limit,
        'show_images' => $showImages,
        'suggest_url' => $suggestUrl,
        'ajax_url' => $ajaxUrl,
        'results_page_url' => $resultsPageUrl
    ]
];

// Load CSS and JS
$document = Factory::getDocument();
$wa = $document->getWebAssetManager();

// Add module CSS
$wa->registerAndUseStyle('mod_raysajaxsearch', 'mod_raysajaxsearch/ajaxsearch.css');

// Add module JS
$wa->registerAndUseScript('mod_raysajaxsearch', 'mod_raysajaxsearch/ajaxsearch.js', [], ['type' => 'module']);

// Pass data to JavaScript
$document->addScriptOptions('mod_raysajaxsearch_' . $module->id, $moduleData);

// Include template
require ModuleHelper::getLayoutPath('mod_raysajaxsearch', $params->get('layout', 'default'));