<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Global Error Handler to ensure JSON output
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "PHP Error: [$errno] $errstr in $errfile:$errline"
    ]);
    exit;
}
set_error_handler("jsonErrorHandler");

// Global Exception Handler
function jsonExceptionHandler($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
    ]);
    exit;
}
set_exception_handler("jsonExceptionHandler");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Updated path for api root level
require_once __DIR__ . '/lib/AdminMiddleware.php';

$input = file_get_contents("php://input");
$data = json_decode($input);

if (!$data || !isset($data->idToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID token is required. debug_input: ' . substr($input, 0, 100)
    ]);
    exit;
}

try {
    $middleware = new AdminMiddleware();
    $admin = $middleware->verifyAdmin($data->idToken);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'admin' => [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'name' => $admin['name'],
            'role' => $admin['role']
        ]
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
