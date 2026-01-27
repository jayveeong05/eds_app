<?php
/**
 * Get Code Invoices
 * Returns all invoices for a specific machine code with presigned URLs
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/SimpleS3.php';
require_once __DIR__ . '/lib/JWTVerifier.php';

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput);

// Check for JSON decode errors
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

// Check if data is null or required fields are missing
if (!$data || empty($data->code) || empty($data->idToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Machine code and authentication token required',
        'debug' => [
            'received_data' => $data,
            'raw_input' => $rawInput
        ]
    ]);
    exit;
}

try {
    // Verify Firebase token
    $verifier = new JWTVerifier();
    $result = $verifier->verify($data->idToken, 'eds-app-1758d');
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }

    $decoded = $result['payload'];
    $firebase_uid = $decoded['sub'] ?? $decoded['user_id'];

    $database = new Database();
    $db = $database->getConnection();

    // Get user info including role
    $userQuery = "SELECT id, role FROM users WHERE firebase_uid = :firebase_uid LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':firebase_uid', $firebase_uid);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $userId = $user['id'];
    $userRole = $user['role'];

    // Authorization check: verify user owns this code (unless admin)
    if ($userRole !== 'admin') {
        $authQuery = "SELECT COUNT(*) as has_access 
                      FROM user_codes 
                      WHERE user_id = :user_id AND code = :code";
        $authStmt = $db->prepare($authQuery);
        $authStmt->bindParam(':user_id', $userId);
        $authStmt->bindParam(':code', $data->code);
        $authStmt->execute();
        $authResult = $authStmt->fetch(PDO::FETCH_ASSOC);

        if ($authResult['has_access'] == 0) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied: You do not have permission to view this machine code'
            ]);
            exit;
        }
    }
    
    // Get latest year's invoice for each month (prefers current year if exists, otherwise latest available)
    // Uses invoice_year column if available, otherwise falls back to extracting from created_at
    // Check which columns exist
    try {
        $checkColumnsQuery = "SELECT column_name FROM information_schema.columns 
                              WHERE table_name = 'invoices' 
                              AND column_name IN ('invoice_year', 'invoice_number')";
        $checkColumnsStmt = $db->query($checkColumnsQuery);
        $existingColumns = [];
        while ($row = $checkColumnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['column_name'];
        }
        $hasInvoiceYearColumn = in_array('invoice_year', $existingColumns);
        $hasInvoiceNumberColumn = in_array('invoice_number', $existingColumns);
    } catch (Exception $e) {
        $hasInvoiceYearColumn = false;
        $hasInvoiceNumberColumn = false;
    }
    
    if ($hasInvoiceYearColumn) {
        // Use invoice_year column (preferred - stores actual invoice year from filename, not upload year)
        // Get ALL invoices for the latest year per month (not just one invoice per month)
        $query = "
            WITH latest_year_per_month AS (
                -- Find the latest invoice_year for each month
                SELECT 
                    month,
                    MAX(invoice_year) as latest_year
                FROM invoices 
                WHERE code = :code
                GROUP BY month
            ),
            latest_invoices AS (
                -- Get all invoices that match the latest year for each month
                SELECT 
                    i.id::text,
                    i.code,
                    i.month,
                    i.file_url,
                    i.created_at,
                    i.invoice_year,
                    i.invoice_number
                FROM invoices i
                INNER JOIN latest_year_per_month lypm 
                    ON i.month = lypm.month 
                    AND i.invoice_year = lypm.latest_year
                WHERE i.code = :code
            )
            SELECT id, code, month, file_url, created_at, invoice_year::integer as invoice_year, invoice_number
            FROM latest_invoices
            ORDER BY 
                CASE month
                    WHEN 'January' THEN 1
                    WHEN 'February' THEN 2
                    WHEN 'March' THEN 3
                    WHEN 'April' THEN 4
                    WHEN 'May' THEN 5
                    WHEN 'June' THEN 6
                    WHEN 'July' THEN 7
                    WHEN 'August' THEN 8
                    WHEN 'September' THEN 9
                    WHEN 'October' THEN 10
                    WHEN 'November' THEN 11
                    WHEN 'December' THEN 12
                END,
                created_at DESC
        ";
    } else {
        // Fallback: extract year from created_at (for backward compatibility during migration)
        // Get ALL invoices for the latest year per month (not just one invoice per month)
        $invoiceNumberSelect = $hasInvoiceNumberColumn ? "i.invoice_number" : "'001' as invoice_number";
        $invoiceNumberField = $hasInvoiceNumberColumn ? "invoice_number" : "'001' as invoice_number";
        
        $query = "
            WITH latest_year_per_month AS (
                -- Find the latest year (from created_at) for each month
                SELECT 
                    month,
                    MAX(EXTRACT(YEAR FROM created_at))::integer as latest_year
                FROM invoices 
                WHERE code = :code
                GROUP BY month
            ),
            latest_invoices AS (
                -- Get all invoices that match the latest year for each month
                SELECT 
                    i.id::text,
                    i.code,
                    i.month,
                    i.file_url,
                    i.created_at,
                    EXTRACT(YEAR FROM i.created_at)::integer as invoice_year,
                    " . $invoiceNumberSelect . "
                FROM invoices i
                INNER JOIN latest_year_per_month lypm 
                    ON i.month = lypm.month 
                    AND EXTRACT(YEAR FROM i.created_at) = lypm.latest_year
                WHERE i.code = :code
            )
            SELECT id, code, month, file_url, created_at, invoice_year, " . $invoiceNumberField . "
            FROM latest_invoices
            ORDER BY 
                CASE month
                    WHEN 'January' THEN 1
                    WHEN 'February' THEN 2
                    WHEN 'March' THEN 3
                    WHEN 'April' THEN 4
                    WHEN 'May' THEN 5
                    WHEN 'June' THEN 6
                    WHEN 'July' THEN 7
                    WHEN 'August' THEN 8
                    WHEN 'September' THEN 9
                    WHEN 'October' THEN 10
                    WHEN 'November' THEN 11
                    WHEN 'December' THEN 12
                END,
                created_at DESC
        ";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':code', $data->code);
    $stmt->execute();
    
    // Initialize S3 for presigned URLs
    $s3ConfigFile = __DIR__ . '/config/s3_config.php';
    if (file_exists($s3ConfigFile)) {
        require_once $s3ConfigFile;
    } else {
        define('AWS_ACCESS_KEY', getenv('AWS_ACCESS_KEY'));
        define('AWS_SECRET_KEY', getenv('AWS_SECRET_KEY'));
        define('AWS_REGION', getenv('AWS_REGION') ?: 'us-east-1');
        define('AWS_BUCKET', getenv('AWS_BUCKET'));
    }
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate presigned URL (valid for 1 hour)
        $pdfUrl = $s3->getPresignedUrl(AWS_BUCKET, $row['file_url'], 3600);
        
        // Use invoice_year from database (actual invoice year, not upload year)
        // Falls back to extracting from created_at if invoice_year column doesn't exist
        $invoiceYear = isset($row['invoice_year']) ? (int)$row['invoice_year'] : date('Y', strtotime($row['created_at']));
        
        $invoice = [
            'id' => $row['id'],
            'code' => $row['code'],
            'month' => $row['month'],
            'invoice_year' => $invoiceYear,  // Actual invoice year (from filename)
            'year' => $invoiceYear,  // Alias for backward compatibility
            'pdf_url' => $pdfUrl,
            'created_at' => $row['created_at']
        ];
        
        // Add invoice_number if available
        if (isset($row['invoice_number'])) {
            $invoice['invoice_number'] = $row['invoice_number'];
        }
        
        $invoices[] = $invoice;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
