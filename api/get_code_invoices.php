<?php
/**
 * Get Code Invoices
 * Returns all invoices for a specific machine code with presigned URLs
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/SimpleS3.php';
require_once __DIR__ . '/lib/JWTVerifier.php';

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput);

// Check for JSON decode errors
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Check if data is null or required fields are missing
if (!$data || empty($data->code) || empty($data->idToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Machine code and authentication token required',
        'debug' => [
            'received_data' => $data,
            'raw_input' => $rawInput
        ]
    ]);
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

    // Authorization check: verify user owns this code (unless admin)
    if ($userRole !== 'admin') {
        $authQuery = "SELECT COUNT(*) as has_access 
                      FROM user_codes 
                      WHERE user_id = :user_id AND code = :code";
        $authStmt = $db->prepare($authQuery);
        $authStmt->bindParam(':user_id', $userId);
        $authStmt->bindParam(':code', $data->code);
        $authStmt->execute();
        $authResult = $authStmt->fetch(PDO::FETCH_ASSOC);

        if ($authResult['has_access'] == 0) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied: You do not have permission to view this machine code'
            ]);
            exit;
        }
    }
    
    // Get all invoices for this code, sorted by most recently created/updated
    $query = "SELECT id::text, code, month, file_url, created_at
              FROM invoices 
              WHERE code = :code
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':code', $data->code);
    $stmt->execute();
    
    // Initialize S3 for presigned URLs
    $s3ConfigFile = __DIR__ . '/config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL (valid for 1 hour)
        $pdfUrl = $s3->getPresignedUrl(AWS_BUCKET, $row['file_url'], 3600);
        
        $invoices[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'month' => $row['month'],
            'pdf_url' => $pdfUrl,
            'created_at' => $row['created_at']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
