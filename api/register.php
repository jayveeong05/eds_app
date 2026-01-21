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
    $email = $data->email;
    $password = $data->password;
    
    // Check if email exists
    $check_query = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0){
        http_response_code(400);
        echo json_encode(array("message" => "Email already exists."));
        exit;
    }

    $query = "INSERT INTO users (email, password_hash, status, role) VALUES (:email, :password_hash, 'inactive', 'user')";
    $stmt = $db->prepare($query);

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);

    if($stmt->execute()){
        http_response_code(201);
        echo json_encode(array("message" => "User was created."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create user."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create user. Data is incomplete."));
}
?>
