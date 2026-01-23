<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->email) &&
    !empty($data->password)
){
    $query = "SELECT id, email, password_hash, status, role FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    // Check if email exists
    if($stmt->rowCount() > 0){
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Block deleted users from logging in
        if ($row['status'] === 'deleted') {
            http_response_code(403);
            echo json_encode(array(
                "message" => "Account has been deleted. Please contact support.",
                "success" => false
            ));
            exit;
        }
        
        $password_hash = $row['password_hash'];
        
        if(password_verify($data->password, $password_hash)){
            http_response_code(200);
            
            // In a real app, generate a JWT here. 
            // For this MVP, we will return the User ID as a token and the status.
            echo json_encode(array(
                "message" => "Login successful.",
                "token" => $row['id'], // Simple ID as token for now
                "user" => array(
                    "id" => $row['id'],
                    "email" => $row['email'],
                    "status" => $row['status'],
                    "role" => $row['role']
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Login failed. Wrong password."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Login failed. Email not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?>
