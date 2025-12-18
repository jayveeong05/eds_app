<?php
/**
 * Add Promotion
 * Allows authenticated users (or admins) to create promotions
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
if (empty($data->image_url) || empty($data->description)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: image_url, description'
    ]);
    exit;
}

// Optional: Admin can specify user_id, otherwise promotion is created without user_id
$userId = null;

// If idToken is provided, verify it and use admin's ID
if (!empty($data->idToken)) {
    $middleware = new AdminMiddleware();
    $admin = $middleware->verifyAdmin($data->idToken);
    
    // Use the authenticated admin's user ID
    $userId = $admin['id'];
}


try {
    // Insert promotion (with PostgreSQL UUID casting)
    $query = "INSERT INTO promotions (user_id, image_url, description) 
              VALUES (:user_id::uuid, :image_url, :description)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':image_url', $data->image_url);
    $stmt->bindParam(':description', $data->description);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Promotion created successfully'
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create promotion: ' . ($errorInfo[2] ?? 'Unknown error')
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
