<?php
/**
 * Admin - Update News
 * Update news title, short_description, details, link, and/or image
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->newsId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, newsId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if news exists
    $checkQuery = "SELECT id FROM news WHERE id = :newsId";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':newsId', $data->newsId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'News not found'
        ]);
        exit;
    }
    
    // Build dynamic update query
    $updates = [];
    $params = [':newsId' => $data->newsId];
    
    if (isset($data->title)) {
        $updates[] = "title = :title";
        $params[':title'] = $data->title;
    }

    if (isset($data->short_description)) {
        $updates[] = "short_description = :short_description";
        $params[':short_description'] = $data->short_description;
    }

    if (isset($data->details)) {
        $updates[] = "details = :details";
        $params[':details'] = $data->details;
    }

    if (isset($data->link)) {
        // Validate URL format
        if (!filter_var($data->link, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid URL format for link field'
            ]);
            exit;
        }
        $updates[] = "link = :link";
        $params[':link'] = $data->link;
    }
    
    if (isset($data->image_url)) {
        $updates[] = "image_url = :image_url";
        $params[':image_url'] = $data->image_url;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields to update. Provide title, short_description, details, link or image_url'
        ]);
        exit;
    }
    
    // Update news
    $query = "UPDATE news SET " . implode(', ', $updates) . " WHERE id = :newsId";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'update_news',
            'news',
            $data->newsId,
            json_encode([
                'updated_fields' => array_keys($updates),
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'News updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update news'
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
