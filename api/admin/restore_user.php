<?php
/**
 * Admin - Restore User
 * Restores a deleted user (sets status to 'inactive' so admin can then activate)
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
    
    // Get user info before restoration
    $userQuery = "SELECT email, status FROM users WHERE id = :userId LIMIT 1";
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
    
    // Check if user is actually deleted
    if ($user['status'] !== 'deleted') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User is not deleted. Current status: ' . $user['status']
        ]);
        exit;
    }
    
    // Restore user: set status to 'inactive' (admin can then activate if needed)
    $query = "UPDATE users SET status = 'inactive' WHERE id = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $data->userId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'restore_user',
            'user',
            $data->userId,
            json_encode([
                'restored_user_email' => $user['email'],
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User restored successfully. Status set to inactive.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to restore user'
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

