<?php
// Demo request handler for gloriatech.co/demo/.
//
// Receives the demo form, emails the sales inbox with the prospect as Reply-To,
// then redirects to the confirmation page. Runs on the site's own host; no
// third-party form service and no ads in the email.
//
// Delivery note: gloriatech.co receives mail at Google Workspace. cPanel had
// the domain set to "Local Mail Exchanger", so anything the server sent to an
// @gloriatech.co address was delivered into this server and dropped. It is now
// "Remote Mail Exchanger" and mail arrives. If deliverability ever needs to be
// stronger, drop a demo/config.php next to this file with SMTP credentials for
// a sender the domain already authorizes (Google Workspace or SendGrid) and it
// is used automatically. That file holds a password, so it lives only on the
// server and is git-ignored.
//
// Recipients are a fixed allowlist. `?to=richard` sends a copy to Richard for
// previewing the layout; anything else goes to Dave.

declare(strict_types=1);

$RECIPIENTS = [
    'dave'    => 'dave@gloriatech.co',
    'richard' => 'richard@gloriatech.co',
];
$FROM_ADDR = 'noreply@gloriatech.co';
$FROM_NAME = 'Gloria Website';
$SITE      = 'https://gloriatech.co';
$CONFIRM   = $SITE . '/demo/confirmation/';
$FORM_PAGE = $SITE . '/demo/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $FORM_PAGE, true, 303);
    exit;
}

// Honeypot: bots fill the hidden field, people never see it.
if (!empty($_POST['_honey'])) {
    header('Location: ' . $CONFIRM, true, 303);
    exit;
}

$field = static function (string $key, int $max = 500): string {
    // PHP rewrites spaces in POST field names to underscores, so accept both.
    $alt = str_replace(' ', '_', $key);
    $raw = $_POST[$key] ?? $_POST[$alt] ?? '';
    $v   = is_string($raw) ? $raw : '';
    $v   = trim((string) preg_replace('/[\r\n\t]+/', ' ', $v));
    return mb_substr($v, 0, $max);
};

$name      = $field('Name', 120);
$email     = $field('Email', 200);
$phone     = $field('Phone', 40);
$community = $field('Community type', 80);
$heard     = $field('How they heard about Gloria', 120);
$help      = $field('How we can help', 2000);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $FORM_PAGE . '?error=1', true, 303);
    exit;
}

$toKey = (isset($_GET['to']) && isset($RECIPIENTS[$_GET['to']])) ? $_GET['to'] : 'dave';
$to    = $RECIPIENTS[$toKey];

$submitted = gmdate('D, M j, Y \a\t g:i A') . ' (UTC)';
$e = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$missing = '<span style="color:#A3A8AF;">Not provided</span>';
$rows = [
    ['Name',                        $e($name)],
    ['Email',                       '<a href="mailto:' . $e($email) . '" style="color:#E4520B;">' . $e($email) . '</a>'],
    ['Phone',                       $phone !== '' ? $e($phone) : $missing],
    ['Community type',              $community !== '' ? $e($community) : $missing],
    ['How they heard about Gloria', $heard !== '' ? $e($heard) : $missing],
    ['How we can help',             $help !== '' ? nl2br($e($help)) : $missing],
];

$rowsHtml = '';
$last = count($rows) - 1;
foreach ($rows as $i => $row) {
    $border = ($i === $last) ? '' : 'border-bottom:1px solid #EDEFF2;';
    $rowsHtml .= '<tr>'
        . '<td style="padding:14px 0;' . $border . 'font-family:Helvetica,Arial,sans-serif;font-size:14px;color:#6B7280;width:44%;vertical-align:top;">' . $e($row[0]) . '</td>'
        . '<td style="padding:14px 0;' . $border . 'font-family:Helvetica,Arial,sans-serif;font-size:15px;color:#1A2B47;vertical-align:top;line-height:1.5;">' . $row[1] . '</td>'
        . '</tr>';
}

