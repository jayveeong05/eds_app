<?php
/**
 * Admin - Get All News (Admin View)
 * List all news items for admin management
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get pagination parameters
    $limit = isset($data->limit) ? (int)$data->limit : 100;
    $offset = isset($data->offset) ? (int)$data->offset : 0;

    // Get news with pagination, ordered by newest first
    $query = "SELECT id, user_id, title, short_description, details, link, image_url, created_at
              FROM news
              ORDER BY created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    // Initialize S3 for presigned URLs
    require_once __DIR__ . '/../../lib/SimpleS3.php';
    $s3ConfigFile = __DIR__ . '/../../config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);

    $newsItems = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL for S3 image (valid for 1 hour)
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
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
