<?php
/**
 * Admin - Reset User Password
 * Triggers a password reset email for the user via Firebase
 * Note: This sends a reset email to the user - admin cannot see or set the password directly
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, userId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user info
    $userQuery = "SELECT id, email, firebase_uid, login_method FROM users WHERE id = :userId LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':userId', $data->userId);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Check if user uses email/password login
    if ($user['login_method'] !== 'email') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password reset is only available for users with email/password login. This user uses ' . $user['login_method'] . ' login.'
        ]);
        exit;
    }
    
    // Check if user has Firebase UID
    if (empty($user['firebase_uid'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User does not have a Firebase account linked'
        ]);
        exit;
    }
    
    // Use Firebase REST API to send password reset email
    // Firebase API endpoint: https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode
    // Try to get from environment, fallback to web API key from firebase_options.dart
    $firebaseApiKey = getenv('FIREBASE_API_KEY');
    
    if (empty($firebaseApiKey)) {
        // Fallback to web API key (from lib/firebase_options.dart)
        // This is the Web API key for project 'eds-app-1758d'
        $firebaseApiKey = 'AIzaSyCeVSNrtNEmclI0jtMEvHWt4QrCdZDDCB0';
    }
    
    $firebaseUrl = "https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=" . $firebaseApiKey;
    
    $firebasePayload = [
        'requestType' => 'PASSWORD_RESET',
        'email' => $user['email']
    ];
    
    $ch = curl_init($firebaseUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebasePayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $firebaseResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'reset_user_password',
            'user',
            $data->userId,
            json_encode([
                'user_email' => $user['email'],
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Password reset email sent successfully to ' . $user['email'],
            'data' => [
                'user_id' => $data->userId,
                'user_email' => $user['email']
            ]
        ]);
    } else {
        $errorData = json_decode($firebaseResponse, true);
        $errorMessage = $errorData['error']['message'] ?? 'Failed to send password reset email';
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send password reset email: ' . $errorMessage
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

