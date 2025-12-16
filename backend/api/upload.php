<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once __DIR__ . '/../config/s3_config.php';
include_once __DIR__ . '/../lib/SimpleS3.php';

$response = array();

if (isset($_FILES['file']) && isset($_POST['folder'])) {
    
    $file_path = $_FILES['file']['tmp_name'];
    $file_name = $_FILES['file']['name'];
    $folder = $_POST['folder']; // e.g., 'promotions' or 'invoices'
    
    // Simple validation
    if (!in_array($folder, ['promotions', 'invoices', 'avatars'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid folder specified."));
        exit;
    }
    
    // Generate unique name
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_name = uniqid() . '.' . $ext;
    $s3_key = "$folder/$new_name";
    
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    $upload_result = $s3->putObject($file_path, AWS_BUCKET, $s3_key);
    
    if ($upload_result === true) {
        // Return S3 key instead of full URL (for proxy pattern)
        http_response_code(201);
        echo json_encode(array(
            "message" => "File uploaded successfully.",
            "url" => $s3_key  // Return key like "promotions/abc123.jpg"
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "S3 Upload Failed: " . $upload_result));
    }

} else {
    http_response_code(400);
    echo json_encode(array("message" => "No file or folder provided."));
}
?>
