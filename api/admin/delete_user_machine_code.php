<?php
/**
 * Admin - Delete Machine Code from User
 * Removes a machine code assignment from a user
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

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

if (empty($data->userId) || empty($data->code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User ID and machine code required'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $code = strtoupper(trim($data->code));
    
    // Delete the assignment
    $deleteQuery = "DELETE FROM user_codes WHERE user_id = :user_id AND code = :code";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':user_id', $data->userId);
    $deleteStmt->bindParam(':code', $code);
    $deleteStmt->execute();
    
    if ($deleteStmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Machine code removed successfully',
            'data' => [
                'user_id' => $data->userId,
                'code' => $code
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Machine code assignment not found'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


