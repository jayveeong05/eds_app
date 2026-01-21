<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../lib/SimpleS3.php';

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

if (empty($data->kb_data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No knowledge base data provided'
    ]);
    exit;
}

try {
    $kbData = $data->kb_data;
    
    // Validate data structure
    if (!isset($kbData->printers) || !is_array($kbData->printers)) {
        throw new Exception('Invalid data format. Expected: { "printers": [...] }');
    }
    
    // Convert to JSON string
    $jsonContent = json_encode($kbData, JSON_PRETTY_PRINT);
    
    // Generate filename
    $filename = 'printers_' . date('Y-m-d_His') . '.json';
    $tempFile = sys_get_temp_dir() . '/' . $filename;
    
    // Write to temporary file
    file_put_contents($tempFile, $jsonContent);
    
    // Upload to S3 - Load config from file first, then env vars
    $s3ConfigFile = __DIR__ . '/../config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        if (!defined('AWS_ACCESS_KEY')) {
            define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        }
        if (!defined('AWS_SECRET_KEY')) {
            define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        }
        if (!defined('AWS_REGION')) {
            define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        }
        if (!defined('AWS_BUCKET')) {
            define('AWS_BUCKET', getenv('AWS_BUCKET'));
        }
    }
    
    // Debug logging
    error_log("S3 Config Check - Access Key: " . (defined('AWS_ACCESS_KEY') && !empty(AWS_ACCESS_KEY) ? 'SET (' . strlen(AWS_ACCESS_KEY) . ' chars)' : 'NOT SET'));
    error_log("S3 Config Check - Secret Key: " . (defined('AWS_SECRET_KEY') && !empty(AWS_SECRET_KEY) ? 'SET (' . strlen(AWS_SECRET_KEY) . ' chars)' : 'NOT SET'));
    error_log("S3 Config Check - Region: " . (defined('AWS_REGION') ? AWS_REGION : 'NOT SET'));
    error_log("S3 Config Check - Bucket: " . (defined('AWS_BUCKET') ? AWS_BUCKET : 'NOT SET'));
    
    // Validate required AWS credentials
    if (!defined('AWS_ACCESS_KEY') || empty(AWS_ACCESS_KEY)) {
        throw new Exception('AWS_ACCESS_KEY environment variable is not set or empty. Please configure it in Vercel environment variables.');
    }
    if (!defined('AWS_SECRET_KEY') || empty(AWS_SECRET_KEY)) {
        throw new Exception('AWS_SECRET_KEY environment variable is not set or empty. Please configure it in Vercel environment variables.');
    }
    if (!defined('AWS_BUCKET') || empty(AWS_BUCKET)) {
        throw new Exception('AWS_BUCKET environment variable is not set or empty. Please configure it in Vercel environment variables.');
    }
    
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    // Upload to S3 in printers folder
    $s3Key = 'printers/' . $filename;
    
    error_log("Uploading printer KB to S3 - Key: " . $s3Key . ", Bucket: " . AWS_BUCKET);
    
    $uploadResult = $s3->putObject($tempFile, AWS_BUCKET, $s3Key);
    
    // Clean up temp file
    unlink($tempFile);
    
    if ($uploadResult === true) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Successfully uploaded printer data to S3',
            'count' => count($kbData->printers),
            'details' => [
                'filename' => $filename,
                's3_key' => $s3Key,
                's3_url' => 'https://' . AWS_BUCKET . '.s3.' . AWS_REGION . '.amazonaws.com/' . $s3Key,
                'instructions' => 'File uploaded to S3. Your DigitalOcean Printer Matcher Agent can now access this file from the "printers" folder.'
            ]
        ]);
    } else {
        throw new Exception('S3 upload failed: ' . $uploadResult);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to upload knowledge base: ' . $e->getMessage()
    ]);
}
?>
