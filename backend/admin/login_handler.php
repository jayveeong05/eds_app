<?php
/**
 * Login Handler
 * Creates PHP session after Firebase authentication
 */
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['email']) && !empty($data['token'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $data['email'];
    $_SESSION['admin_name'] = $data['name'] ?? '';
    $_SESSION['admin_token'] = $data['token'];
    $_SESSION['last_activity'] = time();
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
