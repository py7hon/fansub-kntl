<?php
if(!function_exists('add_filter')) exit;
global $fansub_theme_license;

function fansub_theme_switched($new_name, $new_theme) {
    if(!current_user_can('switch_themes')) {
        return;
    }
    flush_rewrite_rules();
    do_action('fansub_theme_deactivation');
}
add_action('switch_theme', 'fansub_theme_switched', 10, 2);

function fansub_theme_after_switch($old_name, $old_theme) {
    if(!current_user_can('switch_themes')) {
        return;
    }
    update_option('fansub_version', FANSUB_VERSION);
    if(fansub_is_debugging() || fansub_is_localhost()) {
        fansub_update_permalink_struct('/%category%/%postname%.html');
    }
    flush_rewrite_rules();
    do_action('fansub_theme_activation');
}
add_action('after_switch_theme', 'fansub_theme_after_switch', 10, 2);

function fansub_setup_theme_data() {
    load_theme_textdomain('fansub', get_template_directory() . '/languages');
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus(
        array(
            'top' => __('Top menu', 'fansub'),
            'primary'   => __('Primary menu', 'fansub'),
            'secondary' => __('Secondary menu', 'fansub'),
            'mobile' => __('Mobile menu', 'fansub'),
            'footer' => __('Footer menu', 'fansub')
        )
    );
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'fansub_setup_theme_data');

function fansub_theme_hide_admin_bar() {
    if(!current_user_can('read')) {
        show_admin_bar(false);
    }
}
add_action('init', 'fansub_theme_hide_admin_bar');

function fansub_setup_theme_body_class($classes) {
    if(is_single() || is_page() || is_singular()) {
        $classes[] = 'fansub-single';
    }
    $classes[] = fansub_get_browser();
    if(!fansub_theme_license_valid(fansub_theme_get_license_defined_data())) {
        $classes[] = 'fansub-invalid-license';
    }
    if(is_user_logged_in()) {
        $classes[] = 'fansub-user';
        global $current_user;
        if(fansub_is_admin($current_user)) {
            $classes[] = 'fansub-user-admin';
        }
    }
    return $classes;
}
add_filter('body_class', 'fansub_setup_theme_body_class');

function fansub_setup_theme_content_width() {
    $GLOBALS['content_width'] = apply_filters('fansub_content_width', 640);
}
add_action('after_setup_theme', 'fansub_setup_theme_content_width', 0);

function fansub_setup_theme_widgets_init() {
    register_widget('FANSUB_Widget_Banner');
    register_widget('FANSUB_Widget_Facebook_Box');
    register_widget('FANSUB_Widget_Post');
    register_widget('FANSUB_Widget_Top_Commenter');
    $default_sidebars = array(
        'primary',
        'secondary',
        'footer'
    );
    $default_sidebars = apply_filters('fansub_theme_default_sidebars', $default_sidebars);
    if(in_array('primary', $default_sidebars)) {
        fansub_register_sidebar('primary', __('Primary sidebar', 'fansub'), __('Primary sidebar on your site.', 'fansub'));
    }
    if(in_array('secondary', $default_sidebars)) {
        fansub_register_sidebar('secondary', __('Secondary sidebar', 'fansub'), __('Secondary sidebar on your site.', 'fansub'));
    }
    if(in_array('footer', $default_sidebars)) {
        fansub_register_sidebar('footer', __('Footer widget area', 'fansub'), __('The widget area contains footer widgets.', 'fansub'), 'div');
    }
}
add_action('widgets_init', 'fansub_setup_theme_widgets_init');

function fansub_setup_theme_load_style_and_script($use) {
    global $pagenow;
    $current_page = fansub_get_current_admin_page();
    if('widgets.php' == $pagenow || 'post.php' == $pagenow || 'options-writing.php' == $pagenow || 'options-reading.php' == $pagenow) {
        $use = true;
    }
    return $use;
}
add_filter('fansub_use_admin_style_and_script', 'fansub_setup_theme_load_style_and_script');

function fansub_setup_theme_support_enqueue_media($use) {
    global $pagenow;
    $current_page = fansub_get_current_admin_page();
    if('widgets.php' == $pagenow || 'options-writing.php' == $pagenow || 'options-reading.php' == $pagenow) {
        $use = true;
    }
    return $use;
}
add_filter('fansub_wp_enqueue_media', 'fansub_setup_theme_support_enqueue_media');

