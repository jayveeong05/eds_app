<?php
/**
 * Admin - Update Knowledge Base Document
 * Update document title, subtitle, and/or file
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->documentId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, documentId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if document exists
    $checkQuery = "SELECT id FROM knowledge_base WHERE id = :documentId";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':documentId', $data->documentId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Knowledge base document not found'
        ]);
        exit;
    }
    
    // Build dynamic update query
    $updates = [];
    $params = [':documentId' => $data->documentId];
    
    if (isset($data->title)) {
        $updates[] = "title = :title";
        $params[':title'] = $data->title;
    }

    if (isset($data->subtitle)) {
        $updates[] = "subtitle = :subtitle";
        $params[':subtitle'] = $data->subtitle;
    }
    
    if (isset($data->file_url)) {
        $updates[] = "file_url = :file_url";
        $params[':file_url'] = $data->file_url;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields to update. Provide title, subtitle or file_url'
        ]);
        exit;
    }
    
    // Update document
    $query = "UPDATE knowledge_base SET " . implode(', ', $updates) . " WHERE id = :documentId";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'update_knowledge_base',
            'knowledge_base',
            $data->documentId,
            json_encode([
                'updated_fields' => array_keys($updates),
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Knowledge base document updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update document'
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
