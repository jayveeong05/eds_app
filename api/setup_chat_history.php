<?php
header("Content-Type: text/plain");

include_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Starting Chat History Migration (Fixing UUID & Orphans)...\n";

    // 1. Create chat_sessions table
    $sql1 = "CREATE TABLE IF NOT EXISTS chat_sessions (
                id SERIAL PRIMARY KEY,
                user_id UUID NOT NULL,
                title VARCHAR(255) DEFAULT 'New Chat',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );";
    $db->exec($sql1);
    echo "Created table 'chat_sessions'.\n";

    // 2. Add session_id to chat_messages
    $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name='chat_messages' AND column_name='session_id'");
    if ($stmt->fetch()) {
        echo "Column 'session_id' already exists in 'chat_messages'.\n";
    } else {
        $sql2 = "ALTER TABLE chat_messages ADD COLUMN session_id INT NULL DEFAULT NULL;";
        $db->exec($sql2);
        echo "Added column 'session_id' to 'chat_messages'.\n";
        
        $sql3 = "ALTER TABLE chat_messages ADD CONSTRAINT fk_chat_session FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE;";
        $db->exec($sql3);
        echo "Added foreign key constraint.\n";
    }

    // 3. Migrate Orphaned Messages (create Legacy Chat)
    echo "Checking for orphaned messages...\n";
    $sqlOrphans = "SELECT DISTINCT user_id FROM chat_messages WHERE session_id IS NULL";
    $stmtOrphans = $db->query($sqlOrphans);
    $users = $stmtOrphans->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($users)) {
        foreach ($users as $userId) {
            // Check if Legacy Chat already exists to avoid duplicates if run multiple times?
            // Since we can't easily check 'Legacy Chat' specifically (title isn't unique), we rely on idempotency of session_id IS NULL.
            // If they are NULL, they need migration.
            
            $maxDateQuery = "SELECT MAX(created_at) FROM chat_messages WHERE user_id = ? AND session_id IS NULL";
            $stmtMax = $db->prepare($maxDateQuery);
            $stmtMax->execute([$userId]);
            $lastMsgDate = $stmtMax->fetchColumn();

            $createSessionSql = "INSERT INTO chat_sessions (user_id, title, created_at, updated_at) VALUES (?, 'Legacy Chat', ?, ?) RETURNING id";
            $stmtCreate = $db->prepare($createSessionSql);
            $stmtCreate->execute([$userId, $lastMsgDate, $lastMsgDate]);
            $sessionId = $stmtCreate->fetchColumn();

            echo "Created Legacy Session ID: $sessionId for User $userId\n";

            $updateSql = "UPDATE chat_messages SET session_id = ? WHERE user_id = ? AND session_id IS NULL";
            $stmtUpdate = $db->prepare($updateSql);
            $stmtUpdate->execute([$sessionId, $userId]);
        }
        echo "Migrated orphaned messages.\n";
    } else {
        echo "No orphaned messages found.\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
