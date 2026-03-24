<?php
/**
 * TP-Tool Konfiguration
 */

// Zeitzone
date_default_timezone_set('Europe/Zurich');

// App-Einstellungen
define('APP_NAME', 'TP-Tool');
define('APP_SUBTITLE', 'Agentur Management');
define('APP_VERSION', '1.0.0');

// Pfade
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('DB_PATH', DATA_PATH . '/tp-tool.sqlite');

// Währung
define('CURRENCY', 'CHF');
define('MWST_RATE', 8.1); // Schweizer MwSt. 2024

// Session
define('SESSION_LIFETIME', 86400); // 24 Stunden
