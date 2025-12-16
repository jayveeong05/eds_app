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

    $promotions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert S3 key to proxy URL if needed
        $imageUrl = $row['image_url'];
        if (strpos($imageUrl, 'http') !== 0) {
            // It's an S3 key, convert to proxy URL
            // Use current server host to ensure the app can reach it (e.g. 10.0.2.2 or localhost)
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $imageUrl = "http://$host/api/get_image.php?path=" . $imageUrl;
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