function fansub_setup_theme_scripts() {
    if(fansub_use_jquery_cdn()) {
        fansub_load_jquery_from_cdn();
    }
    fansub_theme_register_lib_superfish();
    fansub_theme_register_lib_bootstrap();
    fansub_theme_register_lib_font_awesome();
    fansub_theme_register_core_style_and_script();
    $localize_object = array(
        'expand' => '<span class="screen-reader-text">' . esc_html__('expand child menu', 'fansub') . '</span>',
        'collapse' => '<span class="screen-reader-text">' . esc_html__('collapse child menu', 'fansub') . '</span>'
    );
    $localize_object = wp_parse_args($localize_object, fansub_theme_default_script_localize_object());
    if(fansub_is_debugging()) {
        wp_localize_script('fansub', 'fansub', $localize_object);
        wp_register_style('fansub-front-end-style', get_template_directory_uri() . '/fansub/css/fansub-front-end' . FANSUB_CSS_SUFFIX, array('fansub-style'));
        wp_register_script('fansub-front-end', get_template_directory_uri() . '/fansub/js/fansub-front-end' . FANSUB_JS_SUFFIX, array('fansub'), false, true);
        wp_register_style('fansub-custom-front-end-style', get_template_directory_uri() . '/css/fansub-custom-front-end' . FANSUB_CSS_SUFFIX, array('bootstrap-style', 'font-awesome-style', 'superfish-style', 'fansub-front-end-style'));
        wp_register_script('fansub-custom-front-end', get_template_directory_uri() . '/js/fansub-custom-front-end' . FANSUB_JS_SUFFIX, array('superfish', 'bootstrap', 'fansub-front-end'), false, true);
    } else {
        wp_register_style('fansub-custom-front-end-style', get_template_directory_uri() . '/css/fansub-custom-front-end' . FANSUB_CSS_SUFFIX, array('bootstrap-style', 'font-awesome-style', 'superfish-style'), FANSUB_THEME_VERSION);
        wp_register_script('fansub-custom-front-end', get_template_directory_uri() . '/js/fansub-custom-front-end' . FANSUB_JS_SUFFIX, array('superfish', 'bootstrap'), FANSUB_THEME_VERSION, true);
        wp_localize_script('fansub-custom-front-end', 'fansub', $localize_object);
    }
    wp_enqueue_style('fansub-custom-front-end-style');
    wp_enqueue_script('fansub-custom-front-end');
    if(is_singular()) {
        $post_id = get_the_ID();
        if(comments_open($post_id) && (bool)get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }
}
add_action('wp_enqueue_scripts', 'fansub_setup_theme_scripts');

function fansub_setup_theme_login_scripts() {
    fansub_theme_register_lib_bootstrap();
    fansub_theme_register_core_style_and_script();
    wp_register_style('fansub-login-style', get_template_directory_uri() . '/fansub/css/fansub-login' . FANSUB_CSS_SUFFIX, array('bootstrap-theme-style'), FANSUB_THEME_VERSION);
    wp_register_script('fansub-login', get_template_directory_uri() . '/fansub/js/fansub-login' . FANSUB_JS_SUFFIX, array('jquery', 'fansub'), FANSUB_THEME_VERSION, true);
    wp_localize_script('fansub', 'fansub', fansub_theme_default_script_localize_object());
    wp_enqueue_style('fansub-login-style');
    wp_enqueue_script('fansub-login');
}
add_action('login_enqueue_scripts', 'fansub_setup_theme_login_scripts');

function fansub_setup_theme_admin_scripts() {
    fansub_admin_enqueue_scripts();
}
add_action('admin_enqueue_scripts', 'fansub_setup_theme_admin_scripts');

function fansub_setup_theme_check_javascript_supported() {
    echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action('wp_head', 'fansub_setup_theme_check_javascript_supported', 99);

function fansub_setup_theme_admin_footer_text($text) {
    $text = sprintf(__('Thank you for creating with %s. Proudly powered by WordPress.'), '<a href="' . FANSUB_HOMEPAGE . '">fansub</a>');
    return '<span id="footer-thankyou">' . $text . '</span>';
}
add_filter('admin_footer_text', 'fansub_setup_theme_admin_footer_text', 99);

function fansub_setup_theme_update_footer($text) {
    $tmp = strtolower($text);
    if(fansub_string_contain($tmp, 'version')) {
        $text = sprintf(__('Theme core version %s', 'fansub'), FANSUB_THEME_VERSION);
    }
    return $text;
}
add_filter('update_footer', 'fansub_setup_theme_update_footer', 99);

function fansub_setup_theme_remove_editor_menu() {
    $remove = apply_filters('fansub_remove_theme_editor_menu', true);
    if($remove) {
        $current_page = isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        if('theme-editor.php' == $current_page) {
            wp_redirect(admin_url('/'));
            exit;
        }
        remove_submenu_page('themes.php', 'theme-editor.php');
    }
}
add_action('admin_init', 'fansub_setup_theme_remove_editor_menu');

function fansub_setup_theme_login_headerurl() {
    $url = home_url('/');
    $url = apply_filters('fansub_login_logo_url', $url);
    return $url;
}
add_filter('login_headerurl', 'fansub_setup_theme_login_headerurl');

function fansub_setup_theme_login_headertitle() {
    $desc = get_bloginfo('description');
    $desc = apply_filters('fansub_login_logo_description', $desc);
    return $desc;
}
add_filter('login_headertitle', 'fansub_setup_theme_login_headertitle');

function fansub_setup_theme_check_license() {
    if(!isset($_POST['submit']) && !fansub_is_login_page()) {
        if(!fansub_theme_license_valid(fansub_theme_get_license_defined_data()) || !has_action('fansub_check_license', 'fansub_theme_custom_check_license')) {
            fansub_theme_invalid_license_redirect();
        }
    }
}
add_action('fansub_check_license', 'fansub_setup_theme_check_license');

function fansub_setup_theme_invalid_license_message() {
    delete_transient('fansub_invalid_theme_license');
    $args = array(
        'error' => true,
        'title' => __('Error', 'fansub'),
        'text' => sprintf(__('Your theme is using an invalid license key! If you does not have one, please contact %1$s via email address %2$s for more information.', 'fansub'), '<strong>' . FANSUB_NAME . '</strong>', '<a href="mailto:' . esc_attr(FANSUB_EMAIL) . '">' . FANSUB_EMAIL . '</a>')
    );
    fansub_admin_notice($args);
    $theme = wp_get_theme();
    fansub_send_mail_invalid_license($theme->get('Name'));
}

function fansub_setup_theme_invalid_license_admin_notice() {
    if(false !== ($result = get_transient('fansub_invalid_theme_license')) && 1 == $result) {
        fansub_setup_theme_invalid_license_message();
    }
}
add_action('admin_notices', 'fansub_setup_theme_invalid_license_admin_notice');

function fansub_setup_theme_admin_bar_menu($wp_admin_bar) {
    $option = fansub_option_get_object_from_list('theme_setting');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'themes'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('theme_license');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'themes'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('theme_custom_css');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'themes'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('theme_add_to_head');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'themes'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('theme_custom');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'themes'
        );
        $wp_admin_bar->add_node($args);
    }
    $args = array(
        'id' => 'users',
        'title' => __('Users', 'fansub'),
        'href' => admin_url('users.php'),
        'parent' => 'site-name'
    );
    $wp_admin_bar->add_node($args);
    $option = fansub_option_get_object_from_list('user_login');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'users'
        );
        $wp_admin_bar->add_node($args);
    }
    $args = array(
        'id' => 'options-general',
        'title' => __('Settings', 'fansub'),
        'href' => admin_url('options-general.php'),
        'parent' => 'site-name'
    );
    $wp_admin_bar->add_node($args);
    $option = fansub_option_get_object_from_list('option_social');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'options-general'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('option_smtp_email');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'options-general'
        );
        $wp_admin_bar->add_node($args);
    }
    $option = fansub_option_get_object_from_list('optimize');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'options-general'
        );
        $wp_admin_bar->add_node($args);
    }
    $args = array(
        'id' => 'options-writing',
        'title' => __('Writing', 'fansub'),
        'href' => admin_url('options-writing.php'),
        'parent' => 'options-general'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'options-reading',
        'title' => __('Reading', 'fansub'),
        'href' => admin_url('options-reading.php'),
        'parent' => 'options-general'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'options-discussion',
        'title' => __('Discussion', 'fansub'),
        'href' => admin_url('options-discussion.php'),
        'parent' => 'options-general'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'options-permalink',
        'title' => __('Permalinks', 'fansub'),
        'href' => admin_url('options-permalink.php'),
        'parent' => 'options-general'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'list-posts',
        'title' => __('Posts', 'fansub'),
        'href' => admin_url('edit.php'),
        'parent' => 'site-name'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'plugins',
        'title' => __('Plugins', 'fansub'),
        'href' => admin_url('plugins.php'),
        'parent' => 'site-name'
    );
    $wp_admin_bar->add_node($args);
    $args = array(
        'id' => 'tools',
        'title' => __('Tools', 'fansub'),
        'href' => admin_url('tools.php'),
        'parent' => 'site-name'
    );
    $wp_admin_bar->add_node($args);
    $option = fansub_option_get_object_from_list('maintenance');
    if(fansub_object_valid($option) && current_user_can($option->get_capability())) {
        $args = array(
            'id' => fansub_sanitize_id($option->get_menu_slug()),
            'title' => $option->get_menu_title(),
            'href' => $option->get_page_url(),
            'parent' => 'tools'
        );
        $wp_admin_bar->add_node($args);
    }
    if(fansub_plugin_wpsupercache_installed()) {
        $args = array(
            'id' => 'wpsupercache-content',
            'title' => __('Delete cache', 'fansub'),
            'href' => admin_url('options-general.php?page=wpsupercache&tab=contents#listfiles'),
            'parent' => 'site-name'
        );
        $wp_admin_bar->add_node($args);
    }
}
if(!is_admin()) add_action('admin_bar_menu', 'fansub_setup_theme_admin_bar_menu');

