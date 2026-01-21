<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/lib/JWTVerifier.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->token)) {
        throw new Exception('Token is required');
    }
    
    // Verify JWT token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($data->token, 'eds-app-1758d');
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit();
    }
    
    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user ID
    $userQuery = "SELECT id FROM users WHERE firebase_uid = :firebase_uid LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':firebase_uid', $firebase_uid);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $userId = $user['id'];
    
    // Delete all chat messages for this user
    $query = "DELETE FROM chat_messages WHERE user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $deletedCount = $stmt->rowCount();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Chat history cleared',
        'deleted_count' => $deletedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear history: ' . $e->getMessage()
    ]);
}
?>
