<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../lib/JWTVerifier.php';
require_once __DIR__ . '/../config/ai_config.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->token) || !isset($data->message)) {
        throw new Exception('Token and message are required');
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
    
    // 1. Save user message to database
    $userMessageText = trim($data->message);
    $query = "INSERT INTO chat_messages (user_id, message_text, is_user_message, is_favorite) 
              VALUES (:user_id, :message_text, true, false) 
              RETURNING id, message_text, is_user_message, is_favorite, created_at";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':message_text', $userMessageText);
    $stmt->execute();
    
    $userMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Call DigitalOcean Agent API
    $aiResponse = callDigitalOceanAgent($userMessageText);
    
    // 3. Save bot response to database
    $query = "INSERT INTO chat_messages (user_id, message_text, is_user_message, is_favorite) 
              VALUES (:user_id, :message_text, false, false) 
              RETURNING id, message_text, is_user_message, is_favorite, created_at";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':message_text', $aiResponse);
    $stmt->execute();
    
    $botMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Return both messages
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user_message' => $userMessage,
        'bot_message' => $botMessage
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message: ' . $e->getMessage()
    ]);
}

/**
 * Call DigitalOcean Agent API
 */
function callDigitalOceanAgent($message) {
    $endpoint = rtrim(DO_AGENT_BASE_URL, '/') . '/api/v1/chat/completions';
    
    $data = [
        'messages' => [
            [
                'role' => 'user',
                'content' => $message
            ]
        ],
        'stream' => false,
        'include_functions_info' => false,
        'include_retrieval_info' => false,
        'include_guardrails_info' => false
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DO_AGENT_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('AI API error (HTTP ' . $httpCode . '): ' . $response);
    }
    
    $responseData = json_decode($response, true);
    
    // Parse OpenAI-compatible response format
    if (isset($responseData['choices'][0]['message']['content'])) {
        $content = $responseData['choices'][0]['message']['content'];
        // Return content without stripping markdown to preserve filenames with underscores
        return $content;
        // return stripMarkdown($content);
    }
    
    throw new Exception('Invalid AI response format');
}

/**
 * Strip markdown formatting for cleaner text
 */
function stripMarkdown($text) {
    // Remove bold (**text** or __text__)
    $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
    $text = preg_replace('/__([^_]+)__/', '$1', $text);
    
    // Remove italic (*text* or _text_)
    $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
    $text = preg_replace('/_([^_]+)_/', '$1', $text);
    
    // Remove inline code (`code`)
    $text = preg_replace('/`([^`]+)`/', '$1', $text);
    
    // Remove headers (# ## ###)
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);
    
    // Remove links but keep text ([text](url))
    $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
    
    return $text;
}
?>
