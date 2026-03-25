<?php
require_once __DIR__ . '/config.php';
initSession();
apiHeaders();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'login':
        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if (!$username || !$password) {
            jsonError('Benutzername und Passwort erforderlich');
        }

        $db = getDB();

        // Get users from bt_data
        $stmt = $db->prepare("SELECT data_value FROM bt_data WHERE data_key = ?");
        $stmt->execute(['bt_users']);
        $row = $stmt->fetch();
        $users = $row ? json_decode($row['data_value'], true) : [];

        // Check default admin if no users exist
        if (empty($users)) {
            $users = [['username' => 'admin', 'password' => 'bauterm2024', 'firstName' => 'Admin', 'lastName' => '', 'displayName' => 'Administrator', 'role' => 'admin', 'firma' => '', 'phone' => '', 'email' => '', 'avatar' => null, 'locked' => false]];
            $stmt = $db->prepare("INSERT INTO bt_data (data_key, data_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)");
            $stmt->execute(['bt_users', json_encode($users)]);
        }

        $foundUser = null;
        foreach ($users as &$u) {
            if ($u['username'] === $username) {
                // Check password (support both plaintext legacy and bcrypt)
                if (isset($u['password_hash']) && password_verify($password, $u['password_hash'])) {
                    $foundUser = $u;
                    break;
                } elseif (isset($u['password']) && $u['password'] === $password) {
                    // Migrate plaintext to bcrypt
                    $u['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
                    unset($u['password']);
                    $stmt = $db->prepare("INSERT INTO bt_data (data_key, data_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)");
                    $stmt->execute(['bt_users', json_encode($users)]);
                    $foundUser = $u;
                    break;
                }
            }
        }
        unset($u);

        if (!$foundUser) {
            jsonError('Falsches Passwort oder E-Mail', 401);
        }

        if (!empty($foundUser['locked'])) {
            jsonError('Konto gesperrt', 403);
        }

        // Remove password from session data
        $sessionUser = $foundUser;
        unset($sessionUser['password'], $sessionUser['password_hash']);
        $_SESSION['user'] = $sessionUser;

        jsonResponse(['success' => true, 'user' => $sessionUser]);
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'check':
        if (!empty($_SESSION['user'])) {
            jsonResponse(['loggedIn' => true, 'user' => $_SESSION['user']]);
        } else {
            jsonResponse(['loggedIn' => false]);
        }
        break;

    default:
        jsonError('Unknown action', 400);
}
