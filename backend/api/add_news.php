<?php
/**
 * Add News
 * Allows authenticated admins to create news items
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/AdminMiddleware.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (empty($data->title) || empty($data->short_description) || empty($data->details) || empty($data->link) || empty($data->image_url)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: title, short_description, details, link, image_url'
    ]);
    exit;
}

// Validate URL format for link
if (!filter_var($data->link, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format for link field'
    ]);
    exit;
}

// Optional: Admin can specify user_id, otherwise news is created without user_id
$userId = null;

// If idToken is provided, verify it and use admin's ID
if (!empty($data->idToken)) {
    $middleware = new AdminMiddleware();
    $admin = $middleware->verifyAdmin($data->idToken);
    
    // Use the authenticated admin's user ID
    $userId = $admin['id'];
}


try {
    // Insert news item (with PostgreSQL UUID casting)
    $query = "INSERT INTO news (user_id, title, short_description, details, link, image_url) 
              VALUES (:user_id::uuid, :title, :short_description, :details, :link, :image_url)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':title', $data->title);
    $stmt->bindParam(':short_description', $data->short_description);
    $stmt->bindParam(':details', $data->details);
    $stmt->bindParam(':link', $data->link);
    $stmt->bindParam(':image_url', $data->image_url);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'News created successfully'
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create news: ' . ($errorInfo[2] ?? 'Unknown error')
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
