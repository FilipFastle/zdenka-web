<?php
/**
 * Aktualizácia vlastných balíkov nahratím .zip.
 *
 * WordPress pri ručnom nahratí pluginu odmietne prepísať priečinok, ktorý
 * už existuje. Obrazovku „Nahradiť aktuálnu verziu nahranou“ vie ponúknuť
 * len vtedy, keď existujúci priečinok rozpozná ako nainštalovaný plugin.
 * Keď v ňom ostane torzo po predošlej inštalácii (napr. po balíku s iným
 * názvom priečinka), WordPress ho nepozná a skončí hláškou
 * „Priečinok už existuje“ – hoci ide o ten istý plugin.
 *
 * Preto pre naše vlastné balíky – a len pre ne – povieme inštalátoru,
 * nech starý priečinok pred rozbalením zmaže. Presne to isté robí
 * WordPress sám, keď na tej obrazovke klikneš „Nahradiť“.
 *
 * Údaje sú v databáze (odberatelia, recenzie, ponuky, dopyty, nastavenia),
 * takže výmena priečinka sa ich nedotkne.
 */
defined('ABSPATH') || exit;

/** Priečinky, ktoré patria tomuto webu. Cudzích pluginov sa to netýka. */
function zc_own_packages() {
    return [
        'zdenka-theme',
        'property-pro-plugin',
        'zc-newsletter',
        'zc-reviews',
        'zc-ebook',
        'zc-2fa',
        'zc-installer',
    ];
}

/**
 * Názov koreňového priečinka v .zip súbore.
 * Vráti prázdny reťazec, keď balík nemá práve jeden koreňový priečinok –
 * vtedy sa do ničoho nemiešame.
 */
function zc_zip_root_folder($file) {
    if (!class_exists('ZipArchive')) return '';

    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return '';

    $root  = '';
    $limit = min($zip->numFiles, 80); // stačí nazrieť na začiatok
    for ($i = 0; $i < $limit; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false) continue;

        $name = ltrim(str_replace('\\', '/', $name), '/');
        if ($name === '' || strpos($name, '__MACOSX') === 0) continue;

        $slash = strpos($name, '/');
        if ($slash === false) { $root = ''; break; } // súbor priamo v koreni
        $first = substr($name, 0, $slash);

        if ($root === '')          $root = $first;
        elseif ($root !== $first)  { $root = ''; break; } // viac koreňov
    }
    $zip->close();

    return $root;
}

add_filter('upgrader_package_options', function ($options) {
    $type = $options['hook_extra']['type'] ?? '';
    if (!in_array($type, ['plugin', 'theme'], true)) return $options;

    // Aktualizácie z wordpress.org sťahujú balík z adresy – tých sa to netýka.
    $package = $options['package'] ?? '';
    if (!is_string($package) || $package === '' || !file_exists($package)) return $options;

    if (!in_array(zc_zip_root_folder($package), zc_own_packages(), true)) return $options;

    $options['clear_destination']            = true;
    $options['abort_if_destination_exists']  = false;
    return $options;
});

/**
 * Zrozumiteľná hláška, keby predsa len niečo zlyhalo – aby bolo hneď jasné,
 * čo s tým, a nemuselo sa hľadať po fórach.
 */
add_filter('upgrader_install_package_result', function ($result, $hook_extra) {
    if (!is_wp_error($result) || $result->get_error_code() !== 'folder_exists') return $result;

    $folder = (string) $result->get_error_data('folder_exists');
    $name   = basename(untrailingslashit($folder));
    if (!in_array($name, zc_own_packages(), true)) return $result;

    return new WP_Error('folder_exists', sprintf(
        'Priečinok „%s“ sa nepodarilo prepísať. Otvor Pluginy, daj pri ňom '
        . 'Deaktivovať a Zmazať, a nahraj .zip znova. Odberatelia, recenzie, '
        . 'ponuky ani dopyty sa tým nestratia – tie sú v databáze webu.',
        esc_html($name)
    ), $folder);
}, 10, 2);