function fansub_setup_theme_language_attributes($output) {
    if(!is_admin()) {
        if('vi' == fansub_get_language()) {
            $output = 'lang="vi"';
        }
    }
    return $output;
}
add_filter('language_attributes', 'fansub_setup_theme_language_attributes');

function fansub_setup_theme_wpseo_locale($locale) {
    if(!is_admin()) {
        if('vi' == fansub_get_language()) {
            $locale = 'vi';
        }
    }
    return $locale;
}
add_filter('wpseo_locale', 'fansub_setup_theme_wpseo_locale');

function fansub_setup_theme_wpseo_meta_box_priority() {
    return 'low';
}
add_filter('wpseo_metabox_prio', 'fansub_setup_theme_wpseo_meta_box_priority');

function fansub_setup_theme_default_hidden_meta_boxes($hidden, $screen) {
    if('post' == $screen->base) {
        $defaults = array('slugdiv', 'trackbacksdiv', 'postcustom', 'postexcerpt', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv');
        $hidden = wp_parse_args($hidden, $defaults);
    }
    return $hidden;
}
add_filter('default_hidden_meta_boxes', 'fansub_setup_theme_default_hidden_meta_boxes', 10, 2);

function fansub_theme_pre_ping(&$links) {
    $home = get_option('home');
    foreach($links as $l => $link) {
        if(0 === strpos($link, $home)) {
            unset($links[$l]);
        }
    }
}
add_action('pre_ping', 'fansub_theme_pre_ping');

function fansub_theme_intermediate_image_sizes_advanced($sizes) {
    if(isset($sizes['thumbnail'])) {
        unset($sizes['thumbnail']);
    }
    if(isset($sizes['medium'])) {
        unset($sizes['medium']);
    }
    if(isset($sizes['large'])) {
        unset($sizes['large']);
    }
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'fansub_theme_intermediate_image_sizes_advanced');

function fansub_theme_on_upgrade() {
    $version = get_option('fansub_version');
    if(version_compare($version, FANSUB_VERSION, '<')) {
        update_option('fansub_version', FANSUB_VERSION);
        do_action('fansub_theme_upgrade');
    }
}
add_action('admin_init', 'fansub_theme_on_upgrade');

function fansub_theme_update_rewrite_rules() {
    flush_rewrite_rules();
}
add_action('fansub_theme_upgrade', 'fansub_theme_update_rewrite_rules');
add_action('fansub_theme_activation', 'fansub_theme_update_rewrite_rules');
add_action('fansub_change_domain', 'fansub_theme_update_rewrite_rules');

function fansub_setup_theme_esc_comment_author_url($commentdata) {
    $comment_author_url = fansub_get_value_by_key($commentdata, 'comment_author_url');
    if(!empty($comment_author_url)) {
        $commentdata['comment_author_url'] = esc_url(fansub_get_root_domain_name($comment_author_url));
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'fansub_setup_theme_esc_comment_author_url');