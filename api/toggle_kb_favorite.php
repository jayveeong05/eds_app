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
    
    if (!isset($data->token) || !isset($data->message_id)) {
        throw new Exception('Token and message_id are required');
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
    
    // Toggle favorite status (only for user's own messages)
    $query = "UPDATE chat_messages 
              SET is_favorite = NOT is_favorite
              WHERE id = :message_id AND user_id = :user_id
              RETURNING is_favorite";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':message_id', $data->message_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Message not found or unauthorized');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'is_favorite' => $result['is_favorite']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to toggle favorite: ' . $e->getMessage()
    ]);
}
?>
