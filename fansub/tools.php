<?php
if(!function_exists('add_filter')) exit;

function fansub_maintenance_mode_default_settings() {
    $defaults = array(
        'title' => __('Maintenance mode', 'fansub'),
        'heading' => __('Maintenance mode', 'fansub'),
        'text' => __('<p>Sorry for the inconvenience.<br />Our website is currently undergoing scheduled maintenance.<br />Thank you for your understanding.</p>', 'fansub')
    );
    return apply_filters('fansub_maintenance_mode_default_settings', $defaults);
}

function fansub_prevent_author_see_another_post() {
    $use = false;
    $use = apply_filters('fansub_prevent_author_see_another_post', $use);
    return $use;
}

function fansub_delete_old_file($path, $interval) {
    $files = scandir($path);
    $now = time();
    foreach($files as $file) {
        $file = trailingslashit($path) . $file;
        if(is_file($file)) {
            $file_time = filemtime($file);
            if(($now - $file_time) >= $interval) {
                chmod($file, 0777);
                @unlink($file);
            }
        }
    }
}

function fansub_use_core_style() {
    return apply_filters('fansub_use_core_style', true);
}

function fansub_use_superfish_menu() {
    return apply_filters('fansub_use_superfish_menu', true);
}

function fansub_maintenance_mode_settings() {
    $defaults = fansub_maintenance_mode_default_settings();
    $args = get_option('fansub_maintenance');
    $args = wp_parse_args($args, $defaults);
    return apply_filters('fansub_maintenance_mode_settings', $args);
}

function fansub_google_login_script($args = array()) {
    $connect = fansub_get_value_by_key($args, 'connect');
    if(is_user_logged_in() && !$connect) {
        return;
    }
    $clientid = fansub_get_value_by_key($args, 'clientid', fansub_get_google_client_id());
    if(empty($clientid)) {
        fansub_debug_log(__('Please set your Google Client ID first.', 'fansub'));
        return;
    }
    ?>
    <script type="text/javascript">
        function fansub_google_login() {
            var params = {
                clientid: '<?php echo $clientid; ?>',
                cookiepolicy: 'single_host_origin',
                callback: 'fansub_google_login_on_signin',
                scope: 'email',
                theme: 'dark'
            };
            gapi.auth.signIn(params);
        }
        function fansub_google_login_on_signin(response) {
            if(response['status']['signed_in'] && !response['_aa']) {
                gapi.client.load('plus', 'v1', fansub_google_login_client_loaded);
            }
        }
        function fansub_google_login_client_loaded(response) {
            var request = gapi.client.plus.people.get({userId: 'me'});
            request.execute(function(response) {
                fansub_google_login_connected_callback(response);
            });
        }
        function fansub_google_logout() {
            gapi.auth.signOut();
            location.reload();
        }
        function fansub_google_login_connected_callback(response) {
            (function($) {
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: fansub.ajax_url,
                    data: {
                        action: 'fansub_social_login_google',
                        data: JSON.stringify(response),
                        connect: <?php echo fansub_bool_to_int($connect); ?>
                    },
                    success: function(response){
                        var href = window.location.href;
                        if($.trim(response.redirect_to)) {
                            href = response.redirect_to;
                        }
                        if(response.logged_in) {
                            window.location.href = href;
                        }
                    }
                });
            })(jQuery);
        }
    </script>
    <?php
}

