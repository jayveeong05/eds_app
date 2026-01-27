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
    
    // Get pagination and search parameters
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    $search = isset($data->search) ? trim($data->search) : '';
    
    // Build query with optional search filter
    $query = "SELECT DISTINCT code 
              FROM invoices 
              WHERE 1=1";
    
    $params = [];
    if (!empty($search)) {
        $query .= " AND code ILIKE :search";
        $params[':search'] = "%$search%";
    }
    
    $query .= " ORDER BY code ASC 
                LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (isset($params[':search'])) {
        $stmt->bindValue(':search', $params[':search']);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $codes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $codes[] = $row['code'];
    }
    
    // Get total count (with same filters)
    $countQuery = "SELECT COUNT(DISTINCT code) as total FROM invoices WHERE 1=1";
    if (!empty($search)) {
        $countQuery .= " AND code ILIKE :search";
    }
    $countStmt = $db->prepare($countQuery);
    if (isset($params[':search'])) {
        $countStmt->bindValue(':search', $params[':search']);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $codes,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'hasMore' => ($offset + count($codes)) < $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


