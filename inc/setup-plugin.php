<?php
if(!function_exists('add_filter')) exit;

if(!has_action('init', 'fansub_session_start')) {
    add_action('init', 'fansub_session_start');
}

function fansub_kntl_get_option_defaults() {
    $show_page = get_page_by_title('shows');
    $defaults = array(
        'posts_per_page' => 20,
        'date_format' => 'm/d',
        'show_more_text' => 'Show More',
        'loading_text' => 'Loading...',
        'clear_text' => 'Clear',
        'refresh_text' => 'Refresh',
        'qualities' => '480p,720p,1080p',
        'servers' => 'Magnet,Torrent,FF,UL,TF,UR',
        'post_type_name' => 'animation',
        'post_type_label_name' => 'Animations',
        'post_type_label_singular_name' => 'Animation',
        'release_box_title' => 'New Release',
        'release_box_search_placeholder' => 'Search (e.g. Fate 01)',
        'single_batch_title' => 'Download Batches',
        'single_batch_tip' => 'Tip: Batches usually contain the latest version of the subs, so grab these!',
        'single_batch_none' => 'There are no batches for this show yet.',
        'single_episode_none' => 'There are no episodes for this show yet.',
        'search_none_text' => 'Nothing found!',
        'single_episode_title' => 'Download Single Episodes',
        'single_search_placeholder' => 'Filter by episode (e.g. 01)',
        'reached_end_text' => 'You\'ve reached the end.',
        'single_page' => ''
    );
    if(is_a($show_page, 'WP_Post')) {
        $defaults['single_page'] = $show_page->ID;
    }
    $defaults = apply_filters(FANSUB_KNTL_OPTION_NAME . '_option_defaults', $defaults);
    return $defaults;
}

function fansub_kntl_get_option() {
    $defaults = fansub_kntl_get_option_defaults();
    $option = get_option(FANSUB_KNTL_OPTION_NAME);
    if(!fansub_array_has_value($option)) {
        $option = array();
    }
    $option = wp_parse_args($option, $defaults);
    return apply_filters(FANSUB_KNTL_OPTION_NAME . '_options', $option);
}

function fansub_kntl_get_license_defined_data() {
    global $fansub_kntl_license_data;
    $fansub_kntl_license_data = fansub_sanitize_array($fansub_kntl_license_data);
    return apply_filters('fansub_kntl_license_defined_data', $fansub_kntl_license_data);
}

function fansub_kntl_license_valid() {
    global $fansub_kntl_license, $fansub_kntl_license_valid;

    if(!fansub_object_valid($fansub_kntl_license)) {
        $fansub_kntl_license = new FANSUB_License();
        $fansub_kntl_license->set_type('plugin');
        $fansub_kntl_license->set_use_for(FANSUB_KNTL_BASENAME);
        $fansub_kntl_license->set_option_name(FANSUB_PLUGIN_LICENSE_OPTION_NAME);
    }

    $fansub_kntl_license_valid = $fansub_kntl_license->check_valid(fansub_kntl_get_license_defined_data());
    return $fansub_kntl_license_valid;
}

$GLOBALS['fansub_kntl_license_valid'] = true;

function fansub_kntl_activation() {
    if(!current_user_can('activate_plugins')) {
        return;
    }
    flush_rewrite_rules();
    do_action('fansub_kntl_activation');
}
register_activation_hook(FANSUB_KNTL_FILE, 'fansub_kntl_activation');

function fansub_kntl_deactivation() {
    if(!current_user_can('activate_plugins')) {
        return;
    }
    flush_rewrite_rules();
    do_action('fansub_kntl_deactivation');
}
register_deactivation_hook(FANSUB_KNTL_FILE, 'fansub_kntl_deactivation');

function fansub_kntl_settings_link($links) {
    $settings_link = sprintf('<a href="' . FANSUB_KNTL_SETTINGS_URL . '">%s</a>', __('Settings', 'fansub-kntl'));
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . FANSUB_KNTL_BASENAME, 'fansub_kntl_settings_link');

function fansub_kntl_textdomain() {
    load_plugin_textdomain('fansub-kntl', false, FANSUB_KNTL_DIRNAME . '/languages/');
}
add_action('plugins_loaded', 'fansub_kntl_textdomain');

function fansub_kntl_admin_bar_menu($wp_admin_bar) {
    $args = array(
        'id' => 'plugin-license',
        'title' => __('Plugin Licenses', 'fansub-kntl'),
        'href' => FANSUB_PLUGIN_LICENSE_ADMIN_URL,
        'parent' => 'plugins'
    );
    $wp_admin_bar->add_node($args);
}
if(!is_admin()) add_action('admin_bar_menu', 'fansub_kntl_admin_bar_menu', 99);

