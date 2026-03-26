<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail.php';
initSession();
apiHeaders();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Public action: request access (no auth needed)
if ($action === 'request') {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $firma = trim($input['firma'] ?? '');
    $message = trim($input['message'] ?? '');
    if (!$name || !$email) jsonError('Name und E-Mail erforderlich');

    $subject = 'Testzugang-Anfrage: ' . $name;
    $body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;font-size:14px;color:#222;">'
        . '<h2 style="color:#e8600a;">Neue Testzugang-Anfrage</h2>'
        . '<table style="border-collapse:collapse;">'
        . '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;">Name:</td><td>' . htmlspecialchars($name) . '</td></tr>'
        . '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;">E-Mail:</td><td><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></td></tr>'
        . ($firma ? '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;">Firma:</td><td>' . htmlspecialchars($firma) . '</td></tr>' : '')
        . ($message ? '<tr><td style="padding:6px 12px 6px 0;font-weight:bold;">Nachricht:</td><td>' . nl2br(htmlspecialchars($message)) . '</td></tr>' : '')
        . '</table>'
        . '<p style="margin-top:20px;font-size:12px;color:#888;">Gesendet via bauterm.ch</p>'
        . '</body></html>';

    // Send to admin email
    sendMail('testzugang@bauterm.ch', $subject, $body);
    jsonResponse(['success' => true]);
}

// All other actions require admin auth
$user = requireAuth();
if ($user['role'] !== 'admin') {
    jsonError('Keine Berechtigung', 403);
}

switch ($action) {

    case 'send':
        $emails = $input['emails'] ?? [];
        $licenseCode = $input['code'] ?? '';
        $company = $input['company'] ?? '';
        $expiry = $input['expiry'] ?? '';

        if (empty($emails) || empty($licenseCode)) {
            jsonError('E-Mails und Lizenzcode erforderlich');
        }

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'];
        $sent = [];
        $failed = [];

        foreach ($emails as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed[] = ['email' => $email, 'reason' => 'Ungültige E-Mail'];
                continue;
            }

            $registerUrl = $baseUrl . '/?register=' . urlencode($licenseCode) . '&email=' . urlencode($email);
            $subject = 'Deine Einladung zu BAUTERM';
            $body = buildInviteEmail($company, $licenseCode, $expiry, $registerUrl);

            if (sendMail($email, $subject, $body)) {
                $sent[] = $email;
            } else {
                $failed[] = ['email' => $email, 'reason' => 'Senden fehlgeschlagen'];
            }
        }

        jsonResponse([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($sent)
        ]);
        break;

    default:
        jsonError('Unknown action');
}
