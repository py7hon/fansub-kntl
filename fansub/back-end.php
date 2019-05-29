<?php
if(!function_exists('add_filter')) exit;

function fansub_upgrader_process_complete($upgrader, $options) {
    $type = fansub_get_value_by_key($options, 'type');
    switch($type) {
        case 'plugin':
            do_action('fansub_plugin_upgrader_process_complete', $upgrader, $options);
            break;
    }
}
add_action('upgrader_process_complete', 'fansub_upgrader_process_complete', 10, 2);

function fansub_plugin_upgrader_process_complete($upgrader, $options) {
    $plugins = fansub_get_value_by_key($options, 'plugins');
    if(!fansub_array_has_value($plugins)) {
        return;
    }
    foreach($plugins as $plugin) {
        $slug = fansub_get_plugin_slug_from_file_path($plugin);
        $transient_name = 'fansub_plugins_api_' . $slug . '_plugin_information';
        $transient_name = fansub_sanitize_id($transient_name);
        delete_transient($transient_name);
    }
}
add_action('fansub_plugin_upgrader_process_complete', 'fansub_plugin_upgrader_process_complete', 10, 2);