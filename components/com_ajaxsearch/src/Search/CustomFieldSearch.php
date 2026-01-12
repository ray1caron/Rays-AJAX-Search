<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Custom field search integration
 */
class AjaxsearchCustomFieldSearch
{
    /**
     * @var AjaxsearchDatabaseAdapter Database adapter
     */
    protected $db;
    
    /**
     * @var array Supported field types
     */
    protected $supportedTypes = [
        'text',
        'textarea',
        'editor',
        'list',
        'radio',
        'checkbox',
        'sql'
    ];
    
    /**
     * @var array Field type configurations
     */
    protected $fieldTypeConfig = [
        'text' => ['searchable' => true, 'weight' => 4],
        'textarea' => ['searchable' => true, 'weight' => 4],
        'editor' => ['searchable' => true, 'weight' => 5],
        'list' => ['searchable' => true, 'weight' => 3],
        'radio' => ['searchable' => true, 'weight' => 3],
        'checkbox' => ['searchable' => true, 'weight' => 3],
        'sql' => ['searchable' => true, 'weight' => 3],
        'media' => ['searchable' => false, 'weight' => 0],
        'calendar' => ['searchable' => false, 'weight' => 0],
        'url' => ['searchable' => true, 'weight' => 2]
    ];
    
    /**
     * Constructor
     * 
     * @param AjaxsearchDatabaseAdapter $db Database adapter
     */
    public function __construct(AjaxsearchDatabaseAdapter $db)
    {
        $this->db = $db;
    }
    
    /**
     * Search in custom fields
     * 
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Found items with custom field data
     */
    public function search(array $terms, array $options = [])
    {
        $defaultOptions = [
            'content_type' => 'com_content.article',
            'limit' => 50,
            'offset' => 0,
            'field_types' => $this->supportedTypes,
            'min_relevance' => 1
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Get searchable custom fields
        $fields = $this->getSearchableFields($options['content_type'], $options['field_types']);
        
        if (empty($fields) || empty($terms)) {
            return [];
        }
        
        // Build search query
        $results = $this->executeSearch($fields, $terms, $options);
        
        return $results;
    }
    
    /**
     * Get searchable custom fields
     * 
     * @param string $context Field context (e.g., com_content.article)
     * @param array $fieldTypes Field types to include
     * @return array Searchable fields
     */
    protected function getSearchableFields($context, array $fieldTypes)
    {
        $query = $this->db->getQuery(true);
        
        $query->select(['id', 'title', 'name', 'type', 'params'])
              ->from($this->db->quoteName('#__fields'))
              ->where($this->db->quoteName('context') . ' = ' . $this->db->quote($context))
              ->where($this->db->quoteName('state') . ' = 1')
              ->where($this->db->quoteName('type') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $fieldTypes)) . ')');
        
        $this->db->setQuery($query);
        $fields = $this->db->loadObjectList();
        
        // Filter by type configuration
        $searchableFields = [];
        foreach ($fields as $field) {
            $typeConfig = $this->fieldTypeConfig[$field->type] ?? ['searchable' => false];
            if ($typeConfig['searchable']) {
                $field->weight = $typeConfig['weight'];
                $searchableFields[] = $field;
            }
        }
        
