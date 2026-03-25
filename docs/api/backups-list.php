<?php
require_once __DIR__ . '/config.php';
$user = requireAuth();
if ($user['role'] !== 'admin') jsonError('Keine Berechtigung', 403);
apiHeaders();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'list':
        $backupDir = __DIR__ . '/../backups';
        $files = [];
        if (is_dir($backupDir)) {
            $all = glob($backupDir . '/backup-*.json');
            rsort($all); // newest first
            foreach ($all as $f) {
                $name = basename($f);
                $files[] = [
                    'name' => $name,
                    'size' => filesize($f),
                    'date' => filemtime($f),
                    'dateFormatted' => date('d.m.Y H:i', filemtime($f))
                ];
            }
        }
        jsonResponse(['backups' => $files, 'total' => count($files)]);
        break;

    case 'download':
        $name = basename($_GET['file'] ?? '');
        if (!$name || !preg_match('/^backup-.*\.json$/', $name)) {
            jsonError('Ungültiger Dateiname');
        }
        $path = __DIR__ . '/../backups/' . $name;
        if (!file_exists($path)) {
            jsonError('Backup nicht gefunden', 404);
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    case 'trigger':
        // Manually trigger a backup
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . '/api/backup.php?key=bt-backup-2026-secure';
        $ctx = stream_context_create(['http' => ['timeout' => 30]]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result) {
            jsonResponse(json_decode($result, true));
        } else {
            // Try direct include
            ob_start();
            include __DIR__ . '/backup.php';
            $out = ob_get_clean();
            jsonResponse(json_decode($out, true) ?: ['success' => true, 'message' => 'Backup erstellt']);
        }
        break;

    default:
        jsonError('Unknown action');
}
