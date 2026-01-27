<?php
/**
 * Admin - Get All Invoices
 * Returns all invoices for admin management
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../lib/AdminMiddleware.php';
require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->idToken)) {
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
    
    // Get pagination and search parameters
    $limit = isset($data->limit) ? (int)$data->limit : 50;
    $offset = isset($data->offset) ? (int)$data->offset : 0;
    $search = isset($data->search) ? trim($data->search) : '';
    
    // Check if invoice_year and invoice_number columns exist
    try {
        $checkColumnsQuery = "SELECT column_name FROM information_schema.columns 
                              WHERE table_name = 'invoices' 
                              AND column_name IN ('invoice_year', 'invoice_number')";
        $checkColumnsStmt = $db->query($checkColumnsQuery);
        $existingColumns = [];
        while ($row = $checkColumnsStmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['column_name'];
        }
        $hasInvoiceYear = in_array('invoice_year', $existingColumns);
        $hasInvoiceNumber = in_array('invoice_number', $existingColumns);
    } catch (Exception $e) {
        $hasInvoiceYear = false;
        $hasInvoiceNumber = false;
    }
    
    // Build query with optional search filter
    // Include invoice_year and invoice_number if columns exist
    $selectFields = "id::text, code, month, file_url, created_at";
    if ($hasInvoiceYear) {
        $selectFields .= ", invoice_year";
    }
    if ($hasInvoiceNumber) {
        $selectFields .= ", invoice_number";
    }
    
    $query = "SELECT $selectFields
              FROM invoices
              WHERE 1=1";
    
    $params = [];
    $yearFilter = null;
    
    // Parse search for year filters
    if (!empty($search)) {
        $searchLower = strtolower($search);
        
        // Check for "before 2025" or "< 2025" patterns
        if (preg_match('/before\s+(\d{4})/i', $search, $matches) || preg_match('/<\s*(\d{4})/i', $search, $matches)) {
            $year = (int)$matches[1];
            $yearFilter = ['type' => 'before', 'year' => $year];
            // Remove year filter from text search
            $search = preg_replace('/before\s+\d{4}/i', '', $search);
            $search = preg_replace('/<\s*\d{4}/i', '', $search);
            $search = trim($search);
        }
        // Check for "after 2025" or "> 2025" patterns
        elseif (preg_match('/after\s+(\d{4})/i', $search, $matches) || preg_match('/>\s*(\d{4})/i', $search, $matches)) {
            $year = (int)$matches[1];
            $yearFilter = ['type' => 'after', 'year' => $year];
            // Remove year filter from text search
            $search = preg_replace('/after\s+\d{4}/i', '', $search);
            $search = preg_replace('/>\s*\d{4}/i', '', $search);
            $search = trim($search);
        }
        // Check for exact year (4 digits, standalone or in context)
        elseif (preg_match('/\b(\d{4})\b/', $search, $matches)) {
            $year = (int)$matches[1];
            // Only treat as year if it's a reasonable year (2000-2100)
            if ($year >= 2000 && $year <= 2100) {
                $yearFilter = ['type' => 'exact', 'year' => $year];
                // Remove year from text search
                $search = preg_replace('/\b\d{4}\b/', '', $search);
                $search = trim($search);
            }
        }
    }
    
    // Add year filter if detected
    // Use invoice_year column if available, otherwise fall back to created_at
    if ($yearFilter) {
        $yearColumn = $hasInvoiceYear ? 'invoice_year' : 'EXTRACT(YEAR FROM created_at)';
        if ($yearFilter['type'] === 'exact') {
            $query .= " AND $yearColumn = :year";
            $params[':year'] = $yearFilter['year'];
        } elseif ($yearFilter['type'] === 'before') {
            $query .= " AND $yearColumn < :year";
            $params[':year'] = $yearFilter['year'];
        } elseif ($yearFilter['type'] === 'after') {
            $query .= " AND $yearColumn > :year";
            $params[':year'] = $yearFilter['year'];
        }
    }
    
    // Add text search filter (code, month, or invoice_number) if still has content
    if (!empty($search)) {
        if ($hasInvoiceNumber) {
            $query .= " AND (code ILIKE :search OR month ILIKE :search OR invoice_number ILIKE :search)";
        } else {
            $query .= " AND (code ILIKE :search OR month ILIKE :search)";
        }
        $params[':search'] = "%$search%";
    }
    
    $query .= " ORDER BY created_at DESC 
               LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    if (isset($params[':year'])) {
        $stmt->bindValue(':year', $params[':year'], PDO::PARAM_INT);
    }
    if (isset($params[':search'])) {
        $stmt->bindValue(':search', $params[':search']);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $invoices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Convert timestamp to ISO 8601 format with timezone
        // If timestamp is without timezone, assume it's UTC
        $createdAt = $row['created_at'];
        if ($createdAt && strpos($createdAt, 'T') === false && strpos($createdAt, 'Z') === false) {
            // Format: "2025-12-19 09:56:40.727965" -> convert to ISO with UTC
            $createdAt = str_replace(' ', 'T', $createdAt) . 'Z';
        }
        
        $invoice = [
            'id' => $row['id'],
            'code' => $row['code'],
            'month' => $row['month'],
            'file_url' => $row['file_url'],
            'created_at' => $createdAt
        ];
        
        // Add invoice_year if available
        if ($hasInvoiceYear && isset($row['invoice_year'])) {
            $invoice['invoice_year'] = (int)$row['invoice_year'];
        }
        
        // Add invoice_number if available
        if ($hasInvoiceNumber && isset($row['invoice_number'])) {
            $invoice['invoice_number'] = $row['invoice_number'];
        }
        
        $invoices[] = $invoice;
    }
    
    // Get total count (with same filters)
    $countQuery = "SELECT COUNT(*) as total FROM invoices WHERE 1=1";
    if ($yearFilter) {
        $yearColumn = $hasInvoiceYear ? 'invoice_year' : 'EXTRACT(YEAR FROM created_at)';
        if ($yearFilter['type'] === 'exact') {
            $countQuery .= " AND $yearColumn = :year";
        } elseif ($yearFilter['type'] === 'before') {
            $countQuery .= " AND $yearColumn < :year";
        } elseif ($yearFilter['type'] === 'after') {
            $countQuery .= " AND $yearColumn > :year";
        }
    }
    if (!empty($search)) {
        if ($hasInvoiceNumber) {
            $countQuery .= " AND (code ILIKE :search OR month ILIKE :search OR invoice_number ILIKE :search)";
        } else {
            $countQuery .= " AND (code ILIKE :search OR month ILIKE :search)";
        }
    }
    $countStmt = $db->prepare($countQuery);
    if (isset($params[':year'])) {
        $countStmt->bindValue(':year', $params[':year'], PDO::PARAM_INT);
    }
    if (isset($params[':search'])) {
        $countStmt->bindValue(':search', $params[':search']);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch invoices: ' . $e->getMessage()
    ]);
}
?>