        return $searchableFields;
    }
    
    /**
     * Execute search in custom fields
     * 
     * @param array $fields Searchable fields
     * @param array $terms Search terms
     * @param array $options Search options
     * @return array Search results
     */
    protected function executeSearch(array $fields, array $terms, array $options)
    {
        $query = $this->db->getQuery(true);
        
        // Start building the query
        $query->select([
            'v.item_id',
            'c.title as content_title',
            'c.alias as content_alias',
            'c.catid',
            'c.state',
            'c.access',
            'c.language',
            'c.created',
            'GROUP_CONCAT(DISTINCT f.title SEPARATOR ", ") as matched_fields',
            'COUNT(DISTINCT f.id) as field_match_count',
            $this->buildRelevanceExpression($fields, $terms) . ' as relevance'
        ])
        ->from($this->db->quoteName('#__fields_values', 'v'))
        ->innerJoin($this->db->quoteName('#__fields', 'f') . ' ON f.id = v.field_id')
        ->innerJoin($this->db->quoteName('#__content', 'c') . ' ON c.id = v.item_id')
        ->where('c.state = 1')
        ->where($this->buildWhereClause($fields, $terms))
        ->group('v.item_id, c.title, c.alias, c.catid, c.state, c.access, c.language, c.created')
        ->having('relevance >= ' . (int) $options['min_relevance'])
        ->order('relevance DESC')
        ->setLimit($options['limit'], $options['offset']);
        
        // Access control
        $user = JFactory::getUser();
        $groups = implode(',', array_unique($user->getAuthorisedViewLevels()));
        $query->where('c.access IN (' . $groups . ')');
        
        // Language filter
        if (!empty($options['language']) && $options['language'] !== '*') {
            $query->where('c.language IN (' . $this->db->quote($options['language']) . ', ' . $this->db->quote('*') . ')');
        }
        
        $this->db->setQuery($query);
        
        try {
            return $this->db->loadObjectList() ?: [];
        } catch (Exception $e) {
            error_log('AJAXSearch Custom Field Search Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build WHERE clause for search terms
     * 
     * @param array $fields Searchable fields
     * @param array $terms Search terms
     * @return string WHERE clause
     */
    protected function buildWhereClause(array $fields, array $terms)
    {
        $whereClauses = [];
        
        foreach ($terms as $term) {
            $termWhere = [];
            $escapedTerm = $this->db->escape($term, true);
            
            foreach ($fields as $field) {
                // Different handling for different field types
                switch ($field->type) {
                    case 'list':
                    case 'radio':
                    case 'checkbox':
                        // For list fields, search in both value and label
                        $termWhere[] = $this->db->quoteName('v.value') . ' LIKE ' . $this->db->quote('%' . $escapedTerm . '%');
                        break;
                        
                    default:
                        // For text fields, search in value
                        $termWhere[] = $this->db->quoteName('v.value') . ' LIKE ' . $this->db->quote('%' . $escapedTerm . '%');
                }
            }
            
            if (!empty($termWhere)) {
                $whereClauses[] = '(' . implode(' OR ', $termWhere) . ')';
            }
        }
        
        return !empty($whereClauses) ? '(' . implode(' AND ', $whereClauses) . ')' : '1=1';
    }
    
    /**
     * Build relevance scoring expression
     * 
     * @param array $fields Searchable fields
     * @param array $terms Search terms
     * @return string SQL expression for relevance
     */
    protected function buildRelevanceExpression(array $fields, array $terms)
    {
        $relevanceParts = [];
        
        foreach ($terms as $term) {
            $escapedTerm = $this->db->escape($term, true);
            
            foreach ($fields as $field) {
                $fieldWeight = $field->weight ?? 3;
                
                $relevancePart = 'CASE WHEN ' . 
                    $this->db->quoteName('v.value') . ' LIKE ' . $this->db->quote('%' . $escapedTerm . '%') . 
                    ' THEN ' . (int) $fieldWeight . ' ELSE 0 END';
                
                $relevanceParts[] = $relevancePart;
            }
        }
        
        return !empty($relevanceParts) ? '(' . implode(' + ', $relevanceParts) . ')' : '0';
    }
    
    /**
     * Get custom field values for an item
     * 
     * @param int $itemId Item ID
     * @param string $context Field context
     * @return array Custom field values
     */
    public function getItemCustomFields($itemId, $context = 'com_content.article')
    {
        $query = $this->db->getQuery(true);
        
        $query->select(['f.title', 'f.name', 'f.type', 'v.value'])
              ->from($this->db->quoteName('#__fields_values', 'v'))
              ->innerJoin($this->db->quoteName('#__fields', 'f') . ' ON f.id = v.field_id')
              ->where('v.item_id = ' . (int) $itemId)
              ->where('f.context = ' . $this->db->quote($context))
              ->where('f.state = 1')
              ->order('f.ordering ASC');
        
        $this->db->setQuery($query);
        
        try {
            return $this->db->loadObjectList() ?: [];
        } catch (Exception $e) {
            error_log('AJAXSearch Get Custom Fields Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all custom fields configuration
     * 
     * @param string $context Field context
     * @return array Custom fields configuration
     */
    public function getFieldsConfiguration($context = 'com_content.article')
    {
        $query = $this->db->getQuery(true);
        
        $query->select(['id', 'title', 'name', 'type', 'params', 'default_value', 'required', 'state'])
              ->from($this->db->quoteName('#__fields'))
              ->where('context = ' . $this->db->quote($context))
              ->order('ordering ASC');
        
        $this->db->setQuery($query);
        
        try {
            $fields = $this->db->loadObjectList() ?: [];
            
            // Parse params JSON
            foreach ($fields as &$field) {
                if (!empty($field->params)) {
                    $field->params = json_decode($field->params, true) ?: [];
                } else {
                    $field->params = [];
                }
            }
            
            return $fields;
        } catch (Exception $e) {
            error_log('AJAXSearch Fields Config Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Index custom fields for search
     * 
     * @param int $itemId Item ID
     * @param string $context Field context
     * @return bool Success
     */
    public function indexItem($itemId, $context = 'com_content.article')
    {
        // This method would be used by a content plugin to update search index
        // For now, it's a placeholder for future implementation
        return true;
    }
    
    /**
     * Test custom field search
     * 
     * @param string $query Test query
     * @param array $options Test options
     * @return array Test results
     */
    public function testSearch($query = 'test', array $options = [])
    {
        $terms = $this->extractTerms($query);
        $results = $this->search($terms, $options);
        
        return [
            'query' => $query,
            'terms' => $terms,
            'results_count' => count($results),
            'sample_results' => array_slice($results, 0, 3),
            'fields_config' => $this->getFieldsConfiguration()
        ];
    }
    
    /**
     * Extract terms from query
     * 
     * @param string $query Search query
     * @return array Extracted terms
     */
    protected function extractTerms($query)
    {
        $query = strtolower(trim($query));
        $query = preg_replace('/[^\w\s]/', ' ', $query);
        $words = preg_split('/\s+/', $query);
        
        // Remove short words
        $terms = array_filter($words, function($word) {
            return strlen($word) >= 2;
        });
        
        return array_values(array_unique($terms));
    }
}