<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../lib/SimpleS3.php';

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
    
    // Upload to S3
    if (!defined('AWS_ACCESS_KEY')) {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
    }
    if (!defined('AWS_SECRET_KEY')) {
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
    }
    if (!defined('AWS_REGION')) {
        define('AWS_REGION', getenv('AWS_REGION'));
    }
    if (!defined('AWS_BUCKET')) {
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
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
