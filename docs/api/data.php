<?php
require_once __DIR__ . '/config.php';
$user = requireAuth();
apiHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

switch ($action) {

    // Get all data (on login / full sync)
    case 'getAll':
        $stmt = $db->query("SELECT data_key, data_value FROM bt_data");
        $data = [];
        while ($row = $stmt->fetch()) {
            $data[$row['data_key']] = $row['data_value'];
        }
        jsonResponse(['data' => $data]);
        break;

    // Get single key
    case 'get':
        $key = $_GET['key'] ?? '';
        if (!$key) jsonError('Key required');
        $stmt = $db->prepare("SELECT data_value FROM bt_data WHERE data_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        jsonResponse(['value' => $row ? $row['data_value'] : null]);
        break;

    // Set single key-value
    case 'set':
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['key'] ?? '';
        $value = $input['value'] ?? null;
        if (!$key) jsonError('Key required');

        $stmt = $db->prepare("INSERT INTO bt_data (data_key, data_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)");
        $stmt->execute([$key, is_string($value) ? $value : json_encode($value)]);
        jsonResponse(['success' => true]);
        break;

    // Set multiple key-values at once (batch)
    case 'setBatch':
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        if (empty($items)) jsonError('Items required');

        $stmt = $db->prepare("INSERT INTO bt_data (data_key, data_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)");
        $db->beginTransaction();
        try {
            foreach ($items as $item) {
                $v = $item['value'] ?? null;
                $stmt->execute([$item['key'], is_string($v) ? $v : json_encode($v)]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonError('Batch write failed: ' . $e->getMessage(), 500);
        }
        jsonResponse(['success' => true, 'count' => count($items)]);
        break;

    // Delete key
    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['key'] ?? '';
        if (!$key) jsonError('Key required');
        $stmt = $db->prepare("DELETE FROM bt_data WHERE data_key = ?");
        $stmt->execute([$key]);
        jsonResponse(['success' => true]);
        break;

    // Get changes since timestamp (for sync)
    case 'sync':
        $since = $_GET['since'] ?? '1970-01-01 00:00:00';
        $stmt = $db->prepare("SELECT data_key, data_value, updated_at FROM bt_data WHERE updated_at > ?");
        $stmt->execute([$since]);
        $changes = $stmt->fetchAll();
        jsonResponse(['changes' => $changes, 'serverTime' => date('Y-m-d H:i:s')]);
        break;

    default:
        jsonError('Unknown action', 400);
}
