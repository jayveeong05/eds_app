<?php
/**
 * Invoice Filename Parser
 * 
 * Parses invoice filenames and extracts machine code, month, and year
 * Expected format: {CODE}-{MONTH}-{YEAR}.pdf
 * Examples: AA001001-Jan-2025.pdf, TOG002020-Dec.pdf, 3I001003-Dec.pdf
 * Code format: 1-3 alphanumeric characters (A-Z, 0-9) followed by 6 digits
 */

class InvoiceParser {
    
    /**
     * Month abbreviation to full name mapping
     */
    private static $monthMap = [
        'Jan' => 'January',
        'Feb' => 'February',
        'Mar' => 'March',
        'Apr' => 'April',
        'May' => 'May',
        'Jun' => 'June',
        'Jul' => 'July',
        'Aug' => 'August',
        'Sep' => 'September',
        'Oct' => 'October',
        'Nov' => 'November',
        'Dec' => 'December'
    ];
    
    /**
     * Parse invoice filename
     * 
     * @param string $filename - e.g., "AA001001-Jan.pdf"
     * @return array|null - ['code' => 'AA001001', 'month' => 'January'] or null
     */
    public static function parse($filename) {
        // Remove .pdf extension
        $name = str_ireplace('.pdf', '', $filename);
        
        // Split by hyphen
        $parts = explode('-', $name);
        
        if (count($parts) !== 2) {
            return null; // Invalid format - must have exactly 2 parts
        }
        
        $code = trim($parts[0]);
        $monthAbbr = trim($parts[1]);
        
        // Validate code format: AA001001, TOG002020, 3I001003 (1-3 alphanumeric + 6 digits)
        if (!preg_match('/^[A-Z0-9]{1,3}[0-9]{6}$/', $code)) {
            return null; // Invalid code format
        }
        
        // Convert month abbreviation to full name
        $month = self::$monthMap[$monthAbbr] ?? null;
        
        if (!$month) {
            return null; // Invalid month abbreviation
        }
        
        return [
            'code' => $code,
            'month' => $month
        ];
    }
    
    /**
     * Validate if filename follows the correct pattern
     * 
     * @param string $filename
     * @return bool
     */
    public static function isValid($filename) {
        return self::parse($filename) !== null;
    }
    
    /**
     * Batch validate multiple filenames
     * 
     * @param array $filenames
     * @return array - ['valid' => [...], 'invalid' => [...]]
     */
    public static function batchValidate($filenames) {
        $valid = [];
        $invalid = [];
        
        foreach ($filenames as $filename) {
            if (self::isValid($filename)) {
                $valid[] = $filename;
            } else {
                $invalid[] = $filename;
            }
        }
        
        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }
}
?>
