<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config/database.php';

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get pagination and search parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build query
    $query = "SELECT id, title, subtitle, file_url, created_at 
              FROM knowledge_base";
    
    $params = [];
    
    // Add search filter if provided
    if (!empty($search)) {
        $query .= " WHERE (LOWER(title) LIKE :search OR LOWER(subtitle) LIKE :search)";
        $params[':search'] = '%' . strtolower($search) . '%';
    }
    
    // Order by most recent first
    $query .= " ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
    
    // Execute query
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch results
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert timestamp to ISO 8601 format with timezone
        $createdAt = $row['created_at'];
        if ($createdAt && strpos($createdAt, 'T') === false && strpos($createdAt, 'Z') === false) {
            $createdAt = str_replace(' ', 'T', $createdAt) . 'Z';
        }
        $row['created_at'] = $createdAt;
        $items[] = $row;
    }
    
    // Get total count (with same filters)
    $countQuery = "SELECT COUNT(*) as total FROM knowledge_base";
    if (!empty($search)) {
        $countQuery .= " WHERE (LOWER(title) LIKE :search OR LOWER(subtitle) LIKE :search)";
    }
    $countStmt = $db->prepare($countQuery);
    if (!empty($search)) {
        $countStmt->bindValue(':search', '%' . strtolower($search) . '%');
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'hasMore' => ($offset + count($items)) < $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
