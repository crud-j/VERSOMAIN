<?php

require_once __DIR__ . '/backend/config.php';

try {
    $conn = getDbConnection();

    // Create table if not exists
    $createSql = "
    CREATE TABLE IF NOT EXISTS connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        connected_user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_connections (user_id, connected_user_id),
        INDEX idx_user (user_id),
        INDEX idx_connected (connected_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createSql);

    // Fetch all user ids
    $res = $conn->query("SELECT id FROM users");
    $ids = [];
    while ($r = $res->fetch_assoc()) {
        $ids[] = (int)$r['id'];
    }

    if (count($ids) < 2) {
        echo "Not enough users to connect.\n";
        exit(0);
    }

    // Insert mutual connection rows for each unique pair (i < j)
    $conn->begin_transaction();
    $ins = $conn->prepare("INSERT IGNORE INTO connections (user_id, connected_user_id) VALUES (?, ?)");
    foreach ($ids as $i => $uid) {
        for ($j = $i + 1; $j < count($ids); $j++) {
            $vid = $ids[$j];
            $ins->bind_param('ii', $uid, $vid);
            $ins->execute();
            $ins->bind_param('ii', $vid, $uid);
            $ins->execute();
        }
    }
    $ins->close();
    $conn->commit();

    echo "Connections table created and all users connected mutually.\n";
    $conn->close();
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    error_log("Migration error: " . $e->getMessage());
    exit(1);
}