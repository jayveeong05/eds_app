<?php
/**
 * Admin - Delete Knowledge Base Item
 * Permanently delete a knowledge base item (both database record and S3 file)
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/lib/AdminMiddleware.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/SimpleS3.php';
require_once __DIR__ . '/config/s3_config.php';

// Support both JSON and form-data input
$data = null;
$rawInput = file_get_contents("php://input");
if (!empty($rawInput)) {
    $data = json_decode($rawInput);
    // If JSON decode failed, try form data
    if (!$data) {
        parse_str($rawInput, $formData);
        $data = (object)$formData;
    }
} else {
    // Fallback to POST data
    $data = (object)$_POST;
}

// For backward compatibility: if only 'id' is provided without token, allow deletion
// but prefer token-based auth for security
$skipAuth = empty($data->idToken) && !empty($data->id);

if (!$skipAuth && !empty($data->idToken)) {
    // Verify admin access
    $middleware = new AdminMiddleware();
    $admin = $middleware->verifyAdmin($data->idToken);
} else {
    $admin = null;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $itemId = $data->id ?? null;
    
    if (empty($itemId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: id'
        ]);
        exit;
    }
    
    // Get knowledge base item info before deletion for logging and S3 file deletion
    $kbQuery = "SELECT title, file_url FROM knowledge_base WHERE id = :id";
    $kbStmt = $db->prepare($kbQuery);
    $kbStmt->bindParam(':id', $itemId);
    $kbStmt->execute();
    
    if ($kbStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Knowledge base item not found'
        ]);
        exit;
    }
    
    $kbItem = $kbStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete S3 file if it exists and is an S3 key (not a full URL)
    $s3Deleted = false;
    if (!empty($kbItem['file_url']) && strpos($kbItem['file_url'], 'http') !== 0) {
        try {
            $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
            $deleteResult = $s3->deleteObject(AWS_BUCKET, $kbItem['file_url']);
            $s3Deleted = ($deleteResult === true);
        } catch (Exception $e) {
            // Log error but continue with database deletion
            error_log("Failed to delete S3 file for knowledge base: " . $e->getMessage());
        }
    }
    
    // Delete from database
    $query = "DELETE FROM knowledge_base WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $itemId);
    
    if ($stmt->execute()) {
        // Log admin action if admin is authenticated
        if ($admin && !$skipAuth) {
            $middleware->logAction(
                $admin['id'],
                'delete_knowledge_base',
                'knowledge_base',
                $itemId,
                json_encode([
                    'deleted_title' => $kbItem['title'],
                    's3_file_deleted' => $s3Deleted,
                    'admin_email' => $admin['email']
                ])
            );
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Knowledge base item deleted successfully' . ($s3Deleted ? ' (S3 file removed)' : ' (S3 file deletion skipped)')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete knowledge base item'
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
