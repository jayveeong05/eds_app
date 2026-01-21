<?php
/**
 * Admin Middleware
 * Verifies Firebase token and checks if user has admin role
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/JWTVerifier.php';

class AdminMiddleware {
    
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Verify admin access
     * Returns user data if admin, or sends error response and exits
     */
    public function verifyAdmin($idToken) {
        // Verify Firebase token
        $verifier = new JWTVerifier();
        $verification = $verifier->verify($idToken, 'eds-app-1758d');
        
        if (!$verification['valid']) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid authentication token'
            ]);
            exit;
        }
        
        // Get user from token payload
        $uid = $verification['payload']['sub'] ?? null;
        
        if (!$uid) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid token payload'
            ]);
            exit;
        }
        
        // Check if user exists and has admin role
        $query = "SELECT id, firebase_uid, email, name, role, status 
                  FROM users 
                  WHERE firebase_uid = :uid 
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user has admin role
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]);
            exit;
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Admin account is not active'
            ]);
            exit;
        }
        
        return $user;
    }
    
    /**
     * Log admin action
     */
    public function logAction($adminUserId, $action, $targetType = null, $targetId = null, $details = null) {
        $query = "INSERT INTO admin_activity_log 
                  (admin_user_id, action, target_type, target_id, details) 
                  VALUES (:admin_user_id, :action, :target_type, :target_id, :details)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':admin_user_id', $adminUserId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':target_type', $targetType);
        $stmt->bindParam(':target_id', $targetId);
        $stmt->bindParam(':details', $details);
        
        return $stmt->execute();
    }
}
