<?php
/**
 * BAUTERM — Konfiguration
 * Passwort und Einstellungen hier anpassen.
 */

// Zugangspasswort (ändern!)
define('APP_PASSWORD', 'bauterm2024');

// Datenbankpfad (relativ zum Skript)
define('DB_PATH', __DIR__ . '/data/.bauterm.db');

// Session-Name
define('SESSION_NAME', 'BAUTERM_SESSION');

// Zeitzone
date_default_timezone_set('Europe/Zurich');
