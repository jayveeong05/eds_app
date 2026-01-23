<?php
/**
 * Admin - Delete Invoice
 * Permanently delete an invoice (both database record and S3 file)
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/SimpleS3.php';
require_once __DIR__ . '/../config/s3_config.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->invoiceId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, invoiceId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get invoice info before deletion for logging and S3 file deletion
    $invoiceQuery = "SELECT code, month, file_url FROM invoices WHERE id = :invoiceId";
    $invoiceStmt = $db->prepare($invoiceQuery);
    $invoiceStmt->bindParam(':invoiceId', $data->invoiceId);
    $invoiceStmt->execute();
    
    if ($invoiceStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found'
        ]);
        exit;
    }
    
    $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete S3 file if it exists and is an S3 key (not a full URL)
    $s3Deleted = false;
    if (!empty($invoice['file_url']) && strpos($invoice['file_url'], 'http') !== 0) {
        try {
            $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
            $deleteResult = $s3->deleteObject(AWS_BUCKET, $invoice['file_url']);
            $s3Deleted = ($deleteResult === true);
        } catch (Exception $e) {
            // Log error but continue with database deletion
            error_log("Failed to delete S3 file for invoice: " . $e->getMessage());
        }
    }
    
    // Delete invoice from database
    $query = "DELETE FROM invoices WHERE id = :invoiceId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':invoiceId', $data->invoiceId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'delete_invoice',
            'invoice',
            $data->invoiceId,
            json_encode([
                'deleted_code' => $invoice['code'],
                'deleted_month' => $invoice['month'],
                's3_file_deleted' => $s3Deleted,
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Invoice deleted successfully' . ($s3Deleted ? ' (S3 file removed)' : ' (S3 file deletion skipped)')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete invoice'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

