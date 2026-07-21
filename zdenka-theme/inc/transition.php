<?php
defined('ABSPATH') || exit;
// Page transition – outputs in <head> priority 1 (before everything else)
add_action('wp_head', function () {
    $logo = get_stylesheet_directory_uri() . '/assets/images/zc-logo.svg';
    $logo_e = esc_url($logo);
?>
<!-- Preload SVG so it's ready before first click -->
<link rel="preload" href="<?php echo $logo_e; ?>" as="image" type="image/svg+xml">
<style id="zcPTS">
/* Dark HTML bg prevents white flash between pages */
html { background: #F3EBDD; }
/* Body hidden until overlay says ready */
body { opacity: 0; will-change: opacity; }
body.zc-ready { opacity: 1; transition: opacity .45s ease; }

#zcPT {
    position: fixed; inset: 0;
    z-index: 2147483647;
    background: linear-gradient(135deg, #F5EEDF 0%, #EBDCC0 100%);
    display: flex; align-items: center; justify-content: center;
    pointer-events: all;
}
#zcPT.out {
    opacity: 0;
    pointer-events: none;
    transition: opacity .52s cubic-bezier(.4,0,.2,1);
}
#zcPT img {
    width: 110px; height: 110px;
    filter: brightness(0); /* dark logo on cream bg */
}
@keyframes zcLogoIn {
    0%   { transform: scale(.5) rotate(-20deg); opacity: 0; }
    100% { transform: scale(1) rotate(0deg);    opacity: 1; }
}
#zcPT.entering img {
    animation: zcLogoIn .62s cubic-bezier(.34,1.56,.64,1) both;
}
</style>
<script>
(function () {
    var LOGO = '<?php echo addslashes($logo); ?>';
    var KEY  = 'zcNav';

    /* Build or get the overlay div */
    function getOv() {
        var o = document.getElementById('zcPT');
        if (o) return o;
        o = document.createElement('div');
        o.id = 'zcPT';
        var img = document.createElement('img');
        img.src = LOGO;
        o.appendChild(img);
        document.documentElement.appendChild(o);
        return o;
    }

    function showBody() {
        if (document.body) document.body.classList.add('zc-ready');
    }

    /* ── ARRIVAL ─────────────────────────────────────────────── */
    if (sessionStorage.getItem(KEY) === '1') {
        sessionStorage.removeItem(KEY);

        // Overlay shows immediately (body is opacity:0) – looks seamless
        var ov = getOv();

        function fadeOut() {
            setTimeout(function () {
                // Start BOTH at exact same moment – no crack between them
                ov.classList.add('out');
                showBody();
                setTimeout(function () { if (ov.parentNode) ov.remove(); }, 580);
            }, 80);
        }

        if (document.readyState === 'complete') {
            fadeOut();
        } else {
            window.addEventListener('load', fadeOut);
        }
    } else {
        // Normal load – just show body
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showBody);
        } else {
            showBody();
        }
    }

    /* ── DEPARTURE – intercept internal link clicks ──────────── */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href') || '';

        // Skip anchors, external, admin, special links
        if (!href || href[0] === '#'
            || href.indexOf('mailto:') === 0
            || href.indexOf('tel:')    === 0
            || href.indexOf('javascript') === 0
            || href.indexOf('wp-admin')   !== -1
            || href.indexOf('wp-login')   !== -1
            || a.target === '_blank'
            || a.hasAttribute('download')
            || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var url;
        try { url = new URL(href, location.href); } catch (x) { return; }
        if (url.origin !== location.origin) return;
        if (url.href   === location.href)    return;
        // Panel internal nav (?action=...) - no transition
        if (url.pathname === location.pathname) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        var dest = url.href;

        // Show overlay with logo entrance animation
        var ov2 = getOv();
        ov2.classList.remove('out');
        ov2.classList.add('entering');

        sessionStorage.setItem(KEY, '1');
        setTimeout(function () { location.href = dest; }, 560);
    }, true);

    /* ── BFCache (back/forward) ──────────────────────────────── */
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.body.classList.add('zc-ready');
            var o = document.getElementById('zcPT');
            if (o) o.remove();
        }
    });

})();
</script>
<?php
}, 1);
