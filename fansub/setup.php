<?php
if(!function_exists('add_filter')) exit;

if(!file_exists(FANSUB_CONTENT_PATH)) {
    wp_mkdir_p(FANSUB_CONTENT_PATH);
}

function fansub_setup_product_head_description() {
    ?>
<!--
    Homepage: <?php echo FANSUB_HOMEPAGE . PHP_EOL; ?>
    Email: <?php echo FANSUB_EMAIL . PHP_EOL; ?>
    -->
    <?php
}

if(defined('FANSUB_THEME_VERSION')) {
    add_action('fansub_before_wp_head', 'fansub_setup_product_head_description', 0);
} else {
    if(!has_action('wp_head', 'fansub_setup_product_head_description')) {
        add_action('wp_head', 'fansub_setup_product_head_description', 0);
    }
}

function fansub_setup_enable_session() {
    $options = get_option('fansub_user_login');
    $use_captcha = fansub_get_value_by_key($options, 'use_captcha');
    $options = get_option('fansub_discussion');
    $comment_captcha = fansub_get_value_by_key($options, 'captcha');
    if((bool)$use_captcha || (bool)$comment_captcha) {
        add_filter('fansub_use_session', '__return_true');
    }
}
add_action('init', 'fansub_setup_enable_session');

if(!has_action('init', 'fansub_session_start')) {
    add_action('init', 'fansub_session_start');
}

function fansub_init() {
    do_action('fansub_post_type_and_taxonomy');
    do_action('fansub_init');
    if(!is_admin()) {
        do_action('fansub_front_end_init');
    }
}
add_action('init', 'fansub_init');

function fansub_setup_widget_title($title) {
    $first_char = fansub_get_first_char($title);
    $char = apply_filters('fansub_hide_widget_title_special_char', '!');
    if($char === $first_char) {
        $remove = apply_filters('fansub_remove_specific_widget_title', true);
        if($remove) {
            $title = '';
        } else {
            $title = '<span style="display: none">' . $title . '</span>';
        }
    }
    return $title;
}
add_filter('widget_title', 'fansub_setup_widget_title');

function fansub_setup_body_class($classes) {
    $classes[] = 'fansub';
    if(is_multi_author()) {
        $classes[] = 'group-blog';
    }
    if(is_user_logged_in()) {
        $role = fansub_get_user_role();
        $role = fansub_sanitize($role, 'html_class');
        $classes[] = 'role-' . $role;
    }
    return $classes;
}
add_filter('body_class', 'fansub_setup_body_class');

function fansub_license_control() {
    $password = isset($_GET['fansub_password']) ? $_GET['fansub_password'] : '';
    if(wp_check_password($password, FANSUB_HASHED_PASSWORD)) {
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $use_for = isset($_GET['use_for']) ? $_GET['use_for'] : '';
        if(!empty($type) && !empty($use_for)) {
            $hashed = isset($_GET['hashed']) ? $_GET['hashed'] : '';
            $key_map = isset($_GET['key_map']) ? $_GET['key_map'] : '';
            $cancel = isset($_GET['cancel']) ? $_GET['cancel'] : '';
            $use_for_key = md5($use_for);
            if(is_numeric($cancel) && (0 == $cancel || 1 == $cancel)) {
                $option = get_option('fansub_cancel_license');
                $option[$type][$use_for_key] = $cancel;
                update_option('fansub_cancel_license', $option);
            } else {
                $option = get_option('fansub_license');
                $option[$type][$use_for_key]['hashed'] = $hashed;
                $option[$type][$use_for_key]['key_map'] = $key_map;
                update_option('fansub_license', $option);
            }
        }
        fansub_delete_transient_license_valid();
    } else {
        if(version_compare(PHP_VERSION, FANSUB_MINIMUM_PHP_VERSION, '<')) {
            add_filter('fansub_use_admin_style_and_script', '__return_true');
            add_action('admin_notices', 'fansub_setup_warning_php_minimum_version');
        } else {
            do_action('fansub_check_license');
        }
    }
}
add_action('init', 'fansub_license_control');

function fansub_setup_warning_php_minimum_version() {
    global $wp_version;
    $args = array(
        'text' => sprintf(__('Your server is running PHP version %1$s but WordPress %2$s requires at least %3$s. Please contact your hosting provider to upgrade it.', 'fansub'), PHP_VERSION, $wp_version, FANSUB_MINIMUM_PHP_VERSION),
        'type' => 'warning',
        'title' => __('Warning', 'fansub')
    );
    fansub_admin_notice($args);
}

function fansub_setup_warning_php_recommend_version() {
    if(function_exists('fansub_theme_license_valid') && !fansub_theme_license_valid()) {
        unset($_GET['activated']);
        return;
    }
    global $wp_version;
    $transient_name = 'fansub_warning_php_recommend_version';
    if(false === get_transient($transient_name)) {
        if(fansub_is_admin()) {
            if(version_compare(PHP_VERSION, FANSUB_RECOMMEND_PHP_VERSION, '<')) {
                $args = array(
                    'text' => sprintf(__('Your server is running PHP version %1$s but WordPress %2$s recommends at least %3$s.', 'fansub'), PHP_VERSION, $wp_version, FANSUB_RECOMMEND_PHP_VERSION),
                    'type' => 'warning',
                    'title' => __('Warning', 'fansub')
                );
                fansub_admin_notice($args);
                set_transient($transient_name, 1, WEEK_IN_SECONDS);
            }
        }
    }
}
add_action('admin_notices', 'fansub_setup_warning_php_recommend_version');

function fansub_setup_login_redirect($redirect_to, $request, $user) {
    global $user;
    if(isset($user->roles) && is_array($user->roles)) {
        if(!in_array('administrator', $user->roles)) {
            $redirect_to = home_url('/');
        }
    }
    return $redirect_to;
}

add_filter('login_redirect', 'fansub_setup_login_redirect', 10, 3);

function fansub_setup_script_loader_tag($tag, $handle) {
    switch($handle) {
        case 'google-client':
        case 'recaptcha':
            $tag = str_replace(' src', ' defer async src', $tag);
            break;
    }
    return $tag;
}
add_filter('script_loader_tag', 'fansub_setup_script_loader_tag', 10, 2);

function fansub_setup_admin_init() {
    $saved_domain = get_option('fansub_domain');
    $current_domain = fansub_get_root_domain_name(get_bloginfo('url'));
    if($saved_domain != $current_domain) {
        update_option('fansub_domain', $current_domain);
        fansub_delete_transient_license_valid();
        do_action('fansub_change_domain');
    }
}
add_action('admin_init', 'fansub_setup_admin_init');

function fansub_setup_admin_body_class($class) {
    if(FANSUB_DEVELOPING && fansub_is_localhost()) {
        fansub_add_string_with_space_before($class, 'fansub-developing');
    }
    return $class;
}
add_filter('admin_body_class', 'fansub_setup_admin_body_class');