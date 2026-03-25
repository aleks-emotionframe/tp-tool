<?php
// Database initialization - run once to create tables
require_once __DIR__ . '/config.php';

try {
    $db = getDB();

    // Main key-value store (mirrors localStorage)
    $db->exec("CREATE TABLE IF NOT EXISTS bt_data (
        data_key VARCHAR(500) NOT NULL PRIMARY KEY,
        data_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Index on updated_at for sync queries
    try {
        $db->exec("CREATE INDEX idx_bt_data_updated ON bt_data (updated_at)");
    } catch (Exception $e) {
        // Index may already exist
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully',
        'tables' => ['bt_data']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
