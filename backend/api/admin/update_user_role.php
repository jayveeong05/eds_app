<?php
/**
 * Admin - Update User Role
 * Change user role between 'user' and 'admin'
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->userId) || empty($data->role)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, userId, role'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate role value
    if (!in_array($data->role, ['user', 'admin'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role. Must be "user" or "admin"'
        ]);
        exit;
    }
    
    // Prevent admin from demoting themselves
    if ($data->userId === $admin['id'] && $data->role === 'user') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot demote yourself from admin role'
        ]);
        exit;
    }
    
    // Update user role
    $query = "UPDATE users SET role = :role WHERE id = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':role', $data->role);
    $stmt->bindParam(':userId', $data->userId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'update_user_role',
            'user',
            $data->userId,
            json_encode([
                'new_role' => $data->role,
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User role updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user role'
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
