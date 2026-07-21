<?php
defined('ABSPATH') || exit;
// ── OG / Twitter meta + JSON-LD schema ──────────────────────────────────
// Aby zdieľané odkazy (Messenger, WhatsApp, FB) ukazovali fotku, názov a cenu.

add_action('wp_head', function() {
    $site_name = zc_agent('name', get_bloginfo('name'));
    $sep       = ' – ';

    // Predvolený zdieľací obrázok: hero → portrét → logo
    $default_img = '';
    if (function_exists('zc_photo')) {
        $default_img = zc_photo('hero') ?: zc_photo('portrait');
    }
    if (!$default_img) $default_img = get_stylesheet_directory_uri() . '/assets/images/zc-logo.svg';

    $title = wp_get_document_title();
    if (is_singular())          $url = get_permalink();
    elseif (is_front_page())    $url = home_url('/');
    else                        $url = home_url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));

    $desc  = get_bloginfo('description');
    $image = $default_img;
    $type  = is_singular() ? 'article' : 'website';

    // Detail nehnuteľnosti – bohatší náhľad (fotka + cena + lokalita)
    $property_ld = null;
    if (is_singular('property')) {
        $pid   = get_the_ID();
        $cover = get_post_meta($pid, '_property_cover_id', true);
        if ($cover) {
            $src = wp_get_attachment_image_url($cover, 'large');
            if ($src) $image = $src;
        } elseif (has_post_thumbnail($pid)) {
            $image = get_the_post_thumbnail_url($pid, 'large');
        }
        $cena_raw = get_post_meta($pid, '_property_cena', true);
        $cena_num = preg_replace('/[^0-9]/', '', (string)$cena_raw);
        $lok      = get_post_meta($pid, '_property_lokalita', true);
        $popis    = get_post_meta($pid, '_property_popis_kratky', true);
        $bits = array_filter([
            $lok ? $lok : '',
            $cena_num ? number_format((int)$cena_num, 0, ',', ' ') . ' €' : '',
            $popis,
        ]);
        $desc = $bits ? implode(' · ', $bits) : get_the_title($pid);

        $property_ld = [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => get_the_title($pid),
            'image'    => $image,
            'description' => wp_strip_all_tags($popis ?: get_the_title($pid)),
            'url'      => get_permalink($pid),
        ];
        if ($cena_num) {
            $property_ld['offers'] = [
                '@type'         => 'Offer',
                'price'         => (int)$cena_num,
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'url'           => get_permalink($pid),
            ];
        }
    } elseif (is_singular()) {
        $ex = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', get_the_ID())), 30);
        if ($ex) $desc = $ex;
        if (has_post_thumbnail()) $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
    }

    $og_title = is_front_page() ? ($site_name . $sep . get_bloginfo('description')) : $title;
    $desc = trim(wp_strip_all_tags((string)$desc));

    $tags = [
        ['property' => 'og:site_name', 'content' => $site_name],
        ['property' => 'og:type',      'content' => $type],
        ['property' => 'og:title',     'content' => $og_title],
        ['property' => 'og:description','content' => $desc],
        ['property' => 'og:url',       'content' => $url],
        ['property' => 'og:image',     'content' => $image],
        ['property' => 'og:locale',    'content' => 'sk_SK'],
        ['name' => 'twitter:card',        'content' => 'summary_large_image'],
        ['name' => 'twitter:title',       'content' => $og_title],
        ['name' => 'twitter:description', 'content' => $desc],
        ['name' => 'twitter:image',       'content' => $image],
    ];
    echo "\n<!-- OG / Social -->\n";
    foreach ($tags as $t) {
        $attr = isset($t['property']) ? 'property="' . esc_attr($t['property']) . '"' : 'name="' . esc_attr($t['name']) . '"';
        echo '<meta ' . $attr . ' content="' . esc_attr($t['content']) . '">' . "\n";
    }

    // JSON-LD: realitná maklérka (site-wide)
    $agent_ld = [
        '@context'  => 'https://schema.org',
        '@type'     => 'RealEstateAgent',
        'name'      => $site_name,
        'url'       => home_url('/'),
        'image'     => $default_img,
        'telephone' => zc_agent('phone', ''),
        'areaServed'=> ['Banská Bystrica', 'Zvolen'],
    ];
    $email = zc_agent('email', '');
    if ($email) $agent_ld['email'] = $email;

    echo '<script type="application/ld+json">' . wp_json_encode($agent_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    if ($property_ld) {
        echo '<script type="application/ld+json">' . wp_json_encode($property_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }
}, 5);
