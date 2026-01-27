<?php
/**
 * Invoice Filename Parser
 * 
 * Parses invoice filenames and extracts machine code, month, year, and invoice number
 * Expected format: {CODE}-{MONTH}-{YEAR}-{INVOICE_NUMBER}.pdf
 * Examples: AA001001-Jan-2025-001.pdf, TOG002020-Dec-2025-002.pdf, 3I001003-Dec-2024-123.pdf
 * Code format: 1-3 alphanumeric characters (A-Z, 0-9) followed by 6 digits
 * Year: 4-digit year (2000-2100)
 * Invoice number: Alphanumeric string (typically numeric, but can contain letters)
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
     * @param string $filename - e.g., "AA001001-Jan-2025-001.pdf"
     * @return array|null - ['code' => 'AA001001', 'month' => 'January', 'year' => 2025, 'invoice_number' => '001'] or null
     */
    public static function parse($filename) {
        // Remove .pdf extension
        $name = str_ireplace('.pdf', '', $filename);
        
        // Split by hyphen
        $parts = explode('-', $name);
        
        // Must have exactly 4 parts: CODE-MONTH-YEAR-INVOICENUMBER
        if (count($parts) !== 4) {
            return null; // Invalid format - must have exactly 4 parts
        }
        
        $code = trim($parts[0]);
        $monthAbbr = trim($parts[1]);
        $year = trim($parts[2]);
        $invoiceNumber = trim($parts[3]);
        
        // Validate code format: AA001001, TOG002020, 3I001003 (1-3 alphanumeric + 6 digits)
        if (!preg_match('/^[A-Z0-9]{1,3}[0-9]{6}$/', $code)) {
            return null; // Invalid code format
        }
        
        // Convert month abbreviation to full name
        $month = self::$monthMap[$monthAbbr] ?? null;
        
        if (!$month) {
            return null; // Invalid month abbreviation
        }
        
        // Validate year (4 digits, between 2000-2100)
        if (!preg_match('/^\d{4}$/', $year) || (int)$year < 2000 || (int)$year > 2100) {
            return null; // Invalid year format
        }
        
        // Validate invoice number (alphanumeric, at least 1 character)
        if (empty($invoiceNumber) || !preg_match('/^[A-Z0-9]+$/i', $invoiceNumber)) {
            return null; // Invalid invoice number format
        }
        
        return [
            'code' => $code,
            'month' => $month,
            'year' => (int)$year,
            'invoice_number' => $invoiceNumber
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
