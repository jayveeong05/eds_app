<?php
/**
 * Admin - Get All Promotions (Admin View)
 * List all promotions with creator info for admin management
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

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
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    
    // Get all promotions with user info
    $query = "SELECT p.id::text as id, p.image_url, p.title, p.description, p.created_at,
                     p.user_id::text as user_id,
                     u.email, u.name, u.profile_image_url
              FROM promotions p
              LEFT JOIN users u ON p.user_id = u.id
              ORDER BY p.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    
    //Initialize S3 for presigned URLs
    require_once __DIR__ . '/../lib/SimpleS3.php';
    $s3ConfigFile = __DIR__ . '/../config/s3_config.php';
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
    
    $promotions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL for S3 image (valid for 1 hour)
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
            // It's an S3 key, generate presigned URL
            $imageUrl = $s3->getPresignedUrl(AWS_BUCKET, $imageUrl, 3600);
        }
        
        // Generate presigned URL for user profile image if it exists
        $profileImageUrl = $row['profile_image_url'];
        if ($profileImageUrl && strpos($profileImageUrl, 'http') !== 0) {
            // It's an S3 key, generate presigned URL
            $profileImageUrl = $s3->getPresignedUrl(AWS_BUCKET, $profileImageUrl, 3600);
        }
        
        // Convert timestamp to ISO 8601 format with timezone
        $createdAt = $row['created_at'];
        if ($createdAt && strpos($createdAt, 'T') === false && strpos($createdAt, 'Z') === false) {
            $createdAt = str_replace(' ', 'T', $createdAt) . 'Z';
        }
        
        $promotions[] = [
            'id' => $row['id'],
            'image_url' => $imageUrl,
            'title' => $row['title'],
            'description' => $row['description'],
            'created_at' => $createdAt,
            'user' => [
                'id' => $row['user_id'],
                'email' => $row['email'] ?? 'System',
                'name' => $row['name'],
                'profile_image_url' => $profileImageUrl ?? null
            ]
        ];
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM promotions";
    $total = $db->query($countQuery)->fetch(PDO::FETCH_ASSOC)['total'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $promotions,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch promotions: ' . $e->getMessage()
    ]);
}
?>
