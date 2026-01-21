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
    
    // Optional search parameter
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
    $query .= " ORDER BY created_at DESC";
    
    // Execute query
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Fetch all results
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
