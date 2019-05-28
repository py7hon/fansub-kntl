<?php
/*
Plugin Name: Fansub KNTL
Plugin URI: http://github.com/py7hon/fansub-kntl
Description: You Know KONTOL ?
Author: Iqbal Rifai
Version: 0.1.4
Author URI: http://github.com/py7hon
Text Domain: fansub-kntl
Domain Path: /languages/
*/
if(!function_exists('add_filter')) exit;

define('FANSUB_KNTL_VERSION', '2.3.1');

define('FANSUB_KNTL_FILE', __FILE__);

define('FANSUB_KNTL_PATH', untrailingslashit(plugin_dir_path(FANSUB_KNTL_FILE)));

define('FANSUB_KNTL_URL', plugins_url('', FANSUB_KNTL_FILE));

define('FANSUB_KNTL_INC_PATH', FANSUB_KNTL_PATH . '/inc');

define('FANSUB_KNTL_CUSTOM_PATH', FANSUB_KNTL_PATH . '/custom');

define('FANSUB_KNTL_BASENAME', plugin_basename(FANSUB_KNTL_FILE));

define('FANSUB_KNTL_DIRNAME', dirname(FANSUB_KNTL_BASENAME));

define('FANSUB_KNTL_OPTION_NAME', 'fansub_kntl');

define('FANSUB_KNTL_SETTINGS_URL', 'admin.php?page=' . FANSUB_KNTL_OPTION_NAME);

require_once(FANSUB_KNTL_PATH . '/load.php');