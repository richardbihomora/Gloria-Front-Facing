<?php
// Demo request handler for gloriatech.co/demo/.
//
// Receives the demo form, sends a branded HTML email (plain-text alternative
// included) to the sales inbox with the prospect as Reply-To, then redirects
// to the confirmation page. Runs on the site's PHP host; no third-party form
// service, no ads in the email.
//
// Recipients are a fixed allowlist. `?to=richard` sends a copy to Richard for
// previewing the layout; anything else goes to Dave.

declare(strict_types=1);

$RECIPIENTS = [
    'dave'    => 'dave@gloriatech.co',
    'richard' => 'richard@gloriatech.co',
    // Temporary delivery probe; remove once mail flow is verified.
    'probe'   => 'mbihomora@gmail.com',
];
$FROM       = 'Gloria Website <noreply@gloriatech.co>';
$SITE       = 'https://gloriatech.co';
$CONFIRM    = $SITE . '/demo/confirmation/';
$FORM_PAGE  = $SITE . '/demo/';

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
    $v = isset($_POST[$key]) ? (string) $_POST[$key] : '';
    $v = trim(preg_replace('/[\r\n\t]+/', ' ', $v) ?? '');
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

$toKey = isset($_GET['to']) && isset($RECIPIENTS[$_GET['to']]) ? $_GET['to'] : 'dave';
$to    = $RECIPIENTS[$toKey];

$submitted = gmdate('D, M j, Y \a\t g:i A') . ' (UTC)';
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$rows = [
    ['Name',                        $e($name)],
    ['Email',                       '<a href="mailto:' . $e($email) . '" style="color:#E4520B;text-decoration:underline;">' . $e($email) . '</a>'],
    ['Phone',                       $phone !== '' ? $e($phone) : '<span style="color:#9AA0A6;">Not provided</span>'],
    ['Community type',              $community !== '' ? $e($community) : '<span style="color:#9AA0A6;">Not provided</span>'],
    ['How they heard about Gloria', $heard !== '' ? $e($heard) : '<span style="color:#9AA0A6;">Not provided</span>'],
    ['How we can help',             $help !== '' ? nl2br($e($help)) : '<span style="color:#9AA0A6;">Not provided</span>'],
];

$rowsHtml = '';
$last = count($rows) - 1;
foreach ($rows as $i => [$label, $value]) {
    $border = $i === $last ? '' : 'border-bottom:1px solid #F1E9E2;';
    $rowsHtml .= '
      <tr>
        <td style="padding:16px 18px;' . $border . 'font-family:Helvetica,Arial,sans-serif;font-size:15px;color:#6B6F76;width:42%;vertical-align:top;">' . $e($label) . '</td>
        <td style="padding:16px 18px;' . $border . 'font-family:Helvetica,Arial,sans-serif;font-size:16px;color:#1A2B47;vertical-align:top;line-height:1.5;">' . $value . '</td>
      </tr>';
}

$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>New demo request</title>
</head>
<body style="margin:0;padding:0;background:#FDF3EA;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FDF3EA;">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        <tr>
          <td style="padding:0 0 18px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="vertical-align:middle;padding:0 0 0 6px;">
                  <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="vertical-align:middle;padding-right:8px;"><img src="' . $SITE . '/images/logo.png" width="34" height="34" alt="" style="display:block;border:0;"></td>
                      <td style="vertical-align:middle;font-family:Helvetica,Arial,sans-serif;font-size:30px;font-weight:700;color:#F4600B;letter-spacing:-0.5px;">Gloria</td>
                    </tr>
                  </table>
                  <div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:2px;color:#8A6B58;padding:6px 0 0 2px;">PEOPLE &nbsp;&middot;&nbsp; CONNECTION &nbsp;&middot;&nbsp; CARE</div>
                </td>
                <td width="230" style="vertical-align:middle;">
                  <img src="' . $SITE . '/images/closing-image.jpg" width="230" alt="" style="display:block;border:0;width:230px;height:130px;object-fit:cover;border-radius:18px;">
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="background:#FFFFFF;border-radius:22px;padding:28px 26px 22px 26px;box-shadow:0 12px 30px rgba(90,50,20,0.08);">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="64" style="vertical-align:top;">
                  <div style="width:56px;height:56px;border-radius:28px;background:#FFE7D6;text-align:center;line-height:56px;font-family:Helvetica,Arial,sans-serif;font-size:26px;color:#F4600B;">&#9993;</div>
                </td>
                <td style="vertical-align:top;padding-left:14px;">
                  <div style="font-family:Helvetica,Arial,sans-serif;font-size:28px;font-weight:700;color:#1A2B47;line-height:1.2;">New demo request</div>
                  <div style="font-family:Helvetica,Arial,sans-serif;font-size:15px;color:#4B5058;padding-top:6px;line-height:1.5;">Someone just submitted the demo form on <a href="' . $FORM_PAGE . '" style="color:#E4520B;">gloriatech.co/demo</a>.</div>
                </td>
              </tr>
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;border:1px solid #F1E9E2;border-radius:16px;border-collapse:separate;overflow:hidden;">' . $rowsHtml . '
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;background:#FFF1E6;border-radius:14px;">
              <tr>
                <td style="padding:16px 18px;font-family:Helvetica,Arial,sans-serif;">
                  <div style="font-size:15px;font-weight:700;color:#1A2B47;">Submitted on</div>
                  <div style="font-size:14px;color:#6B6F76;padding-top:3px;">' . $e($submitted) . '</div>
                </td>
              </tr>
            </table>

            <div style="font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#6B6F76;padding-top:18px;line-height:1.5;">Reply to this email to answer ' . $e($name) . ' directly.</div>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:26px 0 8px 0;font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#9AA0A6;">
            Gloria &middot; <a href="' . $SITE . '" style="color:#9AA0A6;">gloriatech.co</a>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>';

$text = "New demo request from gloriatech.co\n\n"
    . "Name: $name\nEmail: $email\nPhone: " . ($phone ?: 'Not provided') . "\n"
    . "Community type: " . ($community ?: 'Not provided') . "\n"
    . "How they heard about Gloria: " . ($heard ?: 'Not provided') . "\n"
    . "How we can help: " . ($help ?: 'Not provided') . "\n\n"
    . "Submitted on $submitted\n";

$boundary = 'gloria-' . bin2hex(random_bytes(12));
$headers  = "From: $FROM\r\n"
    . "Reply-To: " . str_replace(["\r", "\n"], '', $name) . " <$email>\r\n"
    . "MIME-Version: 1.0\r\n"
    . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
    . "X-Mailer: gloriatech.co demo form\r\n";

$body = "--$boundary\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $text . "\r\n"
    . "--$boundary\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $html . "\r\n"
    . "--$boundary--\r\n";

$subject = 'New demo request from gloriatech.co';
$sent = @mail($to, $subject, $body, $headers, '-f noreply@gloriatech.co');

header('Location: ' . $CONFIRM . ($sent ? '' : '?sent=0'), true, 303);
exit;
