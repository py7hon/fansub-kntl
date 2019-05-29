<?php
if(!function_exists('add_filter')) exit;

function fansub_dashboard_widget_loading() {
    $loading = '<p class="fansub-widget-loading widget-loading hide-if-no-js">' . __('Loading&#8230;', 'fansub') . '</p>';
    $loading .= '<p class="hide-if-js">' . __('This widget requires JavaScript.', 'fansub') . '</p>';
    return apply_filters('fansub_dashboard_widget_loading', $loading);
}

function fansub_dashboard_widget_cache($widget_id, $callback, $args = array()) {
    $loading = fansub_dashboard_widget_loading();
    $locale = get_locale();
    $cache_key = 'fansub_dashboard_' . md5($widget_id . '_' . $locale);
    if(false !== ($output = get_transient($cache_key)) && !empty($output)) {
        echo $output;
        return true;
    }
    if(!FANSUB_DOING_AJAX) {
        echo $loading;
    }
    if(fansub_callback_exists($callback)) {
        ob_start();
        call_user_func($callback, $args);
        $html_data = ob_get_clean();
        if(!empty($html_data)) {
            set_transient($cache_key, $html_data, 12 * HOUR_IN_SECONDS);
        }
    } else {
        echo fansub_build_message(__('Please set a valid callback for this widget!', 'fansub'), '');
    }
    return true;
}

function fansub_dashboard_widget_rss_cache($args = array()) {
    echo '<div class="rss-widget">';
    $url = '';
    if(is_string($args)) {
        $url = $args;
    } elseif(is_array($args) && isset($args['url'])) {
        $url = $args['url'];
    }
    if(!empty($url)) {
        $number = fansub_get_value_by_key($args, 'number');
        $feed_args = array('url' => $url);
        if(fansub_id_number_valid($number)) {
            $feed_args['number'] = $number;
        }
        $rss = fansub_get_feed_items($feed_args);
        if(is_wp_error($rss)) {
            $error_code = $rss->get_error_code();
            if('feed_down' === $error_code) {
                echo '<ul><li>' . $rss->get_error_message() . '</li></ul>';
            } else {
                if(is_admin() || current_user_can('manage_options')) {
                    echo '<p>' . sprintf(__('<strong>RSS Error</strong>: %s'), $rss->get_error_message()) . '</p>';
                }
            }
            return;
        }
        if(fansub_array_has_value($rss)) {
            echo '<ul>';
            foreach($rss as $item) {
                $li = new FANSUB_HTML('li');
                $a = new FANSUB_HTML('a');
                $a->set_href($item['permalink']);
                $a->set_text($item['title']);
                $a->set_attribute('target', '_blank');
                $li->set_text($a->build());
                $li->output();
            }
            echo '</ul>';
        }
    } else {
        echo fansub_build_message(__('Please set a valid feed url for this widget!', 'fansub'), '');
    }
    echo '</div>';
}

function fansub_wrap_tag($text, $tag, $class = '') {
    if(empty($text)) {
        return $text;
    }
    $html = new FANSUB_HTML($tag);
    $html->set_text($text);
    if(!empty($class)) {
        $html->set_class($class);
    }
    return $html->build();
}

function fansub_fetch_feed($args = array()) {
    $number = absint(fansub_get_value_by_key($args, 'number', 5));
    $offset = fansub_get_value_by_key($args, 'offset', 0);
    $url = fansub_get_value_by_key($args, 'url');
    if(empty($url)) {
        return '';
    }
    if(!function_exists('fetch_feed')) {
        include_once(ABSPATH . WPINC . '/feed.php');
    }
    $rss = fetch_feed($url);
    if(!is_wp_error($rss)) {
        if(!$rss->get_item_quantity()) {
            $error = new WP_Error('feed_down', __('An error has occurred, which probably means the feed is down. Try again later.'));
            $rss->__destruct();
            unset($rss);
            return $error;
        }
        $max = $rss->get_item_quantity($number);
        $result = $rss->get_items($offset, $max);

    } else {
        $result = $rss;
    }
    return $result;
}

function fansub_dashboard_widget_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $fansub_widget_loading = $('.index-php div.inside:visible .fansub-widget-loading'),
                ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
            $fansub_widget_loading.each(function(i, el) {
                var $element = $(el),
                    $post_box = $element.closest('.postbox'),
                    $parent = $element.parent(),
                    widget_id = '';
                if($post_box.length) {
                    widget_id = $post_box.attr('id');
                }
                if($.trim(widget_id)) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: ajax_url,
                        data: {
                            action: 'fansub_dashboard_widget',
                            widget: widget_id
                        },
                        success: function(response){
                            if(response.html_data) {
                                $parent.html('');
                                $parent.hide().slideDown();
                                $parent.html(response.html_data);
                            }
                        }
                    });
                }
            });
        });
    </script>
    <?php
}

function fansub_get_feed_items($args = array()) {
    $url = fansub_get_value_by_key($args, 'url');
    if(empty($url)) {
        return '';
    }
    $number = fansub_get_value_by_key($args, 'number');
    $expiration = fansub_get_value_by_key($args, 'expiration', 12 * HOUR_IN_SECONDS);
    $transient_name = 'fansub_fetch_feed_' . md5($url);
    if(fansub_id_number_valid($number)) {
        $transient_name .= '_' . $number;
    }
    if(false === ($result = get_transient($transient_name))) {
        $items = fansub_fetch_feed($args);
        if(fansub_array_has_value($items)) {
            $result = array();
            foreach($items as $item) {
                if(!fansub_object_valid($item)) {
                    continue;
                }
                $description = $item->get_description();
                $thumbnail = fansub_get_first_image_source($description);
                $description = wp_strip_all_tags($description);
                $content = $item->get_content();
                if(empty($thumbnail)) {
                    $thumbnail = fansub_get_first_image_source($content);
                }
                $value = array(
                    'permalink' => $item->get_permalink(),
                    'title' => $item->get_title(),
                    'date' => $item->get_date(),
                    'image_url' => $thumbnail,
                    'description' => $description,
                    'content' => $content
                );
                array_push($result, $value);
            }
            if(fansub_array_has_value($result)) {
                set_transient($transient_name, $result, $expiration);
            }
        } else {
            return $items;
        }
    }
    return $result;
}

function fansub_rest_api_get($base_url, $object = 'posts', $query = '') {
    $base_url = trailingslashit($base_url) . 'wp-json/wp/v2/' . $object;
    if(!empty($query)) {
        $base_url .= '?' . $query;
    }
    $data = @file_get_contents($base_url);
    if(!empty($data)) {
        $data = json_decode($data);
    }
    return $data;
}

function fansub_read_xml($xml, $is_url = false) {
    if($is_url) {
        $transient_name = 'fansub_read_xml_' . md5($xml);
        if(false === ($saved = get_transient($transient_name))) {
            $saved = @file_get_contents($xml);
            set_transient($transient_name, $saved, HOUR_IN_SECONDS);
        }
        $xml = $saved;
    }
    $object = new SimpleXMLElement($xml);
    return $object;
}

function fansub_build_message($message, $type = 'info') {
    $p = new FANSUB_HTML('p');
    if(!empty($type)) {
        $p->set_class('text-left alert alert-' . $type);
    }
    $p->set_text($message);
    return $p->build();
}

function fansub_generate_reset_key() {
    return wp_generate_password(20, false);
}

function fansub_generate_verify_link($key) {
    $url = home_url('/');
    $url = add_query_arg(array('key' => $key, 'action' => 'verify_subscription'), $url);
    $a = new FANSUB_HTML('a');
    $a->set_href($url);
    $a->set_text($url);
    return $a->build();
}

function fansub_loading_image($args = array()) {
    $name = fansub_get_value_by_key($args, 'name', 'icon-loading-circle-16.gif');
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'img-loading');
    $alt = fansub_get_value_by_key($args, 'alt');
    $display = fansub_get_value_by_key($args, 'display', 'none');
    $style = 'display: ' . $display;
    $img = new FANSUB_HTML('img');
    $image_url = fansub_get_image_url($name);
    $img->set_image_alt($alt);
    $img->set_class($class);
    $img->set_attribute('style', $style);
    $img->set_image_src($image_url);
    $img->output();
}

function fansub_get_allowed_image_mime_types() {
    $types = get_allowed_mime_types();
    $result = array();
    foreach($types as $key => $text) {
        if(false !== strpos($text, 'image')) {
            $result[$key] = $text;
        }
    }
    return $result;
}

function fansub_auto_reload_script($delay = 2000) {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            var time = new Date().getTime();
            function refresh() {
                if(new Date().getTime() - time >= <?php echo $delay; ?>) {
                    window.location.href = window.location.href;
                } else {
                    setTimeout(refresh, 1000);
                }
            }
            setTimeout(refresh, 1000);
        });
    </script>
    <?php
}

function fansub_get_sidebar_info($post) {
    $post_id = $post->ID;
    $active = (bool)fansub_get_post_meta('active', $post_id);
    $sidebar_id = fansub_get_post_meta('sidebar_id', $post_id);
    $default = (bool)fansub_get_post_meta('sidebar_default', $post_id);
    if(empty($sidebar_id)) {
        $sidebar_id = $post->post_name;
    }
    $sidebar_name = fansub_get_value_by_key('sidebar_name', $post_id);
    if(empty($sidebar_name)) {
        $sidebar_name = $post->post_title;
    }
    $sidebar_description = fansub_get_post_meta('sidebar_description', $post_id);
    $sidebar_tag = fansub_get_post_meta('sidebar_tag', $post_id);
    if(empty($sidebar_tag)) {
        $sidebar_tag = 'div';
    }
    $args = array(
        'id' => fansub_sanitize_id($sidebar_id),
        'name' => strip_tags($sidebar_name),
        'description' => strip_tags($sidebar_description),
        'tag' => strtolower($sidebar_tag),
        'active' => $active,
        'default' => $default
    );
    return $args;
}

