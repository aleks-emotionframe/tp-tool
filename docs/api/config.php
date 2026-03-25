<?php
// BAUTERM Database Configuration
define('DB_HOST', 'bifitudo.mysql.db.internal');
define('DB_NAME', 'bifitudo_bauterm');
define('DB_USER', 'bifitudo_skoba');
define('DB_PASS', 'Novitr@vnik1');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
    }
    return $pdo;
}

// CORS & JSON headers
function apiHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    apiHeaders();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($msg, $code = 400) {
    jsonResponse(['error' => $msg], $code);
}

// Session setup
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function requireAuth() {
    initSession();
    if (empty($_SESSION['user'])) {
        jsonError('Nicht eingeloggt', 401);
    }
    return $_SESSION['user'];
}
