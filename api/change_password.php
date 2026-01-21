<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/JWTVerifier.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id_token = $input['idToken'] ?? '';
$current_password = $input['currentPassword'] ?? '';
$new_password = $input['newPassword'] ?? '';

if (empty($id_token) || empty($current_password) || empty($new_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Password validation
if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Verify Firebase token using JWTVerifier
$verifier = new JWTVerifier();
$verification = $verifier->verify($id_token, 'ANY_PROJECT_ID_FOR_NOW');

if (!$verification['valid']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Get user ID from verification payload
$firebase_uid = $verification['payload']['sub'] ?? null;

if (!$firebase_uid) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user ID in token']);
    exit;
}

// Get user from database
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, email, login_method FROM users WHERE firebase_uid = :uid";
$stmt = $db->prepare($query);
$stmt->bindParam(':uid', $firebase_uid);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Check if user is using email/password login
if ($user['login_method'] !== 'email') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Password change not available for ' . $user['login_method'] . ' login'
    ]);
    exit;
}

// Note: Firebase handles password authentication and updates
// This endpoint is for validation only - actual password change happens in Firebase SDK on client side
// We just verify the user is allowed to change password (email login only)

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Password change validated. Proceed with Firebase update.',
    'user' => [
        'id' => $user['id'],
        'email' => $user['email']
    ]
]);
?>
