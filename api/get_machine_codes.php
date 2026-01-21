<?php
/**
 * Get Machine Codes
 * Returns list of distinct machine codes based on user permissions
 * - Regular users: Only codes assigned to them via user_codes table
 * - Admins: All codes
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/JWTVerifier.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->idToken)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

try {
    // Verify Firebase token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($data->idToken, 'eds-app-1758d');
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Get user info including role
    $userQuery = "SELECT id, role FROM users WHERE firebase_uid = :firebase_uid LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':firebase_uid', $firebase_uid);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $userId = $user['id'];
    $userRole = $user['role'];
    
    // Build query based on user role
    if ($userRole === 'admin') {
        // Admins see all codes
        $query = "SELECT DISTINCT code 
                  FROM invoices 
                  ORDER BY code ASC";
        $stmt = $db->prepare($query);
    } else {
        // Regular users only see their assigned codes
        $query = "SELECT DISTINCT uc.code 
                  FROM user_codes uc
                  INNER JOIN invoices i ON uc.code = i.code
                  WHERE uc.user_id = :user_id
                  ORDER BY uc.code ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
    }
    
    $stmt->execute();
    
    $codes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $codes[] = $row['code'];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $codes,
        'count' => count($codes)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
