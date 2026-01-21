<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get limit from query parameter, default to 50
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $limit = max(1, min($limit, 100)); // Ensure limit is between 1 and 100

    // Get news items ordered by newest first
    $query = "SELECT id, user_id, title, short_description, details, link, image_url, created_at
              FROM news
              ORDER BY created_at DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    
    // Initialize S3 for presigned URLs
    require_once __DIR__ . '/lib/SimpleS3.php';
    $s3ConfigFile = __DIR__ . '/config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
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

    $newsItems = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL for S3 image (valid for 1 hour)
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
            // It's an S3 key, generate presigned URL
            $imageUrl = $s3->getPresignedUrl(AWS_BUCKET, $imageUrl, 3600);
        }
        
        $newsItems[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'title' => $row['title'],
            'short_description' => $row['short_description'],
            'details' => $row['details'],
            'link' => $row['link'],
            'image_url' => $imageUrl,
            'created_at' => $row['created_at']
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $newsItems,
        'count' => count($newsItems)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch news: ' . $e->getMessage()
    ]);
}
?>
