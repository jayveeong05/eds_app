<?php
/**
 * Admin - Update Promotion
 * Update promotion description, title, and/or image
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->promotionId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, promotionId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if promotion exists
    $checkQuery = "SELECT id FROM promotions WHERE id = :promotionId";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':promotionId', $data->promotionId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Promotion not found'
        ]);
        exit;
    }
    
    // Build dynamic update query
    $updates = [];
    $params = [':promotionId' => $data->promotionId];
    
    if (isset($data->title)) {
        $updates[] = "title = :title";
        $params[':title'] = $data->title;
    }

    if (isset($data->description)) {
        $updates[] = "description = :description";
        $params[':description'] = $data->description;
    }
    
    if (isset($data->image_url)) {
        $updates[] = "image_url = :image_url";
        $params[':image_url'] = $data->image_url;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields to update. Provide title, description or image_url'
        ]);
        exit;
    }
    
    // Update promotion
    $query = "UPDATE promotions SET " . implode(', ', $updates) . " WHERE id = :promotionId";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'update_promotion',
            'promotion',
            $data->promotionId,
            json_encode([
                'updated_fields' => array_keys($updates),
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Promotion updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update promotion'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
