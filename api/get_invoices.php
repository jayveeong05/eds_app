<?php
// Suppress PHP errors from being output (they break JSON)
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/lib/JWTVerifier.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->idToken)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

try {
    // Verify Firebase token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($data->idToken, 'eds-app-1758d');
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];

    // Get user ID from firebase_uid
    $userQuery = "SELECT id FROM users WHERE firebase_uid = :firebase_uid LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':firebase_uid', $firebase_uid);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $userId = $user['id'];

    // Build query based on optional month filter
    $query = "SELECT id, month_date, pdf_url, created_at 
              FROM invoices 
              WHERE user_id = :user_id";
    
    if (isset($data->month) && !empty($data->month)) {
        // Filter by month (YYYY-MM format)
        $query .= " AND DATE_TRUNC('month', month_date) = :month::date";
    }
    
    $query .= " ORDER BY month_date DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    
    if (isset($data->month) && !empty($data->month)) {
        $monthDate = $data->month . '-01';
        $stmt->bindParam(':month', $monthDate);
    }
    
    $stmt->execute();

    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $invoices[] = [
            'id' => $row['id'],
            'month_date' => $row['month_date'],
            'pdf_url' => $row['pdf_url'],
            'created_at' => $row['created_at']
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
