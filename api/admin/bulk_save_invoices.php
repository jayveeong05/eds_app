<?php
/**
 * Admin - Bulk Save Invoices
 * Parses S3 keys and saves invoice records to database
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
    $failedFiles = [];
    $assignedCodes = []; // Track unique codes for user assignment
    
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
        
        // Track unique code for user assignment
        if (!in_array($parsed['code'], $assignedCodes)) {
            $assignedCodes[] = $parsed['code'];
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
    
    // Assign codes to user if userId is provided
    $assignedTo = null;
    if (isset($data->userId) && !empty($data->userId)) {
        $userId = $data->userId;
        
        // Get user info for confirmation
        $userQuery = "SELECT name, email FROM users WHERE id = :user_id LIMIT 1";
        $userStmt = $db->prepare($userQuery);
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($targetUser && count($assignedCodes) > 0) {
            // Insert or update user_codes entries
            $assignQuery = "INSERT INTO user_codes (user_id, code, assigned_at) 
                           VALUES (:user_id, :code, CURRENT_TIMESTAMP)
                           ON CONFLICT (user_id, code) DO UPDATE 
                           SET assigned_at = CURRENT_TIMESTAMP";
            
            $assignStmt = $db->prepare($assignQuery);
            foreach ($assignedCodes as $code) {
                $assignStmt->bindParam(':user_id', $userId);
                $assignStmt->bindParam(':code', $code);
                $assignStmt->execute();
            }
            
            $assignedTo = [
                'user_name' => $targetUser['name'],
                'user_email' => $targetUser['email'],
                'codes' => $assignedCodes,
                'code_count' => count($assignedCodes)
            ];
        }
    }
    
    
    http_response_code(200);
    $response = [
        'success' => true,
        'processed' => $successCount,
        'failed' => count($failedFiles),
        'failedFiles' => $failedFiles,
        'message' => 'Uploaded invoices replace previous data for same code+month'
    ];
    
    // Add assignment info if user was assigned
    if ($assignedTo !== null) {
        $response['assignedTo'] = $assignedTo;
        $response['message'] .= ' | Codes assigned to ' . $assignedTo['user_name'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
