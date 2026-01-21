<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Initialize S3 config (File for local, Env for production)
// Initialize S3 config (File for local, Env for production)
$s3ConfigFile = __DIR__ . '/config/s3_config.php';
if (file_exists($s3ConfigFile)) {
    require_once $s3ConfigFile;
} else {
    // Fallback if config file missing (shouldn't happen)
    define('AWS_ACCESS_KEY', getenv('EDS_AWS_ACCESS_KEY') ?: getenv('AWS_ACCESS_KEY'));
    define('AWS_SECRET_KEY', getenv('EDS_AWS_SECRET_KEY') ?: getenv('AWS_SECRET_KEY'));
    define('AWS_REGION', getenv('EDS_AWS_REGION') ?: getenv('AWS_REGION') ?: 'us-east-1');
    define('AWS_BUCKET', getenv('EDS_AWS_BUCKET') ?: getenv('AWS_BUCKET'));
}
include_once __DIR__ . '/lib/SimpleS3.php';

// Debug logging
error_log("S3 Config Check - Access Key: " . (defined('AWS_ACCESS_KEY') && !empty(AWS_ACCESS_KEY) ? 'SET (' . strlen(AWS_ACCESS_KEY) . ' chars)' : 'NOT SET'));
error_log("S3 Config Check - Secret Key: " . (defined('AWS_SECRET_KEY') && !empty(AWS_SECRET_KEY) ? 'SET (' . strlen(AWS_SECRET_KEY) . ' chars)' : 'NOT SET'));
error_log("S3 Config Check - Region: " . (defined('AWS_REGION') ? AWS_REGION : 'NOT SET'));
error_log("S3 Config Check - Bucket: " . (defined('AWS_BUCKET') ? AWS_BUCKET : 'NOT SET'));

// Validate required AWS credentials
if (!defined('AWS_ACCESS_KEY') || empty(AWS_ACCESS_KEY)) {
    http_response_code(500);
    echo json_encode(array("message" => "AWS_ACCESS_KEY environment variable is not set or empty. Please configure it in Vercel environment variables."));
    exit;
}
if (!defined('AWS_SECRET_KEY') || empty(AWS_SECRET_KEY)) {
    http_response_code(500);
    echo json_encode(array("message" => "AWS_SECRET_KEY environment variable is not set or empty. Please configure it in Vercel environment variables."));
    exit;
}
if (!defined('AWS_BUCKET') || empty(AWS_BUCKET)) {
    http_response_code(500);
    echo json_encode(array("message" => "AWS_BUCKET environment variable is not set or empty. Please configure it in Vercel environment variables."));
    exit;
}

$response = array();

if (isset($_FILES['file']) && isset($_POST['folder'])) {
    
    $file_path = $_FILES['file']['tmp_name'];
    $file_name = $_FILES['file']['name'];
    $folder = $_POST['folder']; // e.g., 'promotions' or 'invoices'
    
    // Simple validation
    if (!in_array($folder, ['promotions', 'invoices', 'avatars', 'news'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid folder specified."));
        exit;
    }
    
    // For invoices folder, preserve original filename
    // For other folders, generate unique filename
    if ($folder === 'invoices') {
        // Use original filename for invoices (preserves format like AA001001-Jan.pdf)
        $s3_key = "$folder/$file_name";
    } else {
        // Generate unique filename for avatars/promotions
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_name = uniqid() . '.' . $ext;
        $s3_key = "$folder/$new_name";
    }
    
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    $upload_result = $s3->putObject($file_path, AWS_BUCKET, $s3_key);
    
    if ($upload_result === true) {
        // Return S3 key instead of full URL (for proxy pattern)
        http_response_code(201);
        echo json_encode(array(
            "message" => "File uploaded successfully.",
            "url" => $s3_key  // Return key like "promotions/abc123.jpg"
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "S3 Upload Failed: " . $upload_result));
    }

} else {
    http_response_code(400);
    echo json_encode(array("message" => "No file or folder provided."));
}
?>
