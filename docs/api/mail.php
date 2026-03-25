<?php
// BAUTERM Email Sender via SMTP
define('SMTP_HOST', 'asmtp.mail.hostpoint.ch');
define('SMTP_PORT', 465);
define('SMTP_USER', 'testzugang@bauterm.ch');
define('SMTP_PASS', 'Novitr@vnik1');
define('SMTP_FROM', 'testzugang@bauterm.ch');
define('SMTP_FROM_NAME', 'BAUTERM');

function sendMail($to, $subject, $htmlBody) {
    $boundary = md5(time());
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Use PHP's built-in mail with SMTP on Hostpoint
    // Hostpoint supports mail() with proper headers
    $result = @mail($to, $subject, $htmlBody, $headers);

    if (!$result) {
        // Fallback: direct SMTP socket
        return sendMailSMTP($to, $subject, $htmlBody);
    }
    return true;
}

function sendMailSMTP($to, $subject, $htmlBody) {
    $ctx = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);

    $socket = stream_socket_client(
        'ssl://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno, $errstr, 30,
        STREAM_CLIENT_CONNECT, $ctx
    );

    if (!$socket) return false;

    $resp = fgets($socket, 512);

    // EHLO
    fwrite($socket, "EHLO bauterm.ch\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) == ' ') break;
    }

    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    fgets($socket, 512);
    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    fgets($socket, 512);
    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    $authResp = fgets($socket, 512);
    if (substr($authResp, 0, 3) != '235') {
        fclose($socket);
        return false;
    }

    // MAIL FROM
    fwrite($socket, "MAIL FROM:<" . SMTP_FROM . ">\r\n");
    fgets($socket, 512);

    // RCPT TO
    fwrite($socket, "RCPT TO:<" . $to . ">\r\n");
    fgets($socket, 512);

    // DATA
    fwrite($socket, "DATA\r\n");
    fgets($socket, 512);

    $msg = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: <" . $to . ">\r\n";
    $msg .= "Subject: " . $subject . "\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "\r\n";
    $msg .= $htmlBody . "\r\n.\r\n";

    fwrite($socket, $msg);
    $dataResp = fgets($socket, 512);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return substr($dataResp, 0, 3) == '250';
}

function buildInviteEmail($company, $code, $expiry, $registerUrl) {
    $expiryFormatted = date('d.m.Y', strtotime($expiry));
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#f5f5f7;">
<div style="max-width:520px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
  <div style="background:linear-gradient(135deg,#e8600a,#c2410c);padding:32px 40px;text-align:center;">
    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;margin:0 auto 16px;position:relative;">
      <div style="position:absolute;top:10px;left:9px;width:0;height:0;border-left:15px solid transparent;border-right:15px solid transparent;border-bottom:14px solid #fff;"></div>
      <div style="position:absolute;top:22px;left:12px;width:24px;height:16px;background:#fff;border-radius:0 0 2px 2px;"></div>
      <div style="position:absolute;top:26px;left:18px;width:12px;height:12px;background:#e8600a;border-radius:2px;"></div>
    </div>
    <h1 style="color:#fff;font-size:26px;font-weight:800;margin:0;letter-spacing:-0.5px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">BAUTERM</h1>
    <p style="color:rgba(255,255,255,0.8);font-size:13px;margin:6px 0 0;letter-spacing:0.3px;">Bauprojekte. Einfach organisiert.</p>
  </div>
  <div style="padding:32px 40px;">
    <h2 style="font-size:20px;font-weight:700;color:#1a1a2e;margin:0 0 12px;">Du wurdest eingeladen!</h2>
    <p style="font-size:15px;color:#555;line-height:1.6;margin:0 0 20px;">' . htmlspecialchars($company) . ' hat dir einen Zugang zu BAUTERM freigeschaltet. Erstelle jetzt dein Konto und starte sofort.</p>
    <div style="background:#f8f8fa;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
      <div style="font-size:11px;color:#888;text-transform:uppercase;font-weight:600;letter-spacing:0.5px;margin-bottom:4px;">Dein Lizenzcode</div>
      <div style="font-size:22px;font-weight:800;letter-spacing:2px;color:#e8600a;font-family:monospace;">' . htmlspecialchars($code) . '</div>
      <div style="font-size:12px;color:#888;margin-top:6px;">G&uuml;ltig bis ' . $expiryFormatted . '</div>
    </div>
    <a href="' . htmlspecialchars($registerUrl) . '" style="display:block;text-align:center;padding:14px;background:linear-gradient(135deg,#e8600a,#c2410c);color:#fff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">Jetzt registrieren</a>
    <p style="font-size:12px;color:#999;text-align:center;margin:20px 0 0;">Falls der Button nicht funktioniert, kopiere diesen Link:<br><a href="' . htmlspecialchars($registerUrl) . '" style="color:#e8600a;word-break:break-all;">' . htmlspecialchars($registerUrl) . '</a></p>
  </div>
  <div style="padding:16px 40px;background:#f8f8fa;text-align:center;font-size:11px;color:#999;">
    &copy; ' . date('Y') . ' BAUTERM &middot; bauterm.ch
  </div>
</div>
</body></html>';
}
