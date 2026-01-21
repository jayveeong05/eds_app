<?php
/**
 * Admin - Delete User
 * Soft delete a user (sets status to 'deleted')
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, userId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Prevent admin from deleting themselves
    if ($data->userId === $admin['id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete your own account'
        ]);
        exit;
    }
    
    // Get user info before deletion for logging
    $userQuery = "SELECT email FROM users WHERE id = :userId";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':userId', $data->userId);
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
    
    // Soft delete: set status to 'deleted'
    $query = "UPDATE users SET status = 'deleted' WHERE id = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $data->userId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'delete_user',
            'user',
            $data->userId,
            json_encode([
                'deleted_user_email' => $user['email'],
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user'
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
