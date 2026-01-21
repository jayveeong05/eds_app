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
    $token = $_GET['token'] ?? null;
    
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
    
    // Get Sessions that have at least one message
    $query = "SELECT s.id, s.title, s.created_at, s.updated_at 
              FROM chat_sessions s
              WHERE s.user_id = :user_id 
              AND EXISTS (SELECT 1 FROM chat_messages m WHERE m.session_id = s.id)
              ORDER BY s.updated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sessions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch sessions: ' . $e->getMessage()
    ]);
}
?>
