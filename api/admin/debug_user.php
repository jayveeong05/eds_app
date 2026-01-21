<?php
// api/admin/debug_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../api/config/database.php';

$email = $_GET['email'] ?? 'admin@email.com';
$key = $_GET['key'] ?? '';

// Simple protection
if ($key !== 'debug_secret_123') {
    die('Unauthorized');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, email, role, status, firebase_uid, created_at FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'email' => $email,
        'found' => $user ? true : false,
        'user' => $user,
        'env' => [
            'has_postgres_host' => !empty(getenv('POSTGRES_HOST')),
            'db_name' => getenv('POSTGRES_DATABASE'),
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
