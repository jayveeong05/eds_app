<?php
/**
 * Get Machine Codes
 * Returns list of distinct machine codes from invoices
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get distinct machine codes, sorted alphabetically
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
