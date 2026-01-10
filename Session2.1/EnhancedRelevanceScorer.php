<?php
/**
 * @package     AJAXSearch
 * @subpackage  Search
 * @copyright   [Your Copyright]
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Enhanced relevance scoring with advanced algorithms
 */
class AjaxsearchEnhancedRelevanceScorer
{
    /**
     * @var array Field weights configuration
     */
    protected $weights = [
        'title' => 10,
        'introtext' => 5,
        'fulltext' => 3,
        'custom_fields' => 4,
        'sppagebuilder_title' => 10,
        'sppagebuilder_content' => 5,
        'meta_keywords' => 8,
        'meta_description' => 6,
        'alias' => 7,
        'category_title' => 3,
        'tags' => 4
    ];
    
    /**
     * @var array Stop words to ignore
     */
    protected $stopWords = [
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 
        'for', 'of', 'with', 'by', 'as', 'is', 'was', 'were', 'be', 
        'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 
        'not', 'this', 'that', 'these', 'those', 'am', 'are'
    ];
    
    /**
     * @var array Word stems cache
     */
    protected $stemCache = [];
    
    /**
     * Calculate enhanced relevance score
     * 
     * @param array $data Item data (title, content, etc.)
     * @param array $terms Search terms
     * @param string $contentType Content type (article, sppagebuilder)
     * @return int Relevance score (0-100)
     */
    public function calculateScore(array $data, array $terms, $contentType = 'article')
    {
        if (empty($terms) || empty($data)) {
            return 0;
        }
        
        $totalScore = 0;
        $maxPossibleScore = $this->calculateMaxPossibleScore($terms);
        
        // Score each field
        foreach ($this->weights as $field => $weight) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $fieldScore = $this->scoreField($data[$field], $terms, $weight, $field);
                $totalScore += $fieldScore;
            }
        }
        
        // Apply content type bonus
        $totalScore = $this->applyContentTypeBonus($totalScore, $contentType);
        
        // Apply proximity bonus for phrase matching
        $totalScore = $this->applyProximityBonus($data, $terms, $totalScore);
        
        // Apply recency bonus for newer content
        $totalScore = $this->applyRecencyBonus($data, $totalScore);
        
        // Normalize to 0-100 range
        if ($maxPossibleScore > 0) {
            $normalizedScore = ($totalScore / $maxPossibleScore) * 100;
            return min(100, max(0, (int) round($normalizedScore)));
        }
        
        return min(100, $totalScore);
    }
    
    /**
     * Score a specific field
     * 
     * @param string $fieldContent Field content
     * @param array $terms Search terms
     * @param int $baseWeight Base weight for this field
     * @param string $fieldName Field name (for special handling)
     * @return int Field score
     */
    protected function scoreField($fieldContent, array $terms, $baseWeight, $fieldName)
    {
        $fieldContent = strtolower(trim($fieldContent));
        $score = 0;
        
        foreach ($terms as $term) {
            $term = strtolower(trim($term));
            
            // Skip stop words
            if (in_array($term, $this->stopWords)) {
                continue;
            }
            
            // Exact match
            if (strpos($fieldContent, $term) !== false) {
                $occurrences = substr_count($fieldContent, $term);
                
                // Base score for each occurrence (diminishing returns)
                $occurrenceScore = $this->calculateOccurrenceScore($occurrences, $baseWeight);
                $score += $occurrenceScore;
                
                // Position bonus (earlier is better)
                $position = strpos($fieldContent, $term);
                $positionBonus = $this->calculatePositionBonus($position, strlen($fieldContent), $baseWeight);
                $score += $positionBonus;
                
                // Exact word match bonus (not part of another word)
                if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $fieldContent)) {
                    $score += $baseWeight * 2;
                }
            }
            
            // Stemmed match
            $stemmedTerm = $this->stemWord($term);
            $stemmedContent = $this->stemPhrase($fieldContent);
            
            if ($stemmedTerm !== $term && strpos($stemmedContent, $stemmedTerm) !== false) {
                $score += $baseWeight * 0.5; // Half weight for stemmed matches
            }
            
            // Partial word match (for long words)
            if (strlen($term) > 5) {
                $partialMatches = $this->findPartialMatches($fieldContent, $term);
                $score += count($partialMatches) * ($baseWeight * 0.3);
            }
        }
        
        // Field-specific bonuses
        $score = $this->applyFieldSpecificBonuses($score, $fieldName, $fieldContent, $terms);
        
        return $score;
    }
    
    /**
     * Calculate occurrence score with diminishing returns
     * 
     * @param int $occurrences Number of occurrences
     * @param int $baseWeight Base weight
     * @return int Occurrence score
     */
    protected function calculateOccurrenceScore($occurrences, $baseWeight)
    {
        // Diminishing returns formula: score = baseWeight * (1 - 0.9^occurrences)
        if ($occurrences <= 0) {
            return 0;
        }
        
        $diminished = 1 - pow(0.9, $occurrences);
        return (int) round($baseWeight * $diminished * 2); // Multiply by 2 for more weight
    }
    
    /**
     * Calculate position bonus
     * 
     * @param int $position Position of term
     * @param int $length Total length
     * @param int $baseWeight Base weight
     * @return int Position bonus
     */
    protected function calculatePositionBonus($position, $length, $baseWeight)
    {
        if ($length === 0) {
            return 0;
        }
        
        // Normalize position (0 = beginning, 1 = end)
        $normalizedPosition = $position / $length;
        
        // Bonus is higher for terms near the beginning
        $positionBonus = (1 - $normalizedPosition) * $baseWeight;
        
        return (int) round($positionBonus);
    }
    
    /**
     * Apply content type bonus
     * 
     * @param int $score Current score
     * @param string $contentType Content type
     * @return int Adjusted score
     */
    protected function applyContentTypeBonus($score, $contentType)
    {
        $bonusMultipliers = [
            'article' => 1.0,
            'sppagebuilder' => 1.1, // Slight bonus for SP pages
            'custom' => 0.9
        ];
        
        $multiplier = $bonusMultipliers[$contentType] ?? 1.0;
        return (int) round($score * $multiplier);
    }
    
    /**
     * Apply proximity bonus for phrase matching
     * 
     * @param array $data Item data
     * @param array $terms Search terms
     * @param int $score Current score
     * @return int Adjusted score
     */
    protected function applyProximityBonus(array $data, array $terms, $score)
    {
        // Check if original query was a phrase (multiple words)
        if (count($terms) > 1) {
            $allContent = '';
            
            // Combine all searchable fields
            $searchableFields = ['title', 'introtext', 'fulltext', 'content_text', 'meta_keywords'];
            foreach ($searchableFields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $allContent .= ' ' . strtolower($data[$field]);
                }
            }
            
            // Check for exact phrase match
            $originalPhrase = implode(' ', $terms);
            if (strpos($allContent, $originalPhrase) !== false) {
                $score += 20; // Significant bonus for exact phrase match
            }
            
            // Check for proximity (terms close together)
            $proximityScore = $this->calculateProximityScore($allContent, $terms);
            $score += $proximityScore;
        }
        
        return $score;
    }
    
    /**
     * Calculate proximity score for terms
     * 
     * @param string $content Content to search
     * @param array $terms Search terms
     * @return int Proximity score
     */
    protected function calculateProximityScore($content, array $terms)
    {
        if (count($terms) < 2) {
            return 0;
        }
        
        $positions = [];
        
        // Find positions of each term
        foreach ($terms as $term) {
            $offset = 0;
            while (($pos = strpos($content, $term, $offset)) !== false) {
                $positions[] = $pos;
                $offset = $pos + 1;
            }
        }
        
        if (count($positions) < 2) {
            return 0;
        }
        
        // Sort positions
        sort($positions);
        
        // Calculate average distance between terms
        $totalDistance = 0;
        for ($i = 1; $i < count($positions); $i++) {
            $totalDistance += abs($positions[$i] - $positions[$i - 1]);
        }
        
        $averageDistance = $totalDistance / (count($positions) - 1);
        
        // Lower distance = higher score
        if ($averageDistance <= 10) {
            return 15; // Terms very close together
        } elseif ($averageDistance <= 50) {
            return 10; // Terms reasonably close
        } elseif ($averageDistance <= 100) {
            return 5; // Terms somewhat close
        }
        
        return 0;
    }
    
    /**
     * Apply recency bonus for newer content
     * 
     * @param array $data Item data
     * @param int $score Current score
     * @return int Adjusted score
     */
    protected function applyRecencyBonus(array $data, $score)
    {
        if (!isset($data['created']) && !isset($data['publish_up'])) {
            return $score;
        }
        
        $dateField = isset($data['created']) ? $data['created'] : $data['publish_up'];
        
        try {
            $contentDate = new DateTime($dateField);
            $now = new DateTime();
            $ageInDays = $now->diff($contentDate)->days;
            
            // Bonus for content less than 30 days old
            if ($ageInDays <= 7) {
                $score += 10; // Very recent
            } elseif ($ageInDays <= 30) {
                $score += 5; // Recent
            } elseif ($ageInDays <= 90) {
                $score += 2; // Somewhat recent
            }
            
        } catch (Exception $e) {
            // Date parsing failed, skip bonus
        }
        
        return $score;
    }
    
    /**
     * Apply field-specific bonuses
     * 
     * @param int $score Current score
     * @param string $fieldName Field name
     * @param string $fieldContent Field content
     * @param array $terms Search terms
     * @return int Adjusted score
     */
    protected function applyFieldSpecificBonuses($score, $fieldName, $fieldContent, array $terms)
    {
        switch ($fieldName) {
            case 'title':
                // Bonus for exact title match
                $titleLower = strtolower($fieldContent);
                foreach ($terms as $term) {
                    if ($titleLower === strtolower($term)) {
                        $score += 15; // Exact title match bonus
                    }
                }
                break;
                
            case 'meta_keywords':
                // Meta keywords are highly relevant
                $score = (int) round($score * 1.2);
                break;
                
            case 'alias':
                // Alias matches are important for URLs
                $score = (int) round($score * 1.1);
                break;
        }
        
        return $score;
    }
    
    /**
     * Find partial matches in content
     * 
     * @param string $content Content to search
     * @param string $term Term to find partial matches for
     * @return array Positions of partial matches
     */
    protected function findPartialMatches($content, $term)
    {
        $matches = [];
        $termLength = strlen($term);
        
        // For each possible partial match length (minimum 4 characters)
        for ($length = 4; $length <= $termLength; $length++) {
            for ($start = 0; $start <= $termLength - $length; $start++) {
                $partial = substr($term, $start, $length);
                $pos = strpos($content, $partial);
                
                if ($pos !== false) {
                    $matches[] = $pos;
                }
            }
        }
        
        return array_unique($matches);
    }
    
    /**
     * Stem a word using Porter stemming algorithm (simplified)
     * 
     * @param string $word Word to stem
     * @return string Stemmed word
     */
    protected function stemWord($word)
    {
        if (isset($this->stemCache[$word])) {
            return $this->stemCache[$word];
        }
        
        $stem = $word;
        
        // Common suffixes
        $suffixes = [
            'ing' => '',
            'ed' => '',
            'es' => '',
            's' => '',
            'ly' => '',
            'ment' => '',
            'ness' => '',
            'able' => '',
            'ible' => '',
            'ant' => '',
            'ent' => '',
            'ism' => '',
            'ate' => '',
            'en' => '',
            'er' => '',
            'est' => '',
            'ful' => '',
            'ic' => '',
            'tion' => '',
            'sion' => '',
            'age' => '',
            'al' => '',
            'ance' => '',
            'ence' => ''
        ];
        
        foreach ($suffixes as $suffix => $replacement) {
            $suffixLength = strlen($suffix);
            if (strlen($stem) > $suffixLength + 2 && substr($stem, -$suffixLength) === $suffix) {
                $stem = substr($stem, 0, -$suffixLength) . $replacement;
                break;
            }
        }
        
        // Special cases
        $specialCases = [
            'ies' => 'y',
            'ied' => 'y',
            'ying' => 'y',
            'ied' => 'y'
        ];
        
        foreach ($specialCases as $from => $to) {
            if (substr($stem, -strlen($from)) === $from) {
                $stem = substr($stem, 0, -strlen($from)) . $to;
                break;
            }
        }
        
        $this->stemCache[$word] = $stem;
        return $stem;
    }
    
    /**
     * Stem a phrase (multiple words)
     * 
     * @param string $phrase Phrase to stem
     * @return string Stemmed phrase
     */
    protected function stemPhrase($phrase)
    {
        $words = preg_split('/\s+/', $phrase);
        $stemmedWords = array_map([$this, 'stemWord'], $words);
        return implode(' ', $stemmedWords);
    }
    
    /**
     * Calculate maximum possible score for given terms
     * 
     * @param array $terms Search terms
     * @return int Maximum possible score
     */
    protected function calculateMaxPossibleScore(array $terms)
    {
        $maxScore = 0;
        $nonStopTerms = array_diff($terms, $this->stopWords);
        
        if (empty($nonStopTerms)) {
            return 0;
        }
        
        // Calculate maximum score for each field
        foreach ($this->weights as $weight) {
            foreach ($nonStopTerms as $term) {
                // Maximum per term per field: base weight * 3 (for exact match + position + word boundary)
                $maxScore += $weight * 3;
            }
        }
        
        // Add bonuses
        $maxScore += 20; // Phrase match bonus
        $maxScore += 15; // Exact title match bonus
        $maxScore += 10; // Recency bonus
        
        return $maxScore;
    }
    
    /**
     * Get field weights
     * 
     * @return array Field weights
     */
    public function getWeights()
    {
        return $this->weights;
    }
    
    /**
     * Set field weights
     * 
     * @param array $weights Field weights
     */
    public function setWeights(array $weights)
    {
        $this->weights = array_merge($this->weights, $weights);
    }
    
    /**
     * Test scoring with sample data
     * 
     * @param array $sampleData Sample item data
     * @param string $query Search query
     * @return array Test results
     */
    public function testScoring(array $sampleData, $query)
    {
        $terms = $this->extractTerms($query);
        
        return [
            'query' => $query,
            'terms' => $terms,
            'score' => $this->calculateScore($sampleData, $terms),
            'weights' => $this->weights,
            'max_possible' => $this->calculateMaxPossibleScore($terms)
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
        
        // Remove stop words and short words
        $terms = array_filter($words, function($word) {
            return !in_array($word, $this->stopWords) && strlen($word) >= 2;
        });
        
        return array_values(array_unique($terms));
    }
}