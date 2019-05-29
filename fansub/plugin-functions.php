<?php
if(!function_exists('add_filter')) exit;

function fansub_plugin_get_image_url($base_url, $name) {
    return trailingslashit($base_url) . 'images/' . $name;
}

function fansub_plugin_get_template($base_path, $slug, $name = '') {
    if(!empty($name)) {
        $slug .= '-' . $name;
    }
    $slug .= '.php';
    $base_path = trailingslashit($base_path) . $slug;
    if(file_exists($base_path)) {
        include($base_path);
    }
}

function fansub_plugin_get_module($base_path, $name) {
    fansub_plugin_get_template($base_path, 'module', $name);
}

function fansub_plugin_load_custom_css() {
    $option = get_option('fansub_plugin_custom_css');
    $css = fansub_get_value_by_key($option, 'code');
    if(!empty($css)) {
        $css = fansub_minify_css($css);
        $style = new FANSUB_HTML('style');
        $style->set_attribute('type', 'text/css');
        $style->set_text($css);
        $style->output();
    }
}
add_action('wp_head', 'fansub_plugin_load_custom_css', 99);