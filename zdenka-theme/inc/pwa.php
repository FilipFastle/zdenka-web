<?php
defined('ABSPATH') || exit;
// ── PWA Setup ─────────────────────────────────────────────────────────────

add_action('wp_head', function () {
    $cache_ver = get_option('zc_cache_version', 'v1');
    $theme_uri = get_stylesheet_directory_uri();
    ?>
<link rel="manifest" href="<?php echo $theme_uri; ?>/assets/manifest.json">
<meta name="theme-color" content="#1C1A18">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr(zc_agent('name', 'Mgr. Zdenka Cibuľová')); ?>">
<link rel="apple-touch-icon" href="<?php echo $theme_uri; ?>/assets/images/zc-logo.svg">
<script>
if ('serviceWorker' in navigator) {
    // SW sa servíruje z koreňa (?zcpwa=1) aby platil scope '/' pre celý web
    navigator.serviceWorker.register('<?php echo esc_url(home_url('/sw.js?zcpwa=1')); ?>', { scope: '/' })
        .then(function(reg) { reg.update(); })
        .catch(function(){});
}
</script>
    <?php
}, 5);

// Serve SW from root scope (required for full-site SW)
add_action('parse_request', function () {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'sw.js') !== false
        && isset($_GET['zcpwa'])) {
        $sw = get_stylesheet_directory() . '/assets/sw.js';
        if (file_exists($sw)) {
            header('Content-Type: application/javascript');
            header('Service-Worker-Allowed: /');
            // Inject version
            $ver = get_option('zc_cache_version', 'v1');
            $content = 'self.CACHE_VERSION = ' . json_encode($ver) . ";\n" . file_get_contents($sw);
            echo $content;
            exit;
        }
    }
});

// Rewrite rule for SW at root
add_action('init', function () {
    add_rewrite_rule('^sw\.js$', 'index.php?zcpwa=1', 'top');
});
add_filter('query_vars', function ($vars) { $vars[] = 'zcpwa'; return $vars; });
