<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class AjaxsearchControllerConfig extends BaseController
{
    public function save()
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        $data = $input->get('jform', [], 'array');

        // Minimal save flow: write into #__ajaxsearch_config
        try {
            $db = JFactory::getDbo();
            // Upsert keys - simple approach: delete component/global then reinsert
            $db->setQuery("DELETE FROM " . $db->quoteName('#__ajaxsearch_config') . " WHERE " . $db->quoteName('component') . " = " . $db->quote('global'));
            $db->execute();

            foreach ($data as $k => $v) {
                $obj = new stdClass();
                $obj->component = 'global';
                $obj->param_name = $k;
                $obj->param_value = is_array($v) ? json_encode($v) : (string)$v;
                $obj->param_type = is_array($v) ? 'array' : 'string';
                $db->insertObject('#__ajaxsearch_config', $obj);
            }

            $this->setRedirect('index.php?option=com_ajaxsearch&view=config', JText::_('COM_AJAXSEARCH_CONFIG_SAVED'));
        } catch (Throwable $e) {
            $this->setRedirect('index.php?option=com_ajaxsearch&view=config', JText::_('COM_AJAXSEARCH_CONFIG_SAVE_ERROR: ') . $e->getMessage(), 'error');
        }
    }
}