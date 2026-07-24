<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php $zc_fdir = get_stylesheet_directory_uri() . '/assets/fonts'; ?>
<link rel="preload" href="<?php echo esc_url($zc_fdir); ?>/DMSans.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo esc_url($zc_fdir); ?>/PlayfairDisplay.woff2" as="font" type="font/woff2" crossorigin>
<?php wp_head(); ?>

</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="zc-skip" href="#zc-obsah">Preskočiť na obsah</a>

<div class="zc-header-wrap" id="zcHeader" role="banner">
    <div class="zc-header">

        <!-- Logo -->
        <a href="<?php echo home_url(); ?>" class="zc-logo">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/zc-logo.svg"
                 class="zc-logo-svg" alt="ZC"
                 width="38" height="38"
                 style="width:38px;height:38px;object-fit:contain;flex-shrink:0;transition:filter .3s">
            <span class="zc-logo-text">
                <?php echo esc_html(zc_agent('name', 'Mgr. Zdenka Cibuľová')); ?>
                <small><?php echo esc_html(zc_agent('title','Realitná maklérka')); ?></small>
            </span>
        </a>

        <!-- Desktop nav -->
        <nav class="zc-nav">
            <?php foreach([
                ['/',             is_front_page(),       'Domov'],
                ['/o-mne/',       is_page('o-mne'),      'O mne'],
                ['/ako-pracujem/',is_page('ako-pracujem'),'Ako pracujem'],
                ['/ponuky/',      is_page('ponuky'),     'Ponuky'],
                ['/odhad/',       is_page('odhad'),      'Odhad'],
            ] as [$path,$active,$label]): ?>
            <a href="<?php echo home_url($path); ?>"
               <?php echo $active ? 'class="active"' : ''; ?>><?php echo $label; ?></a>
            <?php endforeach; ?>
            <?php if (function_exists('zc_ebook_enabled') && zc_ebook_enabled()): zc_ebook_flag(true); ?>
            <a href="#" data-zc-ebook-open class="zc-nav-ebook">Ebook</a>
            <?php endif; ?>
            <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-nav-cta"
               <?php echo is_page('kontakt') ? 'style="opacity:.85"' : ''; ?>>Kontakt</a>
            <?php echo zc_social_icons_html('zc-socials--nav'); ?>
        </nav>

        <!-- Hamburger -->
        <button class="zc-hamburger" id="zcHam" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile nav -->
    <nav class="zc-mobile-nav" id="zcMobileNav" aria-hidden="true">
        <?php foreach([
            ['/',             is_front_page(),        'Domov'],
            ['/o-mne/',       is_page('o-mne'),       'O mne'],
            ['/ako-pracujem/',is_page('ako-pracujem'),'Ako pracujem'],
            ['/ponuky/',      is_page('ponuky'),      'Ponuky'],
            ['/odhad/',       is_page('odhad'),       'Odhad ZDARMA'],
            ['/kontakt/',     is_page('kontakt'),     'Kontakt'],
        ] as [$path,$active,$label]): ?>
        <a href="<?php echo home_url($path); ?>"
           style="<?php echo $active ? 'color:var(--accent-txt);font-weight:700' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
        <?php if (function_exists('zc_ebook_enabled') && zc_ebook_enabled()): zc_ebook_flag(true); ?>
        <a href="#" data-zc-ebook-open style="color:var(--accent-txt);font-weight:700">Ebook PDF zdarma</a>
        <?php endif; ?>
        <?php $zc_mnav_soc = function_exists('zc_social_icons_html') ? zc_social_icons_html() : ''; ?>
        <?php if ($zc_mnav_soc): ?>
        <div style="display:flex;justify-content:center;margin-top:18px;padding-top:18px;border-top:1px solid rgba(184,164,122,.25)"><?php echo $zc_mnav_soc; ?></div>
        <?php endif; ?>
    </nav>
</div>
<span id="zc-obsah" tabindex="-1"></span>
