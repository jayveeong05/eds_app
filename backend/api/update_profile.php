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

        // Initialize S3 for presigned URLs
        require_once __DIR__ . '/../lib/SimpleS3.php';
        $s3ConfigFile = __DIR__ . '/../config/s3_config.php';
        if (file_exists($s3ConfigFile)) {
            require_once $s3ConfigFile;
        } else {
            define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
            define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
            define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
            define('AWS_BUCKET', getenv('AWS_BUCKET'));
        }
        $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
        
        // Generate presigned URL for profile image if it exists
        if (!empty($user['profile_image_url']) && strpos($user['profile_image_url'], 'http') !== 0) {
            $user['profile_image_url'] = $s3->getPresignedUrl(AWS_BUCKET, $user['profile_image_url'], 3600);
        }

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