$html = '<!DOCTYPE html>'
    . '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>New demo request</title></head>'
    . '<body style="margin:0;padding:0;background:#FFFFFF;">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;">'
    . '<tr><td align="center" style="padding:32px 16px;">'
    . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">'

    . '<tr><td style="padding:0 0 26px 0;">'
    . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
    . '<td style="vertical-align:middle;padding-right:9px;"><img src="' . $SITE . '/images/logo.png" width="30" height="30" alt="Gloria" style="display:block;border:0;"></td>'
    . '<td style="vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:25px;font-weight:700;color:#F4600B;letter-spacing:-0.4px;">Gloria</td>'
    . '</tr></table></td></tr>'

    . '<tr><td style="font-family:Helvetica,Arial,sans-serif;font-size:24px;font-weight:700;color:#1A2B47;line-height:1.25;padding-bottom:6px;">New demo request</td></tr>'
    . '<tr><td style="font-family:Helvetica,Arial,sans-serif;font-size:15px;color:#5A6068;line-height:1.55;padding-bottom:22px;">Someone just submitted the demo form on <a href="' . $FORM_PAGE . '" style="color:#E4520B;">gloriatech.co/demo</a>.</td></tr>'

    . '<tr><td style="border-top:2px solid #F4600B;font-size:0;line-height:0;">&nbsp;</td></tr>'
    . '<tr><td><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rowsHtml . '</table></td></tr>'

    . '<tr><td style="padding-top:20px;font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#6B7280;line-height:1.6;">'
    . 'Submitted ' . $e($submitted) . '<br>Reply to this email to answer ' . $e($name) . ' directly.'
    . '</td></tr>'

    . '<tr><td style="padding-top:28px;border-top:1px solid #EDEFF2;font-size:0;line-height:0;">&nbsp;</td></tr>'
    . '<tr><td style="padding-top:14px;">'
    . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
    . '<td style="vertical-align:middle;padding-right:7px;"><img src="' . $SITE . '/images/logo.png" width="18" height="18" alt="" style="display:block;border:0;"></td>'
    . '<td style="vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#9AA0A6;"><a href="' . $SITE . '" style="color:#9AA0A6;text-decoration:none;">gloriatech.co</a></td>'
    . '</tr></table></td></tr>'

    . '</table></td></tr></table></body></html>';

$text = "New demo request from gloriatech.co\n\n"
    . 'Name: ' . $name . "\n"
    . 'Email: ' . $email . "\n"
    . 'Phone: ' . ($phone !== '' ? $phone : 'Not provided') . "\n"
    . 'Community type: ' . ($community !== '' ? $community : 'Not provided') . "\n"
    . 'How they heard about Gloria: ' . ($heard !== '' ? $heard : 'Not provided') . "\n"
    . 'How we can help: ' . ($help !== '' ? $help : 'Not provided') . "\n\n"
    . 'Submitted ' . $submitted . "\n";

$subject = 'New demo request from gloriatech.co';
$config  = __DIR__ . '/config.php';
$smtp    = is_readable($config) ? require $config : null;
$sent    = false;

if (is_array($smtp) && !empty($smtp['host']) && !empty($smtp['username'])) {
    require_once __DIR__ . '/lib/Exception.php';
    require_once __DIR__ . '/lib/PHPMailer.php';
    require_once __DIR__ . '/lib/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $port = (int) ($smtp['port'] ?? 587);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->Port       = $port;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'] ?? '';
        $mail->SMTPSecure = ($port === 465)
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;
        $mail->setFrom($smtp['from'] ?? $FROM_ADDR, $smtp['from_name'] ?? $FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo($email, $name);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
        $sent = true;
    } catch (Throwable $err) {
        error_log('[demo form] SMTP send failed: ' . $err->getMessage());
    }
}

if (!$sent) {
    $boundary = 'gloria-' . bin2hex(random_bytes(12));
    $headers  = 'From: ' . $FROM_NAME . ' <' . $FROM_ADDR . '>' . "\r\n"
        . 'Reply-To: ' . str_replace(["\r", "\n"], '', $name) . ' <' . $email . '>' . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n"
        . 'X-Mailer: gloriatech.co demo form' . "\r\n";

    $body = '--' . $boundary . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n"
        . $text . "\r\n"
        . '--' . $boundary . "\r\n"
        . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n"
        . $html . "\r\n"
        . '--' . $boundary . '--' . "\r\n";

    $sent = @mail($to, $subject, $body, $headers, '-f ' . $FROM_ADDR);
}

header('Location: ' . $CONFIRM . ($sent ? '' : '?sent=0'), true, 303);
exit;