function fansub_facebook_login_script($args = array()) {
    $connect = fansub_get_value_by_key($args, 'connect');
    if(is_user_logged_in() && !$connect) {
        return;
    }
    $lang = fansub_get_language();
    $language = fansub_get_value_by_key($args, 'language');
    if(empty($language) && 'vi' === $lang) {
        $language = 'vi_VN';
    }
    $app_id = fansub_get_wpseo_social_facebook_app_id();
    if(empty($app_id)) {
        fansub_debug_log(__('Please set your Facebook APP ID first.', 'fansub'));
        return;
    }
    ?>
    <script type="text/javascript">
        window.fansub = window.fansub || {};
        function fansub_facebook_login_status_callback(response) {
            if(response.status === 'connected') {
                fansub_facebook_login_connected_callback();
            } else if(response.status === 'not_authorized') {

            } else {

            }
        }
        function fansub_facebook_login() {
            FB.login(function(response) {
                fansub_facebook_login_status_callback(response);
            }, { scope: 'email,public_profile,user_friends' });
        }
        window.fbAsyncInit = function() {
            FB.init({
                appId: '<?php echo $app_id; ?>',
                cookie: true,
                xfbml: true,
                version: 'v<?php echo FANSUB_FACEBOOK_GRAPH_API_VERSION; ?>'
            });
        };
        if(typeof FB === 'undefined') {
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/<?php echo $language; ?>/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        }
        function fansub_facebook_login_connected_callback() {
            FB.api('/me', {fields: 'id,name,first_name,last_name,picture,verified,email'}, function(response) {
                (function($) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: fansub.ajax_url,
                        data: {
                            action: 'fansub_social_login_facebook',
                            data: JSON.stringify(response),
                            connect: <?php echo fansub_bool_to_int($connect); ?>
                        },
                        success: function(response){
                            var href = window.location.href;
                            if($.trim(response.redirect_to)) {
                                href = response.redirect_to;
                            }
                            if(response.logged_in) {
                                window.location.href = href;
                            }
                        }
                    });
                })(jQuery);
            });
        }
    </script>
    <?php
}

function fansub_get_default_lat_long() {
    $lat_long = array(
        'lat' => '37.42200662799378',
        'lng' => '-122.08403290000001'
    );
    $data = get_option('fansub_geo');
    $lat = fansub_get_value_by_key($data, 'default_lat');
    $lng = fansub_get_value_by_key($data, 'default_lng');
    if(!empty($lat) && !empty($lng)) {
        $lat_long['lat'] = $lat;
        $lat_long['lng'] = $lng;
    } else {
        if('vi' == fansub_get_language()) {
            $lat_long['lat'] = '21.003118';
            $lat_long['lng'] = '105.820141';
        }
    }
    return apply_filters('fansub_default_lat_lng', $lat_long);
}

function fansub_register_post_type_news($args = array()) {
    $lang = fansub_get_language();
    $slug = 'news';
    if('vi' == $lang) {
        $slug = 'tin-tuc';
    }
    $slug = apply_filters('fansub_post_type_news_base_slug', $slug);
    $defaults = array(
        'name' => __('News', 'fansub'),
        'slug' => $slug,
        'post_type' => 'news',
        'show_in_admin_bar' => true,
        'supports' => array('editor', 'thumbnail', 'comments')
    );
    $args = wp_parse_args($args, $defaults);
    fansub_register_post_type($args);
    $slug = 'news-cat';
    if('vi' == $lang) {
        $slug = 'chuyen-muc';
    }
    $slug = apply_filters('fansub_taxonomy_news_category_base_slug', $slug);
    $args = array(
        'name' => __('News Categories', 'fansub'),
        'singular_name' => __('News Category', 'fansub'),
        'post_types' => 'news',
        'menu_name' => __('Categories', 'fansub'),
        'slug' => $slug,
        'taxonomy' => 'news_cat'
    );
    fansub_register_taxonomy($args);
    $news_tag = apply_filters('fansub_post_type_news_tag', false);
    if($news_tag) {
        $slug = 'news-tag';
        if('vi' == $lang) {
            $slug = 'the';
        }
        $slug = apply_filters('fansub_taxonomy_news_tag_base_slug', $slug);
        $args = array(
            'name' => __('News Tags', 'fansub'),
            'singular_name' => __('News Tag', 'fansub'),
            'post_types' => 'news',
            'menu_name' => __('Tags', 'fansub'),
            'slug' => $slug,
            'hierarchical' => false,
            'taxonomy' => 'news_tag'
        );
        fansub_register_taxonomy($args);
    }
}

