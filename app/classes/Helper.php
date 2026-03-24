<?php
/**
 * Hilfsfunktionen
 */
class Helper
{
    /**
     * Zahl als CHF formatieren
     */
    public static function money(float $amount): string
    {
        return CURRENCY . " " . number_format($amount, 0, '.', "'");
    }

    /**
     * Datum formatieren (Schweizer Format)
     */
    public static function date(string $date): string
    {
        return date('d.m.Y', strtotime($date));
    }

    /**
     * HTML-Escaping
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Nächste Rechnungsnummer generieren
     */
    public static function nextInvoiceNumber(): string
    {
        $db = Database::getInstance();
        $year = date('Y');
        $stmt = $db->prepare("SELECT number FROM invoices WHERE number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute(["RE-{$year}-%"]);
        $last = $stmt->fetch();

        if ($last) {
            $num = (int) substr($last['number'], -3);
            return sprintf("RE-%s-%03d", $year, $num + 1);
        }

        return "RE-{$year}-001";
    }

    /**
     * Nächste Offertennummer generieren
     */
    public static function nextQuoteNumber(): string
    {
        $db = Database::getInstance();
        $year = date('Y');
        $stmt = $db->prepare("SELECT number FROM quotes WHERE number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute(["OF-{$year}-%"]);
        $last = $stmt->fetch();

        if ($last) {
            $num = (int) substr($last['number'], -3);
            return sprintf("OF-%s-%03d", $year, $num + 1);
        }

        return "OF-{$year}-001";
    }

    /**
     * Status-Badge HTML
     */
    public static function statusBadge(string $status): string
    {
        $labels = [
            'draft' => ['Entwurf', 'secondary'],
            'sent' => ['Gesendet', 'primary'],
            'open' => ['Offen', 'info'],
            'paid' => ['Bezahlt', 'success'],
            'overdue' => ['Überfällig', 'danger'],
            'cancelled' => ['Storniert', 'secondary'],
            'accepted' => ['Angenommen', 'success'],
            'declined' => ['Abgelehnt', 'danger'],
        ];

        $label = $labels[$status] ?? [$status, 'secondary'];
        return '<span class="badge badge-' . $label[1] . '">' . self::e($label[0]) . '</span>';
    }

    /**
     * Aktivität loggen
     */
    public static function log(string $type, string $message, ?string $refType = null, ?int $refId = null): void
    {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, type, message, reference_type, reference_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $message, $refType, $refId]);
    }
}
