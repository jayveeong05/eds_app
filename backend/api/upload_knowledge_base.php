<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Validate inputs
    if (!isset($_POST['title']) || empty(trim($_POST['title']))) {
        throw new Exception('Title is required');
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    $title = trim($_POST['title']);
    $subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : '';
    $file = $_FILES['file'];
    
    // Validate file type (PDF only)
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileType !== 'pdf') {
        throw new Exception('Only PDF files are allowed');
    }
    
    // Initialize S3
    $s3ConfigFile = __DIR__ . '/../config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    require_once __DIR__ . '/../lib/SimpleS3.php';
    
    // Upload to S3
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    // Sanitize filename: replace spaces and special chars with underscores
    $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $s3Key = 'knowledge_base/' . uniqid() . '_' . $sanitizedFilename;
    
    // Debug: Log file info
    error_log("Upload attempt - File: " . $file['name'] . ", Size: " . $file['size'] . ", Tmp: " . $file['tmp_name']);
    error_log("S3 Key: " . $s3Key . ", Bucket: " . AWS_BUCKET . ", Region: " . AWS_REGION);
    
    // Verify uploaded file exists
    if (!file_exists($file['tmp_name'])) {
        throw new Exception('Uploaded file not found at: ' . $file['tmp_name']);
    }
    
    if (filesize($file['tmp_name']) == 0) {
        throw new Exception('Uploaded file is empty');
    }
    
    $uploadResult = $s3->putObject($file['tmp_name'], AWS_BUCKET, $s3Key);
    
    if ($uploadResult !== true) {
        // Log detailed error for debugging
        error_log("S3 Upload failed: " . print_r($uploadResult, true));
        throw new Exception('Failed to upload file to S3: ' . $uploadResult);
    }
    
    // Insert into database
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO knowledge_base (title, subtitle, file_url) 
              VALUES (:title, :subtitle, :file_url)
              RETURNING id, created_at";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':subtitle', $subtitle);
    $stmt->bindParam(':file_url', $s3Key);
    $stmt->execute();
    
    // Fetch the returned row with ID and created_at
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Knowledge base item uploaded successfully',
        'data' => [
            'id' => $result['id'],
            'title' => $title,
            'subtitle' => $subtitle,
            'file_url' => $s3Key,
            'created_at' => $result['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