function fansub_register_lib_google_maps($api_key = null) {
    if(empty($api_key)) {
        $options = get_option('fansub_option_social');
        $api_key = fansub_get_value_by_key($options, 'google_api_key');
    }
    if(empty($api_key)) {
        return;
    }
    wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key, array(), false, true);
}

function fansub_register_lib_tinymce() {
    wp_enqueue_script('tinymce', '//cdn.tinymce.com/' . FANSUB_TINYMCE_VERSION . '/tinymce.min.js', array(), false, true);
}

function fansub_inline_css($elements, $properties) {
    $css = fansub_build_css_rule($elements, $properties);
    if(!empty($css)) {
        $style = new FANSUB_HTML('style');
        $style->set_attribute('type', 'text/css');
        $css = fansub_minify_css($css);
        $style->set_text($css);
        if(!empty($css)) {
            $style->output();
        }
    }
}

function fansub_favorite_post_button_text($post_id = null) {
    if(!fansub_id_number_valid($post_id)) {
        $post_id = get_the_ID();
    }
    $text = '<i class="fa fa-heart-o"></i> Lưu tin';
    if(is_user_logged_in()) {
        $user = wp_get_current_user();
        $favorite = fansub_get_user_favorite_posts($user->ID);
        if(in_array($post_id, $favorite)) {
            $text = '<i class="fa fa-heart"></i> Bỏ lưu';;
        }
    }
    $text = apply_filters('fansub_favorite_post_button_text', $text);
    echo $text;
}

function fansub_get_geo_code($args = array()) {
    if(!is_array($args) && !empty($args)) {
        $args = array(
            'address' => $args
        );
    }
    $options = get_option('fansub_option_social');
    $api_key = fansub_get_value_by_key($options, 'google_api_key');
    $defaults = array(
        'sensor' => false,
        'region' => 'Vietnam',
        'key' => $api_key
    );
    $args = wp_parse_args($args, $defaults);
    $address = fansub_get_value_by_key($args, 'address');
    if(empty($address)) {
        return '';
    }
    $address = str_replace(' ', '+', $address);
    $args['address'] = $address;
    $transient_name = 'fansub_geo_code_' . md5(implode('_', $args));
    if(false === ($results = get_transient($transient_name))) {
        $base = 'https://maps.googleapis.com/maps/api/geocode/json';
        $base = add_query_arg($args, $base);
        $json = @file_get_contents($base);
        $results = json_decode($json);
        if('OK' === $results->status) {
            set_transient($transient_name, $results, MONTH_IN_SECONDS);
        }
    }
    return $results;
}

function fansub_generate_min_file($file, $extension = 'js', $compress_min_file = false, $force_compress = false) {
    $transient_name = 'fansub_minified_' . md5($file);
    if(false === get_transient($transient_name) || $force_compress) {
        if(file_exists($file)) {
            $extension = strtolower($extension);
            if('js' === $extension) {
                $minified = fansub_minify_js($file);
            } else {
                $minified = fansub_minify_css($file, true);
            }
            if(!empty($minified)) {
                if($compress_min_file) {
                    if(!file_exists($file)) {
                        $handler = fopen($file, 'w');
                        fwrite($handler, $minified);
                        fclose($handler);
                    } else {
                        @file_put_contents($file, $minified);
                    }
                } else {
                    $info = pathinfo($file);
                    $basename = $info['basename'];
                    $filename = $info['filename'];
                    $extension = $info['extension'];
                    $min_name = $filename;
                    $min_name .= '.min';
                    if(!empty($extension)) {
                        $min_name .= '.' . $extension;
                    }
                    $min_file = str_replace($basename, $min_name, $file);
                    $handler = fopen($min_file, 'w');
                    fwrite($handler, $minified);
                    fclose($handler);
                }
                set_transient($transient_name, 1, 15 * MINUTE_IN_SECONDS);
                fansub_debug_log(sprintf(__('File %s is compressed successfully!', 'fansub'), $file));
            }
        }
    }
}

