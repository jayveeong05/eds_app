<?php
/**
 * Admin - Get Dashboard Statistics
 * Returns key metrics for admin dashboard
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

// Global Error Handler to ensure JSON output
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "PHP Error: [$errno] $errstr in $errfile:$errline"
    ]);
    exit;
}
set_error_handler("jsonErrorHandler");

// Global Exception Handler
function jsonExceptionHandler($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine()
    ]);
    exit;
}
set_exception_handler("jsonExceptionHandler");

$data = json_decode(file_get_contents("php://input"));

if (!$data || empty($data->idToken)) {
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
    
    // Get total users
    $totalUsersQuery = "SELECT COUNT(*) as count FROM users";
    $totalUsers = $db->query($totalUsersQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get active users
    $activeUsersQuery = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
    $activeUsers = $db->query($activeUsersQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get inactive users
    $inactiveUsersQuery = "SELECT COUNT(*) as count FROM users WHERE status = 'inactive'";
    $inactiveUsers = $db->query($inactiveUsersQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total promotions
    $totalPromotionsQuery = "SELECT COUNT(*) as count FROM promotions";
    $totalPromotions = $db->query($totalPromotionsQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent registrations (last 7 days)
    $recentRegsQuery = "SELECT COUNT(*) as count FROM users 
                        WHERE created_at >= NOW() - INTERVAL '7 days'";
    $recentRegistrations = $db->query($recentRegsQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total admins
    $totalAdminsQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $totalAdmins = $db->query($totalAdminsQuery)->fetch(PDO::FETCH_ASSOC)['count'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'active_users' => (int)$activeUsers,
            'inactive_users' => (int)$inactiveUsers,
            'total_promotions' => (int)$totalPromotions,
            'recent_registrations' => (int)$recentRegistrations,
            'total_admins' => (int)$totalAdmins
        ]
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics: ' . $e->getMessage()
    ]);
}
