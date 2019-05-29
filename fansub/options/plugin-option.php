<?php
if(!function_exists('add_filter')) exit;

global $fansub_plugin_option, $fansub_pos_tabs;

$fansub_plugin_option = new FANSUB_Option(__('Plugin Options', 'fansub'), 'fansub_plugin_option');
$fansub_plugin_option->set_parent_slug('');
$fansub_plugin_option->set_icon_url('dashicons-admin-generic');
$fansub_plugin_option->set_position(66);
$fansub_plugin_option->set_use_style_and_script(true);
$fansub_plugin_option->init();

function fansub_plugin_remove_option_submenu_page() {
    remove_submenu_page('fansub_plugin_option', 'fansub_plugin_option');
}
add_action('admin_menu', 'fansub_plugin_remove_option_submenu_page', 99);

function fansub_plugin_redirect_option_page() {
    $page = fansub_get_current_admin_page();
    if('fansub_plugin_option' == $page) {
        $base_url = admin_url('admin.php');
        $base_url = add_query_arg('page', 'fansub_plugin_license', $base_url);
        wp_redirect($base_url);
        exit;
    }
}
add_action('admin_init', 'fansub_plugin_redirect_option_page');

require(HOCWP_PATH . '/options/setting-plugin-license.php');
require(HOCWP_PATH . '/options/setting-plugin-custom-css.php');