function fansub_compress_style($dir, $compress_min_file = false, $force_compress = false) {
    $files = scandir($dir);
    $my_files = array();
    $min_files = array();
    foreach($files as $file) {
        $info = pathinfo($file);
        if(isset($info['extension']) && 'css' == $info['extension']) {
            $base_name = $info['basename'];
            if(false !== strpos($base_name, '.min')) {
                if($compress_min_file) {
                    $min_files[] = trailingslashit($dir) . $file;
                }
                continue;
            }
            $my_files[] = trailingslashit($dir) . $file;
        }
    }
    if(fansub_array_has_value($min_files) || $compress_min_file) {
        foreach($min_files as $file) {
            fansub_generate_min_file($file, 'css', true, $force_compress);
        }
        return;
    }
    if(fansub_array_has_value($my_files)) {
        foreach($my_files as $file) {
            fansub_generate_min_file($file, 'css', false, $force_compress);
        }
    }
}

function fansub_compress_script($dir, $compress_min_file = false, $force_compress = false) {
    $files = scandir($dir);
    $my_files = array();
    $min_files = array();
    foreach($files as $file) {
        $info = pathinfo($file);
        if(isset($info['extension']) && 'js' == $info['extension']) {
            $base_name = $info['basename'];
            if(false !== strpos($base_name, '.min')) {
                if($compress_min_file) {
                    $min_files[] = trailingslashit($dir) . $file;
                }
                continue;
            }
            $my_files[] = trailingslashit($dir) . $file;
        }
    }
    if(fansub_array_has_value($min_files) || $compress_min_file) {
        foreach($min_files as $file) {
            fansub_generate_min_file($file, 'js', true, $force_compress);
        }
        return;
    }
    if(fansub_array_has_value($my_files)) {
        foreach($my_files as $file) {
            fansub_generate_min_file($file, 'js', false, $force_compress);
        }
    }
}

function fansub_compress_style_and_script($args = array()) {
    $type = fansub_get_value_by_key($args, 'type');
    $force_compress = fansub_get_value_by_key($args, 'force_compress');
    if(fansub_array_has_value($type)) {
        $compress_css = false;
        if(in_array('css', $type)) {
            $compress_css = true;
            $fansub_css_path = FANSUB_PATH . '/css';
            fansub_compress_style($fansub_css_path, false, $force_compress);
            if(defined('FANSUB_THEME_VERSION')) {
                $fansub_css_path = FANSUB_THEME_PATH . '/css';
                fansub_compress_style($fansub_css_path, false, $force_compress);
            }
        }
        $compress_js = false;
        if(in_array('js', $type)) {
            $compress_js = true;
            $fansub_js_path = FANSUB_PATH . '/js';
            fansub_compress_script($fansub_js_path, false, $force_compress);
            if(defined('FANSUB_THEME_VERSION')) {
                $fansub_js_path = FANSUB_THEME_PATH . '/js';
                fansub_compress_script($fansub_js_path, false, $force_compress);
            }
        }
        if($compress_css || $compress_js) {
            unset($type['recompress']);
        }
        if(in_array('recompress', $type)) {
            if(defined('FANSUB_THEME_VERSION')) {
                $fansub_js_path = FANSUB_THEME_PATH . '/js';
                fansub_compress_script($fansub_js_path, true, $force_compress);
                $fansub_css_path = FANSUB_THEME_PATH . '/css';
                fansub_compress_style($fansub_css_path, true, $force_compress);
            }
        }
        $compress_paths = apply_filters('fansub_compress_paths', array());
        foreach($compress_paths as $path) {
            $css_path = trailingslashit($path) . 'css';
            $js_path = trailingslashit($path) . 'js';
            $compress_css = false;
            if(in_array('css', $type)) {
                $compress_css = true;
                fansub_compress_style($css_path, false, $force_compress);
            }
            $compress_js = false;
            if(in_array('js', $type)) {
                $compress_js = true;
                fansub_compress_script($js_path, false, $force_compress);
            }
            if($compress_css || $compress_js) {
                unset($type['recompress']);
            }
            if(in_array('recompress', $type)) {
                fansub_compress_script($js_path, true, $force_compress);
                fansub_compress_style($css_path, true, $force_compress);
            }
        }
    }
}

