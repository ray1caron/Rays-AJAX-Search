<?php

/**
 * @title		Minitek Live Search
 * @copyright	Copyright (C) 2011-2022 Minitek, All rights reserved.
 * @license		GNU General Public License version 3 or later.
 * @author url	https://www.minitek.gr/
 * @developers	Minitek.gr
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\MinitekLiveSearch\Site\Controller\DisplayController;

// Add component registry file (for assets)
$document = $app->getDocument();
$wa = $document->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_miniteklivesearch');

$jinput = Factory::getApplication()->input;

// Store original page vars
$option = $jinput->getCmd('option', NULL);
$view = $jinput->getCmd('view', NULL);
$layout = $jinput->getCmd('layout', NULL);
$task = $jinput->getCmd('task', NULL);

// Change to Minitek Live Search view
$jinput->set('option', 'com_miniteklivesearch');
$jinput->set('view', 'search');
$jinput->set('layout', '');
$jinput->set('task', 'display');

// Set moduleid=form so that we know if the search form is loaded via a module and which form id
$jinput->set('moduleid', $params->get('form', 0, 'INT'));

// Load language
$lang = Factory::getLanguage();
$lang->load('com_miniteklivesearch', JPATH_SITE);

// Load controller
$config = array(
	'base_path' => JPATH_SITE . '/components/com_miniteklivesearch',
	'view_path' => JPATH_SITE . '/components/com_miniteklivesearch/src/Module/views',
	'model_path' => JPATH_SITE . '/components/com_miniteklivesearch/src/Module/models',
	'name' => 'Module', // view prefix
	'model_prefix' => 'ModuleModel', // model prefix
);
$controller = new DisplayController($config);
$controller->execute('display');

// Revert back to original page vars
if ($option != null)
	$jinput->set('option', $option);

if ($view != null)
	$jinput->set('view', $view);

if ($layout != null)
	$jinput->set('layout', $layout);

if ($task != null)
	$jinput->set('task', $task);
