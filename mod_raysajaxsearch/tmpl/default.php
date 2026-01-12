<?php
/**
 * Module template: accessible search form with fallback to results page
 * Ensures progressive enhancement: if JS disabled, form posts to results page.
 *
 * The module script options will provide ajax/suggest/resultsPage URLs.
 */
defined('_JEXEC') or die;

$moduleId = $module->id ?? 'raysajaxsearch';
$resultsPageUrl = $moduleData['params']['results_page_url'] ?? JUri::root() . 'index.php?option=com_ajaxsearch&view=results';
$ajaxUrl = $moduleData['params']['ajax_url'] ?? JUri::root() . 'components/com_ajaxsearch/src/Component/ajax.php';
$suggestUrl = $moduleData['params']['suggest_url'] ?? JUri::root() . 'components/com_ajaxsearch/src/Component/suggest.php';
$minChars = (int) ($moduleData['params']['min_chars'] ?? 2);
$limit = (int) ($moduleData['params']['results_limit'] ?? 10);
?>
<form class="ajaxsearch-form" action="<?php echo htmlspecialchars($resultsPageUrl, ENT_QUOTES, 'UTF-8'); ?>" method="get" id="ajaxsearch-module-<?php echo (int)$moduleId; ?>" data-module-id="<?php echo (int)$moduleId; ?>"
      data-ajax-url="<?php echo htmlspecialchars($ajaxUrl, ENT_QUOTES, 'UTF-8'); ?>"
      data-suggest-url="<?php echo htmlspecialchars($suggestUrl, ENT_QUOTES, 'UTF-8'); ?>"
      data-results-page-url="<?php echo htmlspecialchars($resultsPageUrl, ENT_QUOTES, 'UTF-8'); ?>"
      data-min-chars="<?php echo $minChars; ?>"
      data-results-limit="<?php echo $limit; ?>">

    <label for="ajaxsearch-input-<?php echo (int)$moduleId; ?>">Search</label>
    <div class="ajaxsearch-input-group">
        <input type="search" id="ajaxsearch-input-<?php echo (int)$moduleId; ?>" name="q" placeholder="<?php echo JText::_('MOD_AJAXSEARCH_PLACEHOLDER') ?: 'Search...'; ?>" autocomplete="off" aria-autocomplete="list" aria-controls="ajaxsearch-suggestions-<?php echo (int)$moduleId; ?>" />
        <button type="submit" aria-label="<?php echo JText::_('MOD_AJAXSEARCH_SEARCH_BUTTON') ?: 'Search'; ?>">Search</button>
    </div>

    <!-- Suggestion container -->
    <div id="ajaxsearch-suggestions-<?php echo (int)$moduleId; ?>" class="ajaxsearch-suggestions" role="listbox" aria-label="Search suggestions" style="display:none;"></div>
</form>