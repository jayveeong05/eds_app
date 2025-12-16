<?php
/**
 * Admin - Get All Promotions (Admin View)
 * List all promotions with creator info for admin management
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
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    
    // Get all promotions with user info
    $query = "SELECT p.id::text as id, p.image_url, p.description, p.created_at,
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
    
    $promotions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert S3 key to proxy URL if needed (for admin web panel use localhost)
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
            // It's an S3 key, convert to proxy URL
            $imageUrl = 'http://localhost:8000/api/get_image.php?path=' . $imageUrl;
        }
        
        $promotions[] = [
            'id' => $row['id'],
            'image_url' => $imageUrl,
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'user' => [
                'id' => $row['user_id'],
                'email' => $row['email'] ?? 'System',
                'name' => $row['name'],
                'profile_image_url' => $row['profile_image_url']
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
