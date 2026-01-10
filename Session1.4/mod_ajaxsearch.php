<?php
/**
 * @package     AJAXSearch
 * @subpackage  Module
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Uri\Uri;

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
        'ajax_url' => Uri::root() . 'components/com_ajaxsearch/src/Component/ajax.php'
    ]
];

// Load CSS and JS
$document = Factory::getDocument();
$wa = $document->getWebAssetManager();

// Add module CSS
$wa->registerAndUseStyle('mod_ajaxsearch', 'mod_ajaxsearch/ajaxsearch.css');

// Add module JS
$wa->registerAndUseScript('mod_ajaxsearch', 'mod_ajaxsearch/ajaxsearch.js', [], ['type' => 'module']);

// Pass data to JavaScript
$document->addScriptOptions('mod_ajaxsearch_' . $module->id, $moduleData);

// Include template
require ModuleHelper::getLayoutPath('mod_ajaxsearch', $params->get('layout', 'default'));