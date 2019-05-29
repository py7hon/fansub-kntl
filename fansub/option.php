<?php
if(!function_exists('add_filter')) exit;

global $fansub_pos_tabs;

function fansub_option_get_list_object() {
    global $fansub_options;
    return $fansub_options;
}

function fansub_option_add_object_to_list(FANSUB_Option $option) {
    global $fansub_options;
    $option_name = $option->get_option_name_no_prefix();
    $fansub_options[$option_name] = $option;
}

function fansub_option_get_object_from_list($key) {
    global $fansub_options;
    return isset($fansub_options[$key]) ? $fansub_options[$key] : null;
}

function fansub_option_get_data($base_slug) {
    $data = array();
    $option = fansub_option_get_object_from_list($base_slug);
    if(fansub_object_valid($option)) {
        $data = $option->get();
    } else {
        $base_slug = str_replace('fansub_', '', $base_slug);
        $data = get_option('fansub_' . $base_slug);
    }
    return $data;
}

function fansub_get_option_post($option_name, $slug, $option_base = 'fansub_theme_setting') {
    $options = get_option($option_base);
    $post_id = fansub_get_value_by_key($options, $option_name);
    if(fansub_id_number_valid($post_id)) {
        $result = get_post($post_id);
    } else {
        $result = fansub_get_post_by_slug($slug);
    }
    if(!is_a($result, 'WP_Post')) {
        $result = new WP_Error();
    }
    return apply_filters('fansub_get_option_post', $result, $option_name, $slug, $option_base);
}

function fansub_get_option_page($option_name, $slug, $option_base = 'fansub_theme_setting', $template = '') {
    $page = fansub_get_option_post($option_name, $slug, $option_base);
    if(!is_a($page, 'WP_Post') && !empty($template)) {
        $pages = fansub_get_pages_by_template($template);
        if(fansub_array_has_value($pages)) {
            $page = current($pages);
        }
    }
    if(!is_a($page, 'WP_Post')) {
        $page = new WP_Error();
    }
    return apply_filters('fansub_get_option_page', $page, $option_name, $slug, $option_base, $template);
}

function fansub_option_get_value($base, $key) {
    $data = fansub_option_get_data($base);
    $base_slug = str_replace('fansub_', '', $base);
    $defaults = fansub_option_defaults();
    $defaults = fansub_get_value_by_key($defaults, $base_slug);
    if(fansub_array_has_value($defaults)) {
        $data = (array)$data;
        $data = wp_parse_args($data, $defaults);
    }
    if(!empty($key)) {
        $result = fansub_get_value_by_key($data, $key);
    } else {
        $result = $data;
    }
    return $result;
}

function fansub_get_date_format() {
    return get_option('date_format');
}

function fansub_get_option_by_name($base, $name = '') {
    return fansub_option_get_value($base, $name);
}

function fansub_get_reading_option($name = '') {
    return fansub_get_option_by_name('reading', $name);
}

function fansub_get_optimize_option($name = '') {
    return fansub_get_option_by_name('optimize', $name);
}

function fansub_get_thumbnail_size($name = 'thumbnail_small') {
    $width = 0;
    $height = 0;
    switch($name) {
        case 'thumbnail_small':
            $width = absint(get_option('thumbnail_size_w'));
            $height = absint(get_option('thumbnail_size_h'));
            break;
        case 'thumbnail_medium':
            $width = absint(get_option('medium_size_w'));
            $height = absint(get_option('medium_size_h'));
            break;
        case 'thumbnail_large':
            $width = absint(get_option('large_size_w'));
            $height = absint(get_option('large_size_h'));
            break;
    }
    $value = array($width, $height);
    return $value;
}

function fansub_option_add_setting_field($base, $args) {
    $option = fansub_option_get_object_from_list($base);
    if(fansub_object_valid($option)) {
        $id = isset($args['id']) ? $args['id'] : '';
        $name = isset($args['name']) ? $args['name'] : '';
        fansub_transmit_id_and_name($id, $name);
        $args['id'] = $option->get_field_id($id);
        $args['name'] = $option->get_field_name($name);
        if(!isset($args['value'])) {
            $args['value'] = $option->get_by_key($name);
        }
        $option->add_field($args);
    }
}

