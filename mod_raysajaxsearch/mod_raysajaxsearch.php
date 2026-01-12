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
        'ajax_url' => Uri::root(true) . '/index.php?option=com_ajaxsearch&format=json'
    ]
];

// Load CSS and JS
$document = Factory::getDocument();

// Add CSS directly
$document->addStyleSheet(Uri::root(true) . '/modules/mod_raysajaxsearch/media/ajaxsearch.css');

// Inject config directly into page as a global variable before loading the script
$configJson = json_encode($moduleData);
$ajaxUrl = Uri::root(true) . '/modules/mod_raysajaxsearch/ajax.php';
$document->addScriptDeclaration(<<<JS
window.raysAjaxSearchConfig = window.raysAjaxSearchConfig || {};
window.raysAjaxSearchConfig[{$moduleData['id']}] = {
    id: {$moduleData['id']},
    params: {$configJson},
    ajaxUrl: '{$ajaxUrl}'
};
JS
);

// Add JS directly
$document->addScript(Uri::root(true) . '/modules/mod_raysajaxsearch/media/ajaxsearch.js');

// Include template
require ModuleHelper::getLayoutPath('mod_raysajaxsearch', $params->get('layout', 'default'));