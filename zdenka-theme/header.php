<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,400;1,700&display=swap" rel="stylesheet">
<?php wp_head(); ?>

</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="zc-header-wrap" id="zcHeader">
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
            <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-nav-cta"
               <?php echo is_page('kontakt') ? 'style="opacity:.85"' : ''; ?>>Kontakt</a>
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
            ['/odhad/',       is_page('odhad'),       'Odhad ZADARMO'],
            ['/kontakt/',     is_page('kontakt'),     'Kontakt'],
        ] as [$path,$active,$label]): ?>
        <a href="<?php echo home_url($path); ?>"
           style="<?php echo $active ? 'color:var(--accent-txt);font-weight:700' : ''; ?>">
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
