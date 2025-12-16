<?php
/**
 * Admin - Get All Users
 * List all users with search and filter support
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

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
    
    // Get filter parameters
    $search = $data->search ?? '';
    $statusFilter = $data->status ?? 'all';
    $roleFilter = $data->role ?? 'all';
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    
    // Build query with filters
    $query = "SELECT id::text as id, firebase_uid, email, name, role, status, 
                     login_method, profile_image_url, created_at 
              FROM users 
              WHERE 1=1";
    
    $params = [];
    
    // Add search filter (email or name)
    if (!empty($search)) {
        $query .= " AND (email ILIKE :search OR name ILIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Add status filter
    if ($statusFilter !== 'all') {
        $query .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    
    // Add role filter
    if ($roleFilter !== 'all') {
        $query .= " AND role = :role";
        $params[':role'] = $roleFilter;
    }
    
    // Add ordering and pagination
    $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    if (!empty($search)) {
        $countQuery .= " AND (email ILIKE :search OR name ILIKE :search)";
    }
    if ($statusFilter !== 'all') {
        $countQuery .= " AND status = :status";
    }
    if ($roleFilter !== 'all') {
        $countQuery .= " AND role = :role";
    }
    
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        if ($key !== ':limit' && $key !== ':offset') {
            $countStmt->bindValue($key, $value);
        }
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $users,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch users: ' . $e->getMessage()
    ]);
}
?>
