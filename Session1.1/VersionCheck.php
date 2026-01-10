<?php
/**
 * @package     AJAXSearch
 * @subpackage  Compatibility
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Joomla version compatibility checker
 * Supports Joomla 3.10+ through Joomla 6.x
 */
class AjaxsearchCompatibilityVersionCheck
{
    /**
     * @var string Current Joomla version
     */
    private $joomlaVersion;
    
    /**
     * @var string Current PHP version
     */
    private $phpVersion;
    
    /**
     * @var array Compatibility matrix
     */
    private $compatibility = [
        'joomla' => [
            'min' => '3.10.0',
            'max' => '6.999.999',
            'recommended' => '4.0.0'
        ],
        'php' => [
            'min' => '7.4.0',
            'max' => '8.999.999',
            'recommended' => '8.0.0'
        ],
        'database' => [
            'mysql' => '5.7.0',
            'mariadb' => '10.2.0'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->joomlaVersion = $this->getJoomlaVersion();
        $this->phpVersion = PHP_VERSION;
    }
    
    /**
     * Get current Joomla version
     * 
     * @return string Joomla version
     */
    private function getJoomlaVersion()
    {
        if (class_exists('JVersion')) {
            $version = new JVersion();
            return $version->getShortVersion();
        } elseif (class_exists('Joomla\CMS\Version')) {
            $version = new Joomla\CMS\Version();
            return $version->getShortVersion();
        }
        
        // Fallback
        return '3.10.0';
    }
    
    /**
     * Check if current environment is compatible
     * 
     * @return array Compatibility check results
     */
    public function checkCompatibility()
    {
        $results = [
            'joomla' => $this->checkJoomlaVersion(),
            'php' => $this->checkPhpVersion(),
            'database' => $this->checkDatabase(),
            'utf8mb4' => $this->checkUtf8mb4Support(),
            'json' => $this->checkJsonSupport(),
            'overall' => true
        ];
        
        $results['overall'] = $results['joomla']['compatible'] 
            && $results['php']['compatible'] 
            && $results['database']['compatible'];
        
        return $results;
    }
    
    /**
     * Check Joomla version compatibility
     * 
     * @return array Version check results
     */
    private function checkJoomlaVersion()
    {
        $current = $this->joomlaVersion;
        $min = $this->compatibility['joomla']['min'];
        $max = $this->compatibility['joomla']['max'];
        
        $compatible = version_compare($current, $min, '>=') 
            && version_compare($current, $max, '<=');
        
        return [
            'current' => $current,
            'min' => $min,
            'max' => $max,
            'compatible' => $compatible,
            'message' => $compatible 
                ? "Joomla {$current} is compatible"
                : "Joomla {$current} is not compatible. Requires {$min} to {$max}"
        ];
    }
    
    /**
     * Check PHP version compatibility
     * 
     * @return array Version check results
     */
    private function checkPhpVersion()
    {
        $current = $this->phpVersion;
        $min = $this->compatibility['php']['min'];
        $max = $this->compatibility['php']['max'];
        
        $compatible = version_compare($current, $min, '>=') 
            && version_compare($current, $max, '<=');
        
        return [
            'current' => $current,
            'min' => $min,
            'max' => $max,
            'compatible' => $compatible,
            'message' => $compatible 
                ? "PHP {$current} is compatible"
                : "PHP {$current} is not compatible. Requires {$min} to {$max}"
        ];
    }
    
    /**
     * Check database compatibility
     * 
     * @return array Database check results
     */
    private function checkDatabase()
    {
        $db = JFactory::getDbo();
        $version = $db->getVersion();
        $type = strtolower($db->getServerType());
        
        $minVersion = isset($this->compatibility['database'][$type]) 
            ? $this->compatibility['database'][$type] 
            : '0.0.0';
        
        $compatible = version_compare($version, $minVersion, '>=');
        
        return [
            'type' => $type,
            'version' => $version,
            'min' => $minVersion,
            'compatible' => $compatible,
            'message' => $compatible 
                ? "{$type} {$version} is compatible"
                : "{$type} {$version} is not compatible. Requires {$minVersion}+"
        ];
    }
    
    /**
     * Check UTF8MB4 support
     * 
     * @return array UTF8MB4 check results
     */
    private function checkUtf8mb4Support()
    {
        $db = JFactory::getDbo();
        
        try {
            // Check if database supports utf8mb4
            $query = "SHOW CHARACTER SET LIKE 'utf8mb4'";
            $db->setQuery($query);
            $result = $db->loadObject();
            
            $supported = !empty($result);
            
            return [
                'supported' => $supported,
                'message' => $supported 
                    ? 'UTF8MB4 is supported'
                    : 'UTF8MB4 is not supported'
            ];
        } catch (Exception $e) {
            return [
                'supported' => false,
                'message' => 'Error checking UTF8MB4 support: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check JSON support
     * 
     * @return array JSON check results
     */
    private function checkJsonSupport()
    {
        $db = JFactory::getDbo();
        $version = $db->getVersion();
        $type = strtolower($db->getServerType());
        
        // MySQL 5.7.8+ and MariaDB 10.2.7+ have native JSON support
        if ($type === 'mysql') {
            $supported = version_compare($version, '5.7.8', '>=');
        } elseif ($type === 'mariadb') {
            $supported = version_compare($version, '10.2.7', '>=');
        } else {
            $supported = false;
        }
        
        return [
            'supported' => $supported,
            'message' => $supported 
                ? 'Native JSON support available'
                : 'Native JSON support not available'
        ];
    }
    
    /**
     * Get Joomla major version (3, 4, 5, 6)
     * 
     * @return int Major version
     */
    public function getJoomlaMajorVersion()
    {
        $parts = explode('.', $this->joomlaVersion);
        return (int) $parts[0];
    }
    
    /**
     * Check if running Joomla 4+
     * 
     * @return bool True if Joomla 4+
     */
    public function isJoomla4Plus()
    {
        return $this->getJoomlaMajorVersion() >= 4;
    }
    
    /**
     * Get appropriate class prefix based on Joomla version
     * 
     * @return string Class prefix
     */
    public function getClassPrefix()
    {
        if ($this->isJoomla4Plus()) {
            return 'Joomla\\CMS\\';
        }
        
        return 'J';
    }
    
    /**
     * Get database factory method based on version
     * 
     * @return string Factory method name
     */
    public function getDatabaseFactoryMethod()
    {
        if ($this->isJoomla4Plus()) {
            return '\\Joomla\\CMS\\Factory::getContainer()->get(\'DatabaseDriver\')';
        }
        
        return 'JFactory::getDbo()';
    }
}