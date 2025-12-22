<?php
/**
 * Get Code Invoices
 * Returns all invoices for a specific machine code with presigned URLs
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/SimpleS3.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Machine code required'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all invoices for this code, sorted by most recently created/updated
    $query = "SELECT id::text, code, month, file_url, created_at
              FROM invoices 
              WHERE code = :code
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':code', $data->code);
    $stmt->execute();
    
    // Initialize S3 for presigned URLs
    $s3ConfigFile = __DIR__ . '/../config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL (valid for 1 hour)
        $pdfUrl = $s3->getPresignedUrl(AWS_BUCKET, $row['file_url'], 3600);
        
        $invoices[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'month' => $row['month'],
            'pdf_url' => $pdfUrl,
            'created_at' => $row['created_at']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
