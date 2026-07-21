<?php
defined('ABSPATH') || exit;
$name   = zc_agent('name',  'Mgr. Zdenka Cibuľová');
$title  = zc_agent('title', 'Realitná maklérka');
$phone  = zc_agent('phone', '+421 907 579 742');
$wa     = preg_replace('/[^0-9]/', '', zc_agent('wa','421907579742'));
$email  = zc_agent('email', get_option('admin_email'));
$msg    = get_option('zc_maintenance_msg', 'Web sa momentálne aktualizuje. Ozvite sa mi priamo.');
$logo   = get_stylesheet_directory_uri() . '/assets/images/zc-logo.svg';
?><!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($name); ?> – Údržba</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:'DM Sans',sans-serif;
    background:linear-gradient(135deg,#F5EEDF 0%,#EBDCC0 100%);
    color:#1C1A18;
    min-height:100vh;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    padding:32px 20px;
    position:relative;overflow:hidden;
}
/* Subtle grain texture */
body::before{
    content:'';position:absolute;inset:0;
    background:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.03'/%3E%3C/svg%3E");
    opacity:.5;pointer-events:none;z-index:0;
}
.wrap{position:relative;z-index:1;text-align:center;max-width:560px;width:100%}

/* Logo */
.logo-wrap{
    width:110px;height:110px;margin:0 auto 32px;
    animation:logoPulse 3s ease-in-out infinite;
}
.logo-wrap img{width:100%;height:100%;object-fit:contain;filter:brightness(0);}
@keyframes logoPulse{
    0%,100%{transform:scale(1);opacity:1}
    50%{transform:scale(1.05);opacity:.85}
}

/* Accent line */
.accent-line{
    width:48px;height:1.5px;
    background:linear-gradient(90deg,transparent,#B8A47A,transparent);
    margin:0 auto 28px;
}

h1{
    font-family:'Playfair Display',Georgia,serif;
    font-size:clamp(28px,5vw,44px);font-weight:700;
    color:#1C1A18;margin-bottom:12px;line-height:1.2;
}
h1 em{font-style:italic;color:#7C5E33}

.subtitle{
    font-size:11px;letter-spacing:2.5px;text-transform:uppercase;
    color:#7C5E33;margin-bottom:20px;font-weight:600;
}

.message{
    font-size:15px;color:#7A7068;
    line-height:1.8;margin-bottom:36px;
    max-width:400px;margin-left:auto;margin-right:auto;
}

/* Contact buttons */
.contacts{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}
.contact-btn{
    display:inline-flex;align-items:center;gap:9px;
    padding:11px 20px;border-radius:50px;
    font-size:13px;font-weight:600;text-decoration:none;
    transition:all .25s;
}
.contact-btn.phone{background:#B8A47A;color:#1C1A18}
.contact-btn.phone:hover{background:#9A8660;transform:translateY(-2px)}
.contact-btn.wa{background:#22c55e;color:#fff}
.contact-btn.wa:hover{background:#16a34a;transform:translateY(-2px)}
.contact-btn.mail{background:#fff;color:#1C1A18;border:1px solid #E0D8CE}
.contact-btn.mail:hover{background:#F5F1EA;transform:translateY(-2px)}

/* Login link */
.login-wrap{
    padding-top:28px;
    border-top:1px solid #E0D8CE;
    font-size:13px;color:#A79B8A;
}
.login-wrap a{color:#8B7D66;text-decoration:none;transition:color .2s}
.login-wrap a:hover{color:#1C1A18}

/* Agent card */
.agent{
    display:inline-flex;align-items:center;gap:12px;
    background:#fff;border:1px solid #E0D8CE;
    border-radius:12px;padding:12px 18px;margin-bottom:24px;
}
.agent-avatar{
    width:40px;height:40px;border-radius:50%;
    background:#F5F1EA;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;flex-shrink:0;
    border:1.5px solid #B8A47A;
}
.agent-name{font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#1C1A18;text-align:left}
.agent-role{font-size:10px;color:#7C5E33;letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;text-align:left}
</style>
</head>
<body>
<div class="wrap">

    <div class="logo-wrap">
        <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name); ?>">
    </div>

    <div class="subtitle"><?php echo esc_html($title); ?></div>
    <h1><?php echo esc_html($name); ?></h1>

    <div class="accent-line"></div>

    <p class="message"><?php echo esc_html($msg); ?></p>

    <div class="agent">
        <?php $portrait = function_exists('zc_photo') ? zc_photo('portrait') : ''; ?>
        <?php if($portrait): ?>
        <img src="<?php echo esc_url($portrait); ?>" alt="<?php echo esc_attr($name); ?>" class="agent-avatar" style="object-fit:cover;object-position:center 18%">
        <?php else: ?>
        <div class="agent-avatar" style="font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:#7C5E33"><?php echo esc_html(function_exists('zc_initials') ? zc_initials($name) : ''); ?></div>
        <?php endif; ?>
        <div>
            <div class="agent-name"><?php echo esc_html($name); ?></div>
            <div class="agent-role"><?php echo esc_html($title); ?></div>
        </div>
    </div>

    <div class="contacts">
        <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$phone); ?>" class="contact-btn phone">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
            <?php echo esc_html($phone); ?>
        </a>
        <a href="https://wa.me/<?php echo $wa; ?>" class="contact-btn wa" target="_blank">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
        </a>
        <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-btn mail">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
            Email
        </a>
    </div>

    <div class="login-wrap">
        <a href="<?php echo wp_login_url(home_url()); ?>">Prihlásenie pre administrátorov →</a>
    </div>

</div>
</body>
</html>
