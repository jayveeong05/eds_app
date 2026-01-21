<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Item ID is required');
    }
    
    $id = $_POST['id'];
    
    // Delete from database
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "DELETE FROM knowledge_base WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    if ($affected === 0) {
        throw new Exception('Item not found');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Knowledge base item deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