function fansub_print_r($value) {
    echo '<pre>';
    print_r($value);
    echo '</pre>';
}

function fansub_get_post_class($post_id = null, $class = '') {
    if(!fansub_id_number_valid($post_id)) {
        $post_id = get_the_ID();
    }
    return join(' ', get_post_class($class, $post_id));
}

function fansub_use_comment_form_captcha() {
    $use = get_option('fansub_discussion');
    $use = fansub_get_value_by_key($use, 'use_captcha');
    $use = apply_filters('fansub_use_comment_form_captcha', $use);
    return (bool)$use;
}

function fansub_user_not_use_comment_form_captcha() {
    $use = get_option('fansub_discussion');
    $use = fansub_get_value_by_key($use, 'user_no_captcha', 1);
    $use = apply_filters('fansub_user_not_use_comment_form_captcha', $use);
    return (bool)$use;
}

function fansub_use_comment_form_captcha_custom_position() {
    return apply_filters('fansub_use_comment_form_captcha_custom_position', false);
}

function fansub_build_license_transient_name($type, $use_for) {
    $name = 'fansub_' . $type . '_' . $use_for . '_license_valid';
    return 'fansub_check_license_' . md5($name);
}

function fansub_change_tag_attribute($tag, $attr, $value) {
    $tag = preg_replace('/' . $attr . '="(.*?)"/i', $attr . '="' . $value . '"', $tag);
    return $tag;
}

function fansub_add_html_attribute($tag, $html, $attribute) {
    $html = preg_replace('^' . preg_quote('<' . $tag . ' ') . '^', '<' . $tag . ' ' . $attribute . ' ', $html);
    return $html;
}

function fansub_replace_text_placeholder($text) {
    remove_filter('fansub_replace_text_placeholder', 'fansub_replace_text_placeholder');
    $text = apply_filters('fansub_replace_text_placeholder', $text);
    add_filter('fansub_replace_text_placeholder', 'fansub_replace_text_placeholder');
    $text_placeholders = array(
        '%DOMAIN%',
        '%CURRENT_YEAR%',
        '%PAGED%',
        '%HOME_URL%',
        '%SITE_NAME%'
    );
    $text_placeholders = apply_filters('fansub_text_placeholders', $text_placeholders);
    $placeholder_replace = array(
        fansub_get_domain_name(home_url()),
        date('Y'),
        fansub_get_paged(),
        home_url('/'),
        get_bloginfo('name')
    );
    $placeholder_replace = apply_filters('fansub_text_placeholders_replace', $placeholder_replace);
    $text = str_replace($text_placeholders, $placeholder_replace, $text);
    return $text;
}

function fansub_redirect_home() {
    wp_redirect(home_url('/'));
    exit;
}

function fansub_percentage($val1, $val2, $precision = 0) {
    $total = $val1 + $val2;
    if(0 == $total) {
        return 0;
    }
    $val1 /= $total;
    $val1 *= 100;
    return round($val1, $precision);
}

function fansub_widget_item_full_width_result($full_width_value, $total_item_count, $loop_count) {
    $full_width = false;
    $loop_count = absint($loop_count);
    $loop_count++;
    switch($full_width_value) {
        case 'all':
            $full_width = true;
            break;
        case 'first':
            if(0 == $loop_count) {
                $full_width = true;
            }
            break;
        case 'last':
            if($loop_count == $total_item_count) {
                $full_width = true;
            }
            break;
        case 'first_last':
            if(0 == $loop_count || $loop_count == $total_item_count ) {
                $full_width = true;
            }
            break;
        case 'odd':
            if(($loop_count % 2) != 0) {
                $full_width = true;
            }
            break;
        case 'even':
            if(($loop_count % 2) == 0) {
                $full_width = true;
            }
            break;
    }
    return $full_width;
}

function fansub_the_social_list($args = array()) {
    $option_socials = fansub_option_defaults();
    $option_socials = $option_socials['social'];
    $order = fansub_get_value_by_key($args, 'order', fansub_get_value_by_key($option_socials, 'order'));
    $orders = explode(',', $order);
    $orders = array_map('trim', $orders);
    $orders = fansub_sanitize_array($orders);
    $option_names = $option_socials['option_names'];
    $options = fansub_get_option('option_social');
    $icons = $option_socials['icons'];
    if(fansub_array_has_value($orders)) {
        foreach($orders as $social) {
            $option_name = fansub_get_value_by_key($option_names, $social);
            $item = fansub_get_value_by_key($options, $option_name);
            if(!empty($item)) {
                $icon = '<i class="fa ' . $icons[$social] . '"></i>';
                $a = new FANSUB_HTML('a');
                $a->set_href($item);
                $a->set_class('social-item link-' . $social);
                $a->set_text($icon);
                $a->output();
            }
        }
    }
}

function fansub_in_maintenance_mode() {
    $option = get_option('fansub_maintenance');
    $result = fansub_get_value_by_key($option, 'enabled');
    $result = (bool)$result;
    $result = apply_filters('fansub_enable_maintenance_mode', $result);
    if(fansub_maintenance_mode_exclude_condition() || fansub_is_login_page()) {
        $result = false;
    }
    return $result;
}

function fansub_in_maintenance_mode_notice() {
    if(fansub_in_maintenance_mode()) {
        $page = fansub_get_current_admin_page();
        if('fansub_maintenance' != $page) {
            $args = array(
                'text' => sprintf(__('Your site is running in maintenance mode, so you can go to %s and turn it off when done.', 'fansub'), '<a href="' . admin_url('tools.php?page=fansub_maintenance') . '">' . __('setting page', 'fansub') . '</a>')
            );
            fansub_admin_notice($args);
        }
    }
}

function fansub_get_computer_info() {
    $result = array(
        'operating_system_name' => php_uname('s'),
        'computer_name' => php_uname('n'),
        'release_name' => php_uname('r'),
        'version_information' => php_uname('v'),
        'machine_type' => php_uname('m')
    );
    return $result;
}

function fansub_get_web_server() {
    return htmlspecialchars($_SERVER['SERVER_SOFTWARE']);
}

function fansub_get_table_prefix() {
    global $wpdb;
    if(is_multisite()) {
        return $wpdb->base_prefix;
    } else {
        return $wpdb->get_blog_prefix(0);
    }
}

function fansub_get_peak_memory_usage() {
    return memory_get_peak_usage(true);
}

function fansub_get_memory_usage() {
    return memory_get_usage(true);
}

function fansub_get_memory_limit() {
    return ini_get('memory_limit');
}

function fansub_get_curl_version() {
    if(function_exists('curl_version') && function_exists('curl_exec')) {
        $cv = curl_version();
        $cvs = $cv['version'] . ' / SSL: ' . $cv['ssl_version'] . ' / libz: ' . $cv['libz_version'];
    } else {
        $cvs = __('Not installed', 'fansub') . ' (' . __('required for some remote storage providers', 'fansub') . ')';
    }
    return htmlspecialchars($cvs);
}

function fansub_maintenance_mode_exclude_condition() {
    $condition = fansub_is_admin();
    return apply_filters('fansub_maintenance_mode_exclude_condition', $condition);
}

function fansub_get_views_template($slug, $name = '') {
    $template = $slug;
    $template = str_replace('.php', '', $template);
    if(!empty($name)) {
        $name = str_replace('.php', '', $name);
        $template .= '-' . $name;
    }
    $template .= '.php';
    $template = FANSUB_PATH . '/views/' . $template;
    if(file_exists($template)) {
        include($template);
    }
}

function fansub_convert_datetime_format_to_jquery($php_format) {
    $matched_symbols = array(
        // Day
        'd' => 'dd',
        'D' => 'D',
        'j' => 'd',
        'l' => 'DD',
        'N' => '',
        'S' => '',
        'w' => '',
        'z' => 'o',
        // Week
        'W' => '',
        // Month
        'F' => 'MM',
        'm' => 'mm',
        'M' => 'M',
        'n' => 'm',
        't' => '',
        // Year
        'L' => '',
        'o' => '',
        'Y' => 'yy',
        'y' => 'y',
        // Time
        'a' => '',
        'A' => '',
        'B' => '',
        'g' => '',
        'G' => '',
        'h' => '',
        'H' => '',
        'i' => '',
        's' => '',
        'u' => ''
    );
    $result = '';
    $escaping = false;
    for($i = 0; $i < strlen($php_format); $i++) {
        $char = $php_format[$i];
        if(isset($matched_symbols[$char])) {
            $result .= $matched_symbols[$char];
        } else {
            $result .= $char;
        }
    }
    if($escaping) {
        $result = esc_attr($result);
    }
    return $result;
}

function fansub_use_jquery_cdn($value = null) {
    if(null == $value) {
        $option = fansub_get_optimize_option();
        $use = fansub_get_value_by_key($option, 'use_jquery_cdn', 1);
        $value = (bool)$use;
    }
    $value = apply_filters('fansub_use_jquery_google_cdn', $value);
    return $value;
}

