<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->month_date) && !empty($data->pdf_url)) {
    $query = "INSERT INTO invoices (user_id, month_date, pdf_url) 
              VALUES (:user_id, :month_date, :pdf_url)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':month_date', $data->month_date);
    $stmt->bindParam(':pdf_url', $data->pdf_url);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Invoice created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create invoice'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: user_id, month_date, pdf_url'
    ]);
}
?>
