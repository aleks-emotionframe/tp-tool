================================================================
  BAUTERM — Terminprogramm fuer Bauleiter
  Deployment-Anleitung
================================================================

1. INSTALLATION
   - ZIP entpacken
   - In config.php das Passwort aendern:
     define('APP_PASSWORD', 'IhrNeuesPasswort');
   - Gesamten /bauterm/ Ordner via FTP auf den Server laden

2. AUFRUFEN
   Im Browser oeffnen: https://meinedomain.ch/bauterm/
   Mit dem konfigurierten Passwort einloggen.

3. BENUTZUNG
   - "Neu" klicken um ein neues Projekt zu erstellen
   - XML-Datei aus MS Project importieren
     (In MS Project: Datei > Speichern unter > XML)
   - Termine in der Listen- oder Gantt-Ansicht bearbeiten
   - "Speichern" klicken um Aenderungen zu sichern
   - XML/JSON Export ueber die Buttons in der Kopfzeile

4. ANFORDERUNGEN
   - PHP 7.4 oder hoeher
   - PHP PDO SQLite Extension (Standard bei Hostpoint.ch)
   - Apache mit mod_headers (fuer .htaccess)

5. TROUBLESHOOTING
   - "Permission denied": data/ Ordner auf chmod 755 setzen
   - SQLite nicht verfuegbar: PHP PDO SQLite muss aktiviert
     sein (ist Standard bei Hostpoint.ch)
   - Weisse Seite: PHP error_log pruefen
   - Login funktioniert nicht: Passwort in config.php pruefen

6. DATEISTRUKTUR
   bauterm/
   ├── index.php       Haupt-App (Login + SPA)
   ├── api.php         REST API
   ├── config.php      Einstellungen
   ├── .htaccess       Security Headers
   ├── data/           SQLite Datenbank (auto-erstellt)
   │   └── .htaccess   Zugriffschutz
   └── README.txt      Diese Datei

================================================================
