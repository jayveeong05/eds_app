<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get limit from query parameter, default to 50
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $limit = max(1, min($limit, 100)); // Ensure limit is between 1 and 100

    // Get promotions with user info, ordered by newest first
    $query = "SELECT p.id, p.image_url, p.description, p.created_at,
                     u.email, u.profile_image_url
              FROM promotions p
              LEFT JOIN users u ON p.user_id = u.id
              ORDER BY p.created_at DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    
    // Initialize S3 for presigned URLs
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
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);

    $promotions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL for S3 image (valid for 1 hour)
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
            // It's an S3 key, generate presigned URL
            $imageUrl = $s3->getPresignedUrl(AWS_BUCKET, $imageUrl, 3600);
        }
        
        $promotions[] = [
            'id' => $row['id'],
            'image_url' => $imageUrl,
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'user' => [
                'email' => $row['email'] ?? 'Unknown User',
                'profile_image_url' => $row['profile_image_url'] ?? null
            ]
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $promotions,
        'count' => count($promotions)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch promotions: ' . $e->getMessage()
    ]);
}
?>
