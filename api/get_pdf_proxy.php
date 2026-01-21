<?php
/**
 * PDF Proxy Endpoint
 * Fetches PDF from S3 and serves it with proper CORS headers
 * This bypasses S3 CORS issues by proxying the request through the Vercel server
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/lib/SimpleS3.php';

// Get S3 key from request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['s3_key'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'S3 key is required'
    ]);
    exit;
}

$s3Key = $data['s3_key'];

try {
    // Load S3 config
    $s3ConfigFile = __DIR__ . '/config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'ap-southeast-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    
    // Initialize S3
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    // Generate presigned URL (valid for 1 hour)
    $presignedUrl = $s3->getPresignedUrl(AWS_BUCKET, $s3Key, 3600);
    
    if (!$presignedUrl) {
        throw new Exception('Failed to generate presigned URL');
    }
    
    // Fetch the PDF content from S3
    $pdfContent = file_get_contents($presignedUrl);
    
    if ($pdfContent === false) {
        throw new Exception('Failed to fetch PDF from S3');
    }
    
    // Set PDF headers
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdfContent));
    header('Content-Disposition: inline; filename="' . basename($s3Key) . '"');
    header('Accept-Ranges: bytes');
    header('Access-Control-Expose-Headers: Content-Type, Content-Length, Accept-Ranges, Content-Range, ETag');
    
    // Output the PDF content
    echo $pdfContent;
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
