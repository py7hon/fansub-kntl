<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'plugins.php';

$option = new FANSUB_Option(__('Recommended Plugins', 'fansub'), 'fansub_recommended_plugin');
$option->set_parent_slug($parent_slug);
$option->set_is_option_page(false);
$option->set_menu_title(__('Recommended', 'fansub'));
$option->set_use_style_and_script(true);
$option->init();
fansub_option_add_object_to_list($option);

function fansub_option_page_recommended_plugin_content() {
    $base_url = 'plugins.php?page=fansub_recommended_plugin';
    $current_tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'installed';
    $tabs = array(
        'installed' => __('Installed', 'fansub'),
        'activated' => __('Activated', 'fansub'),
        'required' => __('Required', 'fansub'),
        'recommended' => __('Recommended', 'fansub')
    );
    $plugins = array();
    switch($current_tab) {
        case 'required':
            $defaults = fansub_recommended_plugins();
            $lists = fansub_get_value_by_key($defaults, 'required');
            foreach($lists as $key => $data) {
                $slug = fansub_get_plugin_slug_from_file_path($data);
                $plugins[$slug] = $data;
            }
            break;
        case 'installed':
            $lists = fansub_get_installed_plugins();
            foreach($lists as $key => $data) {
                $slug = fansub_get_plugin_slug_from_file_path($key);
                $plugins[$slug] = $key;
            }
            break;
        case 'activated':
            $lists = get_option('active_plugins');
            foreach($lists as $key => $data) {
                $slug = fansub_get_plugin_slug_from_file_path($data);
                $plugins[$slug] = $data;
            }
            break;
        case 'recommended':
            $defaults = fansub_recommended_plugins();
            $lists = fansub_get_value_by_key($defaults, 'recommended');
            foreach($lists as $key => $data) {
                $slug = fansub_get_plugin_slug_from_file_path($data);
                $plugins[$slug] = $data;
            }
            break;
    }
    ?>
    <div class="wp-filter">
        <ul class="filter-links">
            <?php foreach($tabs as $id => $text) : ?>
                <?php
                $url = add_query_arg(array('tab' => $id), $base_url);
                $link_class = '';
                if($id == $current_tab) {
                    fansub_add_string_with_space_before($link_class, 'current');
                }
                ?>
                <li class="plugin-install-<?php echo $id; ?>">
                    <a class="<?php echo $link_class; ?>" data-tab="<?php echo $id; ?>" href="<?php echo $url ?>"><?php echo $text; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <br class="clear">
    <p>Plugins extend and expand the functionality of WordPress. You may automatically install plugins from the <a href="https://wordpress.org/plugins/">WordPress Plugin Directory</a> or upload a plugin in .zip format via <a href="<?php echo admin_url('plugin-install.php?tab=upload'); ?>">this page</a>.</p>
    <div id="the-list" class="widefat">
        <?php
        $plugin_items = array();
        foreach($plugins as $key => $data) {
            $plugin_information = fansub_plugins_api_get_information(array('slug' => $key));
            $plugin_items[$data] = $plugin_information;
        }
        $plugins_allowedtags = array(
            'a' => array('href' => array(),'title' => array(), 'target' => array()),
            'abbr' => array('title' => array()),'acronym' => array('title' => array()),
            'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
            'ul' => array(), 'ol' => array(), 'li' => array(), 'p' => array(), 'br' => array()
        );
        ?>
        <?php
        foreach($plugin_items as $key => $plugin) {
            fansub_loop_plugin_card($plugin, $plugins_allowedtags, $key);
        }
        ?>
    </div>
    <?php
}
add_action('fansub_option_page_' . $option->get_option_name_no_prefix() . '_content', 'fansub_option_page_recommended_plugin_content');