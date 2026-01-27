<?php
/**
 * Admin - Bulk Save Invoices
 * Parses S3 keys and saves invoice records to database
 * Replaces existing invoices when same filename (code+month+year+invoice_number) is uploaded
 * Supports multiple invoices per code+month+year with different invoice numbers
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../lib/InvoiceParser.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No token provided'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

if (empty($data->s3Keys) || !is_array($data->s3Keys)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'S3 keys array required'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $successCount = 0;
    $updatedCount = 0;
    $insertedCount = 0;
    $failedFiles = [];
    
    // Check which columns exist for backward compatibility
    try {
        $checkColumnsQuery = "SELECT column_name FROM information_schema.columns 
                              WHERE table_name = 'invoices' 
                              AND column_name IN ('invoice_number', 'invoice_year')";
        $checkColumnsStmt = $db->query($checkColumnsQuery);
        $existingColumns = [];
        while ($row = $checkColumnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['column_name'];
        }
        $hasInvoiceNumber = in_array('invoice_number', $existingColumns);
        $hasInvoiceYear = in_array('invoice_year', $existingColumns);
    } catch (Exception $e) {
        $hasInvoiceNumber = false;
        $hasInvoiceYear = false;
    }
    
    // Parse each filename and insert/update into database
    // Uses ON CONFLICT to replace existing invoices with same code+month+year+invoice_number
    foreach ($data->s3Keys as $s3Key) {
        $filename = basename($s3Key); // e.g., "AA001001-Jan-2025-001.pdf"
        $parsed = InvoiceParser::parse($filename);
        
        if (!$parsed) {
            $failedFiles[] = [
                'filename' => $filename,
                'reason' => 'Invalid filename format (expected: CODE-MONTH-YEAR-INVOICENUMBER.pdf, e.g., AA001001-Jan-2025-001.pdf)'
            ];
            continue;
        }
        
        try {
            // Build INSERT ... ON CONFLICT DO UPDATE query based on available columns
            if ($hasInvoiceYear && $hasInvoiceNumber) {
                // Full format with year and invoice_number - use unique constraint for conflict
                $query = "INSERT INTO invoices (code, month, invoice_year, invoice_number, file_url, created_at)
                          VALUES (:code, :month, :invoice_year, :invoice_number, :file_url, CURRENT_TIMESTAMP)
                          ON CONFLICT (code, month, invoice_year, invoice_number) 
                          DO UPDATE SET 
                              file_url = EXCLUDED.file_url,
                              created_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':code', $parsed['code']);
                $stmt->bindParam(':month', $parsed['month']);
                $stmt->bindParam(':invoice_year', $parsed['year'], PDO::PARAM_INT);
                $stmt->bindParam(':invoice_number', $parsed['invoice_number']);
                $stmt->bindParam(':file_url', $s3Key);
            } elseif ($hasInvoiceNumber) {
                // Format with invoice_number but no year column yet
                // Check if unique constraint exists on (code, month, invoice_number)
                $query = "INSERT INTO invoices (code, month, invoice_number, file_url, created_at)
                          VALUES (:code, :month, :invoice_number, :file_url, CURRENT_TIMESTAMP)
                          ON CONFLICT (code, month, invoice_number) 
                          DO UPDATE SET 
                              file_url = EXCLUDED.file_url,
                              created_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':code', $parsed['code']);
                $stmt->bindParam(':month', $parsed['month']);
                $stmt->bindParam(':invoice_number', $parsed['invoice_number']);
                $stmt->bindParam(':file_url', $s3Key);
            } else {
                // Fallback to old format (for backward compatibility during migration)
                // Check if unique constraint exists on (code, month)
                $query = "INSERT INTO invoices (code, month, file_url, created_at)
                          VALUES (:code, :month, :file_url, CURRENT_TIMESTAMP)
                          ON CONFLICT (code, month) 
                          DO UPDATE SET 
                              file_url = EXCLUDED.file_url,
                              created_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':code', $parsed['code']);
                $stmt->bindParam(':month', $parsed['month']);
                $stmt->bindParam(':file_url', $s3Key);
            }
            
            // Check if record exists before insert/update
            $exists = false;
            if ($hasInvoiceYear && $hasInvoiceNumber) {
                $checkQuery = "SELECT COUNT(*) FROM invoices 
                               WHERE code = :code AND month = :month 
                               AND invoice_year = :invoice_year AND invoice_number = :invoice_number";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':code', $parsed['code']);
                $checkStmt->bindParam(':month', $parsed['month']);
                $checkStmt->bindParam(':invoice_year', $parsed['year'], PDO::PARAM_INT);
                $checkStmt->bindParam(':invoice_number', $parsed['invoice_number']);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn() > 0;
            } elseif ($hasInvoiceNumber) {
                $checkQuery = "SELECT COUNT(*) FROM invoices 
                               WHERE code = :code AND month = :month AND invoice_number = :invoice_number";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':code', $parsed['code']);
                $checkStmt->bindParam(':month', $parsed['month']);
                $checkStmt->bindParam(':invoice_number', $parsed['invoice_number']);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn() > 0;
            } else {
                $checkQuery = "SELECT COUNT(*) FROM invoices WHERE code = :code AND month = :month";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':code', $parsed['code']);
                $checkStmt->bindParam(':month', $parsed['month']);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn() > 0;
            }
            
            // Execute insert/update
            $stmt->execute();
            $successCount++;
            
            // Track insert vs update
            if ($exists) {
                $updatedCount++;
            } else {
                $insertedCount++;
            }
            
        } catch (PDOException $e) {
            // Handle unique constraint violations or other database errors
            if ($e->getCode() == '23505') {
                // Unique constraint violation - try to update instead
                $failedFiles[] = [
                    'filename' => $filename,
                    'reason' => 'Duplicate invoice exists and update failed: ' . $e->getMessage()
                ];
            } else {
                $failedFiles[] = [
                    'filename' => $filename,
                    'reason' => 'Database error: ' . $e->getMessage()
                ];
            }
        } catch (Exception $e) {
            $failedFiles[] = [
                'filename' => $filename,
                'reason' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }
    
    http_response_code(200);
    $response = [
        'success' => true,
        'processed' => $successCount,
        'inserted' => $insertedCount,
        'updated' => $updatedCount,
        'failed' => count($failedFiles),
        'failedFiles' => $failedFiles,
        'message' => "Processed {$successCount} invoices. " . 
                     ($updatedCount > 0 ? "{$updatedCount} existing invoices replaced. " : '') .
                     ($insertedCount > 0 ? "{$insertedCount} new invoices added. " : '') .
                     "Files with the same filename (code+month+year+invoice_number) will replace existing records."
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