function fansub_load_jquery_from_cdn() {
    if(!is_admin()) {
        $use = fansub_use_jquery_cdn();
        if($use) {
            global $wp_version, $wp_scripts;
            $handle = (version_compare($wp_version, '3.6-alpha1', '>=') ) ? 'jquery-core' : 'jquery';
            $enqueued = wp_script_is($handle);
            wp_enqueue_script($handle);
            $version = '';
            $jquery_url = '';
            $google_not_exists = array(
                '1.12.3'
            );
            if(is_a($wp_scripts, 'WP_Scripts')) {
                $registered = $wp_scripts->registered;
                if(isset($registered[$handle])) {
                    $version = $registered[$handle]->ver;
                    if(in_array($version, $google_not_exists)) {
                        $jquery_url = '//code.jquery.com/jquery-' . $version . '.min.js';
                    }
                }
            }
            if(empty($version)) {
                $version = FANSUB_JQUERY_LATEST_VERSION;
            }
            if(empty($jquery_url)) {
                $jquery_url = '//ajax.googleapis.com/ajax/libs/jquery/'. $version . '/jquery.min.js';
            }
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
            wp_register_script($handle, $jquery_url);
            if($enqueued) {
                wp_enqueue_script($handle);
                add_action('fansub_before_wp_head', 'fansub_jquery_google_cdn_fallback');
            }
        }
    }
}

function fansub_jquery_google_cdn_fallback() {
    echo '<script>window.jQuery || document.write(\'<script src="' . includes_url('js/jquery/jquery.js') . '"><\/script>\')</script>' . "\n";
}

function fansub_plugin_wpsupercache_installed() {
    return function_exists('wpsupercache_activate');
}

function fansub_plugins_api($action, $args = array()) {
    if(!function_exists('plugins_api')) {
        require(ABSPATH . 'wp-admin/includes/plugin-install.php');
    }
    return plugins_api($action, $args);
}

function fansub_plugins_api_get_information($args = array()) {
    $slug = fansub_get_value_by_key($args, 'slug');
    if(empty($slug)) {
        return new WP_Error('missing_slug', __('Please set slug for this plugin.', 'fansub'));
    }
    $transient_name = 'fansub_plugins_api_' . $slug . '_plugin_information';
    $transient_name = fansub_sanitize_id($transient_name);
    if(false === ($data = get_transient($transient_name))) {
        $defaults = array(
            'fields' => array(
                'short_description' => true,
                'screenshots' => false,
                'changelog' => false,
                'installation' => false,
                'description' => false,
                'sections' => false,
                'tags' => false,
                'icons' => true,
                'active_installs' => true,
                'versions' => true
            )
        );
        $args = wp_parse_args($args, $defaults);
        $data = fansub_plugins_api('plugin_information', $args);
        set_transient($transient_name, $data, MONTH_IN_SECONDS);
    }
    return $data;
}

function fansub_plugin_install_status($plugin) {
    if(!function_exists('install_plugin_install_status')) {
        require(ABSPATH . 'wp-admin/includes/plugin-install.php');
    }
    return install_plugin_install_status($plugin);
}

function fansub_setup_loop_data($args) {
    global $fansub_loop_data;
    $fansub_loop_data = $args;
    return $fansub_loop_data;
}

function fansub_get_loop_data() {
    global $fansub_loop_data;
    return $fansub_loop_data;
}

function fansub_get_plugin_icon_url($plugin) {
    if(is_object($plugin)) {
        $plugin = (array)$plugin;
    }
    if(!empty($plugin['icons']['svg'])) {
        $plugin_icon_url = $plugin['icons']['svg'];
    } elseif(!empty($plugin['icons']['2x'])) {
        $plugin_icon_url = $plugin['icons']['2x'];
    } elseif(!empty($plugin['icons']['1x'])) {
        $plugin_icon_url = $plugin['icons']['1x'];
    } else {
        $plugin_icon_url = fansub_get_value_by_key($plugin, array('icons', 'default'));
    }
    if(empty($plugin_icon_url)) {
        $plugin_icon_url = fansub_plugin_random_icon();
    }
    return $plugin_icon_url;
}

function fansub_get_image_url($name) {
    return FANSUB_URL . '/images/' . $name;
}

function fansub_sanitize_first_and_last_name($name) {
    $result = array(
        'first_name' => $name,
        'last_name' => $name
    );
    if(false !== strpos($name, ' ')) {
        $parts = explode(' ', $name);
        if('vi' == fansub_get_language()) {
            $first_name = array_pop($parts);
        } else {
            $first_name = array_shift($parts);
        }
        $last_name = implode(' ', $parts);
        $result['first_name'] = $first_name;
        $result['last_name'] = $last_name;
    }
    $result = apply_filters('fansub_sanitize_first_and_last_name', $result, $name);
    return $result;
}

function fansub_get_rich_text($text) {
    return do_shortcode(wpautop($text));
}

function fansub_widget_title($args, $instance, $echo = true) {
    $title = apply_filters('widget_title', $instance['title']);
    $before_title = fansub_get_value_by_key($args, 'before_title');
    $after_title = fansub_get_value_by_key($args, 'after_title');
    if(!empty($title)) {
        $title = $before_title . $title . $after_title;
    }
    $title = apply_filters('fansub_widget_title_html', $title, $args, $instance);
    if((bool)$echo) {
        echo $title;
    }
    return $title;
}

function fansub_checkbox_post_data_value($data, $key, $default = 0) {
    return (isset($data[$key]) && 0 != $data[$key]) ? 1 : $default;
}

function fansub_change_nav_menu_css_class($terms, $classes, $item) {
    if(fansub_array_has_value($terms)) {
        foreach($terms as $term) {
            if($term->term_id == $item->object_id) {
                $classes[] = 'current-menu-item';
                break;
            }
        }
    }
    return $classes;
}

function fansub_remove_wpseo_breadcrumb_xmlns($output) {
    $output = str_replace(' xmlns:v="http://rdf.data-vocabulary.org/#"', '', $output);
    return $output;
}

function fansub_widget_before($args, $instance, $show_title = true) {
    if(isset($args['before_widget'])) {
        echo $args['before_widget'];
    }
    if($show_title) {
        fansub_widget_title($args, $instance);
    }
    echo '<div class="widget-content">';
}

function fansub_widget_after($args, $instance) {
    echo '</div>';
    if(isset($args['after_widget'])) {
        echo $args['after_widget'];
    }
}

function fansub_get_installed_plugins($folder = '') {
    return fansub_get_plugins($folder);
}

function fansub_get_plugin_slug_from_file_path($file) {
    if('hello.php' == $file) {
        $file = 'hello-dolly';
    }
    $slug = explode('/', $file);
    $slug = current($slug);
    return $slug;
}

function fansub_html_tag_attributes($tag, $context = '') {
    if(!current_theme_supports('fansub-schema')) {
        return;
    }
    $base = 'http://schema.org/';
    $item_type = apply_filters('fansub_html_tag_attribute_item_type', '', $tag, $context);
    if(!empty($item_type)) {
        $schema = ' itemscope itemtype="' . $base . $item_type . '"';
        echo $schema;
    }
    $item_prop = apply_filters('fansub_html_tag_attribute_item_prop', '', $tag, $context);
    if(!empty($item_prop)) {
        $schema = ' itemprop="' . $item_prop . '"';
        echo $schema;
    }
    $attributes = apply_filters('fansub_html_tag_attributes', '', $tag, $context);
    $attributes = trim($attributes);
    if(!empty($attributes)) {
        echo ' ' . $attributes;
    }
}

