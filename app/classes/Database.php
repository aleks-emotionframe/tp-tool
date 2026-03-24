<?php
/**
 * SQLite Datenbank-Klasse
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Sicherstellen, dass das data-Verzeichnis existiert
            if (!is_dir(DATA_PATH)) {
                mkdir(DATA_PATH, 0755, true);
            }

            self::$instance = new PDO('sqlite:' . DB_PATH, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // SQLite Optimierungen
            self::$instance->exec('PRAGMA journal_mode = WAL');
            self::$instance->exec('PRAGMA foreign_keys = ON');
        }

        return self::$instance;
    }

    /**
     * Datenbank-Schema erstellen/aktualisieren
     */
    public static function migrate(): void
    {
        $db = self::getInstance();

        // Benutzer
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            avatar TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Firmen
        $db->exec("CREATE TABLE IF NOT EXISTS companies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            address TEXT,
            zip TEXT,
            city TEXT,
            country TEXT DEFAULT 'CH',
            phone TEXT,
            email TEXT,
            website TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Kontakte/Kunden
        $db->exec("CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id INTEGER,
            first_name TEXT,
            last_name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            position TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
        )");

        // Rechnungen
        $db->exec("CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            number TEXT UNIQUE NOT NULL,
            contact_id INTEGER,
            company_id INTEGER,
            title TEXT NOT NULL,
            status TEXT DEFAULT 'draft',
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            subtotal REAL DEFAULT 0,
            mwst_rate REAL DEFAULT " . MWST_RATE . ",
            mwst_amount REAL DEFAULT 0,
            total REAL DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
        )");

        // Rechnungspositionen
        $db->exec("CREATE TABLE IF NOT EXISTS invoice_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER NOT NULL,
            position INTEGER DEFAULT 0,
            description TEXT NOT NULL,
            quantity REAL DEFAULT 1,
            unit TEXT DEFAULT 'Stk.',
            unit_price REAL DEFAULT 0,
            total REAL DEFAULT 0,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        )");

        // Offerten
        $db->exec("CREATE TABLE IF NOT EXISTS quotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            number TEXT UNIQUE NOT NULL,
            contact_id INTEGER,
            company_id INTEGER,
            title TEXT NOT NULL,
            status TEXT DEFAULT 'draft',
            issue_date DATE NOT NULL,
            valid_until DATE NOT NULL,
            subtotal REAL DEFAULT 0,
            mwst_rate REAL DEFAULT " . MWST_RATE . ",
            mwst_amount REAL DEFAULT 0,
            total REAL DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
        )");

        // Offertenpositionen
        $db->exec("CREATE TABLE IF NOT EXISTS quote_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quote_id INTEGER NOT NULL,
            position INTEGER DEFAULT 0,
            description TEXT NOT NULL,
            quantity REAL DEFAULT 1,
            unit TEXT DEFAULT 'Stk.',
            unit_price REAL DEFAULT 0,
            total REAL DEFAULT 0,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
        )");

        // Zahlungen
        $db->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            payment_date DATE NOT NULL,
            method TEXT DEFAULT 'bank',
            reference TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        )");

        // Aktivitätslog
        $db->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            type TEXT NOT NULL,
            message TEXT NOT NULL,
            reference_type TEXT,
            reference_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )");

        // Standard-Admin erstellen falls kein Benutzer existiert
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];

        if ($count === 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
                ->execute(['Aleks K.', 'admin@tp-tool.ch', $hash, 'admin']);
        }
    }
}
