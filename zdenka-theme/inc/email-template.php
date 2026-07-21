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

    // Extra rows
    $extra_rows = '';
    foreach ($extra as $k => $v) {
        $extra_rows .= '<tr><td class="label">'.esc_html($k).'</td><td class="value">'.esc_html($v).'</td></tr>';
    }
    $msg_row = $message ? '<tr><td class="label" style="vertical-align:top">Správa</td><td class="value" style="white-space:pre-wrap">'.esc_html($message).'</td></tr>' : '';

    ob_start(); ?>
<!DOCTYPE html>
<html lang="sk" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?php echo esc_html($subject); ?></title>
<style>
body  { margin:0; padding:0; background:#F2EEE8; font-family:'DM Sans',Arial,sans-serif; }
.wrap { max-width:580px; margin:28px auto; background:#FFFFFF; border-radius:16px; overflow:hidden; border:1px solid #E0D8CE; box-shadow:0 4px 32px rgba(44,34,20,.09); }
.head { background:#1C1A18; padding:24px 32px; text-align:center; }
.head-name { font-family:Georgia,'Times New Roman',serif; font-size:22px; font-weight:700; color:#FFFFFF; margin:0 0 3px; }
.head-sub  { font-size:9px; letter-spacing:2px; text-transform:uppercase; color:#B8A47A; margin:0; }
.bar  { height:3px; background:linear-gradient(90deg,#9A8660,#D4BC8C,#9A8660); }
.body { padding:28px 32px; }
.sender { display:flex; align-items:center; gap:14px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid #E0D8CE; }
.avatar { width:48px; height:48px; border-radius:50%; background:#F2EEE8; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; border:2px solid #E0D8CE; }
.sender-name { font-family:Georgia,serif; font-size:17px; font-weight:700; color:#1C1A18; margin:0 0 2px; }
.sender-mail { font-size:13px; color:#9A8660; margin:0; }
.details { width:100%; border-collapse:collapse; margin-bottom:24px; }
.details .label { font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; color:#9A8660; padding:10px 12px 10px 0; width:120px; vertical-align:top; border-bottom:1px solid #F2EEE8; }
.details .value { font-size:14px; color:#2C2825; padding:10px 0; border-bottom:1px solid #F2EEE8; }
.actions { background:#F8F5EE; border-radius:10px; padding:16px 20px; margin-bottom:20px; }
.act-label { font-size:10px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:#6B6560; margin:0 0 11px; }
.btn { display:inline-block; padding:9px 16px; border-radius:7px; font-size:12px; font-weight:600; text-decoration:none; margin-right:8px; margin-bottom:6px; }
.btn-dark  { background:#1C1A18; color:#FFFFFF !important; }
.btn-gold  { background:#B8A47A; color:#1C1A18 !important; }
.btn-green { background:#22c55e; color:#FFFFFF !important; }
.meta { font-size:11px; color:#B0A898; margin:20px 0 0; text-align:right; }
.foot { background:#F2EEE8; padding:18px 32px; text-align:center; border-top:1px solid #E0D8CE; }
.foot-name { font-family:Georgia,serif; font-size:14px; color:#7A7068; margin:0 0 3px; }
.foot-url  { font-size:11px; color:#B8A47A; text-decoration:none; }
@media (prefers-color-scheme: dark) {
    body  { background:#1C1A18 !important; }
    .wrap { background:#252220 !important; border-color:#2e2b27 !important; }
    .details .label { color:#B8A47A !important; border-color:#2e2b27 !important; }
    .details .value { color:#d1cbc3 !important; border-color:#2e2b27 !important; }
    .actions { background:#2e2b27 !important; }
    .act-label { color:#9A8660 !important; }
    .meta { color:#6B6560 !important; }
    .foot { background:#1C1A18 !important; border-color:#2e2b27 !important; }
    .foot-name { color:#9A8660 !important; }
}
@media only screen and (max-width:600px) {
    .wrap { margin:0; border-radius:0; }
    .head,.body,.foot { padding-left:20px !important; padding-right:20px !important; }
}
</style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F2EEE8;padding:24px 16px">
<tr><td align="center">
<div class="wrap">
    <div class="head">
        <p class="head-name"><?php echo esc_html($site); ?></p>
        <p class="head-sub">Nová správa z webu</p>
    </div>
    <div class="bar"></div>
    <div class="body">
        <div class="sender">
            <div class="avatar">👤</div>
            <div>
                <p class="sender-name"><?php echo esc_html($name); ?></p>
                <p class="sender-mail"><?php echo esc_html($email); ?></p>
            </div>
        </div>
        <table class="details">
            <?php if ($phone): ?>
            <tr><td class="label">Telefón</td><td class="value"><?php echo esc_html($phone); ?></td></tr>
            <?php endif; ?>
            <?php echo $extra_rows; ?>
            <?php echo $msg_row; ?>
        </table>
        <div class="actions">
            <p class="act-label">Rýchle akcie</p>
            <a href="mailto:<?php echo esc_attr($email); ?>" class="btn btn-dark">✉️ Odpovedať</a>
            <?php if ($phone): ?>
            <a href="tel:<?php echo esc_attr(preg_replace('#[^0-9+]#','', $phone)); ?>" class="btn btn-gold">📞 Zavolať</a>
            <?php endif; ?>
            <?php if ($wa_link): ?>
            <a href="<?php echo esc_url($wa_link); ?>" class="btn btn-green">💬 WhatsApp</a>
            <?php endif; ?>
        </div>
        <p class="meta">IP: <?php echo esc_html($ip); ?> &nbsp;·&nbsp; <?php echo esc_html($date); ?></p>
    </div>
    <div class="foot">
        <p class="foot-name"><?php echo esc_html($agent); ?></p>
        <a href="<?php echo esc_url($url); ?>" class="foot-url"><?php echo esc_html(parse_url($url, PHP_URL_HOST)); ?></a>
    </div>
</div>
</td></tr>
</table>
</body>
</html>
<?php
    return ob_get_clean();
}

add_filter('wp_mail_content_type', function() { return 'text/html'; });
