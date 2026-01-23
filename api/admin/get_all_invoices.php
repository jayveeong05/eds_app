<?php
/**
 * Admin - Get All Invoices
 * Returns all invoices for admin management
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

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
    
    // Get pagination parameters
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    
    // Get all invoices
    $query = "SELECT id::text, code, month, file_url, created_at
              FROM invoices
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert timestamp to ISO 8601 format with timezone
        // If timestamp is without timezone, assume it's UTC
        $createdAt = $row['created_at'];
        if ($createdAt && strpos($createdAt, 'T') === false && strpos($createdAt, 'Z') === false) {
            // Format: "2025-12-19 09:56:40.727965" -> convert to ISO with UTC
            $createdAt = str_replace(' ', 'T', $createdAt) . 'Z';
        }
        
        $invoices[] = [
            'id' => $row['id'],
            'code' => $row['code'],
            'month' => $row['month'],
            'file_url' => $row['file_url'],
            'created_at' => $createdAt
        ];
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM invoices";
    $total = $db->query($countQuery)->fetch(PDO::FETCH_ASSOC)['total'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch invoices: ' . $e->getMessage()
    ]);
}
?>
