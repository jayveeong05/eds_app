<?php
/**
 * Admin - Add Machine Code to User
 * Assigns a machine code to a user (one code can be assigned to many users)
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

// Validate machine code format: 2 uppercase letters + 6 digits (e.g., AA001001)
$code = strtoupper(trim($data->code));
if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid machine code format. Expected format: 2 uppercase letters + 6 digits (e.g., AA001001)'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $userQuery = "SELECT id, name, email FROM users WHERE id = :user_id LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':user_id', $data->userId);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Check if code already assigned to this user
    $checkQuery = "SELECT id FROM user_codes WHERE user_id = :user_id AND code = :code LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $data->userId);
    $checkStmt->bindParam(':code', $code);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Machine code already assigned to this user'
        ]);
        exit;
    }
    
    // Insert new assignment
    $insertQuery = "INSERT INTO user_codes (user_id, code, assigned_at) 
                    VALUES (:user_id, :code, CURRENT_TIMESTAMP)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $data->userId);
    $insertStmt->bindParam(':code', $code);
    
    if ($insertStmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Machine code assigned successfully',
            'data' => [
                'user_id' => $data->userId,
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'code' => $code
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign machine code'
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


