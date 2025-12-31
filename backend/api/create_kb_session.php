<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../lib/JWTVerifier.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    $token = $data->token ?? null;
    $title = $data->title ?? 'New Chat';
    
    if (!$token) {
        throw new Exception('Token is required');
    }
    
    // Verify Token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($token, 'eds-app-1758d');
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit();
    }
    
    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get User ID
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
    
    // Create Session - ensure using UUID for user_id
    $query = "INSERT INTO chat_sessions (user_id, title) VALUES (:user_id, :title) RETURNING id, title, created_at, updated_at";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':title', $title);
    $stmt->execute();
    
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $session
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create session: ' . $e->getMessage()
    ]);
}
?>
