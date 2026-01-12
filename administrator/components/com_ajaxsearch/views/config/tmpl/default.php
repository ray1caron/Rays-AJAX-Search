<?php
// Minimal admin config form
defined('_JEXEC') or die;
$configModel = new AjaxsearchModelConfig();
$config = $configModel->getAll();

// Default toggles
$enabledArticle = $config['enable_articles'] ?? true;
$enabledSP = $config['enable_sppagebuilder'] ?? true;
$enableAnalytics = $config['enable_analytics'] ?? true;

?>
<form action="index.php?option=com_ajaxsearch&task=config.save" method="post" id="adminForm" class="form-validate">
    <fieldset class="adminform">
        <legend>Rays Ajax Search - Configuration</legend>

        <div class="control-group">
            <label class="control-label">Enable Joomla Articles</label>
            <div class="controls">
                <input type="checkbox" name="jform[enable_articles]" value="1" <?php echo $enabledArticle ? 'checked' : ''; ?>>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Enable SP Page Builder pages</label>
            <div class="controls">
                <input type="checkbox" name="jform[enable_sppagebuilder]" value="1" <?php echo $enabledSP ? 'checked' : ''; ?>>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Record Search Analytics</label>
            <div class="controls">
                <input type="checkbox" name="jform[enable_analytics]" value="1" <?php echo $enableAnalytics ? 'checked' : ''; ?>>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </fieldset>

    <?php echo JHtml::_('form.token'); ?>
</form>