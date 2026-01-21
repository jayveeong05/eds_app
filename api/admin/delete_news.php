<?php
/**
 * Admin - Delete News
 * Permanently delete a news item
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken) || empty($data->newsId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: idToken, newsId'
    ]);
    exit;
}

// Verify admin access
$middleware = new AdminMiddleware();
$admin = $middleware->verifyAdmin($data->idToken);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get news info before deletion for logging
    $newsQuery = "SELECT title FROM news WHERE id = :newsId";
    $newsStmt = $db->prepare($newsQuery);
    $newsStmt->bindParam(':newsId', $data->newsId);
    $newsStmt->execute();
    
    if ($newsStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'News not found'
        ]);
        exit;
    }
    
    $news = $newsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete news
    $query = "DELETE FROM news WHERE id = :newsId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':newsId', $data->newsId);
    
    if ($stmt->execute()) {
        // Log admin action
        $middleware->logAction(
            $admin['id'],
            'delete_news',
            'news',
            $data->newsId,
            json_encode([
                'deleted_title' => $news['title'],
                'admin_email' => $admin['email']
            ])
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'News deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete news'
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