function fansub_option_add_setting_section($base, $args) {
    $option = fansub_option_get_object_from_list($base);
    if(fansub_object_valid($option)) {
        $id = isset($args['id']) ? $args['id'] : '';
        $title = isset($args['title']) ? $args['title'] : '';
        if(!empty($id) && !empty($title)) {
            $option->add_section($args);
        }
    }
}

function fansub_get_option($base_name) {
    $option = fansub_option_get_object_from_list($base_name);
    if(fansub_object_valid($option)) {
        return $option->get();
    }
    return array();
}

function fansub_add_option_page_smtp_email($parent_slug = null) {
    if(null != $parent_slug) {
        _deprecated_argument(__FUNCTION__, '2.7.4', __('Please do not use $parent_slug argument since core version 2.7.4 or later.', 'fansub'));
    }
    require(FANSUB_PATH . '/options/setting-smtp-email.php');
}

function fansub_get_google_api_key() {
    $key = fansub_option_get_value('option_social', 'google_api_key');
    $key = apply_filters('fansub_google_api_key', $key);
    return $key;
}

function fansub_get_google_client_id() {
    $clientid = fansub_option_get_value('option_social', 'google_client_id');
    $clientid = apply_filters('fansub_google_client_id', $clientid);
    return $clientid;
}

function fansub_get_footer_logo_url() {
    $result = fansub_theme_get_option('footer_logo');
    $result = fansub_sanitize_media_value($result);
    $result = $result['url'];
    return $result;
}

function fansub_option_defaults() {
    $defaults = array(
        'theme_custom' => array(
            'background_music' => array(
                'play_ons' => array(
                    'home' => __('Homepage', 'fansub'),
                    'single' => __('Single', 'fansub'),
                    'page' => __('Page', 'fansub'),
                    'archive' => __('Archive', 'fansub'),
                    'search' => __('Search', 'fansub'),
                    'all' => __('Play on whole page', 'fansub')
                ),
                'play_on' => 'home'
            )
        ),
        'optimize' => array(
            'use_jquery_cdn' => 1,
            'use_bootstrap' => 1,
            'use_bootstrap_cdn' => 1,
            'use_fontawesome' => 1,
            'use_fontawesome_cdn' => 1,
            'use_superfish' => 1,
            'use_superfish_cdn' => 1
        ),
        'social' => array(
            'order' => 'facebook,twitter,instagram,linkedin,myspace,pinterest,youtube,gplus,rss',
            'option_names' => array(
                'facebook' => 'facebook_site',
                'twitter' => 'twitter_site',
                'instagram' => 'instagram_url',
                'linkedin' => 'linkedin_url',
                'myspace' => 'myspace_url',
                'pinterest' => 'pinterest_url',
                'youtube' => 'youtube_url',
                'gplus' => 'google_plus_url',
                'rss' => 'rss_url'
            ),
            'icons' => array(
                'facebook' => 'fa-facebook',
                'twitter' => 'fa-twitter',
                'instagram' => 'fa-instagram',
                'linkedin' => 'fa-linkedin',
                'myspace' => 'fa-users',
                'pinterest' => 'fa-pinterest',
                'youtube' => 'fa-youtube',
                'gplus' => 'fa-google-plus',
                'rss' => 'fa-rss'
            )
        )
    );
    return apply_filters('fansub_option_defaults', $defaults);
}

function fansub_get_theme_required_plugins() {
    $required = array();
    $required = apply_filters('fansub_required_plugins', $required);
    return $required;
}

