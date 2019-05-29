<?php

if(!function_exists('add_filter')) exit;

if(defined('FANSUB_PATH')) {
    return;
}

define('FANSUB_VERSION', '3.4.0');

define('FANSUB_PATH', dirname(__FILE__));

define('FANSUB_CONTENT_PATH', WP_CONTENT_DIR . '/fansub');

define('FANSUB_NAME', 'Fansub KNTL');

define('FANSUB_EMAIL', 'iqbalrifai@waifu.club');

define('FANSUB_HOMEPAGE', 'http://github.com/py7hon/fansub-kntl');

define('FANSUB_DEVELOPING', ((defined('WP_DEBUG') && true === WP_DEBUG) ? true : false));

define('FANSUB_CSS_SUFFIX', (FANSUB_DEVELOPING) ? '.css' : '.min.css');

define('FANSUB_JS_SUFFIX', (FANSUB_DEVELOPING) ? '.js' : '.min.js');

define('FANSUB_DOING_AJAX', ((defined('DOING_AJAX') && true === DOING_AJAX) ? true : false));

define('FANSUB_DOING_CRON', ((defined('DOING_CRON') && true === DOING_CRON) ? true : false));

define('FANSUB_DOING_AUTO_SAVE', ((defined('DOING_AUTOSAVE') && true === DOING_AUTO_SAVE) ? true : false));

define('FANSUB_MINIMUM_JQUERY_VERSION', '1.9.1');

define('FANSUB_JQUERY_LATEST_VERSION', '1.11.4');

define('FANSUB_TINYMCE_VERSION', '4');

define('FANSUB_BOOTSTRAP_LATEST_VERSION', '3.3.6');

define('FANSUB_FONTAWESOME_LATEST_VERSION', '4.6.1');

define('FANSUB_SUPERFISH_LATEST_VERSION', '1.7.8');

if(!defined('FANSUB_MINIMUM_PHP_VERSION')) {
    define('FANSUB_MINIMUM_PHP_VERSION', '5.2.4');
}

if(!defined('FANSUB_RECOMMEND_PHP_VERSION')) {
    define('FANSUB_RECOMMEND_PHP_VERSION', '5.6');
}

define('FANSUB_HASHED_PASSWORD', '$P$Bj8RQOu1MNcgkC3c3Vl9EOugiXdg951');

define('FANSUB_REQUIRED_HTML', '<span style="color:#FF0000">*</span>');

define('FANSUB_PLUGIN_LICENSE_OPTION_NAME', 'fansub_plugin_licenses');

define('FANSUB_PLUGIN_LICENSE_ADMIN_URL', admin_url('admin.php?page=fansub_plugin_license'));

define('FANSUB_FACEBOOK_GRAPH_API_VERSION', '2.5');

require(FANSUB_PATH . '/lib/bfi-thumb/BFI_Thumb.php');

require(FANSUB_PATH . '/functions.php');

require(FANSUB_PATH . '/setup.php');

function fansub_autoload($class_name) {
    $base_path = FANSUB_PATH;
    $pieces = explode('_', $class_name);
    $pieces = array_filter($pieces);
    $first_piece = current($pieces);
    if('FANSUB' !== $class_name && 'FANSUB' !== $first_piece) {
        return;
    }
    if(false !== strrpos($class_name, 'FANSUB_Widget')) {
        $base_path .= '/widgets';
    }
    $file = $base_path . '/class-' . fansub_sanitize_file_name($class_name);
    $file .= '.php';
    if(file_exists($file)) {
        require($file);
    }
}

spl_autoload_register('fansub_autoload');

//require(FANSUB_PATH . '/text.php');

require(FANSUB_PATH . '/lib.php');

require(FANSUB_PATH . '/tools.php');

require(FANSUB_PATH . '/utils.php');

//require(FANSUB_PATH . '/shortcode.php');

require(FANSUB_PATH . '/query.php');

require(FANSUB_PATH . '/users.php');

//require(FANSUB_PATH . '/mail.php');

require(FANSUB_PATH . '/html-field.php');

//require(FANSUB_PATH . '/wordpress-seo.php');

require(FANSUB_PATH . '/option.php');

if(fansub_has_plugin_activated()) {
    require(FANSUB_PATH . '/options/plugin-option.php');
}

//require(FANSUB_PATH . '/theme-switcher.php');

require(FANSUB_PATH . '/post.php');

//require(FANSUB_PATH . '/media.php');

//require(FANSUB_PATH . '/statistics.php');

//require(FANSUB_PATH . '/term.php');

require(FANSUB_PATH . '/meta.php');

//require(FANSUB_PATH . '/term-meta.php');

//require(FANSUB_PATH . '/login.php');

//require(FANSUB_PATH . '/comment.php');

require(FANSUB_PATH . '/pagination.php');

require(FANSUB_PATH . '/back-end.php');

require(FANSUB_PATH . '/front-end.php');

//require(FANSUB_PATH . '/ads.php');

//require(FANSUB_PATH . '/video.php');

//require(FANSUB_PATH . '/woocommerce.php');

//require(FANSUB_PATH . '/shop.php');

//require(FANSUB_PATH . '/coupon.php');

//require(FANSUB_PATH . '/classifieds.php');

require(FANSUB_PATH . '/ajax.php');

require(FANSUB_PATH . '/options/setting-tool-developer.php');