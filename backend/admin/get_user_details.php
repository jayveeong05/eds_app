<?php
// Check for admin session
require_once 'includes/auth_check.php';

// Include database config
require_once '../config/database.php';

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get user ID
$userId = $_GET['id'] ?? '';

if (empty($userId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch user details
    $stmt = $db->prepare("
        SELECT id, email, name, status, role, created_at, profile_image_url 
        FROM users 
        WHERE id = :id::uuid
    ");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (PDOException $e) {
    // Check for invalid UUID syntax error
    if (strpos($e->getMessage(), 'invalid input syntax for type uuid') !== false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid User ID format']);
    } else {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
