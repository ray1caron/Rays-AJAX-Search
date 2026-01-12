<?php
/**
 * @package     RaysAjaxSearch
 * @subpackage  Module
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// Module CSS class
$moduleClass = 'raysajaxsearch-module' . $moduleClassSfx;
$moduleId = 'raysajaxsearch-module-' . $module->id;

// Get current search query if any
$app = Factory::getApplication();
$currentQuery = $app->input->getString('q', '');

// Get AJAX URL through Joomla routing
$ajaxUrl = Uri::root() . 'index.php?option=com_ajaxsearch&task=ajax&format=json';
?>
<div id="<?php echo $moduleId; ?>" class="<?php echo $moduleClass; ?>" 
     data-module-id="<?php echo $module->id; ?>"
     data-search-types="<?php echo implode(',', $searchTypes); ?>"
     data-min-chars="<?php echo $minChars; ?>"
     data-typing-delay="<?php echo $delay; ?>"
     data-results-limit="<?php echo $limit; ?>"
     data-ajax-url="<?php echo $ajaxUrl; ?>">
    <div class="raysajaxsearch-container">
        <form class="raysajaxsearch-form" role="search" aria-label="<?php echo Text::_('MOD_RAYS_AJAX_SEARCH_SEARCH_FORM'); ?>">
            <div class="raysajaxsearch-input-group">
                <input 
                    type="search" 
                    class="raysajaxsearch-input" 
                    name="q" 
                    value="<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="<?php echo Text::_('MOD_RAYS_AJAX_SEARCH_PLACEHOLDER'); ?>"
                    aria-label="<?php echo Text::_('MOD_RAYS_AJAX_SEARCH_SEARCH_TERM'); ?>"
                    autocomplete="off"
                    data-min-chars="<?php echo $minChars; ?>"
                >
                <button 
                    type="submit" 
                    class="raysajaxsearch-button"
                    aria-label="<?php echo Text::_('MOD_RAYS_AJAX_SEARCH_SEARCH_BUTTON'); ?>"
                >
                    <span class="raysajaxsearch-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                </button>
            </div>
            
            <?php if (count($searchTypes) > 1): ?>
            <div class="raysajaxsearch-filters">
                <div class="raysajaxsearch-filter-group">
                    <span class="raysajaxsearch-filter-label"><?php echo Text::_('MOD_RAYS_AJAX_SEARCH_SEARCH_IN'); ?>:</span>
                    <?php foreach ($searchTypes as $type): ?>
                        <?php 
                        $typeLabel = '';
                        switch ($type) {
                            case 'article':
                                $typeLabel = Text::_('MOD_RAYS_AJAX_SEARCH_TYPE_ARTICLES');
                                break;
                            case 'sppagebuilder':
                                $typeLabel = Text::_('MOD_RAYS_AJAX_SEARCH_TYPE_SPPAGES');
                                break;
                            default:
                                $typeLabel = ucfirst($type);
                        }
                        ?>
                        <label class="raysajaxsearch-filter-option">
                            <input 
                                type="checkbox" 
                                name="type[]" 
                                value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                checked="checked"
                                class="raysajaxsearch-type-filter"
                            >
                            <span><?php echo $typeLabel; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </form>
        
        <div class="raysajaxsearch-results-container" aria-live="polite">
            <div class="raysajaxsearch-results-header" style="display: none;">
                <div class="raysajaxsearch-results-info">
                    <span class="raysajaxsearch-results-count"></span>
                    <span class="raysajaxsearch-results-time"></span>
                </div>
                <div class="raysajaxsearch-results-actions">
                    <button class="raysajaxsearch-clear" style="display: none;">
                        <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_CLEAR'); ?>
                    </button>
                </div>
            </div>
            
            <div class="raysajaxsearch-results">
                <!-- Results will be inserted here by JavaScript -->
            </div>
            
            <div class="raysajaxsearch-loading" style="display: none;">
                <div class="raysajaxsearch-spinner"></div>
                <span class="raysajaxsearch-loading-text"><?php echo Text::_('MOD_RAYS_AJAX_SEARCH_LOADING'); ?></span>
            </div>
            
            <div class="raysajaxsearch-no-results" style="display: none;">
                <div class="raysajaxsearch-no-results-icon">🔍</div>
                <h3 class="raysajaxsearch-no-results-title"><?php echo Text::_('MOD_RAYS_AJAX_SEARCH_NO_RESULTS'); ?></h3>
                <p class="raysajaxsearch-no-results-message"><?php echo Text::_('MOD_RAYS_AJAX_SEARCH_NO_RESULTS_MSG'); ?></p>
            </div>
            
            <div class="raysajaxsearch-error" style="display: none;">
                <div class="raysajaxsearch-error-icon">⚠️</div>
                <h3 class="raysajaxsearch-error-title"><?php echo Text::_('MOD_RAYS_AJAX_SEARCH_ERROR'); ?></h3>
                <p class="raysajaxsearch-error-message"></p>
            </div>
            
            <div class="raysajaxsearch-pagination" style="display: none;">
                <!-- Pagination will be inserted here by JavaScript -->
            </div>
        </div>
        
        <div class="raysajaxsearch-keyboard-hint" style="display: none;">
            <small>
                <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_KEYBOARD_HINT'); ?>
                <kbd>↑</kbd> <kbd>↓</kbd> <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_TO_NAVIGATE'); ?>
                <kbd>Enter</kbd> <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_TO_SELECT'); ?>
                <kbd>Esc</kbd> <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_TO_CLOSE'); ?>
            </small>
        </div>
    </div>
</div>

<!-- Result Item Template (Hidden) -->
<template id="raysajaxsearch-result-template">
    <div class="raysajaxsearch-result">
        <div class="raysajaxsearch-result-content">
            <h3 class="raysajaxsearch-result-title">
                <a class="raysajaxsearch-result-link" href="" tabindex="-1">
                    <span class="raysajaxsearch-result-title-text"></span>
                    <span class="raysajaxsearch-result-type"></span>
                </a>
            </h3>
            <div class="raysajaxsearch-result-snippet"></div>
            <div class="raysajaxsearch-result-meta">
                <span class="raysajaxsearch-result-category"></span>
                <span class="raysajaxsearch-result-date"></span>
                <span class="raysajaxsearch-result-relevance"></span>
            </div>
        </div>
    </div>
</template>

<!-- Pagination Template (Hidden) -->
<template id="raysajaxsearch-pagination-template">
    <div class="raysajaxsearch-pagination-container">
        <button class="raysajaxsearch-pagination-prev" disabled>
            <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_PREV'); ?>
        </button>
        <div class="raysajaxsearch-pagination-pages"></div>
        <button class="raysajaxsearch-pagination-next" disabled>
            <?php echo Text::_('MOD_RAYS_AJAX_SEARCH_NEXT'); ?>
        </button>
    </div>
</template>