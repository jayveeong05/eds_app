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
    // Get token from query parameter
    $token = $_GET['token'] ?? null;
    
    if (!$token) {
        throw new Exception('Token is required');
    }
    
    // Verify JWT token
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
    
    // Get user ID from firebase_uid
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
    
    // Get limit from query parameter, default to 100
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $limit = max(1, min($limit, 500)); // Between 1 and 500

    // Get session_id from query parameter
    $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;
    
    // Get all chat messages for this user/session, ordered by oldest first
    $query = "SELECT id, message_text, is_user_message, is_favorite, created_at, session_id
              FROM chat_messages
              WHERE user_id = :user_id";
    
    if ($sessionId) {
        $query .= " AND session_id = :session_id";
    } else {
        // Option: Show orphaned messages or just recent ones if we wanted logic here
        // For now, if no session_id, we can show NULL session messages OR just return empty?
        // Let's assume sending without session_id means "Legacy" or "Orphaned" messages
         $query .= " AND session_id IS NULL";
    }
    
    $query .= " ORDER BY created_at ASC LIMIT :limit";
    
    $stmt = $db->prepare($query);

    
    // Rebinding correctly
    $stmt->bindValue(':user_id', $userId);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    if ($sessionId) {
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch messages: ' . $e->getMessage()
    ]);
}
?>
