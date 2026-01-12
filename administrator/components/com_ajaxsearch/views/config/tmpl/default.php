<?php
/**
 * @package     AJAXSearch
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

// Get configuration from model
$app = Factory::getApplication();
$db = Factory::getDbo();

// Load configuration
$query = $db->getQuery(true)
    ->select(['param_name', 'param_value', 'param_type'])
    ->from($db->quoteName('#__ajaxsearch_config'))
    ->where($db->quoteName('component') . ' = ' . $db->quote('global'));

$db->setQuery($query);
$configRows = $db->loadObjectList();

$config = [];
foreach ($configRows as $row) {
    $value = $row->param_value;
    if ($row->param_type === 'bool') {
        $value = (bool) $value;
    } elseif ($row->param_type === 'int') {
        $value = (int) $value;
    }
    $config[$row->param_name] = $value;
}

// Set defaults if not found
$searchArticles = $config['search_articles'] ?? true;
$searchSppages = $config['search_sppages'] ?? true;
$enableAnalytics = $config['enable_analytics'] ?? true;
$cacheTtl = $config['cache_ttl'] ?? 300;
$maxResults = $config['max_results'] ?? 50;

?>
<div class="ajaxsearch-admin">
    <h1><?php echo Text::_('COM_AJAXSEARCH_CONFIG_TITLE'); ?></h1>
    
    <form action="index.php?option=com_ajaxsearch&task=config.save" method="post" name="adminForm" id="adminForm">
        
        <div class="control-group">
            <h2><?php echo Text::_('COM_AJAXSEARCH_CONTENT_SOURCES'); ?></h2>
            
            <div class="control-label">
                <label for="search_articles">
                    <input type="checkbox" 
                           name="config[search_articles]" 
                           id="search_articles" 
                           value="1" 
                           <?php echo $searchArticles ? 'checked="checked"' : ''; ?>>
                    <?php echo Text::_('COM_AJAXSEARCH_SEARCH_ARTICLES'); ?>
                </label>
            </div>
            
            <div class="control-label">
                <label for="search_sppages">
                    <input type="checkbox" 
                           name="config[search_sppages]" 
                           id="search_sppages" 
                           value="1" 
                           <?php echo $searchSppages ? 'checked="checked"' : ''; ?>>
                    <?php echo Text::_('COM_AJAXSEARCH_SEARCH_SPPAGES'); ?>
                </label>
            </div>
        </div>
        
        <div class="control-group">
            <h2><?php echo Text::_('COM_AJAXSEARCH_ANALYTICS'); ?></h2>
            
            <div class="control-label">
                <label for="enable_analytics">
                    <input type="checkbox" 
                           name="config[enable_analytics]" 
                           id="enable_analytics" 
                           value="1" 
                           <?php echo $enableAnalytics ? 'checked="checked"' : ''; ?>>
                    <?php echo Text::_('COM_AJAXSEARCH_ENABLE_ANALYTICS'); ?>
                </label>
            </div>
        </div>
        
        <div class="control-group">
            <h2><?php echo Text::_('COM_AJAXSEARCH_CACHE_SETTINGS'); ?></h2>
            
            <div class="control-label">
                <label for="cache_ttl"><?php echo Text::_('COM_AJAXSEARCH_CACHE_TTL'); ?></label>
                <input type="number" 
                       name="config[cache_ttl]" 
                       id="cache_ttl" 
                       value="<?php echo $cacheTtl; ?>" 
                       min="0" 
                       max="3600" 
                       class="form-control">
                <span class="help-block"><?php echo Text::_('COM_AJAXSEARCH_CACHE_TTL_DESC'); ?></span>
            </div>
        </div>
        
        <div class="control-group">
            <h2><?php echo Text::_('COM_AJAXSEARCH_RESULTS_SETTINGS'); ?></h2>
            
            <div class="control-label">
                <label for="max_results"><?php echo Text::_('COM_AJAXSEARCH_MAX_RESULTS'); ?></label>
                <input type="number" 
                       name="config[max_results]" 
                       id="max_results" 
                       value="<?php echo $maxResults; ?>" 
                       min="1" 
                       max="100" 
                       class="form-control">
                <span class="help-block"><?php echo Text::_('COM_AJAXSEARCH_MAX_RESULTS_DESC'); ?></span>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?php echo Text::_('JSAVE'); ?>
            </button>
            <button type="button" class="btn btn-warning" onclick="clearCache()">
                <?php echo Text::_('COM_AJAXSEARCH_CLEAR_CACHE'); ?>
            </button>
        </div>
        
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<script>
function clearCache() {
    if (confirm('<?php echo Text::_('COM_AJAXSEARCH_CLEAR_CACHE_CONFIRM'); ?>')) {
        window.location.href = 'index.php?option=com_ajaxsearch&task=config.clearCache&<?php echo Session::getFormToken(); ?>=1';
    }
}
</script>

<style>
.ajaxsearch-admin {
    padding: 20px;
}

.ajaxsearch-admin h1 {
    margin-bottom: 20px;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
}

.ajaxsearch-admin h2 {
    font-size: 1.2em;
    margin-top: 20px;
    margin-bottom: 10px;
    color: #333;
}

.ajaxsearch-admin .control-group {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.ajaxsearch-admin .control-label {
    margin-bottom: 10px;
}

.ajaxsearch-admin .form-control {
    max-width: 200px;
}

.ajaxsearch-admin .help-block {
    display: block;
    margin-top: 5px;
    font-size: 0.9em;
    color: #666;
}

.ajaxsearch-admin .form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #ddd;
}

.ajaxsearch-admin .form-actions button {
    margin-right: 10px;
}
</style>
