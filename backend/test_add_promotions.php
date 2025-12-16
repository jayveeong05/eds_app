<?php
/**
 * Test script to add sample promotions
 * Run this to populate the database with test data
 */

include_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user ID from email
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

$promotions = [
    [
        'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800',
        'description' => 'Summer Sale! Get up to 50% off on all items. Limited time offer!'
    ],
    [
        'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800',
        'description' => 'New Arrivals: Check out our latest collection of products'
    ],
    [
        'image_url' => 'https://images.unsplash.com/photo-1542744095-fcf48d80b0fd?w=800',
        'description' => 'Flash Deal: Buy 2 Get 1 Free on selected categories'
    ]
];

echo "Adding sample promotions...\n";

foreach ($promotions as $promo) {
    $query = "INSERT INTO promotions (user_id, image_url, description) 
              VALUES (:user_id, :image_url, :description)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':image_url', $promo['image_url']);
    $stmt->bindParam(':description', $promo['description']);
    
    if ($stmt->execute()) {
        echo "✅ Added promotion: {$promo['description']}\n";
    } else {
        echo "❌ Failed to add promotion\n";
    }
}

echo "\n✅ Test promotions added successfully!\n";
echo "Total promotions added: " . count($promotions) . "\n";
?>

echo "\n✅ Test promotions added successfully!\n";
?>
