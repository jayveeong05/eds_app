<?php
/**
 * Test Admin Middleware Endpoint
 * Use this to verify admin authentication is working
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../lib/AdminMiddleware.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No token provided'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

// If we get here, user is authenticated and is an admin
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Admin authentication successful',
    'admin' => [
        'id' => $admin['id'],
        'email' => $admin['email'],
        'name' => $admin['name'],
        'role' => $admin['role']
    ]
]);
?>