function fansub_loop_plugin_card($plugin, $allow_tags = array(), $base_name = '') {
    $is_local = false;
    if(is_object($plugin)) {
        $plugin = (array)$plugin;
    }
    $title = wp_kses(fansub_get_value_by_key($plugin, 'name'), $allow_tags);
    if(empty($title)) {
        $is_local = true;
    }
    $description = strip_tags(fansub_get_value_by_key($plugin, 'short_description'));
    $version = wp_kses(fansub_get_value_by_key($plugin, 'version'), $allow_tags);
    $name = strip_tags($title . ' ' . $version);
    $author = wp_kses(fansub_get_value_by_key($plugin, 'author'), $allow_tags);
    if(!empty($author)) {
        $author = ' <cite>' . sprintf(__('By %s'), $author) . '</cite>';
    }
    $action_links = array();
    if(!$is_local && (current_user_can('install_plugins') || current_user_can('update_plugins'))) {
        $status = fansub_plugin_install_status($plugin);
        switch($status['status']) {
            case 'install':
                if($status['url']) {
                    $action_links[] = '<a class="install-now button" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Install %s now'), $name)) . '" data-name="' . esc_attr($name) . '">' . __('Install Now') . '</a>';
                }
                break;
            case 'update_available':
                if($status['url']) {
                    $action_links[] = '<a class="update-now button" data-plugin="' . esc_attr($status['file']) . '" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Update %s now'), $name)) . '" data-name="' . esc_attr($name) . '">' . __('Update Now') . '</a>';
                }
                break;
            case 'latest_installed':
            case 'newer_installed':
                $action_links[] = '<span class="button button-disabled" title="' . esc_attr__('This plugin is already installed and is up to date') . ' ">' . _x('Installed', 'plugin') . '</span>';
                break;
        }
    }
    $details_link = self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . fansub_get_value_by_key($plugin, 'slug') . '&amp;TB_iframe=true&amp;width=600&amp;height=550');
    $action_links[] = '<a target="_blank" href="' . esc_url($details_link) . '" class="thickbox" aria-label="' . esc_attr(sprintf(__('More information about %s'), $name)) . '" data-title="' . esc_attr($name) . '">' . __('More Details') . '</a>';
    $plugin_icon_url = fansub_get_plugin_icon_url($plugin);
    $action_links = apply_filters('plugin_install_action_links', $action_links, $plugin);
    $date_format = __('M j, Y @ H:i');
    $last_updated_timestamp = strtotime(fansub_get_value_by_key($plugin, 'last_updated'));
    if(empty($title) && !empty($base_name)) {
        $local_plugin = fansub_get_plugin_info($base_name);
        $title = wp_kses($local_plugin['Name'], $allow_tags);
        $description = strip_tags($local_plugin['Description']);
        $description = str_replace(' By HocWP.', '', $description);
        $action_links = array();
        //$version = wp_kses($local_plugin['Version'], $allow_tags);
        //$name = strip_tags($title . ' ' . $version);
        $author = wp_kses($local_plugin['Author'], $allow_tags);
        if(!empty($author)) {
            $author = ' <cite>' . sprintf(__('By %s'), $author) . '</cite>';
        }
    }
    if(empty($title)) {
        return;
    }
    ?>
    <div class="plugin-card plugin-card-<?php echo sanitize_html_class(fansub_get_value_by_key($plugin, 'slug')); ?>">
        <div class="plugin-card-top">
            <div class="name column-name">
                <h3>
                    <a target="_blank" href="<?php echo esc_url($details_link); ?>" class="thickbox">
                        <?php echo $title; ?>
                        <img src="<?php echo esc_attr($plugin_icon_url) ?>" class="plugin-icon" alt="">
                    </a>
                </h3>
            </div>
            <div class="action-links">
                <?php
                if($action_links) {
                    echo '<ul class="plugin-action-buttons"><li>' . implode('</li><li>', $action_links) . '</li></ul>';
                }
                ?>
            </div>
            <div class="desc column-description">
                <p><?php echo $description; ?></p>
                <p class="authors"><?php echo $author; ?></p>
            </div>
        </div>
        <div class="plugin-card-bottom">
            <?php if(!$is_local) : ?>
                <div class="vers column-rating">
                    <?php wp_star_rating(array('rating' => $plugin['rating'], 'type' => 'percent', 'number' => $plugin['num_ratings'])); ?>
                    <span class="num-ratings">(<?php echo number_format_i18n($plugin['num_ratings']); ?>)</span>
                </div>
                <div class="column-updated">
                    <strong><?php _e('Last Updated:'); ?></strong> <span title="<?php echo esc_attr(date_i18n($date_format, $last_updated_timestamp)); ?>">
						<?php printf(__('%s ago'), human_time_diff($last_updated_timestamp)); ?>
					</span>
                </div>
                <div class="column-downloaded">
                    <?php
                    if ($plugin['active_installs'] >= 1000000) {
                        $active_installs_text = _x('1+ Million', 'Active plugin installs');
                    } else {
                        $active_installs_text = number_format_i18n($plugin['active_installs']) . '+';
                    }
                    printf(__('%s Active Installs'), $active_installs_text);
                    ?>
                </div>
                <div class="column-compatibility">
                    <?php
                    if(!empty($plugin['tested']) && version_compare(substr($GLOBALS['wp_version'], 0, strlen($plugin['tested'])), $plugin['tested'], '>')) {
                        echo '<span class="compatibility-untested">' . __('Untested with your version of WordPress') . '</span>';
                    } elseif(! empty($plugin['requires']) && version_compare(substr($GLOBALS['wp_version'], 0, strlen($plugin['requires'])), $plugin['requires'], '<')) {
                        echo '<span class="compatibility-incompatible">' . __('<strong>Incompatible</strong> with your version of WordPress') . '</span>';
                    } else {
                        echo '<span class="compatibility-compatible">' . __('<strong>Compatible</strong> with your version of WordPress') . '</span>';
                    }
                    ?>
                </div>
            <?php else : ?>
                <p><?php _e('This is a local plugin so there is no stats for it.', 'fansub'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function fansub_facebook_like_button($args = array()) {
    $post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
    $permalink = get_permalink($post_id);
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'fb-like');
    $layout = isset($args['layout']) ? $args['layout'] : 'button_count';
    $action = isset($args['action']) ? $args['action'] : 'like';
    $show_faces = isset($args['show_faces']) ? $args['show_faces'] : false;
    $show_faces = fansub_bool_to_string($show_faces);
    $share = isset($args['share']) ? $args['share'] : true;
    $share = fansub_bool_to_string($share);
    ?>
    <div class="<?php echo $class; ?>" data-href="<?php echo $permalink; ?>" data-layout="<?php echo $layout; ?>" data-action="<?php echo $action; ?>" data-show-faces="<?php echo $show_faces; ?>" data-share="<?php echo $share; ?>"></div>
    <?php
}

function fansub_facebook_like_and_recommend_button($args = array()) {
    $url = isset($args['url']) ? $args['url'] : '';
    if(empty($url)) {
        $url = get_permalink();
    }
    $app_id = fansub_get_wpseo_social_facebook_app_id();
    ?>
    <div class="fb-like-buttons like-recommend like-recommend-buttons">
        <div class="item">
            <div data-share="false" data-show-faces="false" data-action="like" data-layout="button_count" data-href="<?php echo $url; ?>" class="fb-like fb_iframe_widget" fb-xfbml-state="rendered" fb-iframe-plugin-query="action=like&amp;app_id=<?php echo $app_id; ?>&amp;container_width=0&amp;href=<?php echo $url; ?>&amp;layout=button_count&amp;locale=en_US&amp;sdk=joey&amp;share=false&amp;show_faces=false"></div>
        </div>
        <div class="item">
            <div data-share="true" data-show-faces="false" data-action="recommend" data-layout="button_count" data-href="<?php echo $url; ?>" class="fb-like fb_iframe_widget" fb-xfbml-state="rendered" fb-iframe-plugin-query="action=recommend&amp;app_id=<?php echo $app_id; ?>&amp;container_width=0&amp;href=<?php echo $url; ?>&amp;layout=button_count&amp;locale=en_US&amp;sdk=joey&amp;share=false&amp;show_faces=false"></div>
        </div>
    </div>
    <?php
}

function fansub_facebook_share_and_like_buttons($args = array()) {
    $url = isset($args['url']) ? $args['url'] : '';
    if(empty($url)) {
        $url = get_permalink();
    }
    $layout = isset($args['layout']) ? $args['layout'] : 'button_count';
    $action = isset($args['action']) ? $args['action'] : 'like';
    $show_faces = isset($args['show_faces']) ? $args['show_faces'] : false;
    $show_faces = fansub_bool_to_string($show_faces);
    $share = isset($args['share']) ? $args['share'] : true;
    $share = fansub_bool_to_string($share);
    ?>
    <div class="fb-like-buttons like-share">
        <div class="item">
            <div class="fb-like" data-href="<?php echo $url; ?>" data-layout="<?php echo $layout; ?>" data-action="<?php echo $action; ?>" data-show-faces="<?php echo $show_faces; ?>" data-share="<?php echo $share; ?>"></div>
        </div>
    </div>
    <?php
}

