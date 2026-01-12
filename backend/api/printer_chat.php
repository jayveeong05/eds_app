<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai_config.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->device_id) || !isset($data->message)) {
        throw new Exception('device_id and message are required');
    }
    
    $deviceId = trim($data->device_id);
    $userMessage = trim($data->message);
    $history = isset($data->history) ? $data->history : [];
    
    if (empty($deviceId) || empty($userMessage)) {
        throw new Exception('device_id and message cannot be empty');
    }
    
    // Call Printer Matcher Agent with conversation history
    $agentResponse = callPrinterMatcherAgent($userMessage, $history);
    
    // Try to parse as JSON (recommendation response)
    $cleanResponse = cleanJsonString($agentResponse);
    $jsonData = json_decode($cleanResponse, true);
    
    // Debug logging
    error_log("=== PRINTER CHAT DEBUG ===");
    error_log("Agent Response: " . substr($agentResponse, 0, 500));
    error_log("Clean Response: " . substr($cleanResponse, 0, 500));
    error_log("JSON Decode Result: " . ($jsonData ? "SUCCESS" : "FAILED"));
    if ($jsonData) {
        error_log("Has 'result' field: " . (isset($jsonData['result']) ? "YES" : "NO"));
    }
    
    // Check if it's a recommendation (has "result" field)
    if ($jsonData && isset($jsonData['result'])) {
        // This is a recommendation response
        // Extract and log customer request data
        if (isset($jsonData['request']['requirements'])) {
            logCustomerRequest($deviceId, $jsonData['request']['requirements']);
        }
        
        // Return only the result to the client
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response_type' => 'recommendation',
            'result' => $jsonData['result']
        ]);
    } else {
        // This is a question response (plain text)
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'response_type' => 'question',
            'message' => $agentResponse
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process request: ' . $e->getMessage()
    ]);
}

/**
 * Call Printer Matcher DigitalOcean Agent API
 */
function callPrinterMatcherAgent($message, $history = []) {
    // Use Printer Matcher Agent configuration (NOT Knowledge Base agent)
    $endpoint = rtrim(DO_PRINTER_AGENT_URL, '/') . '/api/v1/chat/completions';
    
    // Build messages array from history + new message
    $messages = [];
    
    // Add history messages if provided
    if (!empty($history)) {
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role ?? 'user',
                'content' => $msg->content ?? ''
            ];
        }
    }
    
    // Add the new user message
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];
    
    $data = [
        'messages' => $messages,
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
        'Authorization: Bearer ' . DO_PRINTER_AGENT_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Printer Agent API error (HTTP ' . $httpCode . '): ' . $response);
    }
    
    $responseData = json_decode($response, true);
    
    // Parse OpenAI-compatible response format
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    }
    
    throw new Exception('Invalid Printer Agent response format');
}

/**
 * Clean JSON string by removing markdown code blocks
 */
function cleanJsonString($str) {
    $clean = trim($str);
    
    // Remove ```json wrapper
    if (strpos($clean, '```json') === 0) {
        $clean = substr($clean, 7);
    }
    
    // Remove ``` wrapper
    if (strpos($clean, '```') === 0) {
        $clean = substr($clean, 3);
    }
    
    // Remove trailing ```
    if (substr($clean, -3) === '```') {
        $clean = substr($clean, 0, -3);
    }
    
    return trim($clean);
}

/**
 * Log customer request to database
 */
function logCustomerRequest($deviceId, $requirements) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO customer_requests 
                  (device_id, office_size, monthly_volume, color_preference, 
                   paper_size, scanning_frequency, budget_level) 
                  VALUES 
                  (:device_id, :office_size, :monthly_volume, :color_preference, 
                   :paper_size, :scanning_frequency, :budget_level)";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters (with null defaults if not provided)
        $stmt->bindValue(':device_id', $deviceId, PDO::PARAM_STR);
        $stmt->bindValue(':office_size', $requirements['office'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':monthly_volume', $requirements['volume'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':color_preference', $requirements['color'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':paper_size', $requirements['paper'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':scanning_frequency', $requirements['scan'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':budget_level', $requirements['budget'] ?? null, PDO::PARAM_STR);
        
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Failed to log customer request: ' . $e->getMessage());
    }
}
?>
