<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail.php';
$user = requireAuth();
apiHeaders();

// Only admins can invite
if ($user['role'] !== 'admin') {
    jsonError('Keine Berechtigung', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

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