function fansub_recommended_plugins() {
    $required = fansub_get_theme_required_plugins();
    $defaults = array(
        'required' => $required,
        'recommended' => array(
            'wordpress-seo',
            'wp-super-cache',
            'wp-optimize',
            'wp-external-links',
            'syntaxhighlighter',
            'akismet',
            'google-analytics-for-wordpress',
            'updraftplus'
        )
    );
    return apply_filters('fansub_recommended_plugins', $defaults);
}

function fansub_plugin_option_page_header() {
    $core_version = defined('FANSUB_PLUGIN_CORE_VERSION') ? FANSUB_PLUGIN_CORE_VERSION : FANSUB_VERSION;
    ?>
    <div class="page-header">
        <h2 class="theme-name"><?php _e('Plugin Options', 'fansub'); ?></h2>
        <span class="theme-version fansub-version"><?php printf(__('Core Version: %s', 'fansub'), $core_version); ?></span>
    </div>
    <?php
}

function fansub_plugin_option_page_footer() {
    fansub_theme_option_form_after();
}

function fansub_plugin_option_page_sidebar() {
    global $fansub_pos_tabs;
    if(fansub_array_has_value($fansub_pos_tabs)) {
        $current_page = fansub_get_current_admin_page();
        ?>
        <ul class="list-tabs">
            <?php foreach($fansub_pos_tabs as $key => $value) : ?>
                <?php
                $admin_url = admin_url('admin.php');
                $admin_url = add_query_arg(array('page' => $key), $admin_url);
                $item_class = fansub_sanitize_html_class($key);
                if($key == $current_page) {
                    fansub_add_string_with_space_before($item_class, 'active');
                    $admin_url = 'javascript:;';
                }
                $text = fansub_get_value_by_key($value, 'text');
                if(empty($text)) {
                    continue;
                }
                ?>
                <li class="<?php echo $item_class; ?>"><a href="<?php echo $admin_url; ?>"><span><?php echo $text; ?></span></a></li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}

function fansub_theme_option_form_before() {
    global $fansub_theme_option;
    $theme = wp_get_theme();
    $name = $theme->get('Name');
    if(empty($name)) {
        $name = __('Unknown', 'fansub');
    }
    $version = $theme->get('Version');
    if(empty($version)) {
        $version = '1.0.0';
    }
    ?>
    <div class="page-header">
        <h2 class="theme-name"><?php echo $name; ?></h2>
        <span class="theme-version"><?php printf(__('Version: %s', 'fansub'), $version); ?></span>
    </div>
    <?php
}

function fansub_theme_option_form_after() {
    $fansub_root_domain = fansub_get_root_domain_name(FANSUB_HOMEPAGE);
    ?>
    <div class="page-footer">
        <p>Created by <?php echo $fansub_root_domain; ?>. If you have any questions, please send us an email via address: <em><?php echo FANSUB_EMAIL; ?></em></p>
    </div>
    <div class="copyright">
        <p>&copy; 2008 - <?php echo date('Y'); ?> <a target="_blank" href="<?php echo FANSUB_HOMEPAGE; ?>"><?php echo $fansub_root_domain; ?></a>. All Rights Reserved.</p>
    </div>
    <?php
}

function fansub_theme_option_sidebar_tab() {
    global $fansub_tos_tabs;
    if(fansub_array_has_value($fansub_tos_tabs)) {
        $current_page = fansub_get_current_admin_page();
        ?>
        <ul class="list-tabs">
            <?php foreach($fansub_tos_tabs as $key => $value) : ?>
                <?php
                $admin_url = admin_url('admin.php');
                $admin_url = add_query_arg(array('page' => $key), $admin_url);
                $item_class = fansub_sanitize_html_class($key);
                if($key == $current_page) {
                    fansub_add_string_with_space_before($item_class, 'active');
                    $admin_url = 'javascript:;';
                }
                $text = fansub_get_value_by_key($value, 'text');
                if(empty($text)) {
                    continue;
                }
                ?>
                <li class="<?php echo $item_class; ?>"><a href="<?php echo $admin_url; ?>"><span><?php echo $text; ?></span></a></li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}