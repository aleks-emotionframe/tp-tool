<?php
/**
 * TP-Tool - Hauptrouter
 * Reines PHP Agentur-Management-Tool
 */

// Konfiguration laden
require_once __DIR__ . '/config/app.php';

// Klassen laden
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Helper.php';

// Datenbank initialisieren (Migration)
Database::migrate();

// Session starten
Auth::init();

// Routing
$page = $_GET['page'] ?? 'dashboard';

// Logout
if ($page === 'logout') {
    Auth::logout();
    header('Location: index.php?page=login');
    exit;
}

// Login-Seite (ohne Auth)
if ($page === 'login') {
    require __DIR__ . '/pages/login.php';
    exit;
}

// Alle anderen Seiten brauchen Login
Auth::requireLogin();

// Erlaubte Seiten
$allowedPages = [
    'dashboard',
    'invoices',
    'quotes',
    'contacts',
    'companies',
    'settings',
];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';

if (file_exists($pageFile)) {
    require $pageFile;
} else {
    // Platzhalter für noch nicht existierende Seiten
    $pageTitle = ucfirst($page);
    include __DIR__ . '/templates/header.php';
    include __DIR__ . '/templates/sidebar.php';
    echo '<main class="main-content">';
    include __DIR__ . '/templates/topbar.php';
    echo '<div class="content"><div class="card"><div class="card-body"><div class="empty-state">';
    echo '<h3>' . Helper::e($pageTitle) . '</h3>';
    echo '<p>Diese Seite wird bald verfügbar sein.</p>';
    echo '</div></div></div></div></main>';
    include __DIR__ . '/templates/footer.php';
}
