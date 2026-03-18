<?php
/**
 * BAUTERM — REST API
 * Alle AJAX-Calls werden hier verarbeitet.
 */

require_once __DIR__ . '/config.php';

// Session starten
session_name(SESSION_NAME);
session_start();

// JSON Response Helper
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse($message, $code = 400) {
    jsonResponse(['error' => $message], $code);
}

function requireAuth() {
    if (empty($_SESSION['authenticated'])) {
        errorResponse('Nicht eingeloggt', 401);
    }
}

// Datenbank initialisieren
function getDB() {
    static $db = null;
    if ($db !== null) return $db;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT DEFAULT '',
        xml_data TEXT,
        tasks_json TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $db;
}

// XML parsen (MS Project)
function parseProjectXML($xmlString) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlString);
    if ($xml === false) {
        return null;
    }

    // Namespace handling für MS Project XML
    $namespaces = $xml->getNamespaces(true);
    $ns = '';
    if (!empty($namespaces)) {
        $ns = array_values($namespaces)[0];
        $xml->registerXPathNamespace('ms', $ns);
    }

    $tasks = [];

    // Tasks finden (mit oder ohne Namespace)
    $taskNodes = $ns ? $xml->xpath('//ms:Task') : $xml->xpath('//Task');
    if (empty($taskNodes)) {
        $taskNodes = isset($xml->Tasks->Task) ? $xml->Tasks->Task : [];
    }

    foreach ($taskNodes as $task) {
        $uid = (int)($task->UID ?? 0);
        $id = (int)($task->ID ?? 0);
        $name = (string)($task->Name ?? '');
        $start = (string)($task->Start ?? '');
        $finish = (string)($task->Finish ?? '');
        $duration = (string)($task->Duration ?? '');
        $critical = (string)($task->Critical ?? '0');
        $summary = (string)($task->Summary ?? '0');
        $outlineLevel = (int)($task->OutlineLevel ?? 0);
        $wbs = (string)($task->WBS ?? '');
        $percentComplete = (int)($task->PercentComplete ?? 0);

        // Duration in Arbeitstage konvertieren (PT8H0M0S = 1 Tag)
        $durationDays = parseDuration($duration);

        // Vorgänger
        $predecessors = [];
        if (isset($task->PredecessorLink)) {
            foreach ($task->PredecessorLink as $pred) {
                $predecessors[] = [
                    'uid' => (int)($pred->PredecessorUID ?? 0),
                    'type' => (int)($pred->Type ?? 1),
                    'lagDays' => parseDuration((string)($pred->LinkLag ?? '0'), true)
                ];
            }
        }

        // Start/Ende als ISO-Datum
        $startDate = $start ? substr($start, 0, 10) : '';
        $finishDate = $finish ? substr($finish, 0, 10) : '';

        $tasks[] = [
            'uid' => $uid,
            'id' => $id,
            'name' => $name,
            'start' => $startDate,
            'finish' => $finishDate,
            'duration' => $durationDays,
            'durationRaw' => $duration,
            'critical' => ($critical === '1' || $critical === 'true'),
            'summary' => ($summary === '1' || $summary === 'true'),
            'outlineLevel' => $outlineLevel,
            'wbs' => $wbs,
            'percentComplete' => $percentComplete,
            'predecessors' => $predecessors
        ];
    }

    return $tasks;
}

function parseDuration($dur, $isLag = false) {
    if (empty($dur) || $dur === '0') return 0;

    // MS Project LinkLag ist in Zehntel-Minuten (tenths of minutes)
    if ($isLag && is_numeric($dur)) {
        $minutes = (int)$dur / 10;
        return round($minutes / 480); // 480 min = 1 Arbeitstag (8h)
    }

    // ISO 8601 Duration: PT8H0M0S, P5D, PT40H, etc.
    if (preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $dur, $m)) {
        $days = isset($m[1]) && $m[1] !== '' ? (int)$m[1] : 0;
        $hours = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 0;
        $minutes = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 0;
        return $days + round(($hours + $minutes / 60) / 8, 1);
    }

    return 0;
}