function fansub_php_thumb() {

}

function fansub_post_rating_ajax_callback() {
    $result = array(
        'success' => false
    );
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
    if(fansub_id_number_valid($post_id)) {
        $score = isset($_POST['score']) ? $_POST['score'] : 0;
        if(is_numeric($score) && $score > 0) {
            $number = isset($_POST['number']) ? $_POST['number'] : 5;
            $number_max = isset($_POST['number_max']) ? $_POST['number_max'] : 5;
            $high_number = $number;
            if($number > $number_max) {
                $high_number = $number_max;
            }
            $ratings_score = floatval(get_post_meta($post_id, 'ratings_score', true));
            $ratings_score += $score;
            $ratings_users = absint(get_post_meta($post_id, 'ratings_users', true));
            $ratings_users++;
            $high_ratings_users = absint(get_post_meta($post_id, 'high_ratings_users', true));
            if($score == $high_number) {
                $high_ratings_users++;
                update_post_meta($post_id, 'high_ratings_users', $high_ratings_users);
            }
            $ratings_average = $score;
            update_post_meta($post_id, 'ratings_users', $ratings_users);
            update_post_meta($post_id, 'ratings_score', $ratings_score);
            if($ratings_users > 0) {
                $ratings_average = $ratings_score / $ratings_users;
            }
            update_post_meta($post_id, 'ratings_average', $ratings_average);
            $result['success'] = true;
            $result['score'] = $ratings_average;
            $session_key = 'fansub_post_' . $post_id . '_rated';
            $_SESSION[$session_key] = 1;
            do_action('fansub_post_rated', $score, $post_id);
        }
    }
    return $result;
}

function fansub_change_url($new_url, $old_url = '', $force_update = false) {
    $transient_name = 'fansub_update_data_after_url_changed';
    $site_url = trailingslashit(get_bloginfo('url'));
    if(!empty($old_url)) {
        $old_url = trailingslashit($old_url);
        if($old_url != $site_url && !$force_update) {
            return;
        }
    } else {
        $old_url = $site_url;
    }
    $new_url = trailingslashit($new_url);
    if($old_url == $new_url && !$force_update) {
        return;
    }
    if(false === get_transient($transient_name) || $force_update) {
        global $wpdb;
        $wpdb->query("UPDATE $wpdb->options SET option_value = replace(option_value, '$old_url', '$new_url') WHERE option_name = 'home' OR option_name = 'siteurl'");
        $wpdb->query("UPDATE $wpdb->posts SET guid = (REPLACE (guid, '$old_url', '$new_url'))");
        $wpdb->query("UPDATE $wpdb->posts SET post_content = (REPLACE (post_content, '$old_url', '$new_url'))");

        $wpdb->query("UPDATE $wpdb->postmeta SET meta_value = (REPLACE (meta_value, '$old_url', '$new_url'))");
        $wpdb->query("UPDATE $wpdb->termmeta SET meta_value = (REPLACE (meta_value, '$old_url', '$new_url'))");
        $wpdb->query("UPDATE $wpdb->commentmeta SET meta_value = (REPLACE (meta_value, '$old_url', '$new_url'))");
        $wpdb->query("UPDATE $wpdb->usermeta SET meta_value = (REPLACE (meta_value, '$old_url', '$new_url'))");
        if(is_multisite()) {
            $wpdb->query("UPDATE $wpdb->sitemeta SET meta_value = (REPLACE (meta_value, '$old_url', '$new_url'))");
        }
        set_transient($transient_name, 1, 5 * MINUTE_IN_SECONDS);
    }
}

function fansub_disable_emoji() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
}