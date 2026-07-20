<?php
defined('ABSPATH') || exit;
function zc_is_elementor_page() {
    if (!class_exists('\Elementor\Plugin')) return false;
    return \Elementor\Plugin::$instance->db->is_built_with_elementor(get_the_ID());
}