function fansub_plugin_random_icon() {
    $icons = array(
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTAiIGhlaWdodD0iMjUwIiB2aWV3Ym94PSIwIDAgMjUwIDI1MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDE1OCwgMTYxLCA0NSkiIC8+PGNpcmNsZSBjeD0iMCIgY3k9IjAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wNjMzMzMzMzMzMzMzMzM7IiAvPjxjaXJjbGUgY3g9IjI1MCIgY3k9IjAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wNjMzMzMzMzMzMzMzMzM7IiAvPjxjaXJjbGUgY3g9IjAiIGN5PSIyNTAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wNjMzMzMzMzMzMzMzMzM7IiAvPjxjaXJjbGUgY3g9IjI1MCIgY3k9IjI1MCIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA2MzMzMzMzMzMzMzMzMzsiIC8+PGNpcmNsZSBjeD0iNDEuNjY2NjY2NjY2NjY3IiBjeT0iMCIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjsiIC8+PGNpcmNsZSBjeD0iNDEuNjY2NjY2NjY2NjY3IiBjeT0iMjUwIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyOyIgLz48Y2lyY2xlIGN4PSI4My4zMzMzMzMzMzMzMzMiIGN5PSIwIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7IiAvPjxjaXJjbGUgY3g9IjgzLjMzMzMzMzMzMzMzMyIgY3k9IjI1MCIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAyOyIgLz48Y2lyY2xlIGN4PSIxMjUiIGN5PSIwIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTI0OyIgLz48Y2lyY2xlIGN4PSIxMjUiIGN5PSIyNTAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMjQ7IiAvPjxjaXJjbGUgY3g9IjE2Ni42NjY2NjY2NjY2NyIgY3k9IjAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMTY2LjY2NjY2NjY2NjY3IiBjeT0iMjUwIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTA2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjIwOC4zMzMzMzMzMzMzMyIgY3k9IjAiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wOTg7IiAvPjxjaXJjbGUgY3g9IjIwOC4zMzMzMzMzMzMzMyIgY3k9IjI1MCIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA5ODsiIC8+PGNpcmNsZSBjeD0iMCIgY3k9IjQxLjY2NjY2NjY2NjY2NyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMjUwIiBjeT0iNDEuNjY2NjY2NjY2NjY3IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSI0MS42NjY2NjY2NjY2NjciIGN5PSI0MS42NjY2NjY2NjY2NjciIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xNDEzMzMzMzMzMzMzMzsiIC8+PGNpcmNsZSBjeD0iODMuMzMzMzMzMzMzMzMzIiBjeT0iNDEuNjY2NjY2NjY2NjY3IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDk4OyIgLz48Y2lyY2xlIGN4PSIxMjUiIGN5PSI0MS42NjY2NjY2NjY2NjciIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wMjg2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjE2Ni42NjY2NjY2NjY2NyIgY3k9IjQxLjY2NjY2NjY2NjY2NyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEwNjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSIyMDguMzMzMzMzMzMzMzMiIGN5PSI0MS42NjY2NjY2NjY2NjciIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMCIgY3k9IjgzLjMzMzMzMzMzMzMzMyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMjUwIiBjeT0iODMuMzMzMzMzMzMzMzMzIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSI0MS42NjY2NjY2NjY2NjciIGN5PSI4My4zMzMzMzMzMzMzMzMiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iODMuMzMzMzMzMzMzMzMzIiBjeT0iODMuMzMzMzMzMzMzMzMzIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTMyNjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjEyNSIgY3k9IjgzLjMzMzMzMzMzMzMzMyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMTY2LjY2NjY2NjY2NjY3IiBjeT0iODMuMzMzMzMzMzMzMzMzIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTA2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjIwOC4zMzMzMzMzMzMzMyIgY3k9IjgzLjMzMzMzMzMzMzMzMyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjE0MTMzMzMzMzMzMzMzOyIgLz48Y2lyY2xlIGN4PSIwIiBjeT0iMTI1IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSIyNTAiIGN5PSIxMjUiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjQxLjY2NjY2NjY2NjY2NyIgY3k9IjEyNSIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA2MzMzMzMzMzMzMzMzMzsiIC8+PGNpcmNsZSBjeD0iODMuMzMzMzMzMzMzMzMzIiBjeT0iMTI1IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDI4NjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSIxMjUiIGN5PSIxMjUiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjE2Ni42NjY2NjY2NjY2NyIgY3k9IjEyNSIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjsiIC8+PGNpcmNsZSBjeD0iMjA4LjMzMzMzMzMzMzMzIiBjeT0iMTI1IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7IiAvPjxjaXJjbGUgY3g9IjAiIGN5PSIxNjYuNjY2NjY2NjY2NjciIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjI1MCIgY3k9IjE2Ni42NjY2NjY2NjY2NyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iNDEuNjY2NjY2NjY2NjY3IiBjeT0iMTY2LjY2NjY2NjY2NjY3IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTA2NjY2NjY2NjY2Njc7IiAvPjxjaXJjbGUgY3g9IjgzLjMzMzMzMzMzMzMzMyIgY3k9IjE2Ni42NjY2NjY2NjY2NyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAyOyIgLz48Y2lyY2xlIGN4PSIxMjUiIGN5PSIxNjYuNjY2NjY2NjY2NjciIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMzI2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMTY2LjY2NjY2NjY2NjY3IiBjeT0iMTY2LjY2NjY2NjY2NjY3IiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7IiAvPjxjaXJjbGUgY3g9IjIwOC4zMzMzMzMzMzMzMyIgY3k9IjE2Ni42NjY2NjY2NjY2NyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iMCIgY3k9IjIwOC4zMzMzMzMzMzMzMyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEwNjY2NjY2NjY2NjY3OyIgLz48Y2lyY2xlIGN4PSIyNTAiIGN5PSIyMDguMzMzMzMzMzMzMzMiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NzsiIC8+PGNpcmNsZSBjeD0iNDEuNjY2NjY2NjY2NjY3IiBjeT0iMjA4LjMzMzMzMzMzMzMzIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDM3MzMzMzMzMzMzMzMzOyIgLz48Y2lyY2xlIGN4PSI4My4zMzMzMzMzMzMzMzMiIGN5PSIyMDguMzMzMzMzMzMzMzMiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7IiAvPjxjaXJjbGUgY3g9IjEyNSIgY3k9IjIwOC4zMzMzMzMzMzMzMyIgcj0iNDEuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA2MzMzMzMzMzMzMzMzMzsiIC8+PGNpcmNsZSBjeD0iMTY2LjY2NjY2NjY2NjY3IiBjeT0iMjA4LjMzMzMzMzMzMzMzIiByPSI0MS42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzOyIgLz48Y2lyY2xlIGN4PSIyMDguMzMzMzMzMzMzMzMiIGN5PSIyMDguMzMzMzMzMzMzMzMiIHI9IjQxLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMzI2NjY2NjY2NjY2NzsiIC8+PC9zdmc+',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMTAiIGhlaWdodD0iMTkwIiB2aWV3Ym94PSIwIDAgMTEwIDE5MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDYzLCA4NCwgMTQzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA2MzMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE4LjMzMzMzMzMzMzMzMywgMCkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDYzMzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MS42NjY2NjY2NjY2NjcsIDApIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAwKSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE4LjMzMzMzMzMzMzMzMywgMCkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTI0IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzNi42NjY2NjY2NjY2NjcsIDApIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNTUsIDApIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjAyODY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNzMuMzMzMzMzMzMzMzMzLCAwKSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTguMzMzMzMzMzMzMzMzLCAzMS43NTQyNjQ4MDU0MjkpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNzIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkxLjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDI4NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAzMS43NTQyNjQ4MDU0MjkpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA0NiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTguMzMzMzMzMzMzMzMzLCAzMS43NTQyNjQ4MDU0MjkpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNjMzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5KSByb3RhdGUoMTgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNTUsIDMxLjc1NDI2NDgwNTQyOSkgcm90YXRlKDAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg3My4zMzMzMzMzMzMzMzMsIDMxLjc1NDI2NDgwNTQyOSkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xOC4zMzMzMzMzMzMzMzMsIDYzLjUwODUyOTYxMDg1OSkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkxLjY2NjY2NjY2NjY2NywgNjMuNTA4NTI5NjEwODU5KSByb3RhdGUoMTgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgNjMuNTA4NTI5NjEwODU5KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxOC4zMzMzMzMzMzMzMzMsIDYzLjUwODUyOTYxMDg1OSkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM2LjY2NjY2NjY2NjY2NywgNjMuNTA4NTI5NjEwODU5KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDU1LCA2My41MDg1Mjk2MTA4NTkpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA0NiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNzMuMzMzMzMzMzMzMzMzLCA2My41MDg1Mjk2MTA4NTkpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMjQiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xOC4zMzMzMzMzMzMzMzMsIDk1LjI2Mjc5NDQxNjI4OCkgcm90YXRlKDAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEyNCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTEuNjY2NjY2NjY2NjY3LCA5NS4yNjI3OTQ0MTYyODgpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xNSIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgOTUuMjYyNzk0NDE2Mjg4KSByb3RhdGUoMTgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE4LjMzMzMzMzMzMzMzMywgOTUuMjYyNzk0NDE2Mjg4KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzNi42NjY2NjY2NjY2NjcsIDk1LjI2Mjc5NDQxNjI4OCkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDM3MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg1NSwgOTUuMjYyNzk0NDE2Mjg4KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg3My4zMzMzMzMzMzMzMzMsIDk1LjI2Mjc5NDQxNjI4OCkgcm90YXRlKDE4MCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDYzMzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTguMzMzMzMzMzMzMzMzLCAxMjcuMDE3MDU5MjIxNzIpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA2MzMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTEuNjY2NjY2NjY2NjY3LCAxMjcuMDE3MDU5MjIxNzIpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEyNCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgMTI3LjAxNzA1OTIyMTcyKSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE4LjMzMzMzMzMzMzMzMywgMTI3LjAxNzA1OTIyMTcyKSByb3RhdGUoMTgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzYuNjY2NjY2NjY2NjY3LCAxMjcuMDE3MDU5MjIxNzIpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDU1LCAxMjcuMDE3MDU5MjIxNzIpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjE1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg3My4zMzMzMzMzMzMzMzMsIDEyNy4wMTcwNTkyMjE3Mikgcm90YXRlKDAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE4LjMzMzMzMzMzMzMzMywgMTU4Ljc3MTMyNDAyNzE1KSByb3RhdGUoMCwgMTguMzMzMzMzMzMzMzMzLCAxNS44NzcxMzI0MDI3MTUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE4LjMzMzMzMzMzMzMzMywgMCwgMzYuNjY2NjY2NjY2NjY3LCAzMS43NTQyNjQ4MDU0MjksIDAsIDMxLjc1NDI2NDgwNTQyOSwgMTguMzMzMzMzMzMzMzMzLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MS42NjY2NjY2NjY2NjcsIDE1OC43NzEzMjQwMjcxNSkgcm90YXRlKDAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxNTguNzcxMzI0MDI3MTUpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxOC4zMzMzMzMzMzMzMzMsIDAsIDM2LjY2NjY2NjY2NjY2NywgMzEuNzU0MjY0ODA1NDI5LCAwLCAzMS43NTQyNjQ4MDU0MjksIDE4LjMzMzMzMzMzMzMzMywgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEyNCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTguMzMzMzMzMzMzMzMzLCAxNTguNzcxMzI0MDI3MTUpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM2LjY2NjY2NjY2NjY2NywgMTU4Ljc3MTMyNDAyNzE1KSByb3RhdGUoMTgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNzIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDU1LCAxNTguNzcxMzI0MDI3MTUpIHJvdGF0ZSgwLCAxOC4zMzMzMzMzMzMzMzMsIDE1Ljg3NzEzMjQwMjcxNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTguMzMzMzMzMzMzMzMzLCAwLCAzNi42NjY2NjY2NjY2NjcsIDMxLjc1NDI2NDgwNTQyOSwgMCwgMzEuNzU0MjY0ODA1NDI5LCAxOC4zMzMzMzMzMzMzMzMsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNzMuMzMzMzMzMzMzMzMzLCAxNTguNzcxMzI0MDI3MTUpIHJvdGF0ZSgxODAsIDE4LjMzMzMzMzMzMzMzMywgMTUuODc3MTMyNDAyNzE1KSIgLz48L3N2Zz4=',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDAiIGhlaWdodD0iMzYwIiB2aWV3Ym94PSIwIDAgMTQwIDM2MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDg2LCAxNTAsIDE3OSkiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIC0xMDgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDM3MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAyNTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDk4O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtOTgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDk4O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAyNjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtODgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAyNzIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIC03OCkiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMjgyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtNjgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTMyNjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDI5MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMTUzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgLTU4KSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjExNTMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAzMDIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtNDgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAzMTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtMzgpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAzMjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIC0yOCkiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMzMyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgLTE4KSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMzQyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAtOCkiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMzI2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMzUyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA4MDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDM2MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDM3MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMTUzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTE1MzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDM4MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xNDEzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMzIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTQxMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDM5MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wNjMzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDQyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA2MzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDAyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA1MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMzI2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDEyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA0MjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA3MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wODkzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDQzMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDgyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDQyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgOTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA0NTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDI4NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAxMDIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDI4NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA0NjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDI7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDExMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDcyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMTIyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA3MjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDgyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA4MDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMTMyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA4MDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNDkyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAxNDIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTMyNjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDUwMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wMjg2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDE1MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wMjg2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDUxMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wODkzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDE2MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wODkzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDUyMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDE3MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDUzMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDE4MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDU0MikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xNDEzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMTkyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjE0MTMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA1NTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAyMDIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA1NjIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCAyMTIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMHB4OyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTM1LCA1NzIpIiAvPjxwYXRoIGQ9Ik0wIDcyIEMgMjQuNSAwLCA0NiAwLCA3MCA3MiBTIDExNiAxNDQsIDE0MCA3MiBTIDE4NiAwLCAyMTAsIDcyIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMTA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwcHg7IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMzUsIDIyMikiIC8+PHBhdGggZD0iTTAgNzIgQyAyNC41IDAsIDQ2IDAsIDcwIDcyIFMgMTE2IDE0NCwgMTQwIDcyIFMgMTg2IDAsIDIxMCwgNzIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNTgyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA5ODtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMjMyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA5ODtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNTkyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA0NjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgMjQyKSIgLz48cGF0aCBkPSJNMCA3MiBDIDI0LjUgMCwgNDYgMCwgNzAgNzIgUyAxMTYgMTQ0LCAxNDAgNzIgUyAxODYgMCwgMjEwLCA3MiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA0NjtzdHJva2Utd2lkdGg6MTBweDsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0zNSwgNjAyKSIgLz48L3N2Zz4=',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyODAiIGhlaWdodD0iMjgwIiB2aWV3Ym94PSIwIDAgMjgwIDI4MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDExMiwgNjksIDEzNykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDYuNjY2NjY2NjY2NjY3LCAwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkzLjMzMzMzMzMzMzMzMywgMCkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxNDAsIDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTg2LjY2NjY2NjY2NjY3LCAwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNzIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIzMy4zMzMzMzMzMzMzMywgMCkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCA0Ni42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0Ni42NjY2NjY2NjY2NjcsIDQ2LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5My4zMzMzMzMzMzMzMzMsIDQ2LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDg5MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxNDAsIDQ2LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxODYuNjY2NjY2NjY2NjcsIDQ2LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDg5MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyMzMuMzMzMzMzMzMzMzMsIDQ2LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDkzLjMzMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQ2LjY2NjY2NjY2NjY2NywgOTMuMzMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkzLjMzMzMzMzMzMzMzMywgOTMuMzMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTQwLCA5My4zMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxODYuNjY2NjY2NjY2NjcsIDkzLjMzMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyMzMuMzMzMzMzMzMzMzMsIDkzLjMzMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxNDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAzNzMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDYuNjY2NjY2NjY2NjY3LCAxNDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5My4zMzMzMzMzMzMzMzMsIDE0MCkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTQxMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE0MCwgMTQwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE4Ni42NjY2NjY2NjY2NywgMTQwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjMzLjMzMzMzMzMzMzMzLCAxNDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAxODYuNjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0Ni42NjY2NjY2NjY2NjcsIDE4Ni42NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTI0IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5My4zMzMzMzMzMzMzMzMsIDE4Ni42NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxNDAsIDE4Ni42NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxODYuNjY2NjY2NjY2NjcsIDE4Ni42NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIzMy4zMzMzMzMzMzMzMywgMTg2LjY2NjY2NjY2NjY3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDIzMy4zMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQ2LjY2NjY2NjY2NjY2NywgMjMzLjMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxNS40LDAsMzEuMjY2NjY2NjY2NjY3LDAsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsNDYuNjY2NjY2NjY2NjY3LDE1LjQsNDYuNjY2NjY2NjY2NjY3LDAsMzEuMjY2NjY2NjY2NjY3LDAsMTUuNCwxNS40LDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTMuMzMzMzMzMzMzMzMzLCAyMzMuMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjE1LjQsMCwzMS4yNjY2NjY2NjY2NjcsMCw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDMxLjI2NjY2NjY2NjY2Nyw0Ni42NjY2NjY2NjY2NjcsMTUuNCw0Ni42NjY2NjY2NjY2NjcsMCwzMS4yNjY2NjY2NjY2NjcsMCwxNS40LDE1LjQsMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxNDAsIDIzMy4zMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxODYuNjY2NjY2NjY2NjcsIDIzMy4zMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTUuNCwwLDMxLjI2NjY2NjY2NjY2NywwLDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywzMS4yNjY2NjY2NjY2NjcsMzEuMjY2NjY2NjY2NjY3LDQ2LjY2NjY2NjY2NjY2NywxNS40LDQ2LjY2NjY2NjY2NjY2NywwLDMxLjI2NjY2NjY2NjY2NywwLDE1LjQsMTUuNCwwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyMzMuMzMzMzMzMzMzMzMsIDIzMy4zMzMzMzMzMzMzMykiIC8+PC9zdmc+',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1OTAiIGhlaWdodD0iNTkwIiB2aWV3Ym94PSIwIDAgNTkwIDU5MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDE4NywgMTk3LCAyMDEpIiAvPjxyZWN0IHg9IjUuNDY2NjY2NjY2NjY2NyIgeT0iNS40NjY2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMjcuMzMzMzMzMzMzMzMzIiB5PSIyNy4zMzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMDMuODY2NjY2NjY2NjciIHk9IjUuNDY2NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMTUzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjEyNS43MzMzMzMzMzMzMyIgeT0iMjcuMzMzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTU7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMDIuMjY2NjY2NjY2NjciIHk9IjUuNDY2NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMzI2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjIyNC4xMzMzMzMzMzMzMyIgeT0iMjcuMzMzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzAwLjY2NjY2NjY2NjY3IiB5PSI1LjQ2NjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDcyO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzIyLjUzMzMzMzMzMzMzIiB5PSIyNy4zMzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wODkzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIzOTkuMDY2NjY2NjY2NjciIHk9IjUuNDY2NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI0MjAuOTMzMzMzMzMzMzMiIHk9IjI3LjMzMzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAzNzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjQ5Ny40NjY2NjY2NjY2NyIgeT0iNS40NjY2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEyNDtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjUxOS4zMzMzMzMzMzMzMyIgeT0iMjcuMzMzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNS40NjY2NjY2NjY2NjY3IiB5PSIxMDMuODY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDQ2O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMjcuMzMzMzMzMzMzMzMzIiB5PSIxMjUuNzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMjQ7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMDMuODY2NjY2NjY2NjciIHk9IjEwMy44NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wNjMzMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMjUuNzMzMzMzMzMzMzMiIHk9IjEyNS43MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjIwMi4yNjY2NjY2NjY2NyIgeT0iMTAzLjg2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjIyNC4xMzMzMzMzMzMzMyIgeT0iMTI1LjczMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzAwLjY2NjY2NjY2NjY3IiB5PSIxMDMuODY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDgwNjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzIyLjUzMzMzMzMzMzMzIiB5PSIxMjUuNzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIzOTkuMDY2NjY2NjY2NjciIHk9IjEwMy44NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI0MjAuOTMzMzMzMzMzMzMiIHk9IjEyNS43MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjExNTMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNDk3LjQ2NjY2NjY2NjY3IiB5PSIxMDMuODY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDI4NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNTE5LjMzMzMzMzMzMzMzIiB5PSIxMjUuNzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wOTg7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI1LjQ2NjY2NjY2NjY2NjciIHk9IjIwMi4yNjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjI3LjMzMzMzMzMzMzMzMyIgeT0iMjI0LjEzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMTAzLjg2NjY2NjY2NjY3IiB5PSIyMDIuMjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTMyNjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMjUuNzMzMzMzMzMzMzMiIHk9IjIyNC4xMzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEyNDtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjIwMi4yNjY2NjY2NjY2NyIgeT0iMjAyLjI2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEyNDtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjIyNC4xMzMzMzMzMzMzMyIgeT0iMjI0LjEzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzAwLjY2NjY2NjY2NjY3IiB5PSIyMDIuMjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDQ2O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzIyLjUzMzMzMzMzMzMzIiB5PSIyMjQuMTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4wODA2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIzOTkuMDY2NjY2NjY2NjciIHk9IjIwMi4yNjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjQyMC45MzMzMzMzMzMzMyIgeT0iMjI0LjEzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNDk3LjQ2NjY2NjY2NjY3IiB5PSIyMDIuMjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDk4O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNTE5LjMzMzMzMzMzMzMzIiB5PSIyMjQuMTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI1LjQ2NjY2NjY2NjY2NjciIHk9IjMwMC42NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMTUzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjI3LjMzMzMzMzMzMzMzMyIgeT0iMzIyLjUzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTU7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMDMuODY2NjY2NjY2NjciIHk9IjMwMC42NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIxMjUuNzMzMzMzMzMzMzMiIHk9IjMyMi41MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMjAyLjI2NjY2NjY2NjY3IiB5PSIzMDAuNjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTMyNjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMjQuMTMzMzMzMzMzMzMiIHk9IjMyMi41MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAzNzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjMwMC42NjY2NjY2NjY2NyIgeT0iMzAwLjY2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjE1O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzIyLjUzMzMzMzMzMzMzIiB5PSIzMjIuNTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyMiIgc3R5bGU9Im9wYWNpdHk6MC4xMTUzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjM5OS4wNjY2NjY2NjY2NyIgeT0iMzAwLjY2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAzNzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjQyMC45MzMzMzMzMzMzMyIgeT0iMzIyLjUzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDk4O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNDk3LjQ2NjY2NjY2NjY3IiB5PSIzMDAuNjY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNTE5LjMzMzMzMzMzMzMzIiB5PSIzMjIuNTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjUuNDY2NjY2NjY2NjY2NyIgeT0iMzk5LjA2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA4MDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjI3LjMzMzMzMzMzMzMzMyIgeT0iNDIwLjkzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDQ2O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMTAzLjg2NjY2NjY2NjY3IiB5PSIzOTkuMDY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMTI1LjczMzMzMzMzMzMzIiB5PSI0MjAuOTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMjQ7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMDIuMjY2NjY2NjY2NjciIHk9IjM5OS4wNjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMjQ7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMjQuMTMzMzMzMzMzMzMiIHk9IjQyMC45MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjEzMjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzAwLjY2NjY2NjY2NjY3IiB5PSIzOTkuMDY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDYzMzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzIyLjUzMzMzMzMzMzMzIiB5PSI0MjAuOTMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMjtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjM5OS4wNjY2NjY2NjY2NyIgeT0iMzk5LjA2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA5ODtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjQyMC45MzMzMzMzMzMzMyIgeT0iNDIwLjkzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDI4NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNDk3LjQ2NjY2NjY2NjY3IiB5PSIzOTkuMDY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMTE1MzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI1MTkuMzMzMzMzMzMzMzMiIHk9IjQyMC45MzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjA1NDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjUuNDY2NjY2NjY2NjY2NyIgeT0iNDk3LjQ2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA4MDY2NjY2NjY2NjY2NztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjI3LjMzMzMzMzMzMzMzMyIgeT0iNTE5LjMzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDgwNjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMTAzLjg2NjY2NjY2NjY3IiB5PSI0OTcuNDY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDg5MzMzMzMzMzMzMzMzO3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMTI1LjczMzMzMzMzMzMzIiB5PSI1MTkuMzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMDIuMjY2NjY2NjY2NjciIHk9IjQ5Ny40NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wNTQ2NjY2NjY2NjY2Njc7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSIyMjQuMTMzMzMzMzMzMzMiIHk9IjUxOS4zMzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjIyIiBzdHlsZT0ib3BhY2l0eTowLjA2MzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjMwMC42NjY2NjY2NjY2NyIgeT0iNDk3LjQ2NjY2NjY2NjY3IiB3aWR0aD0iNzYuNTMzMzMzMzMzMzMzIiBoZWlnaHQ9Ijc2LjUzMzMzMzMzMzMzMyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjEyNDtzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjxyZWN0IHg9IjMyMi41MzMzMzMzMzMzMyIgeT0iNTE5LjMzMzMzMzMzMzMzIiB3aWR0aD0iMzIuOCIgaGVpZ2h0PSIzMi44IiBmaWxsPSJub25lIiBzdHJva2U9IiMyMjIiIHN0eWxlPSJvcGFjaXR5OjAuMDQ2O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iMzk5LjA2NjY2NjY2NjY3IiB5PSI0OTcuNDY2NjY2NjY2NjciIHdpZHRoPSI3Ni41MzMzMzMzMzMzMzMiIGhlaWdodD0iNzYuNTMzMzMzMzMzMzMzIiBmaWxsPSJub25lIiBzdHJva2U9IiNkZGQiIHN0eWxlPSJvcGFjaXR5OjAuMDU0NjY2NjY2NjY2NjY3O3N0cm9rZS13aWR0aDoxMC45MzMzMzMzMzMzMzNweDsiIC8+PHJlY3QgeD0iNDIwLjkzMzMzMzMzMzMzIiB5PSI1MTkuMzMzMzMzMzMzMzMiIHdpZHRoPSIzMi44IiBoZWlnaHQ9IjMyLjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4xMjQ7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI0OTcuNDY2NjY2NjY2NjciIHk9IjQ5Ny40NjY2NjY2NjY2NyIgd2lkdGg9Ijc2LjUzMzMzMzMzMzMzMyIgaGVpZ2h0PSI3Ni41MzMzMzMzMzMzMzMiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2RkZCIgc3R5bGU9Im9wYWNpdHk6MC4wMzczMzMzMzMzMzMzMzM7c3Ryb2tlLXdpZHRoOjEwLjkzMzMzMzMzMzMzM3B4OyIgLz48cmVjdCB4PSI1MTkuMzMzMzMzMzMzMzMiIHk9IjUxOS4zMzMzMzMzMzMzMyIgd2lkdGg9IjMyLjgiIGhlaWdodD0iMzIuOCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGRkIiBzdHlsZT0ib3BhY2l0eTowLjAzNzMzMzMzMzMzMzMzMztzdHJva2Utd2lkdGg6MTAuOTMzMzMzMzMzMzMzcHg7IiAvPjwvc3ZnPg==',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI3NyIgaGVpZ2h0PSI0NSIgdmlld2JveD0iMCAwIDc3IDQ1IiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJub25lIj48cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJyZ2IoNjksIDExNCwgMTM3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgLTcuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAzNy41KSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMi45OTAzODEwNTY3NjcsIC03LjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMi45OTAzODEwNTY3NjcsIDM3LjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI1Ljk4MDc2MjExMzUzMywgLTcuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyNS45ODA3NjIxMTM1MzMsIDM3LjUpIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzguOTcxMTQzMTcwMywgLTcuNSkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzguOTcxMTQzMTcwMywgMzcuNSkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDUxLjk2MTUyNDIyNzA2NiwgLTcuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAzNzMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNTEuOTYxNTI0MjI3MDY2LCAzNy41KSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTQxMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDY0Ljk1MTkwNTI4MzgzMywgLTcuNSkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNjQuOTUxOTA1MjgzODMzLCAzNy41KSByb3RhdGUoMCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjEzMjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAwKSByb3RhdGUoMCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA5OCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTIuOTkwMzgxMDU2NzY3LCAwKSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyNS45ODA3NjIxMTM1MzMsIDApIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM4Ljk3MTE0MzE3MDMsIDApIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMjQiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDUxLjk2MTUyNDIyNzA2NiwgMCkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDY0Ljk1MTkwNTI4MzgzMywgMCkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCA3LjUpIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTIuOTkwMzgxMDU2NzY3LCA3LjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDgwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyNS45ODA3NjIxMTM1MzMsIDcuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjAyODY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzguOTcxMTQzMTcwMywgNy41KSByb3RhdGUoMCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjEzMjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg1MS45NjE1MjQyMjcwNjYsIDcuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA5OCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNjQuOTUxOTA1MjgzODMzLCA3LjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTMyNjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsIDE1KSByb3RhdGUoMCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMi45OTAzODEwNTY3NjcsIDE1KSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTUiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI1Ljk4MDc2MjExMzUzMywgMTUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM4Ljk3MTE0MzE3MDMsIDE1KSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDUxLjk2MTUyNDIyNzA2NiwgMTUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTE1MzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDY0Ljk1MTkwNTI4MzgzMywgMTUpIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xNSIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgMjIuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMi45OTAzODEwNTY3NjcsIDIyLjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyNS45ODA3NjIxMTM1MzMsIDIyLjUpIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzguOTcxMTQzMTcwMywgMjIuNSkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDUxLjk2MTUyNDIyNzA2NiwgMjIuNSkgcm90YXRlKDE4MCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjAyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2NC45NTE5MDUyODM4MzMsIDIyLjUpIHJvdGF0ZSgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAzMCkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEyLjk5MDM4MTA1Njc2NywgMzApIHJvdGF0ZSgxODAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjUuOTgwNzYyMTEzNTMzLCAzMCkgcm90YXRlKDAsIDYuNDk1MTkwNTI4MzgzMywgNy41KSIgLz48cG9seWxpbmUgcG9pbnRzPSIwLCAwLCAxMi45OTAzODEwNTY3NjcsIDcuNSwgMCwgMTUsIDAsIDAiIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDM4Ljk3MTE0MzE3MDMsIDMwKSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMCwgMCwgMTIuOTkwMzgxMDU2NzY3LCA3LjUsIDAsIDE1LCAwLCAwIiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTI0IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg1MS45NjE1MjQyMjcwNjYsIDMwKSByb3RhdGUoMCwgNi40OTUxOTA1MjgzODMzLCA3LjUpIiAvPjxwb2x5bGluZSBwb2ludHM9IjAsIDAsIDEyLjk5MDM4MTA1Njc2NywgNy41LCAwLCAxNSwgMCwgMCIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2NC45NTE5MDUyODM4MzMsIDMwKSByb3RhdGUoMTgwLCA2LjQ5NTE5MDUyODM4MzMsIDcuNSkiIC8+PC9zdmc+',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OTAiIGhlaWdodD0iNDkwIiB2aWV3Ym94PSIwIDAgNDkwIDQ5MCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDc4LCAxNDMsIDE2OSkiIC8+PHJlY3QgeD0iMCIgeT0iMTYiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjIwIiBvcGFjaXR5PSIwLjE1IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjUyIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxNiIgb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIwIiB5PSI4MSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNyIgb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iMTAxIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxNSIgb3BhY2l0eT0iMC4xMDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSIxMjciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjYiIG9wYWNpdHk9IjAuMDI4NjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjEzOSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTMiIG9wYWNpdHk9IjAuMDg5MzMzMzMzMzMzMzMzIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjE2OSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTQiIG9wYWNpdHk9IjAuMDk4IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjAiIHk9IjE5MSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iNyIgb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iMjE1IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMiIgb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMCIgeT0iMjM1IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxNCIgb3BhY2l0eT0iMC4wOTgiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMCIgeT0iMjYxIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMiIgb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMCIgeT0iMjg4IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxOCIgb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIwIiB5PSIzMjAiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjciIG9wYWNpdHk9IjAuMDM3MzMzMzMzMzMzMzMzIiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjAiIHk9IjM0MiIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTUiIG9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iMzY1IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIyMCIgb3BhY2l0eT0iMC4xNSIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIwIiB5PSI0MDEiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjUiIG9wYWNpdHk9IjAuMDIiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMCIgeT0iNDI1IiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSI5IiBvcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSI0NDciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjE3IiBvcGFjaXR5PSIwLjEyNCIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIwIiB5PSI0NzkiIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjExIiBvcGFjaXR5PSIwLjA3MiIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIxNiIgeT0iMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMTUiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iNTIiIHk9IjAiIHdpZHRoPSIxNiIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjExNTMzMzMzMzMzMzMzIiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjgxIiB5PSIwIiB3aWR0aD0iNyIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAzNzMzMzMzMzMzMzMzMyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIxMDEiIHk9IjAiIHdpZHRoPSIxNSIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjEyNyIgeT0iMCIgd2lkdGg9IjYiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMTM5IiB5PSIwIiB3aWR0aD0iMTMiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMTY5IiB5PSIwIiB3aWR0aD0iMTQiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wOTgiIGZpbGw9IiMyMjIiIC8+PHJlY3QgeD0iMTkxIiB5PSIwIiB3aWR0aD0iNyIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjAzNzMzMzMzMzMzMzMzMyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIyMTUiIHk9IjAiIHdpZHRoPSIxMiIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA4MDY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIyMzUiIHk9IjAiIHdpZHRoPSIxNCIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA5OCIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIyNjEiIHk9IjAiIHdpZHRoPSIxMiIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjA4MDY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgLz48cmVjdCB4PSIyODgiIHk9IjAiIHdpZHRoPSIxOCIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjEzMjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjMyMCIgeT0iMCIgd2lkdGg9IjciIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wMzczMzMzMzMzMzMzMzMiIGZpbGw9IiNkZGQiIC8+PHJlY3QgeD0iMzQyIiB5PSIwIiB3aWR0aD0iMTUiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4xMDY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSIzNjUiIHk9IjAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIxMDAlIiBvcGFjaXR5PSIwLjE1IiBmaWxsPSIjMjIyIiAvPjxyZWN0IHg9IjQwMSIgeT0iMCIgd2lkdGg9IjUiIGhlaWdodD0iMTAwJSIgb3BhY2l0eT0iMC4wMiIgZmlsbD0iI2RkZCIgLz48cmVjdCB4PSI0MjUiIHk9IjAiIHdpZHRoPSI5IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjQ0NyIgeT0iMCIgd2lkdGg9IjE3IiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMTI0IiBmaWxsPSIjZGRkIiAvPjxyZWN0IHg9IjQ3OSIgeT0iMCIgd2lkdGg9IjExIiBoZWlnaHQ9IjEwMCUiIG9wYWNpdHk9IjAuMDcyIiBmaWxsPSIjZGRkIiAvPjwvc3ZnPg==',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjMiIGhlaWdodD0iMTQyIiB2aWV3Ym94PSIwIDAgMTIzIDE0MiIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0icmdiKDYwLCAxNDcsIDEyMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTEwLjMzMzMzMzMzMzMzMywgLTIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTEzLjY2NjY2NjY2NjY3LCAtMjMuNjY2NjY2NjY2NjY3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDU0NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTAuMzMzMzMzMzMzMzMzLCAxMTguMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wNTQ2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDExMy42NjY2NjY2NjY2NywgMTE4LjMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTQxMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwLjMzMzMzMzMzMzMzMywgLTIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMC4zMzMzMzMzMzMzMzMsIDExOC4zMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjE1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMSwgLTIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjE1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMSwgMTE4LjMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDg5MzMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg1MS42NjY2NjY2NjY2NjcsIC0yMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDUxLjY2NjY2NjY2NjY2NywgMTE4LjMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMTMyNjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDcyLjMzMzMzMzMzMzMzMywgLTIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjEzMjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg3Mi4zMzMzMzMzMzMzMzMsIDExOC4zMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MywgLTIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjEwNjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg5MywgMTE4LjMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDQ2IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCAwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDQ2IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMjQsIDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDIwLjY2NjY2NjY2NjY2NywgMCkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA1NDY2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDEuMzMzMzMzMzMzMzMzLCAwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDI4NjY2NjY2NjY2NjY3IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg2MiwgMCkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA2MzMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoODIuNjY2NjY2NjY2NjY3LCAwKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDk4IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMDMuMzMzMzMzMzMzMzMsIDApIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xMC4zMzMzMzMzMzMzMzMsIDIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjA0NiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTEzLjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMTAuMzMzMzMzMzMzMzMzLCAyMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wODkzMzMzMzMzMzMzMzMiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDMxLCAyMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNTEuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMzI2NjY2NjY2NjY2NyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNzIuMzMzMzMzMzMzMzMzLCAyMy42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4wMiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTMsIDIzLjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA3MiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwgNDcuMzMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDcyIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxMjQsIDQ3LjMzMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA3MiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjAuNjY2NjY2NjY2NjY3LCA0Ny4zMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xMTUzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDEuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xNDEzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNjIsIDQ3LjMzMzMzMzMzMzMzMykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA4OTMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoODIuNjY2NjY2NjY2NjY3LCA0Ny4zMzMzMzMzMzMzMzMpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiNkZGQiIGZpbGwtb3BhY2l0eT0iMC4xMjQiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwMy4zMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xMC4zMzMzMzMzMzMzMzMsIDcxKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMTA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDExMy42NjY2NjY2NjY2NywgNzEpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wMjg2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwLjMzMzMzMzMzMzMzMywgNzEpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wODA2NjY2NjY2NjY2NjciIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDMxLCA3MSkiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iIzIyMiIgZmlsbC1vcGFjaXR5PSIwLjE1IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg1MS42NjY2NjY2NjY2NjcsIDcxKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjZGRkIiBmaWxsLW9wYWNpdHk9IjAuMDIiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDcyLjMzMzMzMzMzMzMzMywgNzEpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4xNSIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoOTMsIDcxKSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDQ2IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLCA5NC42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wNDYiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEyNCwgOTQuNjY2NjY2NjY2NjY3KSIgLz48cG9seWxpbmUgcG9pbnRzPSIxMC4zMzMzMzMzMzMzMzMsIDAsIDIwLjY2NjY2NjY2NjY2NywgMjMuNjY2NjY2NjY2NjY3LCAxMC4zMzMzMzMzMzMzMzMsIDQ3LjMzMzMzMzMzMzMzMywgMCwgMjMuNjY2NjY2NjY2NjY3IiBmaWxsPSIjMjIyIiBmaWxsLW9wYWNpdHk9IjAuMDQ2IiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyMC42NjY2NjY2NjY2NjcsIDk0LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjE0MTMzMzMzMzMzMzMzIiBzdHJva2U9IiMwMDAiIHN0cm9rZS1vcGFjaXR5PSIwLjAyIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0MS4zMzMzMzMzMzMzMzMsIDk0LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA3MiIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNjIsIDk0LjY2NjY2NjY2NjY2NykiIC8+PHBvbHlsaW5lIHBvaW50cz0iMTAuMzMzMzMzMzMzMzMzLCAwLCAyMC42NjY2NjY2NjY2NjcsIDIzLjY2NjY2NjY2NjY2NywgMTAuMzMzMzMzMzMzMzMzLCA0Ny4zMzMzMzMzMzMzMzMsIDAsIDIzLjY2NjY2NjY2NjY2NyIgZmlsbD0iI2RkZCIgZmlsbC1vcGFjaXR5PSIwLjA4OTMzMzMzMzMzMzMzMyIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utb3BhY2l0eT0iMC4wMiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoODIuNjY2NjY2NjY2NjY3LCA5NC42NjY2NjY2NjY2NjcpIiAvPjxwb2x5bGluZSBwb2ludHM9IjEwLjMzMzMzMzMzMzMzMywgMCwgMjAuNjY2NjY2NjY2NjY3LCAyMy42NjY2NjY2NjY2NjcsIDEwLjMzMzMzMzMzMzMzMywgNDcuMzMzMzMzMzMzMzMzLCAwLCAyMy42NjY2NjY2NjY2NjciIGZpbGw9IiMyMjIiIGZpbGwtb3BhY2l0eT0iMC4wOTgiIHN0cm9rZT0iIzAwMCIgc3Ryb2tlLW9wYWNpdHk9IjAuMDIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEwMy4zMzMzMzMzMzMzMywgOTQuNjY2NjY2NjY2NjY3KSIgLz48L3N2Zz4='
    );
    return $icons[array_rand($icons)];
}