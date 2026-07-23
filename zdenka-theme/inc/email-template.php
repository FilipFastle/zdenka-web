<?php
defined('ABSPATH') || exit;
// HTML Email template – light/dark mode, clean ob_start approach
function zc_email_template($data) {
    $name    = $data['name']    ?? '';
    $email   = $data['email']   ?? '';
    $phone   = $data['phone']   ?? '';
    $message = $data['message'] ?? '';
    $subject = $data['subject'] ?? 'Správa z webu';
    $ip      = $data['ip']      ?? '';
    $extra   = $data['extra']   ?? [];
    $body_html = $data['body_html'] ?? ''; // vlastný obsah namiesto tabuľky detailov
    $heading   = $data['heading']   ?? 'Nová správa z webu';
    $agent   = zc_agent('name', 'Mgr. Zdenka Cibuľová');
    $site    = zc_agent('name', 'Mgr. Zdenka Cibuľová');
    $url     = home_url();
    $date    = date('d.m.Y H:i');

    // Normalize phone for WhatsApp
    $cp = preg_replace('#[^0-9]#', '', $phone);
    if     (strpos($cp, '00421') === 0)                        $cp = substr($cp,2);
    elseif (strpos($cp, '421') === 0 && strlen($cp)===12)     {}
    elseif (strpos($cp, '0') === 0 && strlen($cp)===10)       $cp = '421'.substr($cp,1);
    elseif (strlen($cp)===9 && in_array($cp[0],['6','7','9']))  $cp = '421'.$cp;
    else $cp = '';
    $wa_link = $cp ? 'https://wa.me/'.$cp.'?text='.rawurlencode("Dobrý deň {$name}, kontaktujem vás k vašej správe z webu.") : '';

    // Riadky s detailmi (jednoduchá, „transakčná" podoba — nie reklamná)
    $rows = '';
    $add_row = function ($label, $val) use (&$rows) {
        if ($val === '' || $val === null) return;
        $rows .= '<tr>'
            . '<td style="padding:7px 14px 7px 0;font-size:13px;color:#6B6560;white-space:nowrap;vertical-align:top">' . esc_html($label) . ':</td>'
            . '<td style="padding:7px 0;font-size:14px;color:#1C1A18;vertical-align:top">' . $val . '</td>'
            . '</tr>';
    };
    $add_row('Meno', esc_html($name));
    $add_row('E-mail', '<a href="mailto:' . esc_attr($email) . '" style="color:#7C5E33">' . esc_html($email) . '</a>');
    if ($phone) $add_row('Telefón', '<a href="tel:' . esc_attr(preg_replace('#[^0-9+]#','', $phone)) . '" style="color:#7C5E33">' . esc_html($phone) . '</a>');
    foreach ($extra as $k => $v) $add_row($k, esc_html($v));
    if ($message) $add_row('Správa', '<span style="white-space:pre-wrap">' . esc_html($message) . '</span>');

    $host = esc_html(wp_parse_url($url, PHP_URL_HOST));

    ob_start(); ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($subject); ?></title>
</head>
<body style="margin:0;padding:0;background:#F5F1EA;font-family:-apple-system,'Segoe UI',Arial,sans-serif;color:#2C2825">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F1EA">
<tr><td align="center" style="padding:28px 16px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FFFFFF;border:1px solid #E7E1D6;border-radius:16px;overflow:hidden">

    <!-- Hlavička – značková, svetlá -->
    <tr><td style="padding:26px 30px 20px;border-bottom:1px solid #EFE9DE">
        <div style="font-family:Georgia,'Times New Roman',serif;font-size:19px;font-weight:700;color:#1C1A18;letter-spacing:.2px"><?php echo esc_html($agent); ?></div>
        <div style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#B8A47A;margin-top:4px"><?php echo esc_html($heading); ?></div>
    </td></tr>
    <tr><td style="height:3px;background:#B8A47A;line-height:3px;font-size:0">&nbsp;</td></tr>

    <?php if ($body_html): ?>
    <!-- Vlastný obsah -->
    <tr><td style="padding:26px 30px 22px;font-size:15px;color:#2C2825;line-height:1.6"><?php echo $body_html; ?></td></tr>
    <?php else: ?>
    <!-- Detaily -->
    <tr><td style="padding:24px 30px 8px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <?php echo $rows; ?>
        </table>
    </td></tr>

    <!-- Poznámka -->
    <tr><td style="padding:8px 30px 24px">
        <p style="margin:0;font-size:13px;color:#6B6560;line-height:1.6">
            Odpovedať môžete priamo na tento e-mail<?php if ($phone): ?>, alebo zavolať na <a href="tel:<?php echo esc_attr(preg_replace('#[^0-9+]#','', $phone)); ?>" style="color:#7C5E33;font-weight:600"><?php echo esc_html($phone); ?></a><?php endif; ?>.
        </p>
    </td></tr>
    <?php endif; ?>

    <!-- Pätička + LOG (IP, dátum) -->
    <tr><td style="padding:16px 30px;background:#FAF7F1;border-top:1px solid #EFE9DE">
        <p style="margin:0;font-size:12px;color:#9A8F80">
            <?php echo esc_html($agent); ?> ·
            <a href="<?php echo esc_url($url); ?>" style="color:#B8A47A;text-decoration:none"><?php echo $host; ?></a>
        </p>
        <p style="margin:8px 0 0;font-size:11px;color:#B5AC9E;font-family:'Courier New',monospace">
            Prijaté: <?php echo esc_html($date); ?><?php if ($ip): ?> &nbsp;·&nbsp; IP adresa: <?php echo esc_html($ip); ?><?php endif; ?>
        </p>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
<?php
    return ob_get_clean();
}

add_filter('wp_mail_content_type', function() { return 'text/html'; });

// Pridá aj plain-text alternatívu (multipart) — znižuje spam skóre
// (HTML-only e-maily filtre penalizujú, napr. SpamAssassin MIME_HTML_ONLY).
add_action('phpmailer_init', function ($phpmailer) {
    if ($phpmailer->ContentType === 'text/html' && empty($phpmailer->AltBody)) {
        $text = wp_strip_all_tags(preg_replace('#<(br|/p|/tr|/div)[^>]*>#i', "\n", $phpmailer->Body));
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));
        $phpmailer->AltBody = $text;
    }
});
