<?php
// Suppress PHP errors from being output (they break JSON)
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

    // Get user profile
    $query = "SELECT id, firebase_uid, email, name, role, status, profile_image_url, created_at 
              FROM users 
              WHERE firebase_uid = :firebase_uid 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':firebase_uid', $firebase_uid);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($user) {
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
        $profileImageUrl = $user['profile_image_url'];
        if ($profileImageUrl && strpos($profileImageUrl, 'http') !== 0) {
            // It's an S3 key, generate presigned URL
            $profileImageUrl = $s3->getPresignedUrl(AWS_BUCKET, $profileImageUrl, 3600);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'status' => $user['status'],
                'profile_image_url' => $profileImageUrl,
                'created_at' => $user['created_at']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
