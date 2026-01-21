<?php
/**
 * Get Admin Token
 * Returns the Firebase token from PHP session
 */
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['admin_token'])) {
    echo json_encode([
        'success' => true,
        'token' => $_SESSION['admin_token']
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
}
?>
