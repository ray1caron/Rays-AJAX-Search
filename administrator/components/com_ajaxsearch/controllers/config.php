<?php
/**
 * @package     AJAXSearch
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Configuration controller for AJAX Search
 */
class AjaxsearchControllerConfig extends BaseController
{
    /**
     * Display configuration form
     */
    public function display($cachable = false, $urlparams = [])
    {
        $app = Factory::getApplication();
        $model = $this->getModel('Config');
        
        // Get configuration
        $config = $model->getConfig();
        
        // Pass to view
        $app->input->set('view', 'config');
        parent::display($cachable, $urlparams);
    }
    
    /**
     * Save configuration
     */
    public function save()
    {
        // Check token
        JSession::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $model = $this->getModel('Config');
        
        // Get posted data
        $data = $app->input->post->get('config', [], 'array');
        
        // Save configuration
        if ($model->saveConfig($data)) {
            $app->enqueueMessage(Text::_('COM_AJAXSEARCH_CONFIG_SAVED'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_AJAXSEARCH_CONFIG_SAVE_FAILED'), 'error');
        }
        
        // Redirect back to config
        $this->setRedirect('index.php?option=com_ajaxsearch&view=config');
    }
    
    /**
     * Clear cache
     */
    public function clearCache()
    {
        // Check token
        JSession::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $model = $this->getModel('Config');
        
        // Clear cache
        if ($model->clearCache()) {
            $app->enqueueMessage(Text::_('COM_AJAXSEARCH_CACHE_CLEARED'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_AJAXSEARCH_CACHE_CLEAR_FAILED'), 'error');
        }
        
        // Redirect back to config
        $this->setRedirect('index.php?option=com_ajaxsearch&view=config');
    }
}
