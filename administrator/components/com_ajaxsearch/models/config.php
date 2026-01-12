<?php
/**
 * @package     AJAXSearch
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2026 Ray Caron. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

/**
 * Configuration model for AJAX Search
 */
class AjaxsearchModelConfig extends BaseDatabaseModel
{
    /**
     * Get configuration from database
     * 
     * @return array Configuration array
     */
    public function getConfig()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select(['param_name', 'param_value', 'param_type', 'component'])
            ->from($db->quoteName('#__ajaxsearch_config'))
            ->order('component ASC, param_name ASC');
        
        $db->setQuery($query);
        
        try {
            $results = $db->loadObjectList();
            $config = [];
            
            foreach ($results as $row) {
                $value = $row->param_value;
                
                // Convert value based on type
                switch ($row->param_type) {
                    case 'int':
                        $value = (int) $value;
                        break;
                    case 'bool':
                        $value = (bool) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                }
                
                $config[$row->param_name] = [
                    'value' => $value,
                    'type' => $row->param_type,
                    'component' => $row->component
                ];
            }
            
            return $config;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Save configuration to database
     * 
     * @param array $data Configuration data
     * @return bool Success
     */
    public function saveConfig($data)
    {
        $db = $this->getDbo();
        
        try {
            foreach ($data as $key => $value) {
                // Determine type
                $type = 'string';
                if (is_bool($value)) {
                    $type = 'bool';
                    $value = $value ? '1' : '0';
                } elseif (is_int($value)) {
                    $type = 'int';
                } elseif (is_float($value)) {
                    $type = 'float';
                }
                
                // Update or insert
                $query = $db->getQuery(true)
                    ->select('id')
                    ->from($db->quoteName('#__ajaxsearch_config'))
                    ->where($db->quoteName('param_name') . ' = ' . $db->quote($key))
                    ->where($db->quoteName('component') . ' = ' . $db->quote('global'));
                
                $db->setQuery($query);
                $exists = $db->loadResult();
                
                if ($exists) {
                    // Update
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__ajaxsearch_config'))
                        ->set($db->quoteName('param_value') . ' = ' . $db->quote($value))
                        ->set($db->quoteName('param_type') . ' = ' . $db->quote($type))
                        ->where($db->quoteName('id') . ' = ' . (int) $exists);
                    
                    $db->setQuery($query);
                    $db->execute();
                } else {
                    // Insert
                    $query = $db->getQuery(true)
                        ->insert($db->quoteName('#__ajaxsearch_config'))
                        ->columns([
                            $db->quoteName('param_name'),
                            $db->quoteName('param_value'),
                            $db->quoteName('param_type'),
                            $db->quoteName('component')
                        ])
                        ->values(
                            $db->quote($key) . ', ' .
                            $db->quote($value) . ', ' .
                            $db->quote($type) . ', ' .
                            $db->quote('global')
                        );
                    
                    $db->setQuery($query);
                    $db->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Clear search cache
     * 
     * @return bool Success
     */
    public function clearCache()
    {
        $db = $this->getDbo();
        
        try {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__ajaxsearch_cache'));
            
            $db->setQuery($query);
            $db->execute();
            
            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
}
