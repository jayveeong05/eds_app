<?php
/**
 * Admin - Get User Machine Codes
 * Returns all machine codes assigned to a specific user
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

if (empty($data->userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User ID required'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all machine codes assigned to this user
    $query = "SELECT code, assigned_at 
              FROM user_codes 
              WHERE user_id = :user_id 
              ORDER BY code ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $data->userId);
    $stmt->execute();
    
    $codes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $codes[] = [
            'code' => $row['code'],
            'assigned_at' => $row['assigned_at']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $codes,
        'count' => count($codes)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


