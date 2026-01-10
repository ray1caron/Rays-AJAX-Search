<?php
/**
 * @package     AJAXSearch
 * @subpackage  Module
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// Module CSS class
$moduleClass = 'ajaxsearch-module' . $moduleClassSfx;
$moduleId = 'ajaxsearch-module-' . $module->id;

// Get current search query if any
$app = JFactory::getApplication();
$currentQuery = $app->input->getString('q', '');
?>
<div id="<?php echo $moduleId; ?>" class="<?php echo $moduleClass; ?>" data-module-id="<?php echo $module->id; ?>">
    <div class="ajaxsearch-container">
        <form class="ajaxsearch-form" role="search" aria-label="<?php echo Text::_('MOD_AJAXSEARCH_SEARCH_FORM'); ?>">
            <div class="ajaxsearch-input-group">
                <input 
                    type="search" 
                    class="ajaxsearch-input" 
                    name="q" 
                    value="<?php echo htmlspecialchars($currentQuery, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="<?php echo Text::_('MOD_AJAXSEARCH_PLACEHOLDER'); ?>"
                    aria-label="<?php echo Text::_('MOD_AJAXSEARCH_SEARCH_TERM'); ?>"
                    autocomplete="off"
                    data-min-chars="<?php echo $minChars; ?>"
                >
                <button 
                    type="submit" 
                    class="ajaxsearch-button"
                    aria-label="<?php echo Text::_('MOD_AJAXSEARCH_SEARCH_BUTTON'); ?>"
                >
                    <span class="ajaxsearch-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                </button>
            </div>
            
            <?php if (count($searchTypes) > 1): ?>
            <div class="ajaxsearch-filters">
                <div class="ajaxsearch-filter-group">
                    <span class="ajaxsearch-filter-label"><?php echo Text::_('MOD_AJAXSEARCH_SEARCH_IN'); ?>:</span>
                    <?php foreach ($searchTypes as $type): ?>
                        <?php 
                        $typeLabel = '';
                        switch ($type) {
                            case 'article':
                                $typeLabel = Text::_('MOD_AJAXSEARCH_TYPE_ARTICLES');
                                break;
                            case 'sppagebuilder':
                                $typeLabel = Text::_('MOD_AJAXSEARCH_TYPE_SPPAGES');
                                break;
                            default:
                                $typeLabel = ucfirst($type);
                        }
                        ?>
                        <label class="ajaxsearch-filter-option">
                            <input 
                                type="checkbox" 
                                name="type[]" 
                                value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                checked="checked"
                                class="ajaxsearch-type-filter"
                            >
                            <span><?php echo $typeLabel; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </form>
        
        <div class="ajaxsearch-results-container" aria-live="polite">
            <div class="ajaxsearch-results-header" style="display: none;">
                <div class="ajaxsearch-results-info">
                    <span class="ajaxsearch-results-count"></span>
                    <span class="ajaxsearch-results-time"></span>
                </div>
                <div class="ajaxsearch-results-actions">
                    <button class="ajaxsearch-clear" style="display: none;">
                        <?php echo Text::_('MOD_AJAXSEARCH_CLEAR'); ?>
                    </button>
                </div>
            </div>
            
            <div class="ajaxsearch-results">
                <!-- Results will be inserted here by JavaScript -->
            </div>
            
            <div class="ajaxsearch-loading" style="display: none;">
                <div class="ajaxsearch-spinner"></div>
                <span class="ajaxsearch-loading-text"><?php echo Text::_('MOD_AJAXSEARCH_LOADING'); ?></span>
            </div>
            
            <div class="ajaxsearch-no-results" style="display: none;">
                <div class="ajaxsearch-no-results-icon">🔍</div>
                <h3 class="ajaxsearch-no-results-title"><?php echo Text::_('MOD_AJAXSEARCH_NO_RESULTS'); ?></h3>
                <p class="ajaxsearch-no-results-message"><?php echo Text::_('MOD_AJAXSEARCH_NO_RESULTS_MSG'); ?></p>
            </div>
            
            <div class="ajaxsearch-error" style="display: none;">
                <div class="ajaxsearch-error-icon">⚠️</div>
                <h3 class="ajaxsearch-error-title"><?php echo Text::_('MOD_AJAXSEARCH_ERROR'); ?></h3>
                <p class="ajaxsearch-error-message"></p>
            </div>
            
            <div class="ajaxsearch-pagination" style="display: none;">
                <!-- Pagination will be inserted here by JavaScript -->
            </div>
        </div>
        
        <div class="ajaxsearch-keyboard-hint" style="display: none;">
            <small>
                <?php echo Text::_('MOD_AJAXSEARCH_KEYBOARD_HINT'); ?>
                <kbd>↑</kbd> <kbd>↓</kbd> <?php echo Text::_('MOD_AJAXSEARCH_TO_NAVIGATE'); ?>
                <kbd>Enter</kbd> <?php echo Text::_('MOD_AJAXSEARCH_TO_SELECT'); ?>
                <kbd>Esc</kbd> <?php echo Text::_('MOD_AJAXSEARCH_TO_CLOSE'); ?>
            </small>
        </div>
    </div>
</div>

<!-- Result Item Template (Hidden) -->
<template id="ajaxsearch-result-template">
    <div class="ajaxsearch-result">
        <div class="ajaxsearch-result-content">
            <h3 class="ajaxsearch-result-title">
                <a class="ajaxsearch-result-link" href="" tabindex="-1">
                    <span class="ajaxsearch-result-title-text"></span>
                    <span class="ajaxsearch-result-type"></span>
                </a>
            </h3>
            <div class="ajaxsearch-result-snippet"></div>
            <div class="ajaxsearch-result-meta">
                <span class="ajaxsearch-result-category"></span>
                <span class="ajaxsearch-result-date"></span>
                <span class="ajaxsearch-result-relevance"></span>
            </div>
        </div>
    </div>
</template>

<!-- Pagination Template (Hidden) -->
<template id="ajaxsearch-pagination-template">
    <div class="ajaxsearch-pagination-container">
        <button class="ajaxsearch-pagination-prev" disabled>
            <?php echo Text::_('MOD_AJAXSEARCH_PREV'); ?>
        </button>
        <div class="ajaxsearch-pagination-pages"></div>
        <button class="ajaxsearch-pagination-next" disabled>
            <?php echo Text::_('MOD_AJAXSEARCH_NEXT'); ?>
        </button>
    </div>
</template>