function fansub_kntl_check_license() {
    if(!isset($_POST['submit']) && !fansub_is_login_page()) {
        if(!fansub_kntl_license_valid()) {
            if(!is_admin() && current_user_can('manage_options')) {
                wp_redirect(FANSUB_PLUGIN_LICENSE_ADMIN_URL);
                exit;
            }
            add_action('admin_notices', 'fansub_kntl_invalid_license_notice');
        }
    }
}
add_action('fansub_check_license', 'fansub_kntl_check_license');

function fansub_kntl_invalid_license_notice() {
    $plugin_name = fansub_get_plugin_name(FANSUB_KNTL_FILE, FANSUB_KNTL_BASENAME);
    $plugin_name = fansub_wrap_tag($plugin_name, 'strong');
    $args = array(
        'error' => true,
        'title' => __('Error', 'fansub-kntl'),
        'text' => sprintf(__('Plugin %1$s is using an invalid license key! If you does not have one, please contact %2$s via email address %3$s for more information.', 'fansub-kntl'), $plugin_name, '<strong>' . FANSUB_NAME . '</strong>', '<a href="mailto:' . esc_attr(FANSUB_EMAIL) . '">' . FANSUB_EMAIL . '</a>')
    );
    fansub_admin_notice($args);
}

function fansub_kntl_enqueue_scripts() {
    fansub_register_core_style_and_script();
    $localize_object = fansub_default_script_localize_object();
    if(fansub_is_debugging()) {
        wp_localize_script('fansub', 'fansub', $localize_object);
        wp_register_script('fansub-front-end', FANSUB_URL . '/js/fansub-front-end' . FANSUB_JS_SUFFIX, array('fansub'), false, true);
        wp_register_script('fansub-kntl', FANSUB_KNTL_URL . '/js/fansub-plugin' . FANSUB_JS_SUFFIX, array('fansub-front-end'), false, true);
    } else {
        wp_register_script('fansub-kntl', FANSUB_KNTL_URL . '/js/fansub-plugin' . FANSUB_JS_SUFFIX, array(), FANSUB_KNTL_VERSION, true);
        wp_localize_script('fansub-kntl', 'fansub', $localize_object);
    }
    wp_register_style('fansub-kntl-style', FANSUB_KNTL_URL . '/css/fansub-plugin' . FANSUB_CSS_SUFFIX, array(), FANSUB_KNTL_VERSION);
    wp_enqueue_style('fansub-kntl-style');
    wp_enqueue_script('fansub-kntl');
}
add_action('wp_enqueue_scripts', 'fansub_kntl_enqueue_scripts');

function fansub_kntl_admin_style_and_script() {
    fansub_register_core_style_and_script();
    wp_register_style('fansub-admin-style', FANSUB_URL . '/css/fansub-admin'. FANSUB_CSS_SUFFIX, array('fansub-style'), FANSUB_KNTL_VERSION);
    wp_register_script('fansub-admin', FANSUB_URL . '/js/fansub-admin' . FANSUB_JS_SUFFIX, array('jquery', 'fansub'), FANSUB_KNTL_VERSION, true);
    wp_register_style('fansub-kntl-style', FANSUB_KNTL_URL . '/css/fansub-plugin-admin' . FANSUB_CSS_SUFFIX, array('fansub-admin-style'), FANSUB_KNTL_VERSION);
    wp_register_script('fansub-kntl', FANSUB_KNTL_URL . '/js/fansub-plugin-admin' . FANSUB_JS_SUFFIX, array('fansub-admin'), FANSUB_KNTL_VERSION, true);
    wp_localize_script('fansub-kntl', 'fansub', fansub_default_script_localize_object());
    wp_enqueue_style('fansub-kntl-style');
    wp_enqueue_script('fansub-kntl');
}
add_action('admin_enqueue_scripts', 'fansub_kntl_admin_style_and_script');

function fansub_kntl_admin_init_hook() {
    $plugin_base_name = md5(FANSUB_KNTL_BASENAME);
    $option_name = 'plugin_' . $plugin_base_name . '_version';
    $version = get_option($option_name);
    if($version != FANSUB_KNTL_VERSION) {
        update_option($option_name, FANSUB_KNTL_VERSION);
        flush_rewrite_rules();
    }
}
add_action('admin_init', 'fansub_kntl_admin_init_hook');