// ============================================================
// ROUTING
// ============================================================

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    // --- LOGIN ---
    case 'login':
        $input = json_decode(file_get_contents('php://input'), true);
        $password = isset($input['password']) ? $input['password'] : '';
        if ($password === APP_PASSWORD) {
            $_SESSION['authenticated'] = true;
            jsonResponse(['success' => true]);
        } else {
            errorResponse('Falsches Passwort', 401);
        }
        break;

    // --- LOGOUT ---
    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    // --- CHECK AUTH ---
    case 'check_auth':
        jsonResponse(['authenticated' => !empty($_SESSION['authenticated'])]);
        break;

    // --- PROJEKTE AUFLISTEN ---
    case 'projects':
        requireAuth();
        $db = getDB();
        $stmt = $db->query("SELECT id, name, description, created_at, updated_at FROM projects ORDER BY updated_at DESC");
        jsonResponse($stmt->fetchAll());
        break;

    // --- NEUES PROJEKT (XML Upload) ---
    case 'project_new':
        requireAuth();
        $db = getDB();

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if (empty($name)) {
            errorResponse('Projektname fehlt');
        }

        $xmlData = '';
        $tasksJson = '[]';

        if (isset($_FILES['xml_file']) && $_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
            $xmlData = file_get_contents($_FILES['xml_file']['tmp_name']);
            $tasks = parseProjectXML($xmlData);
            if ($tasks === null) {
                errorResponse('XML konnte nicht geparst werden');
            }
            $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $db->prepare("INSERT INTO projects (name, xml_data, tasks_json) VALUES (?, ?, ?)");
        $stmt->execute([$name, $xmlData, $tasksJson]);
        $id = $db->lastInsertId();

        jsonResponse(['success' => true, 'id' => (int)$id]);
        break;

    // --- PROJEKT LADEN ---
    case 'project_load':
        requireAuth();
        $db = getDB();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $db->prepare("SELECT id, name, description, tasks_json, xml_data, created_at, updated_at FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) {
            errorResponse('Projekt nicht gefunden', 404);
        }
        jsonResponse($project);
        break;

    // --- PROJEKT SPEICHERN ---
    case 'project_save':
        requireAuth();
        $db = getDB();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $tasksJson = isset($input['tasks_json']) ? $input['tasks_json'] : '';

        if (!$id) errorResponse('Projekt-ID fehlt');

        $stmt = $db->prepare("UPDATE projects SET tasks_json = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$tasksJson, $id]);

        jsonResponse(['success' => true]);
        break;

    // --- PROJEKT UMBENENNEN ---
    case 'project_rename':
        requireAuth();
        $db = getDB();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $name = isset($input['name']) ? trim($input['name']) : '';

        if (!$id || !$name) errorResponse('ID und Name erforderlich');

        $stmt = $db->prepare("UPDATE projects SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$name, $id]);

        jsonResponse(['success' => true]);
        break;

    // --- PROJEKT LOESCHEN ---
    case 'project_delete':
        requireAuth();
        $db = getDB();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;

        if (!$id) errorResponse('Projekt-ID fehlt');

        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(['success' => true]);
        break;

    // --- EXPORT XML ---
    case 'export_xml':
        requireAuth();
        $db = getDB();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $db->prepare("SELECT name, xml_data FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        if (!$project || empty($project['xml_data'])) {
            errorResponse('Keine XML-Daten vorhanden', 404);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['name']) . '.xml';
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $project['xml_data'];
        exit;

    // --- EXPORT JSON ---
    case 'export_json':
        requireAuth();
        $db = getDB();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $db->prepare("SELECT name, tasks_json FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        if (!$project) {
            errorResponse('Projekt nicht gefunden', 404);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['name']) . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $project['tasks_json'];
        exit;

    default:
        errorResponse('Unbekannte Aktion: ' . htmlspecialchars($action));
}
