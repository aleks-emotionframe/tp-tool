<?php
require_once __DIR__ . '/config.php';
initSession();
apiHeaders();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'register') {
    jsonError('Unknown action');
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$code = strtoupper(trim($input['code'] ?? ''));

// Validation
if (!$name) jsonError('Bitte Namen eingeben.');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Bitte gültige E-Mail eingeben.');
if (!$password || strlen($password) < 4) jsonError('Passwort muss mindestens 4 Zeichen haben.');
if (!$code) jsonError('Bitte Lizenzcode eingeben.');

$db = getDB();

// Get existing users
$stmt = $db->prepare("SELECT data_value FROM bt_data WHERE data_key = ?");
$stmt->execute(['bt_users']);
$row = $stmt->fetch();
$users = $row ? json_decode($row['data_value'], true) : [];

// Check if user already exists
$emailLower = strtolower($email);
foreach ($users as $u) {
    if (strtolower($u['username']) === $emailLower) {
        jsonError('Ein Konto mit dieser E-Mail existiert bereits.');
    }
}

// Get licenses and validate code
$stmt = $db->prepare("SELECT data_value FROM bt_data WHERE data_key = ?");
$stmt->execute(['bt_licenses']);
$row = $stmt->fetch();
$licenses = $row ? json_decode($row['data_value'], true) : [];

$foundLic = null;
$licIndex = -1;
$now = new DateTime();
foreach ($licenses as $i => $lic) {
    if (strtoupper($lic['code']) !== $code) continue;
    if (empty($lic['active'])) jsonError('Dieser Lizenzcode ist deaktiviert.');
    $expiry = new DateTime($lic['expiry'] . 'T23:59:59');
    if ($now > $expiry) jsonError('Dieser Lizenzcode ist am ' . date('d.m.Y', strtotime($lic['expiry'])) . ' abgelaufen.');
    $usedSeats = count($lic['users'] ?? []);
    if ($usedSeats >= $lic['seats']) jsonError('Alle ' . $lic['seats'] . ' Plätze dieses Codes sind bereits belegt.');
    $foundLic = $lic;
    $licIndex = $i;
    break;
}

if (!$foundLic) jsonError('Ungültiger Lizenzcode.');

// Create user
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';
$newUser = [
    'username' => $emailLower,
    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'displayName' => $name,
    'role' => 'user',
    'firma' => '',
    'phone' => '',
    'email' => $email,
    'avatar' => null,
    'locked' => false
];
$users[] = $newUser;

// Add user to license
if (!isset($licenses[$licIndex]['users'])) $licenses[$licIndex]['users'] = [];
if (!in_array($emailLower, $licenses[$licIndex]['users'])) {
    $licenses[$licIndex]['users'][] = $emailLower;
}

// Save both to DB
$stmtSave = $db->prepare("INSERT INTO bt_data (data_key, data_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)");
$stmtSave->execute(['bt_users', json_encode($users)]);
$stmtSave->execute(['bt_licenses', json_encode($licenses)]);

// Create session for auto-login
$sessionUser = $newUser;
unset($sessionUser['password_hash']);
$_SESSION['user'] = $sessionUser;

jsonResponse([
    'success' => true,
    'user' => $sessionUser
]);
