<?php
defined('_JEXEC') or die;

class AjaxsearchModelConfig
{
    protected $db;

    public function __construct()
    {
        $this->db = JFactory::getDbo();
    }

    public function getAll()
    {
        $query = $this->db->getQuery(true)
            ->select('param_name, param_value, param_type')
            ->from($this->db->quoteName('#__ajaxsearch_config'))
            ->where($this->db->quoteName('component') . ' = ' . $this->db->quote('global'));
        $this->db->setQuery($query);

        try {
            $rows = $this->db->loadObjectList();
            $config = [];
            foreach ($rows as $r) {
                $value = $r->param_value;
                if ($r->param_type === 'array') {
                    $value = json_decode($value, true) ?: [];
                } elseif ($r->param_type === 'int') {
                    $value = (int) $value;
                } elseif ($r->param_type === 'bool') {
                    $value = (bool) $value;
                }
                $config[$r->param_name] = $value;
            }
            return $config;
        } catch (Throwable $e) {
            return [];
        }
    }
}