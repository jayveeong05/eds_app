<?php
/**
 * Admin - Get All Machine Codes
 * Returns all distinct machine codes from invoices table for admin dropdown
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all distinct machine codes from invoices
    $query = "SELECT DISTINCT code 
              FROM invoices 
              ORDER BY code ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $codes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $codes[] = $row['code'];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $codes,
        'count' => count($codes)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


