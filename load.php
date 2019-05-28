<?php
if(!function_exists('add_filter')) exit;

if(!defined('FANSUB_PLUGIN_CORE_VERSION')) {
    define('FANSUB_PLUGIN_CORE_VERSION', '1.0.1');
}

$path = get_template_directory() . '/fansub/load.php';

function fansub_kntl_missing_core_notice() {
    $plugin_data = get_plugin_data(FANSUB_KNTL_FILE);
    ?>
    <div class="updated notice settings-error error">
        <p><strong><?php _e('Error:', 'fansub-kntl'); ?></strong> <?php printf(__('Plugin %s cannot be run properly because of missing core.', 'fansub-kntl'), '<strong>' . $plugin_data['Name'] . '</strong>'); ?></p>
    </div>
    <?php
}

if(!defined('FANSUB_URL')) {
    if(file_exists($path)) {
        define('FANSUB_URL', untrailingslashit(get_template_directory_uri()) . '/fansub');
    } else {
        define('FANSUB_URL', untrailingslashit(FANSUB_KNTL_URL) . '/fansub');
    }
}

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-pre-hook.php');

if(!defined('FANSUB_PATH')) {
    if(!file_exists($path)) {
        $path = FANSUB_KNTL_PATH . '/fansub/load.php';
    }

    if(!file_exists($path)) {
        add_action('admin_notices', 'fansub_kntl_missing_core_notice');
        return;
    }

    require_once($path);
}

require_once(FANSUB_PATH . '/plugin-functions.php');

require_once(FANSUB_KNTL_INC_PATH . '/setup-plugin.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-functions.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-setup.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-admin.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/shortcode.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-meta.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-hook.php');

require_once(FANSUB_KNTL_CUSTOM_PATH . '/fansub-plugin-ajax.php');