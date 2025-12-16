<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../lib/JWTVerifier.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->idToken)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

try {
    // Verify Firebase token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($data->idToken, 'eds-app-1758d'); // Your Firebase project ID
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token: ' . ($result['error'] ?? 'Unknown error')]);
        exit;
    }

    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];

    // Update user profile
    $query = "UPDATE users 
              SET name = :name, 
                  profile_image_url = :profile_image_url 
              WHERE firebase_uid = :firebase_uid";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':profile_image_url', $data->profile_image_url);
    $stmt->bindParam(':firebase_uid', $firebase_uid);

    if ($stmt->execute()) {
        // Return updated user data
        $query = "SELECT id, email, name, role, status, profile_image_url 
                  FROM users 
                  WHERE firebase_uid = :firebase_uid 
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':firebase_uid', $firebase_uid);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
