<?php
/**
 * Test script to add sample invoices for the current logged-in user
 * Run this to populate test data
 */

include_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// First, get your user ID
echo "Enter your email address: ";
$email = trim(fgets(STDIN));

$userQuery = "SELECT id, email FROM users WHERE email = :email LIMIT 1";
$userStmt = $db->prepare($userQuery);
$userStmt->bindParam(':email', $email);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ User not found with email: $email\n");
}

echo "✅ Found user: {$user['email']}\n\n";
$userId = $user['id'];

// Sample invoices for the last 6 months
$invoices = [
    [
        'month_date' => '2024-12-01',
        'pdf_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf'
    ],
    [
        'month_date' => '2024-11-01',
        'pdf_url' => 'https://www.africau.edu/images/default/sample.pdf'
    ],
    [
        'month_date' => '2024-10-01',
        'pdf_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf'
    ],
    [
        'month_date' => '2024-09-01',
        'pdf_url' => 'https://www.africau.edu/images/default/sample.pdf'
    ],
];

echo "Adding sample invoices...\n";

foreach ($invoices as $invoice) {
    $query = "INSERT INTO invoices (user_id, month_date, pdf_url) 
              VALUES (:user_id, :month_date, :pdf_url)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':month_date', $invoice['month_date']);
    $stmt->bindParam(':pdf_url', $invoice['pdf_url']);
    
    if ($stmt->execute()) {
        echo "✅ Added invoice for {$invoice['month_date']}\n";
    } else {
        echo "❌ Failed to add invoice for {$invoice['month_date']}\n";
    }
}

echo "\n✅ Test invoices added successfully!\n";
echo "Total invoices added: " . count($invoices) . "\n";
?>
