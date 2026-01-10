<?php
/**
 * @package     AJAXSearch
 * @subpackage  Database
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Database adapter for Joomla version compatibility
 */
class AjaxsearchDatabaseAdapter
{
    /**
     * @var object Database connection
     */
    protected $db;
    
    /**
     * @var AjaxsearchCompatibilityVersionCheck Version checker
     */
    protected $versionCheck;
    
    /**
     * @var string Database table prefix
     */
    protected $prefix;
    
    /**
     * @var bool UTF8MB4 support
     */
    protected $utf8mb4Supported = false;
    
    /**
     * Constructor
     * 
     * @param object $db Joomla database object
     */
    public function __construct($db = null)
    {
        $this->versionCheck = new AjaxsearchCompatibilityVersionCheck();
        
        if ($db === null) {
            $this->db = $this->getDatabase();
        } else {
            $this->db = $db;
        }
        
        $this->prefix = $this->db->getPrefix();
        $this->checkCapabilities();
    }
    
    /**
     * Get database connection based on Joomla version
     * 
     * @return object Database connection
     */
    private function getDatabase()
    {
        if ($this->versionCheck->isJoomla4Plus()) {
            // Joomla 4+ method
            if (class_exists('Joomla\CMS\Factory')) {
                $container = Joomla\CMS\Factory::getContainer();
                return $container->get('DatabaseDriver');
            }
        }
        
        // Joomla 3.x method
        return JFactory::getDbo();
    }
    
    /**
     * Check database capabilities
     */
    private function checkCapabilities()
    {
        $compatibility = $this->versionCheck->checkCompatibility();
        $this->utf8mb4Supported = $compatibility['utf8mb4']['supported'];
    }
    
    /**
     * Get table name with prefix
     * 
     * @param string $table Table name without prefix
     * @return string Full table name
     */
    public function getTableName($table)
    {
        return $this->prefix . $table;
    }
    
    /**
     * Get appropriate charset for table creation
     * 
     * @return string Charset declaration
     */
    public function getCharset()
    {
        return $this->utf8mb4Supported ? 'utf8mb4' : 'utf8';
    }
    
    /**
     * Get appropriate collation
     * 
     * @return string Collation declaration
     */
    public function getCollation()
    {
        if ($this->utf8mb4Supported) {
            return 'utf8mb4_unicode_ci';
        }
        
        return 'utf8_general_ci';
    }
    
    /**
     * Create a query object
     * 
     * @return object Query object
     */
    public function getQuery()
    {
        return $this->db->getQuery(true);
    }
    
    /**
     * Quote a string for safe database use
     * 
     * @param string $value String to quote
     * @param bool $escape Whether to escape the string
     * @return string Quoted string
     */
    public function quote($value, $escape = true)
    {
        return $this->db->quote($value, $escape);
    }
    
    /**
     * Quote an array of strings
     * 
     * @param array $values Array of strings to quote
     * @return array Quoted strings
     */
    public function quoteArray(array $values)
    {
        $quoted = [];
        foreach ($values as $value) {
            $quoted[] = $this->quote($value);
        }
        return $quoted;
    }
    
    /**
     * Quote database identifier (table/column name)
     * 
     * @param string $name Identifier to quote
     * @return string Quoted identifier
     */
    public function quoteName($name)
    {
        return $this->db->quoteName($name);
    }
    
    /**
     * Execute a query
     * 
     * @param string $query SQL query
     * @return bool Success
     */
    public function execute($query)
    {
        try {
            $this->db->setQuery($query);
            return $this->db->execute();
        } catch (Exception $e) {
            // Log error
            error_log('AJAXSearch Database Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load results from query
     * 
     * @param string $query SQL query
     * @param string $mode Load mode (object, array, assoc, row)
     * @return mixed Query results
     */
    public function loadResults($query, $mode = 'object')
    {
        try {
            $this->db->setQuery($query);
            
            switch (strtolower($mode)) {
                case 'array':
                    return $this->db->loadObjectList('id');
                case 'assoc':
                    return $this->db->loadAssocList();
                case 'row':
                    return $this->db->loadObject();
                case 'object':
                default:
                    return $this->db->loadObjectList();
            }
        } catch (Exception $e) {
            error_log('AJAXSearch Database Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Insert an object into a table
     * 
     * @param string $table Table name (without prefix)
     * @param object $data Data object
     * @param string $key Primary key name
     * @return bool Success
     */
    public function insertObject($table, &$data, $key = 'id')
    {
        $fullTable = $this->getTableName($table);
        
        try {
            return $this->db->insertObject($fullTable, $data, $key);
        } catch (Exception $e) {
            error_log('AJAXSearch Insert Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an object in a table
     * 
     * @param string $table Table name (without prefix)
     * @param object $data Data object
     * @param string $key Primary key name
     * @return bool Success
     */
    public function updateObject($table, &$data, $key = 'id')
    {
        $fullTable = $this->getTableName($table);
        
        try {
            return $this->db->updateObject($fullTable, $data, $key);
        } catch (Exception $e) {
            error_log('AJAXSearch Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete rows from a table
     * 
     * @param string $table Table name (without prefix)
     * @param string $where WHERE clause
     * @return bool Success
     */
    public function delete($table, $where = '')
    {
        $fullTable = $this->getTableName($table);
        $query = "DELETE FROM {$fullTable}";
        
        if ($where) {
            $query .= " WHERE {$where}";
        }
        
        return $this->execute($query);
    }
    
    /**
     * Get database server version
     * 
     * @return string Database version
     */
    public function getVersion()
    {
        return $this->db->getVersion();
    }
    
    /**
     * Get database server type
     * 
     * @return string Database type (mysql, mysqli, pgsql, etc.)
     */
    public function getServerType()
    {
        return strtolower($this->db->getServerType());
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $table Table name (without prefix)
     * @return bool True if table exists
     */
    public function tableExists($table)
    {
        $fullTable = $this->getTableName($table);
        $tables = $this->db->getTableList();
        
        return in_array($fullTable, $tables);
    }
    
    /**
     * Get last insert ID
     * 
     * @return int Last insert ID
     */
    public function insertId()
    {
        return $this->db->insertid();
    }
    
    /**
     * Get last database error
     * 
     * @return string Error message
     */
    public function getError()
    {
        return $this->db->getErrorMsg();
    }
}