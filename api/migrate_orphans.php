<?php
header("Content-Type: text/plain");
include_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Checking for orphaned messages...\n";

    // Find users who have messages with NULL session_id
    $sql = "SELECT DISTINCT user_id FROM chat_messages WHERE session_id IS NULL";
    $stmt = $db->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($users)) {
        echo "No orphaned messages found.\n";
        exit;
    }

    foreach ($users as $userId) {
        echo "Processing user: $userId\n";

        // Create a new session for these messages
        // Use the timestamp of the last message as session created_at/updated_at
        $maxDateQuery = "SELECT MAX(created_at) FROM chat_messages WHERE user_id = ? AND session_id IS NULL";
        $stmtMax = $db->prepare($maxDateQuery);
        $stmtMax->execute([$userId]);
        $lastMsgDate = $stmtMax->fetchColumn();

        $createSessionSql = "INSERT INTO chat_sessions (user_id, title, created_at, updated_at) VALUES (?, 'Legacy Chat', ?, ?) RETURNING id";
        $stmtCreate = $db->prepare($createSessionSql);
        $stmtCreate->execute([$userId, $lastMsgDate, $lastMsgDate]);
        $sessionId = $stmtCreate->fetchColumn();

        echo "Created session ID: $sessionId\n";

        // Update messages
        $updateSql = "UPDATE chat_messages SET session_id = ? WHERE user_id = ? AND session_id IS NULL";
        $stmtUpdate = $db->prepare($updateSql);
        $stmtUpdate->execute([$sessionId, $userId]);

        echo "Updated messages to session ID $sessionId\n";
    }

    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
