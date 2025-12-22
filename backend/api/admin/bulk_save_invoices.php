<?php
/**
 * Admin - Bulk Save Invoices
 * Parses S3 keys and saves invoice records to database
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../lib/InvoiceParser.php';
require_once __DIR__ . '/../../config/database.php';

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
    $failedFiles = [];
    
    // Parse each filename and insert/update in database
    foreach ($data->s3Keys as $s3Key) {
        $filename = basename($s3Key); // e.g., "AA001001-Jan.pdf"
        $parsed = InvoiceParser::parse($filename);
        
        if (!$parsed) {
            $failedFiles[] = [
                'filename' => $filename,
                'reason' => 'Invalid filename format (expected: AA001001-Jan.pdf)'
            ];
            continue;
        }
        
        // Check if record exists for this code+month
        $checkQuery = "SELECT id FROM invoices WHERE code = :code AND month = :month LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':code', $parsed['code']);
        $checkStmt->bindParam(':month', $parsed['month']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing record (replacement)
            $query = "UPDATE invoices 
                      SET file_url = :file_url, created_at = CURRENT_TIMESTAMP
                      WHERE code = :code AND month = :month";
        } else {
            // Insert new record
            $query = "INSERT INTO invoices (code, month, file_url, created_at)
                      VALUES (:code, :month, :file_url, CURRENT_TIMESTAMP)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $parsed['code']);
        $stmt->bindParam(':month', $parsed['month']);
        $stmt->bindParam(':file_url', $s3Key);
        
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $failedFiles[] = [
                'filename' => $filename,
                'reason' => 'Database operation failed'
            ];
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'processed' => $successCount,
        'failed' => count($failedFiles),
        'failedFiles' => $failedFiles,
        'message' => 'Uploaded invoices replace previous data for same code+month'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
