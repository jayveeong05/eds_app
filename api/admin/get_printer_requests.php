<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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
    
    // Get all printer requests
    $query = "SELECT 
                id,
                device_id,
                office_size,
                monthly_volume,
                color_preference,
                paper_size,
                scanning_frequency,
                budget_level,
                created_at
              FROM customer_requests 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rawRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert timestamps to ISO 8601 format with timezone
    $requests = [];
    foreach ($rawRequests as $row) {
        $createdAt = $row['created_at'];
        if ($createdAt && strpos($createdAt, 'T') === false && strpos($createdAt, 'Z') === false) {
            $createdAt = str_replace(' ', 'T', $createdAt) . 'Z';
        }
        $row['created_at'] = $createdAt;
        $requests[] = $row;
    }
    
    // Calculate statistics
    $stats = [
        'total_requests' => count($requests),
        'unique_devices' => 0,
        'avg_volume' => 0,
        'today_requests' => 0
    ];
    
    if (count($requests) > 0) {
        // Unique devices
        $uniqueDevices = array_unique(array_column($requests, 'device_id'));
        $stats['unique_devices'] = count($uniqueDevices);
        
        // Average monthly volume
        $volumes = array_filter(array_column($requests, 'monthly_volume'));
        if (count($volumes) > 0) {
            $stats['avg_volume'] = (int) round(array_sum($volumes) / count($volumes));
        }
        
        // Today's requests
        $today = date('Y-m-d');
        $stats['today_requests'] = count(array_filter($requests, function($r) use ($today) {
            return strpos($r['created_at'], $today) === 0;
        }));
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch printer requests: ' . $e->getMessage()
    ]);
}
?>
