<?php
// ── WebP Image Optimizer ─────────────────────────────────────────────────

/**
 * Kvalita zmenšených verzií fotiek.
 * WordPress ich robí na 82 %, čo je pri veľkých fotkách nehnuteľností
 * viditeľné. 90 % je stále rozumná veľkosť súboru a znateľne ostrejšie.
 * Platí len pre novo nahraté (alebo znova vygenerované) fotky.
 */
add_filter('jpeg_quality', function () { return 90; });
add_filter('wp_editor_set_quality', function ($q, $mime) {
    return $mime === 'image/webp' ? 88 : 90;
}, 10, 2);

// Convert image to WebP on upload
add_filter('wp_handle_upload', function($upload) {
    if (!in_array($upload['type'], ['image/jpeg','image/png','image/gif'])) return $upload;
    if (!function_exists('imagewebp')) return $upload;

    $file    = $upload['file'];
    $webp    = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file);
    $quality = get_option('zc_webp_quality', 82);

    $img = null;
    switch ($upload['type']) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($file); break;
        case 'image/png':  $img = @imagecreatefrompng($file); break;
        case 'image/gif':  $img = @imagecreatefromgif($file); break;
    }

    if ($img) {
        // Preserve transparency for PNG
        if ($upload['type'] === 'image/png') {
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }
        imagewebp($img, $webp, intval($quality));
        imagedestroy($img);
    }

    return $upload;
});

// Serve WebP if available (via .htaccess or filter)
add_filter('wp_get_attachment_url', function($url) {
    if (!get_option('zc_webp_serve', 0)) return $url;
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $url);
    $path = str_replace(home_url(), ABSPATH, $webp);
    return file_exists($path) ? $webp : $url;
});

// Bulk convert existing images
function zc_bulk_convert_webp() {
    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => ['image/jpeg','image/png'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $converted = 0;
    $failed    = 0;

    foreach ($attachments as $id) {
        $file = get_attached_file($id);
        if (!$file || !file_exists($file)) { $failed++; continue; }

        $webp    = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
        if (file_exists($webp)) { $converted++; continue; }

        $mime = get_post_mime_type($id);
        $img  = $mime === 'image/jpeg' ? @imagecreatefromjpeg($file) : @imagecreatefrompng($file);
        if (!$img) { $failed++; continue; }

        if ($mime === 'image/png') { imagealphablending($img, true); imagesavealpha($img, true); }
        $ok = imagewebp($img, $webp, intval(get_option('zc_webp_quality', 82)));
        imagedestroy($img);
        $ok ? $converted++ : $failed++;
    }

    return ['total' => count($attachments), 'converted' => $converted, 'failed' => $failed];
}

// Handle bulk convert AJAX
add_action('wp_ajax_zc_bulk_webp', function() {
    check_admin_referer('zc_bulk_webp');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $result = zc_bulk_convert_webp();
    wp_send_json_success($result);
});
