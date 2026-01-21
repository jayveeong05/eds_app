<?php
include_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "--- Chat Sessions ---\n";
$stmt = $db->query("SELECT * FROM chat_sessions");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($sessions);

echo "\n--- Chat Messages (Last 10) ---\n";
$stmt = $db->query("SELECT id, user_id, session_id, message_text, created_at FROM chat_messages ORDER BY created_at DESC LIMIT 10");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($messages);
?>
