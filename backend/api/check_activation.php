<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/JWTVerifier.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id_token = $input['idToken'] ?? '';

if (empty($id_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing token']);
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

// Get user from database using firebase_uid
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, email, name, role, status FROM users WHERE firebase_uid = :uid";
$stmt = $db->prepare($query);
$stmt->bindParam(':uid', $firebase_uid);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Return user status
http_response_code(200);
echo json_encode([
    'success' => true,
    'status' => $user['status'],
    'message' => $user['status'] === 'active' ? 'Account is active' : 'Account pending approval',
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'status' => $user['status']
    ]
]);
?>
