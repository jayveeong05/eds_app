<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    if (!isset($_POST['s3_key']) || empty($_POST['s3_key'])) {
        throw new Exception('S3 key is required');
    }
    
    $s3Key = $_POST['s3_key'];
    
    // Initialize S3
    $s3ConfigFile = __DIR__ . '/config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    require_once __DIR__ . '/lib/SimpleS3.php';
    
    // Generate presigned URL (valid for 1 hour)
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    $presignedUrl = $s3->getPresignedUrl(AWS_BUCKET, $s3Key, 3600);
    
    if ($presignedUrl) {
        echo json_encode([
            'success' => true,
            'url' => $presignedUrl
        ]);
    } else {
        throw new Exception('Failed to generate presigned URL');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
