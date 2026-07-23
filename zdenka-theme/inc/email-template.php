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

    ob_start(); ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($subject); ?></title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:-apple-system,'Segoe UI',Arial,sans-serif;color:#1C1A18">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff">
<tr><td align="center" style="padding:24px 16px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">
    <tr><td style="padding:0 4px 16px">
        <p style="margin:0;font-size:16px;color:#1C1A18">Máte novú správu z webu</p>
        <p style="margin:4px 0 0;font-size:13px;color:#8A8072"><?php echo esc_html($date); ?></p>
    </td></tr>
    <tr><td style="border:1px solid #E7E1D6;border-radius:10px;padding:20px 22px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <?php echo $rows; ?>
        </table>
    </td></tr>
    <tr><td style="padding:16px 4px 0">
        <p style="margin:0;font-size:13px;color:#6B6560">
            Odpovedať môžete priamo na tento e-mail<?php if ($phone): ?>, alebo zavolať na <a href="tel:<?php echo esc_attr(preg_replace('#[^0-9+]#','', $phone)); ?>" style="color:#7C5E33"><?php echo esc_html($phone); ?></a><?php endif; ?>.
        </p>
    </td></tr>
    <tr><td style="padding:18px 4px 0;border-top:1px solid #E7E1D6;margin-top:16px">
        <p style="margin:14px 0 0;font-size:12px;color:#A79E90"><?php echo esc_html($agent); ?> · <a href="<?php echo esc_url($url); ?>" style="color:#A79E90;text-decoration:none"><?php echo esc_html(wp_parse_url($url, PHP_URL_HOST)); ?></a></p>
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
