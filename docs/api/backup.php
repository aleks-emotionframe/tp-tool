<?php
// BAUTERM Automatic Backup
// Runs daily via cron job at 03:00
// Creates JSON backup of all data in /backups/ directory

require_once __DIR__ . '/config.php';

// Security: Only allow from CLI (cron) or with secret key
$isCliOrCron = (php_sapi_name() === 'cli' || !isset($_SERVER['REMOTE_ADDR']));
$hasSecret = isset($_GET['key']) && $_GET['key'] === 'bt-backup-2026-secure';
if (!$isCliOrCron && !$hasSecret) {
    http_response_code(403);
    die('Access denied');
}

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
    if (!is_dir($backupDir)) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Cannot create backup directory: ' . $backupDir]));
    }
}

// Protect backups directory
$htaccess = $backupDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

try {
    $db = getDB();
    $stmt = $db->query("SELECT data_key, data_value FROM bt_data");
    $data = [];
    while ($row = $stmt->fetch()) {
        $data[$row['data_key']] = $row['data_value'];
    }

    $backup = [
        '_bauterm_backup' => true,
        'version' => 2,
        'type' => 'auto',
        'environment' => IS_DEV ? 'dev' : 'live',
        'database' => DB_NAME,
        'timestamp' => date('c'),
        'data' => $data
    ];

    $filename = 'backup-' . (IS_DEV ? 'dev-' : '') . date('Y-m-d_His') . '.json';
    $filepath = $backupDir . '/' . $filename;
    file_put_contents($filepath, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Keep only last 30 backups (delete oldest)
    $files = glob($backupDir . '/backup-*.json');
    if (count($files) > 30) {
        sort($files);
        $toDelete = array_slice($files, 0, count($files) - 30);
        foreach ($toDelete as $f) {
            unlink($f);
        }
    }

    $result = [
        'success' => true,
        'file' => $filename,
        'size' => filesize($filepath),
        'keys' => count($data),
        'kept' => min(count($files) + 1, 30),
        'timestamp' => date('c')
    ];

    if ($isCliOrCron) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }

} catch (Exception $e) {
    $error = ['error' => 'Backup failed: ' . $e->getMessage()];
    if ($isCliOrCron) {
        echo json_encode($error) . "\n";
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode($error);
    }
}
