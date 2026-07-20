<?php
defined('ABSPATH') || exit;
function zcn_email_wrap($subject, $content, $footer_extra = '') {
    $site    = function_exists('zc_agent') ? zc_agent('name', 'Zdenka Cibuľová') : (get_bloginfo('name') ?: 'Zdenka Cibuľová');
    $url     = home_url();
    $year    = date('Y');

    return '<!DOCTYPE html>
<html lang="sk" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>' . esc_html($subject) . '</title>
<!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<style>
:root { color-scheme: light dark; }
body  { margin:0; padding:0; background:#F2EEE8; }
@media (prefers-color-scheme: dark) {
    body   { background:#1C1A18 !important; }
    .card  { background:#252220 !important; border-color:#2e2b27 !important; }
    .card-body p, .card-body li, .card-body td { color:#d1cbc3 !important; }
    .card-body h1,.card-body h2,.card-body h3 { color:#fff !important; }
    .card-body strong { color:#e8dfd0 !important; }
    .card-body a  { color:#C9B38A !important; }
    .divider { border-color:#2e2b27 !important; }
    .footer-text { color:#6B6560 !important; }
    .footer-link { color:#7A7068 !important; }
    .chip { background:#2e2b27 !important; color:#9A8660 !important; }
}
@media only screen and (max-width: 620px) {
    .outer  { padding: 16px !important; }
    .card   { border-radius: 14px !important; }
    .header { padding: 22px 24px !important; }
    .card-body { padding: 24px !important; }
    .footer-inner { padding: 16px 24px !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background:#F2EEE8;font-family:\'DM Sans\',Arial,Helvetica,sans-serif">

<!-- Preheader -->
<div style="display:none;max-height:0;overflow:hidden;color:#F2EEE8">' . wp_strip_all_tags($content) . '&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;</div>

<table class="outer" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F2EEE8;padding:32px 16px">
<tr><td align="center">

<!-- Card -->
<table class="card" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="max-width:580px;background:#FFFFFF;border-radius:20px;border:1px solid #E0D8CE;
              box-shadow:0 4px 40px rgba(44,34,20,.09);overflow:hidden">

  <!-- Header -->
  <tr>
    <td class="header" style="background:#1C1A18;padding:28px 36px;text-align:center">
      <a href="' . $url . '" style="text-decoration:none">
        <div style="font-family:Georgia,\'Times New Roman\',serif;font-size:24px;font-weight:700;color:#FFFFFF;line-height:1.2">' . esc_html($site) . '</div>
        <div style="font-family:Arial,sans-serif;font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:#B8A47A;margin-top:4px">Realitná maklérka</div>
      </a>
    </td>
  </tr>

  <!-- Accent line -->
  <tr>
    <td style="height:3px;background:linear-gradient(90deg,#9A8660,#D4BC8C,#9A8660)"></td>
  </tr>

  <!-- Body -->
  <tr>
    <td class="card-body" style="padding:36px 40px;color:#2C2825;font-size:15px;line-height:1.8">
      ' . $content . '
    </td>
  </tr>

  <!-- Divider -->
  <tr>
    <td style="padding:0 36px">
      <hr class="divider" style="border:none;border-top:1px solid #E0D8CE;margin:0">
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td class="footer-inner" style="padding:20px 36px 28px;text-align:center">
      <div style="display:inline-block;background:#F2EEE8;border-radius:50px;padding:5px 14px;margin-bottom:14px">
        <span class="chip" style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:#9A8660;font-weight:700">' . esc_html($site) . '</span>
      </div>
      <div class="footer-text" style="font-size:11px;color:#9A8660;line-height:1.8">
        ' . $footer_extra . '
        © ' . $year . ' ' . esc_html($site) . ' ·
        <a href="' . $url . '" class="footer-link" style="color:#9A8660;text-decoration:none">' . parse_url($url, PHP_URL_HOST) . '</a>
      </div>
    </td>
  </tr>

</table>
<!-- /Card -->

</td></tr>
</table>
</body></html>';
}

// Markdown to HTML (simple, email-safe)
function zcn_markdown_to_html($text) {
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Headings
    $text = preg_replace('/^### (.+)$/m', '<h4 style="font-family:Georgia,serif;font-size:17px;color:#1C1A18;margin:20px 0 8px;font-weight:700">$1</h4>', $text);
    $text = preg_replace('/^## (.+)$/m',  '<h3 style="font-family:Georgia,serif;font-size:20px;color:#1C1A18;margin:24px 0 10px;font-weight:700">$1</h3>', $text);
    $text = preg_replace('/^# (.+)$/m',   '<h2 style="font-family:Georgia,serif;font-size:24px;color:#1C1A18;margin:28px 0 12px;font-weight:800">$1</h2>', $text);

    // Bold + italic
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong style="font-weight:700;color:#1C1A18"><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s',     '<strong style="font-weight:700;color:#1C1A18">$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s',          '<em style="font-style:italic;color:#7A7068">$1</em>', $text);

    // Links
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" style="color:#9A8660;font-weight:600;text-decoration:underline">$1</a>', $text);

    // Images
    $text = preg_replace('/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1" style="max-width:100%;border-radius:10px;margin:12px 0;display:block">', $text);

    // Buttons: [Button text](url){.btn}
    $text = preg_replace('/\[(.+?)\]\((.+?)\)\{\.btn\}/', '<div style="text-align:center;margin:24px 0"><a href="$2" style="display:inline-block;padding:14px 32px;background:#B8A47A;color:#1C1A18;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;letter-spacing:.5px">$1</a></div>', $text);

    // Horizontal rule
    $text = preg_replace('/^---$/m', '<hr style="border:none;border-top:1px solid #E0D8CE;margin:24px 0">', $text);

    // Unordered list
    $text = preg_replace_callback('/(?:^- .+\n?)+/m', function($m) {
        $items = preg_replace('/^- (.+)$/m', '<li style="margin:6px 0;padding-left:4px">$1</li>', rtrim($m[0]));
        return '<ul style="padding-left:20px;margin:14px 0;color:#555">' . $items . '</ul>';
    }, $text);

    // Ordered list
    $text = preg_replace_callback('/(?:^\d+\. .+\n?)+/m', function($m) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li style="margin:6px 0;padding-left:4px">$1</li>', rtrim($m[0]));
        return '<ol style="padding-left:20px;margin:14px 0;color:#555">' . $items . '</ol>';
    }, $text);

    // Blockquote
    $text = preg_replace('/^> (.+)$/m', '<blockquote style="border-left:3px solid #B8A47A;margin:16px 0;padding:10px 16px;background:#F8F5EE;border-radius:0 8px 8px 0;color:#7A7068;font-style:italic">$1</blockquote>', $text);

    // Paragraphs — blank line = new paragraph
    $blocks = preg_split('/\n{2,}/', trim($text));
    $result = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if (!$block) continue;
        // Don't wrap already-block-level HTML
        if (preg_match('/^<(h[1-6]|ul|ol|hr|blockquote|div|img)/i', $block)) {
            $result .= $block . "\n";
        } else {
            $result .= '<p style="margin:0 0 16px;color:#4A4540;line-height:1.8">' . nl2br($block) . '</p>';
        }
    }

    return $result;
}
