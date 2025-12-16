<?php
/**
 * Admin - Delete Promotion
 * Permanently delete a promotion
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../lib/AdminMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->promotionId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, promotionId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get promotion info before deletion for logging
    $promoQuery = "SELECT description FROM promotions WHERE id = :promotionId";
    $promoStmt = $db->prepare($promoQuery);
    $promoStmt->bindParam(':promotionId', $data->promotionId);
    $promoStmt->execute();
    
    if ($promoStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Promotion not found'
        ]);
        exit;
    }
    
    $promotion = $promoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete promotion
    $query = "DELETE FROM promotions WHERE id = :promotionId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':promotionId', $data->promotionId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'delete_promotion',
            'promotion',
            $data->promotionId,
            json_encode([
                'deleted_description' => substr($promotion['description'], 0, 100),
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Promotion deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete promotion'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
