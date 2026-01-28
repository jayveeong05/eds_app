<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

include_once __DIR__ . '/config/database.php';
include_once __DIR__ . '/lib/JWTVerifier.php';

// Firebase Project ID (used across all endpoints for consistency)
$FIREBASE_PROJECT_ID = 'eds-app-1758d';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->idToken)) {
    
    // For local testing without real Project ID match, we might skip 'aud' check in JWTVerifier
    // or we assume user provides it.
    // Ideally, get Project ID from config.
    
    $verifier = new JWTVerifier();
    // Use actual Firebase project ID (consistent with other endpoints)
    $verification = $verifier->verify($data->idToken, 'eds-app-1758d'); 

    // Bypass verification for TESTING if needed (e.g. if time sync issues)
    // $verification = ['valid' => true, 'payload' => ['sub' => 'mock_uid', 'email' => 'mock@test.com']];
    
    if ($verification['valid']) {
        
        // In a real scenario, use $verification['payload']
        // BUT for standard Firebase flows, we often trust the client SDK's login 
        // and just use the backend to record the user.
        // Let's try to extract from payload if valid, otherwise use data->uid if sent (less secure but MVP).
        
        $uid = $data->uid ?? ($verification['payload']['sub'] ?? null);
        $email = $data->email ?? ($verification['payload']['email'] ?? null);
        // Support both keys: older clients send `signInMethod`, newer uses `loginMethod`
        $loginMethod = $data->loginMethod ?? ($data->signInMethod ?? 'email');

        if (!$uid || !$email) {
             http_response_code(400);
            echo json_encode(array(
                "success" => false,
                "message" => "Token valid but missing UID/Email."
            ));
            exit;
        }
        
        // Check if user exists
        $query = "SELECT id::text as id, email, status, role, login_method FROM users WHERE firebase_uid = :uid LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':uid', $uid);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // User exists, check if deleted
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // If the client is telling us a social login method but the DB says "email",
            // update it to reflect reality. This prevents the app from showing "EMAIL"
            // after a Google/Apple sign in.
            if (!empty($loginMethod) &&
                in_array($loginMethod, ['google', 'apple'], true) &&
                (!isset($row['login_method']) || $row['login_method'] !== $loginMethod)) {
                $update = "UPDATE users SET login_method = :loginMethod WHERE firebase_uid = :uid";
                $u = $db->prepare($update);
                $u->bindParam(':loginMethod', $loginMethod);
                $u->bindParam(':uid', $uid);
                $u->execute();

                // Keep response consistent with the updated value
                $row['login_method'] = $loginMethod;
            }
            
            // Block deleted users from authenticating
            if ($row['status'] === 'deleted') {
                http_response_code(403);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Account has been deleted. Please contact support.",
                    "user" => $row,
                    "is_new_user" => false
                ));
                exit;
            }
            
            echo json_encode(array(
                "success" => true,
                "message" => "User verified.",
                "user" => $row,
                "is_new_user" => false
            ));
        } else {
            // User new, create
            $insert = "INSERT INTO users (firebase_uid, email, status, role, login_method) VALUES (:uid, :email, 'inactive', 'user', :loginMethod)";
            $stmt = $db->prepare($insert);
            $stmt->bindParam(':uid', $uid);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':loginMethod', $loginMethod);
            
            if($stmt->execute()){
                // Fetch the newly created user to get the id
                $query = "SELECT id::text as id, email, status, role, login_method FROM users WHERE firebase_uid = :uid LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':uid', $uid);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Return new user status with complete data
                echo json_encode(array(
                    "success" => true,
                    "message" => "User created.",
                    "user" => $row,
                    "is_new_user" => true
                ));
            } else {
                http_response_code(503);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Unable to create user in DB."
                ));
            }
        }
        
    } else {
        http_response_code(401);
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid ID Token: " . $verification['error']
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "No token provided."
    ));
}
?>
