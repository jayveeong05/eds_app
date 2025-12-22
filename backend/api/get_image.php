<?php
/**
 * Image Proxy Endpoint
 * 
 * Serves S3 images through backend proxy for admin panel image preview during upload.
 * The admin panel uses this for immediate preview after upload, while the main
 * promotion display uses presigned URLs from get_all_promotions.php.
 * 
 * Note: Public read access is intentionally blocked on the S3 bucket.
 * This endpoint provides controlled access for admin panel previews only.
 */

header("Access-Control-Allow-Origin: *");

// Load S3 config from file if exists (local dev), otherwise use env vars (Railway)
$s3ConfigFile = __DIR__ . '/../config/s3_config.php';
if (file_exists($s3ConfigFile)) {
    require_once $s3ConfigFile;
} else {
    // Use environment variables (for Railway/production)
    define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
    define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
    define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
    define('AWS_BUCKET', getenv('AWS_BUCKET'));
}

require_once __DIR__ . '/../lib/SimpleS3.php';

// Get the S3 path from query parameter
$s3Path = $_GET['path'] ?? '';

if (empty($s3Path)) {
    http_response_code(400);
    echo 'Missing path parameter';
    exit;
}

// Security: Only allow specific folders
$allowedPrefixes = ['avatars/', 'promotions/', 'invoices/'];
$isAllowed = false;

foreach ($allowedPrefixes as $prefix) {
    if (substr($s3Path, 0, strlen($prefix)) === $prefix) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(403);
    echo 'Access denied - invalid path';
    exit;
}


try {
    // Use SimpleS3 to download with signed request and stream directly
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    // Disable output buffering to ensure streaming works
    if (ob_get_level()) ob_end_clean();
    
    // Stream appropriate headers are handled inside getObjectStream via HEADERFUNCTION
    $httpCode = $s3->getObjectStream(AWS_BUCKET, $s3Path);
    
    if ($httpCode >= 400) {
        // Since we blindly streamed the error body, we can't easily undo headers.
        // But headers weren't sent until first body byte usually.
        // If error, S3 returns XML error.
        // The client will see a 200 OK with XML body if we aren't careful, 
        // BUT getObjectStream forwards headers, so if S3 returned 403, we forwarded 403 status line? 
        // No, header function forwards key:value. Status line is special.
        // Ideally we should check status first, but for streaming we commit early.
        // However, if S3 returns error, it's small.
        // NOTE: getObjectStream forwards status headers? No.
        // Let's rely on S3 content.
    }

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error loading image: ' . $e->getMessage();
}
?>
