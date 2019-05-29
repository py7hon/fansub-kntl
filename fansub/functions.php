<?php
if(!function_exists('add_filter')) exit;

function fansub_use_session() {
    $use_session = apply_filters('fansub_track_user_viewed_posts', false);
    $use_session = apply_filters('fansub_use_session', $use_session);
    return (bool)$use_session;
}

function fansub_session_start() {
    $use_session = fansub_use_session();
    if(!$use_session) {
        return;
    }
    $session_start = true;
    if(version_compare(PHP_VERSION, '5.4', '>=')) {
        if(session_status() == PHP_SESSION_NONE) {
            $session_start = false;
        }
    } else {
        if('' == session_id()) {
            $session_start = false;
        }
    }
    if(!$session_start) {
        do_action('fansub_session_start_before');
        session_start();
    }
}

function fansub_debug_log($message) {
    if(WP_DEBUG === true) {
        if(is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}

function fansub_create_file($path, $content = '') {
    if($fh = fopen($path, 'w')) {
        fwrite($fh, $content, 1024);
        fclose($fh);
    }
}

function fansub_get_pc_ip() {
    $result = '';
    if(function_exists('getHostByName')) {
        if(version_compare(PHP_VERSION, '5.3', '<') && function_exists('php_uname')) {
            $result = getHostByName(php_uname('n'));
        } elseif(function_exists('getHostName')) {
            $result = getHostByName(getHostName());
        }
    }
    return $result;
}

function fansub_get_all_shortcodes() {
    return $GLOBALS['shortcode_tags'];
}

function fansub_get_alphabetical_chars() {
    $result = '#ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $result = str_split($result);
    return $result;
}

function fansub_get_all_sb_shortcodes() {
    $shortcodes = fansub_get_all_shortcodes();
    $result = array();
    foreach($shortcodes as $key => $function) {
        if(('sb' == substr($key, 0, 2) && 'sb' == substr($function, 0, 2)) || ('fansub' == substr($key, 0, 5) && 'fansub' == substr($function, 0, 5))) {
            $result[$key] = $function;
        }
    }
    return $result;
}

function fansub_get_my_shortcodes() {
    return fansub_get_all_sb_shortcodes();
}

function fansub_get_timezone_string() {
    $timezone_string = get_option('timezone_string');
    if(empty($timezone_string)) {
        $timezone_string = 'Asia/Ho_Chi_Minh';
    }
    return $timezone_string;
}

function fansub_get_current_date($format = 'Y-m-d') {
    date_default_timezone_set(fansub_get_timezone_string());
    $result = date($format);
    return $result;
}

function fansub_get_current_datetime_mysql() {
    return fansub_get_current_date('Y-m-d H:i:s');
}

function fansub_is_ip($ip) {
    if(filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }
    return false;
}

function fansub_get_ipinfo($ip) {
    if(!fansub_is_ip($ip)) {
        return '';
    }
    $json = @file_get_contents('http://ipinfo.io/' . $ip);
    $details = json_decode($json);
    $details = (array)$details;
    return $details;
}

function fansub_get_user_isp_ip() {
    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];
    if(fansub_is_ip($client)) {
        $ip = $client;
    } elseif(fansub_is_ip($forward)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    return $ip;
}

function fansub_array_has_value($arr) {
    if(is_array($arr) && count($arr) > 0) {
        return true;
    }
    return false;
}

function fansub_get_plugin_info($plugin_file) {
    if(!file_exists($plugin_file)) {
        $plugin_file = trailingslashit(WP_PLUGIN_DIR) . $plugin_file;
    }
    if(!file_exists($plugin_file)) {
        return null;
    }
    return get_plugin_data($plugin_file);
}

function fansub_get_plugin_name($plugin_file, $default = '') {
    $plugin = fansub_get_plugin_info($plugin_file);
    return fansub_get_value_by_key($plugin, 'Name', $default);
}

function fansub_string_empty($string) {
    if('' === $string) {
        return true;
    }
    return false;
}

function fansub_get_value_by_key($arr, $key, $default = '') {
    if(is_object($key) || is_object($arr) || fansub_string_empty($key)) {
        return $default;
    }
    $has_key = false;
    $arr = fansub_sanitize_array($arr);
    $result = '';
    if(fansub_array_has_value($arr)) {
        if(is_array($key)) {
            if(count($key) == 1) {
                $key = array_shift($key);
                if(isset($arr[$key])) {
                    return $arr[$key];
                }
            } else {
                $tmp = $arr;
                if(is_array($tmp)) {
                    $has_value = false;
                    $level = 0;
                    foreach($key as $index => $child_key) {
                        if(is_array($child_key)) {
                            if(count($child_key) == 1) {
                                $child_key = array_shift($child_key);
                            }
                            $result = fansub_get_value_by_key($tmp, $child_key);
                        } else {
                            if(isset($tmp[$child_key])) {
                                $tmp = $tmp[$child_key];
                                $has_value = true;
                                $level++;
                                $has_key = true;
                            }
                        }
                    }
                    if(!$has_value) {
                        reset($key);
                        $first_key = current($key);
                        if(fansub_array_has_value($arr)) {
                            $tmp = fansub_get_value_by_key($arr, $first_key);
                            if(fansub_array_has_value($tmp)) {
                                $result = fansub_get_value_by_key($tmp, $key);
                            }
                        }
                    }
                    if($has_value && fansub_string_empty($result)) {
                        $result = $tmp;
                    }
                }
            }
        } else {
            if(isset($arr[$key])) {
                $result = $arr[$key];
                $has_key = true;
            } else {
                foreach($arr as $index => $value) {
                    if(is_array($value)) {
                        $result = fansub_get_value_by_key($value, $key);
                    } else {
                        if($key === $index) {
                            $has_key = true;
                            $result = $value;
                        }
                    }
                }
            }
        }
    }
    if(!$has_key) {
        $result = $default;
    }
    return $result;
}

function fansub_get_method_value($key, $method = 'post', $default = '') {
    $method = strtoupper($method);
    switch($method) {
        case 'POST':
            $result = fansub_get_value_by_key($_POST, $key, $default);
            break;
        case 'GET':
            $result = fansub_get_value_by_key($_GET, $key, $default);
            break;
        default:
            $result = fansub_get_value_by_key($_REQUEST, $key, $default);
    }
    return $result;
}

function fansub_array_unique($arr) {
    if(is_array($arr)) {
        $arr = array_map('unserialize', array_unique(array_map('serialize', $arr)));
    }
    return $arr;
}

function fansub_get_terms($taxonomy, $args = array()) {
    global $wp_version;
    $defaults = array(
        'hide_empty' => 0,
        'taxonomy' => $taxonomy
    );
    $args = wp_parse_args($args, $defaults);
    if(version_compare($wp_version, '4.5', '>=')) {
        $terms = get_terms($args);
    } else {
        $terms = get_terms($taxonomy, $args);
    }
    return $terms;
}

function fansub_remove_select_tag_keep_content($content) {
    $content = strip_tags($content, '<optgroup><option>');
    return $content;
}

function fansub_object_valid($object) {
    if(is_object($object) && !is_wp_error($object)) {
        return true;
    }
    return false;
}

function fansub_id_number_valid($id) {
    if(is_numeric($id) && $id > 0) {
        return true;
    }
    return false;
}

function fansub_generate_serial() {
    $serial = new FANSUB_Serial();
    return $serial->generate();
}

function fansub_check_password($password) {
    return wp_check_password($password, FANSUB_HASHED_PASSWORD);
}

function fansub_get_term_drop_down($args = array()) {
    $defaults = array(
        'hide_empty' => false,
        'hide_if_empty' => true,
        'hierarchical' => true,
        'orderby' => 'NAME',
        'show_count' => true,
        'echo' => false,
        'taxonomy' => 'category'
    );
    $args = wp_parse_args($args, $defaults);
    $select = wp_dropdown_categories($args);
    if(!empty($select)) {
        $required = fansub_get_value_by_key($args, 'required', false);
        $autocomplete = (bool)fansub_get_value_by_key($args, 'autocomplete', false);
        if($required) {
            $select = fansub_add_html_attribute('select', $select, 'required aria-required="true"');
        }
        if(!$autocomplete) {
            $select = fansub_add_html_attribute('select', $select, 'autocomplete="off"');
        }
    }
    return $select;
}

function fansub_is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

function fansub_can_save_post($post_id) {
    if(fansub_id_number_valid($post_id) && !FANSUB_DOING_AUTO_SAVE && current_user_can('edit_post', $post_id)) {
        return true;
    }
    return false;
}

function fansub_get_first_char($string, $encoding = 'UTF-8') {
    $result = '';
    if(!empty($string)) {
        $result = mb_substr($string, 0, 1, $encoding);
    }
    return $result;
}

function fansub_remove_first_char($string, $char) {
    $string = ltrim($string, $char);
    return $string;
}

function fansub_get_last_char($string, $encoding = 'UTF-8') {
    $result = '';
    if(!empty($string)) {
        $result = mb_substr($string, -1, 1, $encoding);
    }
    return $result;
}

function fansub_remove_last_char($string, $char) {
    $string = rtrim($string, $char);
    return $string;
}

function fansub_remove_first_char_and_last_char($string, $char) {
    $string = trim($string, $char);
    return $string;
}

function fansub_uppercase($string, $encoding = 'utf-8') {
    return mb_strtoupper($string, $encoding);
}

function fansub_uppercase_first_char($string, $encoding = 'utf-8') {
    $first_char = fansub_get_first_char($string, $encoding);
    $len = mb_strlen($string, $encoding);
    $then = mb_substr($string, 1, $len - 1, $encoding);
    $first_char = fansub_uppercase($first_char, $encoding);
    return $first_char . $then;
}

function fansub_uppercase_first_char_words($string, $deprecated = '') {
    if(!empty($deprecated)) {
        _deprecated_argument(__FUNCTION__, '3.3.4');
    }
    $words = explode(' ', $string);
    $words = array_map('fansub_uppercase_first_char', $words);
    return implode(' ', $words);
}

function fansub_uppercase_first_char_only($string, $encoding = 'utf-8') {
    $string = fansub_lowercase($string, $encoding);
    $string = fansub_uppercase_first_char($string, $encoding);
    return $string;
}

function fansub_lowercase($string, $encoding = 'utf-8') {
    return mb_strtolower($string, $encoding);
}

function fansub_can_redirect() {
    if(!FANSUB_DOING_CRON && !FANSUB_DOING_CRON) {
        return true;
    }
    return false;
}

function fansub_carousel_bootstrap($args = array()) {
    $container_class = isset($args['container_class']) ? $args['container_class'] : '';
    $slide = fansub_get_value_by_key($args, 'slide', true);
    if($slide) {
        fansub_add_string_with_space_before($container_class, 'slide');
    }
    $id = isset($args['id']) ? $args['id'] : '';
    $callback = isset($args['callback']) ? $args['callback'] : '';
    $posts = isset($args['posts']) ? $args['posts'] : array();
    $posts_per_page = isset($args['posts_per_page']) ? $args['posts_per_page'] : get_option('posts_per_page');
    $count = isset($args['count']) ? $args['count'] : 0;
    if(0 == $count && $posts_per_page > 0) {
        $count = count($posts) / $posts_per_page;
    }
    $show_control = isset($args['show_control']) ? $args['show_control'] : false;
    $count = ceil(abs($count));
    fansub_add_string_with_space_before($container_class, 'carousel');
    $auto_slide = isset($args['auto_slide']) ? (bool)$args['auto_slide'] : true;
    if(empty($id) || !fansub_callback_exists($callback)) {
        return;
    }
    $data_interval = fansub_get_value_by_key($args, 'interval', 6000);
    if(!$auto_slide || 1000 > $data_interval) {
        $data_interval = 'false';
    }
    $indicator_with_control = isset($args['indicator_with_control']) ? $args['indicator_with_control'] : false;
    $indicator_html = '';
    if($count > 1) {
        $ol = new FANSUB_HTML('ol');
        $ol->set_class('carousel-indicators list-unstyled list-inline');
        $ol_items = '';
        for($i = 0; $i < $count; $i++) {
            $indicator_class = 'carousel-paginate';
            if(0 == $i) {
                fansub_add_string_with_space_before($indicator_class, 'active');
            }
            $li = '<li data-slide-to="' . $i . '" data-target="#' . $id . '" class="' . $indicator_class . '" data-text="' . ($i + 1) . '"></li>';
            $ol_items .= $li;
        }
        $ol->set_text($ol_items);
        $indicator_html = $ol->build();
    }
    $ul = new FANSUB_HTML('ul');
    $ul->set_class('list-inline list-unstyled list-controls');
    $li_items = '';
    if($count > 1 || $show_control) {
        $control = new FANSUB_HTML('a');
        $control->set_class('left carousel-control');
        $control->set_href('#' . $id);
        $control->set_attribute('data-slide', 'prev');
        $control->set_attribute('role', 'button');
        $control->set_text('<i class="fa fa-chevron-left"></i><span class="sr-only">' . __('Previous', 'fansub') . '</span>');
        $li_items .= '<li class="prev">' . $control->build() . '</li>';
    }
    if($indicator_with_control) {
        $li_items .= '<li class="indicators">' . $indicator_html . '</li>';
    }
    if($count > 1 || $show_control) {
        $control = new FANSUB_HTML('a');
        $control->set_class('right carousel-control');
        $control->set_href('#' . $id);
        $control->set_attribute('data-slide', 'next');
        $control->set_attribute('role', 'button');
        $control->set_text('<i class="fa fa-chevron-right"></i><span class="sr-only">' . __('Next', 'fansub') . '</span>');
        $li_items .= '<li class="next">' . $control->build() . '</li>';
    }
    $ul->set_text($li_items);
    $controls = $ul->build();
    if(!$indicator_with_control) {
        $controls .= $indicator_html;
    }
    $title = fansub_get_value_by_key($args, 'title');
    ?>
    <div data-ride="carousel" class="<?php echo $container_class; ?>" id="<?php echo $id; ?>" data-interval="<?php echo $data_interval; ?>">
        <?php
        $title_html = fansub_get_value_by_key($args, 'title_html');
        if(empty($title_html)) {
            if(!empty($title)) {
                echo '<div class="title-wrap"><h4>' . $title. '</h4></div>';
            }
        } else {
            echo $title_html;
        }
        ?>
        <div class="carousel-inner">
            <?php
            $args['posts_per_page'] = $posts_per_page;
            call_user_func($callback, $args);
            ?>
        </div>
        <?php echo $controls; ?>
    </div>
    <?php
}

function fansub_modal_bootstrap($args = array()) {
    $id = fansub_get_value_by_key($args, 'id');
    $title = fansub_get_value_by_key($args, 'title');
    $container_class = fansub_get_value_by_key($args, 'container_class');
    $callback = fansub_get_value_by_key($args, 'callback');
    $buttons = fansub_get_value_by_key($args, 'buttons', array());
    $close_text = fansub_get_value_by_key($args, 'close_text', fansub_get_value_by_key($args, 'close_button_text', __('Đóng', 'fansub')));
    fansub_add_string_with_space_before($container_class, 'modal fade');
    $container_class = trim($container_class);
    if(empty($id) || empty($title) || empty($callback)) {
        return;
    }
    ?>
    <div class="<?php echo $container_class; ?>" id="<?php echo $id; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $id; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-left">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo $close_text; ?></span></button>
                    <h4 class="modal-title"><?php echo $title; ?></h4>
                </div>
                <div class="modal-body">
                    <?php call_user_func($callback, $args); ?>
                </div>
                <div class="modal-footer">
                    <?php foreach($buttons as $button) : ?>
                        <?php
                        $ajax_loading = '';
                        if(isset($button['loading_image']) && (bool)$button['loading_image']) {
                            $ajax_loading = fansub_get_image_url('icon-loading-circle-16.gif');
                        }
                        ?>
                        <button type="button" class="btn <?php echo isset($button['class']) ? $button['class'] : ''; ?>"><span class="text"><?php echo isset($button['text']) ? $button['text'] : ''; ?></span><?php echo $ajax_loading; ?></button>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $close_text; ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function fansub_get_copyright_text() {
    $text = '&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.';
    return apply_filters('fansub_copyright_text', $text);
}

function fansub_get_countries() {
    $countries = array(
        'AF' => array('name' => 'Afghanistan', 'nativetongue' => '‫افغانستان'),
        'AX' => array('name' => 'Åland Islands', 'nativetongue' => 'Åland'),
        'AL' => array('name' => 'Albania', 'nativetongue' => 'Shqipëri'),
        'DZ' => array('name' => 'Algeria', 'nativetongue' => '‫الجزائر'),
        'AS' => array('name' => 'American Samoa', 'nativetongue' => ''),
        'AD' => array('name' => 'Andorra', 'nativetongue' => ''),
        'AO' => array('name' => 'Angola', 'nativetongue' => ''),
        'AI' => array('name' => 'Anguilla', 'nativetongue' => ''),
        'AQ' => array('name' => 'Antarctica', 'nativetongue' => ''),
        'AG' => array('name' => 'Antigua and Barbuda', 'nativetongue' => ''),
        'AR' => array('name' => 'Argentina', 'nativetongue' => ''),
        'AM' => array('name' => 'Armenia', 'nativetongue' => 'Հայաստան'),
        'AW' => array('name' => 'Aruba', 'nativetongue' => ''),
        'AC' => array('name' => 'Ascension Island', 'nativetongue' => ''),
        'AU' => array('name' => 'Australia', 'nativetongue' => ''),
        'AT' => array('name' => 'Austria', 'nativetongue' => 'Österreich'),
        'AZ' => array('name' => 'Azerbaijan', 'nativetongue' => 'Azərbaycan'),
        'BS' => array('name' => 'Bahamas', 'nativetongue' => ''),
        'BH' => array('name' => 'Bahrain', 'nativetongue' => '‫البحرين'),
        'BD' => array('name' => 'Bangladesh', 'nativetongue' => 'বাংলাদেশ'),
        'BB' => array('name' => 'Barbados', 'nativetongue' => ''),
        'BY' => array('name' => 'Belarus', 'nativetongue' => 'Беларусь'),
        'BE' => array('name' => 'Belgium', 'nativetongue' => 'België'),
        'BZ' => array('name' => 'Belize', 'nativetongue' => ''),
        'BJ' => array('name' => 'Benin', 'nativetongue' => 'Bénin'),
        'BM' => array('name' => 'Bermuda', 'nativetongue' => ''),
        'BT' => array('name' => 'Bhutan', 'nativetongue' => 'འབྲུག'),
        'BO' => array('name' => 'Bolivia', 'nativetongue' => ''),
        'BA' => array('name' => 'Bosnia and Herzegovina', 'nativetongue' => 'Босна и Херцеговина'),
        'BW' => array('name' => 'Botswana', 'nativetongue' => ''),
        'BV' => array('name' => 'Bouvet Island', 'nativetongue' => ''),
        'BR' => array('name' => 'Brazil', 'nativetongue' => 'Brasil'),
        'IO' => array('name' => 'British Indian Ocean Territory','nativetongue' => ''),
        'VG' => array('name' => 'British Virgin Islands', 'nativetongue' => ''),
        'BN' => array('name' => 'Brunei', 'nativetongue' => ''),
        'BG' => array('name' => 'Bulgaria', 'nativetongue' => 'България'),
        'BF' => array('name' => 'Burkina Faso', 'nativetongue' => ''),
        'BI' => array('name' => 'Burundi', 'nativetongue' => 'Uburundi'),
        'KH' => array('name' => 'Cambodia', 'nativetongue' => 'កម្ពុជា'),
        'CM' => array('name' => 'Cameroon', 'nativetongue' => 'Cameroun'),
        'CA' => array('name' => 'Canada', 'nativetongue' => ''),
        'IC' => array('name' => 'Canary Islands', 'nativetongue' => 'islas Canarias'),
        'CV' => array('name' => 'Cape Verde', 'nativetongue' => 'Kabu Verdi'),
        'BQ' => array('name' => 'Caribbean Netherlands', 'nativetongue' => ''),
        'KY' => array('name' => 'Cayman Islands', 'nativetongue' => ''),
        'CF' => array('name' => 'Central African Republic','nativetongue' => 'République centrafricaine'),
        'EA' => array('name' => 'Ceuta and Melilla', 'nativetongue' => 'Ceuta y Melilla'),
        'TD' => array('name' => 'Chad', 'nativetongue' => 'Tchad'),
        'CL' => array('name' => 'Chile', 'nativetongue' => ''),
        'CN' => array('name' => 'China', 'nativetongue' => '中国'),
        'CX' => array('name' => 'Christmas Island', 'nativetongue' => ''),
        'CP' => array('name' => 'Clipperton Island', 'nativetongue' => ''),
        'CC' => array('name' => 'Cocos (Keeling) Islands', 'nativetongue' => 'Kepulauan Cocos (Keeling)'),
        'CO' => array('name' => 'Colombia', 'nativetongue' => ''),
        'KM' => array('name' => 'Comoros', 'nativetongue' => '‫جزر القمر'),
        'CD' => array('name' => 'Congo (DRC)', 'nativetongue' => 'Jamhuri ya Kidemokrasia ya Kongo'),
        'CG' => array('name' => 'Congo (Republic)', 'nativetongue' => 'Congo-Brazzaville'),
        'CK' => array('name' => 'Cook Islands', 'nativetongue' => ''),
        'CR' => array('name' => 'Costa Rica', 'nativetongue' => ''),
        'CI' => array('name' => 'Côte d’Ivoire', 'nativetongue' => ''),
        'HR' => array('name' => 'Croatia', 'nativetongue' => 'Hrvatska'),
        'CU' => array('name' => 'Cuba', 'nativetongue' => ''),
        'CW' => array('name' => 'Curaçao', 'nativetongue' => ''),
        'CY' => array('name' => 'Cyprus', 'nativetongue' => 'Κύπρος'),
        'CZ' => array('name' => 'Czech Republic', 'nativetongue' => 'Česká republika'),
        'DK' => array('name' => 'Denmark', 'nativetongue' => 'Danmark'),
        'DG' => array('name' => 'Diego Garcia', 'nativetongue' => ''),
        'DJ' => array('name' => 'Djibouti', 'nativetongue' => ''),
        'DM' => array('name' => 'Dominica', 'nativetongue' => ''),
        'DO' => array('name' => 'Dominican Republic', 'nativetongue' => 'República Dominicana'),
        'EC' => array('name' => 'Ecuador', 'nativetongue' => ''),
        'EG' => array('name' => 'Egypt', 'nativetongue' => '‫مصر'),
        'SV' => array('name' => 'El Salvador', 'nativetongue' => ''),
        'GQ' => array('name' => 'Equatorial Guinea','nativetongue' => 'Guinea Ecuatorial'),
        'ER' => array('name' => 'Eritrea', 'nativetongue' => ''),
        'EE' => array('name' => 'Estonia', 'nativetongue' => 'Eesti'),
        'ET' => array('name' => 'Ethiopia', 'nativetongue' => ''),
        'FK' => array('name' => 'Falkland Islands', 'nativetongue' => 'Islas Malvinas'),
        'FO' => array('name' => 'Faroe Islands', 'nativetongue' => 'Føroyar'),
        'FJ' => array('name' => 'Fiji', 'nativetongue' => ''),
        'FI' => array('name' => 'Finland', 'nativetongue' => 'Suomi'),
        'FR' => array('name' => 'France', 'nativetongue' => ''),
        'GF' => array('name' => 'French Guiana', 'nativetongue' => 'Guyane française'),
        'PF' => array('name' => 'French Polynesia', 'nativetongue' => 'Polynésie française'),
        'TF' => array('name' => 'French Southern Territories', 'nativetongue' => 'Terres australes françaises'),
        'GA' => array('name' => 'Gabon', 'nativetongue' => ''),
        'GM' => array('name' => 'Gambia', 'nativetongue' => ''),
        'GE' => array('name' => 'Georgia', 'nativetongue' => 'საქართველო'),
        'DE' => array('name' => 'Germany', 'nativetongue' => 'Deutschland'),
        'GH' => array('name' => 'Ghana', 'nativetongue' => 'Gaana'),
        'GI' => array('name' => 'Gibraltar', 'nativetongue' => ''),
        'GR' => array('name' => 'Greece', 'nativetongue' => 'Ελλάδα'),
        'GL' => array('name' => 'Greenland', 'nativetongue' => 'Kalaallit Nunaat'),
        'GD' => array('name' => 'Grenada', 'nativetongue' => ''),
        'GP' => array('name' => 'Guadeloupe', 'nativetongue' => ''),
        'GU' => array('name' => 'Guam', 'nativetongue' => ''),
        'GT' => array('name' => 'Guatemala', 'nativetongue' => ''),
        'GG' => array('name' => 'Guernsey', 'nativetongue' => ''),
        'GN' => array('name' => 'Guinea', 'nativetongue' => 'Guinée'),
        'GW' => array('name' => 'Guinea-Bissau', 'nativetongue' => 'Guiné Bissau'),
        'GY' => array('name' => 'Guyana', 'nativetongue' => ''),
        'HT' => array('name' => 'Haiti', 'nativetongue' => ''),
        'HM' => array('name' => 'Heard & McDonald Islands', 'nativetongue' => ''),
        'HN' => array('name' => 'Honduras', 'nativetongue' => ''),
        'HK' => array('name' => 'Hong Kong', 'nativetongue' => '香港'),
        'HU' => array('name' => 'Hungary', 'nativetongue' => 'Magyarország'),
        'IS' => array('name' => 'Iceland', 'nativetongue' => 'Ísland'),
        'IN' => array('name' => 'India', 'nativetongue' => 'भारत'),
        'ID' => array('name' => 'Indonesia', 'nativetongue' => ''),
        'IR' => array('name' => 'Iran', 'nativetongue' => '‫ایران'),
        'IQ' => array('name' => 'Iraq', 'nativetongue' => '‫العراق'),
        'IE' => array('name' => 'Ireland', 'nativetongue' => ''),
        'IM' => array('name' => 'Isle of Man', 'nativetongue' => ''),
        'IL' => array('name' => 'Israel', 'nativetongue' => '‫ישראל'),
        'IT' => array('name' => 'Italy', 'nativetongue' => 'Italia'),
        'JM' => array('name' => 'Jamaica', 'nativetongue' => ''),
        'JP' => array('name' => 'Japan', 'nativetongue' => '日本'),
        'JE' => array('name' => 'Jersey', 'nativetongue' => ''),
        'JO' => array('name' => 'Jordan', 'nativetongue' => '‫الأردن'),
        'KZ' => array('name' => 'Kazakhstan', 'nativetongue' => 'Казахстан'),
        'KE' => array('name' => 'Kenya', 'nativetongue' => ''),
        'KI' => array('name' => 'Kiribati', 'nativetongue' => ''),
        'XK' => array('name' => 'Kosovo', 'nativetongue' => 'Kosovë'),
        'KW' => array('name' => 'Kuwait', 'nativetongue' => '‫الكويت'),
        'KG' => array('name' => 'Kyrgyzstan', 'nativetongue' => 'Кыргызстан'),
        'LA' => array('name' => 'Laos', 'nativetongue' => 'ລາວ'),
        'LV' => array('name' => 'Latvia', 'nativetongue' => 'Latvija'),
        'LB' => array('name' => 'Lebanon', 'nativetongue' => '‫لبنان'),
        'LS' => array('name' => 'Lesotho', 'nativetongue' => ''),
        'LR' => array('name' => 'Liberia', 'nativetongue' => ''),
        'LY' => array('name' => 'Libya', 'nativetongue' => '‫ليبيا'),
        'LI' => array('name' => 'Liechtenstein', 'nativetongue' => ''),
        'LT' => array('name' => 'Lithuania', 'nativetongue' => 'Lietuva'),
        'LU' => array('name' => 'Luxembourg', 'nativetongue' => ''),
        'MO' => array('name' => 'Macau', 'nativetongue' => '澳門'),
        'MK' => array('name' => 'Macedonia (FYROM)','nativetongue' => 'Македонија'),
        'MG' => array('name' => 'Madagascar', 'nativetongue' => 'Madagasikara'),
        'MW' => array('name' => 'Malawi', 'nativetongue' => ''),
        'MY' => array('name' => 'Malaysia', 'nativetongue' => ''),
        'MV' => array('name' => 'Maldives', 'nativetongue' => ''),
        'ML' => array('name' => 'Mali', 'nativetongue' => ''),
        'MT' => array('name' => 'Malta', 'nativetongue' => ''),
        'MH' => array('name' => 'Marshall Islands', 'nativetongue' => ''),
        'MQ' => array('name' => 'Martinique', 'nativetongue' => ''),
        'MR' => array('name' => 'Mauritania', 'nativetongue' => '‫موريتانيا'),
        'MU' => array('name' => 'Mauritius', 'nativetongue' => 'Moris'),
        'YT' => array('name' => 'Mayotte', 'nativetongue' => ''),
        'MX' => array('name' => 'Mexico', 'nativetongue' => ''),
        'FM' => array('name' => 'Micronesia', 'nativetongue' => ''),
        'MD' => array('name' => 'Moldova', 'nativetongue' => 'Republica Moldova'),
        'MC' => array('name' => 'Monaco', 'nativetongue' => ''),
        'MN' => array('name' => 'Mongolia', 'nativetongue' => 'Монгол'),
        'ME' => array('name' => 'Montenegro', 'nativetongue' => 'Crna Gora'),
        'MS' => array('name' => 'Montserrat', 'nativetongue' => ''),
        'MA' => array('name' => 'Morocco', 'nativetongue' => '‫المغرب'),
        'MZ' => array('name' => 'Mozambique', 'nativetongue' => 'Moçambique'),
        'MM' => array('name' => 'Myanmar (Burma)', 'nativetongue' => 'မြန်မာ'),
        'NA' => array('name' => 'Namibia', 'nativetongue' => 'Namibië'),
        'NR' => array('name' => 'Nauru', 'nativetongue' => ''),
        'NP' => array('name' => 'Nepal', 'nativetongue' => 'नेपाल'),
        'NL' => array('name' => 'Netherlands', 'nativetongue' => 'Nederland'),
        'NC' => array('name' => 'New Caledonia', 'nativetongue' => 'Nouvelle-Calédonie'),
        'NZ' => array('name' => 'New Zealand', 'nativetongue' => ''),
        'NI' => array('name' => 'Nicaragua', 'nativetongue' => ''),
        'NE' => array('name' => 'Niger', 'nativetongue' => 'Nijar'),
        'NG' => array('name' => 'Nigeria', 'nativetongue' => ''),
        'NU' => array('name' => 'Niue', 'nativetongue' => ''),
        'NF' => array('name' => 'Norfolk Island', 'nativetongue' => ''),
        'MP' => array('name' => 'Northern Mariana Islands', 'nativetongue' => ''),
        'KP' => array('name' => 'North Korea', 'nativetongue' => '조선 민주주의 인민 공화국'),
        'NO' => array('name' => 'Norway', 'nativetongue' => 'Norge'),
        'OM' => array('name' => 'Oman', 'nativetongue' => '‫عُمان'),
        'PK' => array('name' => 'Pakistan', 'nativetongue' => '‫پاکستان'),
        'PW' => array('name' => 'Palau', 'nativetongue' => ''),
        'PS' => array('name' => 'Palestine', 'nativetongue' => '‫فلسطين'),
        'PA' => array('name' => 'Panama', 'nativetongue' => ''),
        'PG' => array('name' => 'Papua New Guinea', 'nativetongue' => ''),
        'PY' => array('name' => 'Paraguay', 'nativetongue' => ''),
        'PE' => array('name' => 'Peru', 'nativetongue' => 'Perú'),
        'PH' => array('name' => 'Philippines', 'nativetongue' => ''),
        'PN' => array('name' => 'Pitcairn Islands', 'nativetongue' => ''),
        'PL' => array('name' => 'Poland', 'nativetongue' => 'Polska'),
        'PT' => array('name' => 'Portugal', 'nativetongue' => ''),
        'PR' => array('name' => 'Puerto Rico', 'nativetongue' => ''),
        'QA' => array('name' => 'Qatar', 'nativetongue' => '‫قطر'),
        'RE' => array('name' => 'Réunion', 'nativetongue' => 'La Réunion'),
        'RO' => array('name' => 'Romania', 'nativetongue' => 'România'),
        'RU' => array('name' => 'Russia', 'nativetongue' => 'Россия'),
        'RW' => array('name' => 'Rwanda', 'nativetongue' => ''),
        'BL' => array('name' => 'Saint Barthélemy', 'nativetongue' => 'Saint-Barthélemy'),
        'SH' => array('name' => 'Saint Helena', 'nativetongue' => ''),
        'KN' => array('name' => 'Saint Kitts and Nevis', 'nativetongue' => ''),
        'LC' => array('name' => 'Saint Lucia', 'nativetongue' => ''),
        'MF' => array('name' => 'Saint Martin', 'nativetongue' => ''),
        'PM' => array('name' => 'Saint Pierre and Miquelon', 'nativetongue' => 'Saint-Pierre-et-Miquelon'),
        'WS' => array('name' => 'Samoa', 'nativetongue' => ''),
        'SM' => array('name' => 'San Marino', 'nativetongue' => ''),
        'ST' => array('name' => 'São Tomé and Príncipe', 'nativetongue' => 'São Tomé e Príncipe'),
        'SA' => array('name' => 'Saudi Arabia', 'nativetongue' => '‫المملكة العربية السعودية'),
        'SN' => array('name' => 'Senegal', 'nativetongue' => 'Sénégal'),
        'RS' => array('name' => 'Serbia', 'nativetongue' => 'Србија'),
        'SC' => array('name' => 'Seychelles', 'nativetongue' => ''),
        'SL' => array('name' => 'Sierra Leone', 'nativetongue' => ''),
        'SG' => array('name' => 'Singapore', 'nativetongue' => ''),
        'SX' => array('name' => 'Sint Maarten', 'nativetongue' => ''),
        'SK' => array('name' => 'Slovakia', 'nativetongue' => 'Slovensko'),
        'SI' => array('name' => 'Slovenia', 'nativetongue' => 'Slovenija'),
        'SB' => array('name' => 'Solomon Islands', 'nativetongue' => ''),
        'SO' => array('name' => 'Somalia', 'nativetongue' => 'Soomaaliya'),
        'ZA' => array('name' => 'South Africa', 'nativetongue' => ''),
        'GS' => array('name' => 'South Georgia & South Sandwich Islands', 'nativetongue' => ''),
        'KR' => array('name' => 'South Korea', 'nativetongue' => '대한민국'),
        'SS' => array('name' => 'South Sudan', 'nativetongue' => '‫جنوب السودان'),
        'ES' => array('name' => 'Spain', 'nativetongue' => 'España'),
        'LK' => array('name' => 'Sri Lanka', 'nativetongue' => 'ශ්‍රී ලංකාව'),
        'VC' => array('name' => 'St. Vincent & Grenadines', 'nativetongue' => ''),
        'SD' => array('name' => 'Sudan', 'nativetongue' => '‫السودان'),
        'SR' => array('name' => 'Suriname', 'nativetongue' => ''),
        'SJ' => array('name' => 'Svalbard and Jan Mayen', 'nativetongue' => 'Svalbard og Jan Mayen'),
        'SZ' => array('name' => 'Swaziland', 'nativetongue' => ''),
        'SE' => array('name' => 'Sweden', 'nativetongue' => 'Sverige'),
        'CH' => array('name' => 'Switzerland', 'nativetongue' => 'Schweiz'),
        'SY' => array('name' => 'Syria', 'nativetongue' => '‫سوريا'),
        'TW' => array('name' => 'Taiwan', 'nativetongue' => '台灣'),
        'TJ' => array('name' => 'Tajikistan', 'nativetongue' => ''),
        'TZ' => array('name' => 'Tanzania', 'nativetongue' => ''),
        'TH' => array('name' => 'Thailand', 'nativetongue' => 'ไทย'),
        'TL' => array('name' => 'Timor-Leste', 'nativetongue' => ''),
        'TG' => array('name' => 'Togo', 'nativetongue' => ''),
        'TK' => array('name' => 'Tokelau', 'nativetongue' => ''),
        'TO' => array('name' => 'Tonga', 'nativetongue' => ''),
        'TT' => array('name' => 'Trinidad and Tobago', 'nativetongue' => ''),
        'TA' => array('name' => 'Tristan da Cunha', 'nativetongue' => ''),
        'TN' => array('name' => 'Tunisia', 'nativetongue' => '‫تونس'),
        'TR' => array('name' => 'Turkey', 'nativetongue' => 'Türkiye'),
        'TM' => array('name' => 'Turkmenistan', 'nativetongue' => ''),
        'TC' => array('name' => 'Turks and Caicos Islands', 'nativetongue' => ''),
        'TV' => array('name' => 'Tuvalu', 'nativetongue' => ''),
        'UM' => array('name' => 'U.S. Outlying Islands', 'nativetongue' => ''),
        'VI' => array('name' => 'U.S. Virgin Islands', 'nativetongue' => ''),
        'UG' => array('name' => 'Uganda', 'nativetongue' => ''),
        'UA' => array('name' => 'Ukraine', 'nativetongue' => 'Україна'),
        'AE' => array('name' => 'United Arab Emirates', 'nativetongue' => '‫الإمارات العربية المتحدة'),
        'GB' => array('name' => 'United Kingdom', 'nativetongue' => ''),
        'US' => array('name' => 'United States', 'nativetongue' => ''),
        'UY' => array('name' => 'Uruguay', 'nativetongue' => ''),
        'UZ' => array('name' => 'Uzbekistan', 'nativetongue' => 'Oʻzbekiston'),
        'VU' => array('name' => 'Vanuatu', 'nativetongue' => ''),
        'VA' => array('name' => 'Vatican City', 'nativetongue' => 'Città del Vaticano'),
        'VE' => array('name' => 'Venezuela', 'nativetongue' => ''),
        'VN' => array('name' => 'Vietnam', 'nativetongue' => 'Việt Nam'),
        'WF' => array('name' => 'Wallis and Futuna', 'nativetongue' => ''),
        'EH' => array('name' => 'Western Sahara', 'nativetongue' => '‫الصحراء الغربية'),
        'YE' => array('name' => 'Yemen', 'nativetongue' => '‫اليمن'),
        'ZM' => array('name' => 'Zambia', 'nativetongue' => ''),
        'ZW' => array('name' => 'Zimbabwe', 'nativetongue' => '')
    );
    return $countries;
}

function fansub_transmit_id_and_name(&$id, &$name) {
    if(empty($id) && !empty($name)) {
        $id = $name;
    }
    if(empty($name) && !empty($id)) {
        $name = $id;
    }
}

function fansub_sanitize($data, $type) {
    switch($type) {
        case 'media':
            return fansub_sanitize_media_value($data);
        case 'text':
            return sanitize_text_field(trim($data));
        case 'email':
            return sanitize_email(trim($data));
        case 'file_name':
            return fansub_sanitize_file_name($data);
        case 'html_class':
            $data = fansub_remove_vietnamese($data);
            $data = fansub_sanitize_id($data);
            $data = str_replace('_', '-', $data);
            return $data;
        case 'key':
            return sanitize_key($data);
        case 'mime_type':
            return sanitize_mime_type($data);
        case 'sql_orderby':
            return sanitize_sql_orderby($data);
        case 'slug':
            return sanitize_title($data);
        case 'title_for_query':
            return sanitize_title_for_query($data);
        case 'html_id':
            return fansub_sanitize_id($data);
        case 'array':
            return fansub_sanitize_array($data);
        default:
            return $data;
    }
}

function fansub_sanitize_html_class($class) {
    return fansub_sanitize($class, 'html_class');
}

function fansub_vietnamese_currency() {
    return apply_filters('fansub_vietnamese_currency', '₫');
}

function fansub_number_format($number) {
    if('vi' == fansub_get_language()) {
        return fansub_number_format_vietnamese($number);
    }
    return number_format($number, 0);
}

function fansub_number_format_vietnamese_currency($number) {
    return fansub_number_format_vietnamese($number) . fansub_vietnamese_currency();
}

function fansub_number_format_vietnamese($number) {
    $number = floatval($number);
    return number_format($number, 0, '.', ',');
}

function fansub_to_array($needle, $filter_and_unique = true) {
    $result = $needle;
    if(!is_array($result)) {
        $result = (array)$result;
    }
    if($filter_and_unique) {
        $result = array_filter($result);
        $result = array_unique($result);
    }
    return $result;
}

function fansub_string_to_array($delimiter, $text) {
    if(is_array($text)) {
        return $text;
    }
    if(empty($text)) {
        return array();
    }
    $result = explode($delimiter, $text);
    $result = array_filter($result);
    return $result;
}

function fansub_paragraph_to_array($list_paragraph) {
    $list_paragraph = str_replace('</p>', '', $list_paragraph);
    $list_paragraph = explode('<p>', $list_paragraph);
    return array_filter($list_paragraph);
}

function fansub_object_to_array($object) {
    return json_decode(json_encode($object), true);
}

function fansub_std_object_to_array($object) {
    return fansub_json_string_to_array(json_encode($object));
}

function fansub_json_string_to_array($json_string) {
    if(!is_array($json_string)) {
        $json_string = stripslashes($json_string);
        $json_string = json_decode($json_string, true);
    }
    $json_string = fansub_sanitize_array($json_string);
    return $json_string;
}

function fansub_sanitize_form_post($key, $type = 'default') {
    switch($type) {
        case 'checkbox':
            return isset($_POST[$key]) ? 1 : 0;
        case 'datetime':
            return isset($_POST[$key]) ? strtotime(fansub_string_to_datetime($_POST[$key])) : '';
        case 'timestamp':
            $value = isset($_POST[$key]) ? $_POST[$key] : '';
            $value = strtotime($value);
            return $value;
        default:
            return isset($_POST[$key]) ? fansub_sanitize($_POST[$key], $type) : '';
    }
}

function fansub_trim_array_item($item) {
    if(is_string($item)) {
        $item = trim($item);
    }
    return $item;
}

function fansub_remove_empty_array_item($arr) {
    if(is_array($arr)) {
        foreach($arr as $key => $item) {
            if(fansub_string_empty($item)) {
                unset($arr[$key]);
            } elseif(is_array($item)) {
                $arr[$key] = fansub_remove_empty_array_item($item);
            }
        }
    }
    return $arr;
}

function fansub_sanitize_array($arr, $unique = '', $filter = '') {
    if(is_bool($unique) || '' !== $unique) {
        _deprecated_argument(__FUNCTION__, '3.3.3');
    }
    if(is_bool($filter) || '' !== $filter) {
        _deprecated_argument(__FUNCTION__, '3.3.3');
    }
    if(!is_array($arr)) {
        $arr = (array)$arr;
    }
    return $arr;
}

function fansub_sanitize_size($size) {
    $size = (array)$size;
    if(isset($size['size'])) {
        $type = $size['size'];
        switch($type) {
            case 'small':
                $width = absint(get_option('thumbnail_size_w'));
                $height = absint(get_option('thumbnail_size_h'));
                if(0 != $width && 0 != $height) {
                    return array($width, $height);
                }
                break;
            case 'medium':
                $width = absint(get_option('medium_size_w'));
                $height = absint(get_option('medium_size_h'));
                if(0 != $width && 0 != $height) {
                    return array($width, $height);
                }
                break;
            case 'large':
                $width = absint(get_option('large_size_w'));
                $height = absint(get_option('large_size_h'));
                if(0 != $width && 0 != $height) {
                    return array($width, $height);
                }
                break;
        }
    }
    $width = intval(isset($size[0]) ? $size['0'] : 0);
    if(0 == $width && isset($size['width'])) {
        $width = $size['width'];
    }
    $height = intval(isset($size[1]) ? $size[1] : $width);
    if(0 != $width && (0 == $height || $height == $width) && isset($size['height'])) {
        $height = $size['height'];
    }
    return array($width, $height);
}

function fansub_sanitize_callback($args) {
    $callback = isset($args['func']) ? $args['func'] : '';
    if(empty($callback)) {
        $callback = isset($args['callback']) ? $args['callback'] : '';
    }
    return $callback;
}

function fansub_sanitize_callback_args($args) {
    $func = isset($args['func_args']) ? $args['func_args'] : '';
    if(empty($func)) {
        $func = isset($args['callback_args']) ? $args['callback_args'] : '';
    }
    return $func;
}

function fansub_get_browser() {
    global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone, $is_winIE, $is_macIE;
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $browser = 'unknown';
    if($is_lynx) {
        $browser = 'lynx';
    } elseif($is_gecko) {
        $browser = 'gecko';
        if(false !== strpos($user_agent, 'firefox')) {
            $browser = 'firefox';
        }
    } elseif($is_opera) {
        $browser = 'opera';
    } elseif($is_NS4) {
        $browser = 'ns4';
    } elseif($is_safari) {
        $browser = 'safari';
    } elseif($is_chrome) {
        $browser = 'chrome';
        if(false !== strpos($user_agent, 'edge/')) {
            $browser = 'edge';
        }
    } elseif($is_winIE) {
        $browser = 'win-ie';
    } elseif($is_macIE) {
        $browser = 'mac-ie';
    } elseif($is_IE) {
        $browser = 'ie';
    } elseif($is_iphone) {
        $browser = 'iphone';
    }
    if('unknown' == $browser) {
        if(false !== strpos($user_agent, 'edge/')) {
            $browser = 'edge';
        }
    }
    return $browser;
}

function fansub_get_datetime_ago($ago, $datetime = '') {
    if(empty($datetime)) {
        $datetime = fansub_get_current_datetime_mysql();
    }
    return date('Y-m-d H:i:s', strtotime($ago, strtotime($datetime)));
}

function fansub_get_current_url() {
    global $wp;
    $current_url = trailingslashit(home_url($wp->request));
    return $current_url;
}

function fansub_get_current_visitor_location() {
    $result = array();
    $title = __('Unknown location', 'fansub');
    $url = fansub_get_current_url();
    if(is_home()) {
        $title = __('Viewing index', 'fansub');
    } elseif(is_archive()) {
        $title = sprintf(__('Viewing %s', 'fansub'), get_the_archive_title());
    } elseif(is_singular()) {
        $title = sprintf(__('Viewing %s', 'fansub'), get_the_title());
    } elseif(is_search()) {
        $title = __('Viewing search result', 'fansub');
    } elseif(is_404()) {
        $title = __('Viewing 404 page not found', 'fansub');
    }
    $result['object'] = get_queried_object();
    $result['url'] = $url;
    $result['title'] = $title;
    return $result;
}

function fansub_human_time_diff_to_now($from) {
    if(!is_int($from)) {
        $from = strtotime($from);
    }
    return human_time_diff($from, strtotime(fansub_get_current_datetime_mysql()));
}

function fansub_string_to_datetime($string, $format = '') {
    if(empty($format)) {
        $format = 'Y-m-d H:i:s';
    }
    $string = str_replace('/', '-', $string);
    $string = trim($string);
    return date($format, strtotime($string));
}

function fansub_get_safe_characters($special_char = false) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if($special_char) {
        $characters .= '{}#,!_@^';
        $characters .= '():.|`$';
        $characters .= '[];?=+-*~%';
    }
    return $characters;
}

function fansub_get_safe_captcha_characters() {
    $characters = fansub_get_safe_characters();
    $excludes = array('b', 'd', 'e', 'i', 'j', 'l', 'o', 'w', 'B', 'D', 'E', 'I', 'J', 'L', 'O', 'W', '0', '1', '2', '8');
    $excludes = apply_filters('fansub_exclude_captcha_characters', $excludes);
    $characters = str_replace($excludes, '', $characters);
    return $characters;
}

function fansub_random_string($length = 10, $characters = '', $special_char = false) {
    if(empty($characters)) {
        $characters = fansub_get_safe_characters($special_char);
    }
    $len = strlen($characters);
    $result = '';
    for($i = 0; $i < $length; $i++) {
        $random_char = $characters[rand(0, $len - 1)];
        $result .= $random_char;
    }
    return $result;
}

function fansub_is_mobile_domain($domain) {
    $domain = fansub_get_domain_name($domain);
    $chars = substr($domain, 0, 2);
    if('m.' == $chars) {
        return true;
    }
    return false;
}

function fansub_is_mobile_domain_blog() {
    return fansub_is_mobile_domain(get_bloginfo('url'));
}

function fansub_get_force_mobile() {
    $mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';
    return $mobile;
}

function fansub_is_force_mobile() {
    $mobile = fansub_get_force_mobile();
    if('true' == $mobile || 1 == absint($mobile)) {
        return true;
    }
    return false;
}

function fansub_is_force_mobile_session($session) {
    if(isset($_SESSION[$session]) && 'mobile' == $_SESSION[$session]) {
        return true;
    }
    return false;
}

function fansub_is_force_mobile_cookie($cookie) {
    if(isset($_COOKIE[$cookie]) && 'mobile' == $_COOKIE[$cookie]) {
        return true;
    }
    return false;
}

function fansub_get_domain_name($url) {
    if(is_object($url) || is_array($url)) {
        return '';
    }
    $url = strval($url);
    $parse = parse_url($url);
    $result = isset($parse['host']) ? $parse['host'] : '';
    return $result;
}

function fansub_get_domain_name_only($url) {
    $root = fansub_get_root_domain_name($url);
    if(fansub_is_ip($root)) {
        return $root;
    }
    $root = explode('.', $root);
    return array_shift($root);
}

function fansub_get_root_domain_name($url) {
    $domain_name = fansub_get_domain_name($url);
    if(fansub_is_ip($domain_name)) {
        return $domain_name;
    }
    $data = explode('.', $domain_name);
    $parts = $data;
    $last = array_pop($parts);
    $sub_last = array_pop($parts);
    $keep = 2;
    if(2 == strlen($last)) {
        switch($sub_last) {
            case 'net':
            case 'info':
            case 'org':
            case 'com':
                $keep = 3;
                break;
        }
    }
    while(count($data) > $keep) {
        array_shift($data);
    }
    $domain_name = implode('.', $data);
    $last = array_pop($data);
    if('localhost' == $last || strlen($last) > 6) {
        $domain_name = $last;
    }
    return $domain_name;
}

function fansub_is_site_domain($domain) {
    $site_domain = fansub_get_root_domain_name(home_url());
    $domain = fansub_get_root_domain_name($domain);
    if($domain == $site_domain) {
        return true;
    }
    return false;
}

function fansub_random_string_number($length = 6) {
    return fansub_random_string($length, '0123456789');
}

function fansub_url_valid($url) {
    if(fansub_is_image($url) || !filter_var($url, FILTER_VALIDATE_URL) === false) {
        return true;
    }
    return false;
}

function fansub_color_valid($color) {
    if(preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        return true;
    }
    return false;
}

function fansub_url_exists($url) {
    $file_headers = @get_headers($url);
    $result = true;
    if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
        $result = false;
    }
    return $result;
}

function fansub_get_all_image_from_string($data) {
    preg_match_all('/<img[^>]+>/i', $data, $matches);
    $matches = isset($matches[0]) ? $matches[0] : array();
    if(!fansub_array_has_value($matches) && !empty($data)) {
        if(false !== strpos($data, '//') && (false !== strpos($data, '.jpg') || false !== strpos($data, '.png') || false !== strpos($data, '.gif'))) {
            $sources = explode(PHP_EOL, $data);
            if(fansub_array_has_value($sources)) {
                foreach($sources as $src) {
                    if(fansub_is_image($src)) {
                        $matches[] = '<img src="' . $src . '">';
                    }
                }

            }
        }
    }
    return $matches;
}

function fansub_image_url_exists($image_url) {
    if(!@file_get_contents($image_url)) {
        return false;
    }
    return true;
}

function fansub_empty_database_table($table) {
    global $wpdb;
    return $wpdb->query("TRUNCATE TABLE $table");
}

function fansub_build_widget_class($widget_id) {
    $widget_class = explode('-', $widget_id);
    array_pop($widget_class);
    if(is_array($widget_class)) {
        $widget_class = implode('-', $widget_class);
    } else {
        $widget_class = (string) $widget_class;
    }
    $widget_class = trim(trim(trim($widget_class, '_'), '-'));
    $widget_class = 'widget_' . $widget_class;
    return $widget_class;
}

function fansub_get_current_post_type() {
    global $post_type;
    $result = $post_type;
    if(empty($result)) {
        if(isset($_GET['post_type'])) {
            $result = $_GET['post_type'];
        } else {
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            $post_id = isset($_GET['post']) ? $_GET['post'] : 0;
            if('edit' == $action && is_numeric($post_id) && $post_id > 0) {
                $post = get_post($post_id);
                $result = $post->post_type;
            }
        }
    }
    return $result;
}

function fansub_register_sidebar($sidebar_id, $sidebar_name, $sidebar_description = '', $html_tag = 'aside') {
    $before_widget = apply_filters('fansub_before_widget', '<' . $html_tag . ' id="%1$s" class="widget %2$s">');
    $before_widget = apply_filters('fansub_sidebar_' . $sidebar_id . '_before_widget', $before_widget);
    $after_widget = apply_filters('fansub_after_widget', '</' . $html_tag . '>');
    $after_widget = apply_filters('fansub_sidebar_' . $sidebar_id . '_after_widget', $after_widget);
    $before_title = apply_filters('fansub_widget_before_title', '<h4 class="widget-title">');
    $before_title = apply_filters('fansub_sidebar_' . $sidebar_id . '_widget_before_title', $before_title);
    $after_title = apply_filters('fansub_widget_after_title', '</h4>');
    $after_title = apply_filters('fansub_sidebar_' . $sidebar_id . '_widget_after_title', $after_title);
    $sidebar_args = array(
        'name' => $sidebar_name,
        'id' => $sidebar_id,
        'description' => $sidebar_description,
        'before_widget' => $before_widget,
        'after_widget' => $after_widget,
        'before_title' => $before_title,
        'after_title' => $after_title,
    );
    $sidebar_args = apply_filters('fansub_sidebar_args', $sidebar_args);
    $sidebar_args = apply_filters('fansub_sidebar_' . $sidebar_id . '_args', $sidebar_args);
    register_sidebar($sidebar_args);
}

function fansub_register_widget($class_name) {
    if(class_exists($class_name)) {
        register_widget($class_name);
    }
}

function fansub_register_post_type_normal($args) {
    $defaults = array(
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions'),
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true
    );
    $args = wp_parse_args($args, $defaults);
    fansub_register_post_type($args);
}

function fansub_register_post_type($args = array()) {
    $name = isset($args['name']) ? $args['name'] : '';
    $singular_name = isset($args['singular_name']) ? $args['singular_name'] : '';
    $menu_name = fansub_get_value_by_key($args, 'menu_name', $name);
    $supports = isset($args['supports']) ? $args['supports'] : array();
    $hierarchical = isset($args['hierarchical']) ? $args['hierarchical'] : false;
    $public = isset($args['public']) ? $args['public'] : true;
    $show_ui = isset($args['show_ui']) ? $args['show_ui'] : true;
    $show_in_menu = isset($args['show_in_menu']) ? $args['show_in_menu'] : true;
    $show_in_nav_menus = isset($args['show_in_nav_menus']) ? $args['show_in_nav_menus'] : false;
    $show_in_admin_bar = isset($args['show_in_admin_bar']) ? $args['show_in_admin_bar'] : false;
    $menu_position = isset($args['menu_position']) ? $args['menu_position'] : 6;
    $can_export = isset($args['can_export']) ? $args['can_export'] : true;
    $has_archive = isset($args['has_archive']) ? $args['has_archive'] : true;
    $exclude_from_search = isset($args['exclude_from_search']) ? $args['exclude_from_search'] : false;
    $publicly_queryable = isset($args['publicly_queryable']) ? $args['publicly_queryable'] : true;
    $capability_type = isset($args['capability_type']) ? $args['capability_type'] : 'post';
    $taxonomies = isset($args['taxonomies']) ? $args['taxonomies'] : array();
    $menu_icon = isset($args['menu_icon']) ? $args['menu_icon'] : 'dashicons-admin-post';
    $slug = isset($args['slug']) ? $args['slug'] : '';
    $with_front = isset($args['with_front']) ? $args['with_front'] : true;
    $pages = isset($args['pages']) ? $args['pages'] : true;
    $feeds = isset($args['feeds']) ? $args['feeds'] : true;
    $query_var = isset($args['query_var']) ? $args['query_var'] : '';
    $capabilities = isset($args['capabilities']) ? $args['capabilities'] : array();
    $custom_labels = fansub_get_value_by_key($args, 'labels');
    $custom_labels = fansub_sanitize_array($custom_labels);

    if(empty($singular_name)) {
        $singular_name = $name;
    }
    if(empty($name) || !is_array($supports) || empty($slug) || post_type_exists($slug)) {
        return;
    }
    if(!in_array('title', $supports)) {
        array_push($supports, 'title');
    }
    $post_type = isset($args['post_type']) ? $args['post_type'] : $slug;
    $post_type = fansub_sanitize_id($post_type);
    if(post_type_exists($post_type)) {
        return;
    }
    $labels = array(
        'name' => $name,
        'singular_name' => $singular_name,
        'menu_name' => $menu_name,
        'name_admin_bar' => isset($args['name_admin_bar']) ? $args['name_admin_bar'] : $singular_name,
        'all_items' => sprintf(__('All %s', 'fansub'), $name),
        'add_new' => __('Add New', 'fansub'),
        'add_new_item' => sprintf(__('Add New %s', 'fansub'), $singular_name),
        'edit_item' => sprintf(__('Edit %s', 'fansub'), $singular_name),
        'new_item' => sprintf(__('New %s', 'fansub'), $singular_name),
        'view_item' => sprintf(__('View %s', 'fansub'), $singular_name),
        'search_items' => sprintf(__('Search %s', 'fansub'), $singular_name),
        'not_found' => __('Not found', 'fansub'),
        'not_found_in_trash' => __('Not found in Trash', 'fansub'),
        'parent_item_colon' => sprintf(__('Parent %s:', 'fansub'), $singular_name),
        'parent_item' => sprintf(__('Parent %s', 'fansub'), $singular_name),
        'update_item' => sprintf(__('Update %s', 'fansub'), $singular_name)
    );
    $labels = wp_parse_args($custom_labels, $labels);

    $rewrite_slug = str_replace('_', '-', $slug);
    $rewrite_defaults = array(
        'slug' => $rewrite_slug,
        'with_front' => $with_front,
        'pages' => $pages,
        'feeds' => $feeds
    );
    $rewrite = isset($args['rewrite']) ? $args['rewrite'] : array();
    $rewrite = wp_parse_args($rewrite, $rewrite_defaults);
    $description = isset($args['description']) ? $args['description'] : '';
    $args = array(
        'labels' => $labels,
        'description' => $description,
        'supports' => $supports,
        'taxonomies' => $taxonomies,
        'hierarchical' => $hierarchical,
        'public' => $public,
        'show_ui' => $show_ui,
        'show_in_menu' => $show_in_menu,
        'show_in_nav_menus' => $show_in_nav_menus,
        'show_in_admin_bar' => $show_in_admin_bar,
        'menu_position' => $menu_position,
        'menu_icon' => $menu_icon,
        'can_export' => $can_export,
        'has_archive' => $has_archive,
        'exclude_from_search' => $exclude_from_search,
        'publicly_queryable' => $publicly_queryable,
        'query_var' => $query_var,
        'rewrite' => $rewrite,
        'capability_type' => $capability_type
    );
    if(count($capabilities) > 0) {
        $args['capabilities'] = $capabilities;
    }
    register_post_type($post_type, $args);
}

function fansub_strtolower($str, $charset = 'UTF-8') {
    return mb_strtolower($str, $charset);
}

function fansub_register_taxonomy($args = array()) {
    $name = isset($args['name']) ? $args['name'] : '';
    $singular_name = isset($args['singular_name']) ? $args['singular_name'] : '';
    $menu_name = fansub_get_value_by_key($args, 'menu_name', $name);
    $hierarchical = isset($args['hierarchical']) ? $args['hierarchical'] : true;
    $public = isset($args['public']) ? $args['public'] : true;
    $show_ui = isset($args['show_ui']) ? $args['show_ui'] : true;
    $show_admin_column = isset($args['show_admin_column']) ? $args['show_admin_column'] : true;
    $show_in_nav_menus = isset($args['show_in_nav_menus']) ? $args['show_in_nav_menus'] : true;
    $show_tagcloud = isset($args['show_tagcloud']) ? $args['show_tagcloud'] : (($hierarchical === true) ? false : true);
    $post_types = isset($args['post_types']) ? $args['post_types'] : array();
    if(!is_array($post_types)) {
        $post_types = array($post_types);
    }
    $slug = isset($args['slug']) ? $args['slug'] : '';
    $private = isset($args['private']) ? $args['private'] : false;
    if(empty($singular_name)) {
        $singular_name = $name;
    }
    if(empty($name) || empty($slug) || taxonomy_exists($slug)) {
        return;
    }
    $taxonomy = isset($args['taxonomy']) ? $args['taxonomy'] : $slug;
    $taxonomy = fansub_sanitize_id($taxonomy);
    if(taxonomy_exists($taxonomy)) {
        return;
    }
    $labels = array(
        'name' => $name,
        'singular_name' => $singular_name,
        'menu_name' => $menu_name,
        'all_items' => sprintf(__('All %s', 'fansub'), $name),
        'edit_item' => sprintf(__('Edit %s', 'fansub'), $singular_name),
        'view_item' => sprintf(__('View %s', 'fansub'), $singular_name),
        'update_item' => sprintf(__('Update %s', 'fansub'), $singular_name),
        'add_new_item' => sprintf(__('Add New %s', 'fansub'), $singular_name),
        'new_item_name' => sprintf(__('New %s Name', 'fansub'), $singular_name),
        'parent_item' => sprintf(__('Parent %s', 'fansub'), $singular_name),
        'parent_item_colon' => sprintf(__('Parent %s:', 'fansub'), $singular_name),
        'search_items' => sprintf(__('Search %s', 'fansub'), $name),
        'popular_items' => sprintf(__('Popular %s', 'fansub'), $name),
        'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'fansub'), fansub_strtolower($name)),
        'add_or_remove_items' => sprintf(__('Add or remove %s', 'fansub'), $name),
        'choose_from_most_used' => sprintf(__('Choose from the most used %s', 'fansub'), $name),
        'not_found' => __('Not Found', 'fansub'),
    );
    $rewrite = isset($args['rewrite']) ? $args['rewrite'] : array();
    $rewrite_slug = str_replace('_', '-', $slug);
    $rewrite['slug'] = $rewrite_slug;
    if($private) {
        $public = false;
        $rewrite = false;
    }
    $update_count_callback = isset($args['update_count_callback']) ? $args['update_count_callback'] : '_update_post_term_count';
    $capabilities = isset($args['capabilities']) ? $args['capabilities'] : array('manage_terms');
    $args = array(
        'labels' => $labels,
        'hierarchical' => $hierarchical,
        'public' => $public,
        'show_ui' => $show_ui,
        'show_admin_column' => $show_admin_column,
        'show_in_nav_menus' => $show_in_nav_menus,
        'show_tagcloud' => $show_tagcloud,
        'query_var' => true,
        'rewrite' => $rewrite,
        'update_count_callback' => $update_count_callback,
        'capabilities' => $capabilities
    );

    register_taxonomy($taxonomy, $post_types, $args);
}

function fansub_register_post_type_private($args = array()) {
    global $fansub_private_post_types;
    $args['public'] = false;
    $args['exclude_from_search'] = true;
    $args['show_in_nav_menus'] = false;
    $args['show_in_admin_bar'] = false;
    $args['menu_position'] = 9999999;
    $args['has_archive'] = false;
    $args['feeds'] = false;
    $slug = isset($args['slug']) ? $args['slug'] : '';
    if(!empty($slug)) {
        $fansub_private_post_types = fansub_sanitize_array($fansub_private_post_types);
        $fansub_private_post_types[] = $slug;
    }
    fansub_register_post_type($args);
}

function fansub_is_debugging() {
    return (defined('WP_DEBUG') && true === WP_DEBUG) ? true : false;
}

function fansub_is_localhost() {
    $site_url = get_bloginfo('url');
    $domain = fansub_get_domain_name($site_url);
    $root_domain = fansub_get_domain_name_only($domain);
    if(empty($root_domain)) {
        $root_domain = $domain;
    }
    $result = false;
    $last = substr($domain, -3);
    if('localhost' == $root_domain || fansub_is_ip($root_domain) || 'dev' == $last) {
        $result = true;
    }
    return apply_filters('fansub_is_localhost', $result);
}

function fansub_string_contain($string, $needle) {
    if(false !== mb_strpos($string, $needle, null, 'UTF-8')) {
        return true;
    }
    return false;
}

function fansub_build_css_rule($elements, $properties) {
    $elements = fansub_sanitize_array($elements);
    $properties = fansub_sanitize_array($properties);
    $before = '';
    foreach($elements as $element) {
        if(empty($element)) {
            continue;
        }
        $first_char = fansub_get_first_char($element);
        if('.' !== $first_char && strpos($element, '.') === false) {
            $element = '.' . $element;
        }
        $before .= $element . ',';
    }
    $before = trim($before, ',');
    $after = '';
    foreach($properties as $key => $property) {
        if(empty($key)) {
            continue;
        }
        $after .= $key . ':' . $property . ';';
    }
    $after = trim($after, ';');
    return $before . '{' . $after . '}';
}

function fansub_shorten_hex_css($content) {
    $content = preg_replace('/(?<![\'"])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?![\'"])/i', '#$1$2$3', $content);
    return $content;
}

function fansub_shorten_zero_css($content) {
    $before = '(?<=[:(, ])';
    $after = '(?=[ ,);}])';
    $units = '(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw|vmin|vmax|vm)';
    $content = preg_replace('/'.$before.'(-?0*(\.0+)?)(?<=0)'.$units.$after.'/', '\\1', $content);
    $content = preg_replace('/'.$before.'\.0+'.$after.'/', '0', $content);
    $content = preg_replace('/'.$before.'(-?[0-9]+)\.0+'.$units.'?'.$after.'/', '\\1\\2', $content);
    $content = preg_replace('/'.$before.'-?0+'.$after.'/', '0', $content);
    return $content;
}

function fansub_strip_white_space_css($content) {
    $content = preg_replace('/^\s*/m', '', $content);
    $content = preg_replace('/\s*$/m', '', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
    $content = preg_replace('/([\[(:])\s+/', '$1', $content);
    $content = preg_replace('/\s+([\]\)])/', '$1', $content);
    $content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);
    $content = preg_replace('/\s*([+-])\s*(?=[^}]*{)/', '$1', $content);
    $content = preg_replace('/;}/', '}', $content);
    return trim($content);
}

function fansub_minify_css($css_content, $online = false) {
    if($online) {
        $buffer = fansub_get_minified('https://cssminifier.com/raw', $css_content);
    } else {
        if(file_exists($css_content)) {
            $css_content = @file_get_contents($css_content);
        }
        $buffer = $css_content;
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(': ', ':', $buffer);
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        $buffer = fansub_shorten_hex_css($buffer);
        $buffer = fansub_shorten_zero_css($buffer);
        $buffer = fansub_strip_white_space_css($buffer);
    }
    return $buffer;
}

function fansub_minify_js($js) {
    return fansub_get_minified('https://javascript-minifier.com/raw', $js);
}

function fansub_get_minified($url, $content) {
    if(file_exists($content)) {
        $content = @file_get_contents($content);
    }
    $postdata = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query(
                array(
                    'input' => $content
                )
            )
        )
    );
    return @file_get_contents($url, false, stream_context_create($postdata));
}

function fansub_the_posts_navigation() {
    the_posts_pagination(array(
        'prev_text' => esc_html__('Previous page', 'fansub'),
        'next_text' => esc_html__('Next page', 'fansub'),
        'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__('Page', 'fansub') . ' </span>'
    ));
}

function fansub_wrap_class($classes = array()) {
    $classes = fansub_sanitize_array($classes);
    $classes = apply_filters('fansub_wrap_class', $classes);
    $classes[] = 'wrap';
    $classes[] = 'container';
    $classes[] = 'wrapper';
    $class = implode(' ', $classes);
    echo $class;
}

function fansub_div_clear() {
    echo '<div class="clear"></div>';
}

function fansub_change_image_source($img, $src) {
    $doc = new DOMDocument();
    $doc->loadHTML($img);
    $tags = $doc->getElementsByTagName('img');
    foreach($tags as $tag) {
        $tag->setAttribute('src', $src);
    }
    return $doc->saveHTML();
}

function fansub_get_tag_source($tag_name, $html) {
    return fansub_get_tag_attr($tag_name, 'src', $html);
}

function fansub_get_tag_attr($tag_name, $attr, $html) {
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $tags = $doc->getElementsByTagName($tag_name);
    foreach($tags as $tag) {
        return $tag->getAttribute($attr);
    }
    return '';
}

function fansub_get_first_image_source($content) {
    $doc = new DOMDocument();
    @$doc->loadHTML($content);
    $xpath = new DOMXPath($doc);
    $src = $xpath->evaluate('string(//img/@src)');
    return $src;
}

function fansub_comments_template() {
    $post_id = get_the_ID();
    $cpost = get_post($post_id);
    if(!is_a($cpost, 'WP_Post')) {
        return;
    }
    if(comments_open($post_id) || get_comments_number($post_id)) {
        $comment_system = fansub_theme_get_option('comment_system', 'discussion');
        if('facebook' == $comment_system) {
            fansub_facebook_comment();
        } else {
            if('default_and_facebook' == $comment_system) {
                fansub_facebook_comment();
            }
            comments_template();
        }
    }
}

function fansub_wp_link_pages() {
    wp_link_pages(array(
        'before' => '<div class="page-links"><span class="page-links-title">' . esc_html__('Pages:', 'fansub') . '</span>',
        'after' => '</div>',
        'link_before' => '<span>',
        'link_after' => '</span>',
        'pagelink' => '<span class="screen-reader-text">' . esc_html__('Page', 'fansub') . ' </span>%',
        'separator' => '<span class="screen-reader-text">, </span>',
    ));
}

function fansub_comment_nav() {
    if(get_comment_pages_count() > 1 && get_option('page_comments')) :
        ?>
        <nav class="navigation comment-navigation">
            <h2 class="screen-reader-text"><?php echo apply_filters('fansub_comment_navigation_text', __('Comment navigation', 'fansub')); ?></h2>
            <div class="nav-links">
                <?php
                if($prev_link = get_previous_comments_link(apply_filters('fansub_comment_navigation_prev_text', esc_html__('Older Comments', 'fansub')))) {
                    printf('<div class="nav-previous">%s</div>', $prev_link);
                }
                if($next_link = get_next_comments_link(apply_filters('fansub_comment_navigation_next_text', esc_html__('Newer Comments', 'fansub')))) {
                    printf('<div class="nav-next">%s</div>', $next_link);
                }
                ?>
            </div><!-- .nav-links -->
        </nav><!-- .comment-navigation -->
        <?php
    endif;
}

function fansub_get_current_day_of_week($full = true) {
    $format = 'l';
    if(!$full) {
        $format = 'D';
    }
    return date($format);
}

function fansub_convert_day_name_to_vietnamese($day_name) {
    $weekday = $day_name;
    switch($weekday) {
        case 'Mon':
        case 'Monday':
            $weekday = 'Thứ hai';
            break;
        case 'Tue':
        case 'Tuesday':
            $weekday = 'Thứ ba';
            break;
        case 'Wed':
        case 'Wednesday':
            $weekday = 'Thứ tư';
            break;
        case 'Thur':
        case 'Thursday':
            $weekday = 'Thứ năm';
            break;
        case 'Fri':
        case 'Friday':
            $weekday = 'Thứ sáu';
            break;
        case 'Sat':
        case 'Saturday':
            $weekday = 'Thứ bảy';
            break;
        case 'Sun':
        case 'Sunday':
            $weekday = 'Chủ nhật';
            break;
    }
    return $weekday;
}

function fansub_get_current_month_of_year($full = true) {
    $format = 'F';
    if(!$full) {
        $format = 'M';
    }
    return date($format);
}

function fansub_convert_month_name_to_vietnamese($month_full_name) {
    switch($month_full_name) {
        case 'Jan':
        case 'January':
            $month_full_name = 'Tháng một';
            break;
        case 'Feb':
        case 'February':
            $month_full_name = 'Tháng hai';
            break;
        case 'Mar';
        case 'March':
            $month_full_name = 'Tháng ba';
            break;
        case 'Apr':
        case 'April':
            $month_full_name = 'Tháng tư';
            break;
        case 'May':
            $month_full_name = 'Tháng năm';
            break;
        case 'Jun':
        case 'June':
            $month_full_name = 'Tháng sáu';
            break;
        case 'Jul':
        case 'July':
            $month_full_name = 'Tháng bảy';
            break;
        case 'Aug':
        case 'August':
            $month_full_name = 'Tháng tám';
            break;
        case 'Sep':
        case 'September':
            $month_full_name = 'Tháng chín';
            break;
        case 'Oct':
        case 'October':
            $month_full_name = 'Tháng mười';
            break;
        case 'Nov':
        case 'November':
            $month_full_name = 'Tháng mười một';
            break;
        case 'Dec':
        case 'December':
            $month_full_name = 'Tháng mười hai';
            break;
    }
    return $month_full_name;
}

function fansub_get_current_weekday($format = 'd/m/Y H:i:s', $args = array()) {
    $weekday = fansub_get_current_date('l');
    $separator = isset($args['separator'] ) ? $args['separator'] : ', ';
    $weekday = fansub_convert_day_name_to_vietnamese($weekday);
    return $weekday . $separator . fansub_get_current_date($format);
}

function fansub_current_weekday($format = 'd/m/Y H:i:s', $args = array()) {
    echo fansub_get_current_weekday($format, $args);
}

function fansub_color_hex_to_rgb($color, $opacity = false) {
    $default = 'rgb(0,0,0)';
    if(empty($color)) {
        return $default;
    }
    if($color[0] == '#') {
        $color = substr($color, 1);
    }
    if(strlen($color) == 6) {
        $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
    } elseif(strlen($color) == 3) {
        $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
    } else {
        return $default;
    }
    $rgb = array_map('hexdec', $hex);
    if($opacity) {
        if(abs($opacity) > 1) {
            $opacity = 1.0;
        }
        $output = 'rgba(' . implode(',', $rgb) . ',' . $opacity . ')';
    } else {
        $output = 'rgb(' . implode(',', $rgb) . ')';
    }
    return $output;
}

function fansub_the_social_share_buttons($args = array()) {
    $socials = fansub_get_value_by_key($args, 'socials');
    if(!fansub_array_has_value($socials)) {
        $socials = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'googleplus' => 'Google+',
            'pinterest' => 'Pinterest',
            'email' => 'Email'
        );
        $socials = apply_filters('fansub_social_share_buttons', $socials);
    }
    ?>
    <div class="social-share">
        <ul class="list-inline list-unstyled list-share-buttons">
            <?php
            foreach($socials as $social_name => $text) {
                $font_awesome = 'fa-' . $social_name;
                $btn_class = 'btn-' . $social_name;
                switch($social_name) {
                    case 'email':
                        fansub_add_string_with_space_before($font_awesome, 'fa-envelope');
                        break;
                    case 'googleplus':
                    case 'gplus':
                        fansub_add_string_with_space_before($font_awesome, 'fa-google-plus');
                        fansub_add_string_with_space_before($btn_class, 'btn-google-plus');
                        break;
                }
                echo '<li><a target="_blank" href="' . fansub_get_social_share_url(array('social_name' => $social_name)) . '" class="btn btn-social ' . $btn_class . '"><i class="fa ' . $font_awesome . ' icon-left"></i> ' . $text . '</a></li>';
            }
            ?>
        </ul>
    </div>
    <?php
}

function fansub_get_social_share_url($args = array()) {
    $result = '';
    $title = fansub_get_value_by_key($args, 'title', get_the_title());
    $permalink = fansub_get_value_by_key($args, 'permalink', get_the_permalink());
    $social_name = fansub_get_value_by_key($args, 'social_name');
    $thumbnail = fansub_get_value_by_key($args, 'thumbnail');
    $excerpt = fansub_get_value_by_key($args, 'excerpt', get_the_excerpt());
    $language = fansub_get_value_by_key($args, 'language', fansub_get_language());
    $twitter_account = fansub_get_value_by_key($args, 'twitter_account', 'skylarkcob');
    $permalink = urlencode($permalink);
    if(empty($twitter_account)) {
        $twitter_account = fansub_get_wpseo_social_value('twitter_site');
        $twitter_account = basename($twitter_account);
    }
    switch($social_name) {
        case 'email':
            $result = 'mailto:email@fansub.net?subject=' . $title . '&amp;body=' . $permalink;
            break;
        case 'facebook':
            $url = 'https://www.facebook.com/sharer/sharer.php';
            $url = add_query_arg('u', $permalink, $url);
            if(!empty($title)) {
                $url = add_query_arg('t', $title, $url);
            }
            $result = $url;
            break;
        case 'gplus':
        case 'googleplus':
            $url = 'http://plusone.google.com/_/+1/confirm';
            $url = add_query_arg('hl', $language, $url);
            $url = add_query_arg('url', $permalink, $url);
            $result = $url;
            break;
        case 'twitter':
            $url = 'http://twitter.com/share';
            $url = add_query_arg('url', $permalink, $url);
            if(!empty($title)) {
                $url = add_query_arg('text', $title, $url);
            }
            $url = add_query_arg('via', $twitter_account, $url);
            $result = $url;
            break;
        case 'pinterest':
            $url = 'http://www.pinterest.com/pin/create/button';
            if(!empty($thumbnail)) {
                $url = add_query_arg('media', $thumbnail, $url);
            }
            $url = add_query_arg('url', $permalink, $url);
            if(!empty($title)) {
                $url = add_query_arg('description', $title . ' ' . $permalink, $url);
            }
            $result = $url;
            break;
        case 'zingme':
            $url = 'http://link.apps.zing.vn/share';
            if(!empty($title)) {
                $url = add_query_arg('t', $title, $url);
            }
            $url = add_query_arg('u', $permalink, $url);
            if(!empty($excerpt)) {
                $url = add_query_arg('desc', $excerpt, $url);
            }
            $result = $url;
            break;
    }
    return $result;
}

function fansub_remove_vietnamese($string) {
    $characters = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D' => 'Đ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    );
    foreach($characters as $key => $value) {
        $string = preg_replace("/($value)/i", $key, $string);
    }
    return $string;
}

function fansub_sanitize_file_name($name) {
    $name = fansub_remove_vietnamese($name);
    $name = strtolower($name);
    $name = str_replace('_', '-', $name);
    $name = str_replace(' ', '-', $name);
    $name = sanitize_file_name($name);
    return $name;
}

function fansub_menu_page_exists($slug) {
    if(empty($GLOBALS['admin_page_hooks'][$slug])) {
        return false;
    }
    return true;
}

function fansub_callback_exists($callback) {
    if(empty($callback) || (!is_array($callback) && !function_exists($callback)) || (is_array($callback) && count($callback) != 2) || (is_array($callback) && !method_exists($callback[0], $callback[1]))) {
        return false;
    }
    if(!is_callable($callback)) {
        return false;
    }
    return true;
}

function fansub_add_unique_string(&$string, $add, $tail = true) {
    if(empty($string)) {
        $string = $add;
    } elseif(!fansub_string_contain($string, $add)) {
        if($tail) {
            $string .= $add;
        } else {
            $string = $add . $string;
        }
    }
    $string = trim($string);
    return $string;
}

function fansub_add_string_with_space_before(&$string, $add) {
    $add = ' ' . $add;
    $string = trim(fansub_add_unique_string($string, $add));
    return $string;
}

function fansub_get_current_admin_page() {
    return isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
}

function fansub_is_current_admin_page($page) {
    $admin_page = fansub_get_current_admin_page();
    if(!empty($admin_page) && $admin_page == $page) {
        return true;
    }
    return false;
}

function fansub_get_plugins($folder = '') {
    if(!function_exists('get_plugins')) {
        require(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    return get_plugins($folder);
}

function fansub_get_my_plugins() {
    $result = array();
    $lists = fansub_get_plugins();
    foreach($lists as $file => $plugin) {
        if(fansub_is_my_plugin($plugin)) {
            $result[$file] = $plugin;
        }
    }
    return $result;
}

function fansub_is_my_plugin($plugin_data) {
    $result = false;
    $author_uri = isset($plugin_data['AuthorURI']) ? $plugin_data['AuthorURI'] : '';
    if(fansub_get_root_domain_name($author_uri) == fansub_get_root_domain_name(FANSUB_HOMEPAGE)) {
        $result = true;
    }
    return $result;
}

function fansub_is_my_theme($stylesheet = null, $theme_root = null) {
    $result = false;
    $theme = wp_get_theme($stylesheet, $theme_root);
    $theme_uri = $theme->get('ThemeURI');
    $text_domain = $theme->get('TextDomain');
    $author_uri = $theme->get('AuthorURI');
    if((fansub_string_contain($theme_uri, 'fansub') && fansub_string_contain($author_uri, 'fansub')) || (fansub_string_contain($text_domain, 'fansub') && fansub_string_contain($theme_uri, 'fansub')) || (fansub_string_contain($text_domain, 'fansub') && fansub_string_contain($author_uri, 'fansub'))) {
        $result = true;
    }
    return $result;
}

function fansub_has_plugin() {
    $result = false;
    $plugins = fansub_get_plugins();
    foreach($plugins as $plugin) {
        if(fansub_is_my_plugin($plugin)) {
            $result = true;
            break;
        }
    }
    return $result;
}

function fansub_has_plugin_activated() {
    $plugins = get_option('active_plugins');
    if(fansub_array_has_value($plugins)) {
        foreach($plugins as $base_name) {
            if(fansub_string_contain($base_name, 'fansub')) {
                return true;
            }
        }
    }
    return false;
}

function fansub_admin_notice($args = array()) {
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'updated notice');
    $error = isset($args['error']) ? (bool)$args['error'] : false;
    $type = isset($args['type']) ? $args['type'] : 'default';
    $bs_callout = 'bs-callout-' . $type;
    fansub_add_string_with_space_before($class, $bs_callout);
    if($error) {
        fansub_add_string_with_space_before($class, 'settings-error error');
    }
    $dismissible = isset($args['dismissible']) ? (bool)$args['dismissible'] : true;
    if($dismissible) {
        fansub_add_string_with_space_before($class, 'is-dismissible');
    }
    $id = isset($args['id']) ? $args['id'] : '';
    $id = fansub_sanitize_id($id);
    $text = isset($args['text']) ? $args['text'] : '';
    if(empty($text)) {
        return;
    }
    $title = isset($args['title']) ? $args['title'] : '';
    if($error && empty($title)) {
        $title = __('Error', 'fansub');
    }
    if(!empty($title)) {
        $text = '<strong>' . $title . ':</strong> ' . $text;
    }
    ?>
    <div class="<?php echo esc_attr($class); ?>" id="<?php echo esc_attr($id); ?>">
        <p><?php echo $text; ?></p>
    </div>
    <?php
}

function fansub_sanitize_id($id) {
    if(is_array($id)) {
        $id = implode('@', $id);
    }
    $id = strtolower($id);
    $id = str_replace('][', '_', $id);
    $chars = array(
        '-',
        ' ',
        '[',
        ']',
        '@',
        '.'
    );
    $id = str_replace($chars, '_', $id);
    $id = trim($id, '_');
    return $id;
}

function fansub_admin_notice_setting_saved() {
    fansub_admin_notice(array('text' => '<strong>' . __('Settings saved.', 'fansub') . '</strong>'));
}

function fansub_sanitize_field_name($base_name, $arr = array()) {
    $name = '';
    if(!is_array($arr)) {
        if(fansub_string_contain($arr, $base_name)) {
            return $arr;
        }
        $arr = (array)$arr;
    }
    foreach($arr as $part) {
        if(!is_array($part) && fansub_string_contain($part, $base_name)) {
            return array_shift($arr);
        }
        $name .= '[' . $part . ']';
    }
    return $base_name . $name;
}

function fansub_sanitize_field_args(&$args) {
    if(isset($args['sanitized'])) {
        return $args;
    }
    $field_class = isset($args['field_class']) ? $args['field_class'] : '';
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($field_class, $class);
    $widefat = isset($args['widefat']) ? (bool)$args['widefat'] : true;
    $id = isset($args['id']) ? $args['id'] : '';
    $label = isset($args['label']) ? $args['label'] : '';
    $name = isset($args['name']) ? $args['name'] : '';
    fansub_transmit_id_and_name($id, $name);
    $value = isset($args['value']) ? $args['value'] : '';
    $description = isset($args['description']) ? $args['description'] : '';
    $args['class'] = $field_class;
    $args['field_class'] = $field_class;
    $args['id'] = $id;
    $args['label'] = $label;
    $args['name'] = $name;
    $args['value'] = $value;
    $args['description'] = $description;
    $args['widefat'] = $widefat;
    $args['sanitized'] = true;
    return $args;
}

function fansub_is_image($url, $id = 0) {
    $result = false;
    if(fansub_id_number_valid($id)) {
        $result = wp_attachment_is_image($id);
    } else {
        $img_formats = array('png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'ico');
        $path_info = pathinfo($url);
        $extension = isset($path_info['extension']) ? $path_info['extension'] : '';
        if(in_array(strtolower($extension), $img_formats)) {
            $result = true;
        }
    }
    return $result;
}

function fansub_sanitize_media_value($value) {
    $url = isset($value['url']) ? $value['url'] : '';
    $has_url = false;
    if(!empty($url)) {
        $has_url = true;
    }
    $id = isset($value['id']) ? $value['id'] : '';
    $id = absint($id);
    if(0 < $id && fansub_media_file_exists($id)) {
        $url = fansub_get_media_image_url($id);
    }
    if(0 >= $id && !is_array($value) && !empty($value)) {
        $url = $value;
    }
    if($has_url && empty($url)) {
        $url = wp_get_attachment_url($id);
    }
    $icon = wp_mime_type_icon($id);
    $size = fansub_get_media_size($id);
    $result = array(
        'id' => $id,
        'url' => $url,
        'type_icon' => $icon,
        'is_image' => fansub_is_image($url, $id),
        'size' => $size,
        'size_format' => fansub_size_converter($size),
        'mime_type' => get_post_mime_type($id)
    );
    return $result;
}

function fansub_get_media_path($id) {
    return get_attached_file($id);
}

function fansub_media_file_exists($id) {
    if(file_exists(fansub_get_media_path($id))) {
        return true;
    }
    return false;
}

function fansub_get_media_id($url) {
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url));
    return isset($attachment[0]) ? $attachment[0] : 0;
}

function fansub_get_media_image_detail($id) {
    return wp_get_attachment_image_src($id, 'full');
}

function fansub_get_media_image_url($id) {
    $detail = fansub_get_media_image_detail($id);
    return isset($detail[0]) ? $detail[0] : '';
}

function fansub_size_converter($bytes, $decimals = 2) {
    $result = size_format($bytes, $decimals);
    $result = strtoupper($result);
    return $result;
}

function fansub_get_media_size($id) {
    return filesize(get_attached_file($id));
}

function fansub_get_image_sizes($id) {
    $path = $id;
    if(fansub_id_number_valid($id)) {
        $path = get_attached_file($id);
    }
    if(!file_exists($path)) {
        return null;
    }
    return getimagesize($path);
}

function fansub_get_media_option_url($value) {
    $value = fansub_sanitize_media_value($value);
    return $value['url'];
}

function fansub_bool_to_int($value) {
    if($value) {
        return 1;
    }
    return 0;
}

function fansub_int_to_bool($value) {
    $value = absint($value);
    if(0 < $value) {
        return true;
    }
    return false;
}

function fansub_bool_to_string($value) {
    if($value) {
        return 'true';
    }
    return 'false';
}

function fansub_string_to_bool($string) {
    $string = strtolower($string);
    if('true' == $string) {
        return true;
    }
    return false;
}

function fansub_search_form($args = array()) {
    $echo = isset($args['echo']) ? (bool)$args['echo'] : true;
    $class = isset($args['class']) ? $args['class'] : '';
    fansub_add_string_with_space_before($class, 'search-form');
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : _x('Search &hellip;', 'placeholder');
    $search_icon = isset($args['search_icon']) ? $args['search_icon'] : false;
    $submit_text = _x('Search', 'submit button');
    if($search_icon) {
        fansub_add_string_with_space_before($class, 'use-icon-search');
        $submit_text = '&#xf002;';
    }
    $icon_in = fansub_get_value_by_key($args, 'icon_in');
    if((bool)$icon_in) {
        fansub_add_string_with_space_before($class, 'icon-in');
    }
    $action = fansub_get_value_by_key($args, 'action', home_url('/'));
    $action = trailingslashit($action);
    $name = fansub_get_value_by_key($args, 'name', 's');
    $form = '<form method="get" class="' . $class . '" action="' . esc_url($action) . '">
				<label>
					<span class="screen-reader-text">' . _x('Search for:', 'label') . '</span>
					<input type="search" class="search-field" placeholder="' . esc_attr($placeholder) . '" value="' . get_search_query() . '" name="' . $name . '" title="' . esc_attr_x('Search for:', 'label') . '" />
				</label>
				<input type="submit" class="search-submit" value="'. esc_attr($submit_text) .'" />
			</form>';
    if($echo) {
        echo $form;
    }
    return $form;
}

function fansub_feedburner_form($args = array()) {
    $name = isset($args['name']) ? $args['name'] : '';
    $locale = isset($args['locale']) ? $args['locale'] : 'en_US';
    $submit_button_text = isset($args['submit_button_text']) ? $args['submit_button_text'] : '';
    if(!isset($args['submit_button_text']) && isset($args['button_text'])) {
        $submit_button_text = $args['button_text'];
    }
    if(empty($submit_button_text)) {
        $submit_button_text = __('Đăng ký', 'fansub');
    }
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : __('Nhập địa chỉ email của bạn...', 'fansub');
    ?>
    <form class="feedburner-form" action="https://feedburner.google.com/fb/a/mailverify" method="post" target="popupwindow" onsubmit="window.open('https://feedburner.google.com/fb/a/mailverify?uri=<?php echo $name; ?>', 'popupwindow', 'scrollbars=yes,width=550,height=520');return true">
        <?php do_action('fansub_feedburner_before'); ?>
        <input class="email-field" type="text" placeholder="<?php echo $placeholder; ?>" name="email" autocomplete="off">
        <input type="hidden" value="<?php echo $name; ?>" name="uri">
        <input type="hidden" name="loc" value="<?php echo $locale; ?>">
        <input class="btn btn-submit" type="submit" value="<?php echo $submit_button_text; ?>">
        <?php do_action('fansub_feedburner_after'); ?>
    </form>
    <?php
}

function fansub_get_sidebars() {
    return $GLOBALS['wp_registered_sidebars'];
}

function fansub_get_sidebar_by($key, $value) {
    $sidebars = fansub_get_sidebars();
    foreach ($sidebars as $id => $sidebar) {
        switch($key) {
            default:
                if($id == $value) {
                    return $sidebar;
                }
        }
    }
    return array();
}

function fansub_sidebar_has_widget($sidebar, $widget) {
    $sidebar_name = $sidebar;
    $sidebars = fansub_get_sidebars();
    $sidebar = isset($sidebars[$sidebar]) ? $sidebars[$sidebar] : '';
    if(!empty($sidebar)) {
        $widgets = fansub_get_sidebar_widgets($sidebar_name);
        foreach($widgets as $widget_name) {
            if(fansub_string_contain($widget_name, $widget)) {
                return true;
            }
        }
    }
    return false;
}

function fansub_get_sidebar_widgets($sidebar) {
    $widgets = wp_get_sidebars_widgets();
    $widgets = isset($widgets[$sidebar]) ? $widgets[$sidebar] : null;
    return $widgets;
}

function fansub_supported_languages() {
    $languages = array(
        'vi' => __('Vietnamese', 'fansub'),
        'en' => __('English', 'fansub')
    );
    return apply_filters('fansub_supported_languages', $languages);
}

function fansub_get_language() {
    $lang = fansub_option_get_value('theme_setting', 'language');
    if(empty($lang)) {
        $lang = 'vi';
    }
    return apply_filters('fansub_language', $lang);
}

function fansub_register_core_style_and_script() {
    wp_register_style('fansub-style', FANSUB_URL . '/css/fansub' . FANSUB_CSS_SUFFIX, array(), FANSUB_VERSION);
    wp_register_script('fansub', FANSUB_URL . '/js/fansub' . FANSUB_JS_SUFFIX, array('jquery'), FANSUB_VERSION, true);
}

function fansub_default_script_localize_object() {
    $datepicker_icon = apply_filters('fansub_datepicker_icon', FANSUB_URL . '/images/icon-datepicker-calendar.gif');
    $shortcodes = fansub_get_all_shortcodes();
    $args = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'datepicker_icon' => $datepicker_icon,
        'shortcodes' => $shortcodes,
        'logged_in' => fansub_bool_to_int(is_user_logged_in()),
        'i18n' => array(
            'jquery_undefined_error' => __('HocWP\'s JavaScript requires jQuery', 'fansub'),
            'jquery_version_error' => sprintf(__('HocWP\'s JavaScript requires jQuery version %s or higher', 'fansub'), FANSUB_MINIMUM_JQUERY_VERSION),
            'insert_media_title' => __('Insert media', 'fansub'),
            'insert_media_button_text' => __('Use this media', 'fansub'),
            'insert_media_button_texts' => __('Use these medias', 'fansub'),
            'confirm_message' => __('Are you sure?', 'fansub'),
            'disconnect_confirm_message' => __('Are you sure you want to disconnect?', 'fansub')
        ),
        'ajax_loading' => '<p class="ajax-wrap"><img class="ajax-loading" src="' . fansub_get_image_url('icon-loading-circle-light-full.gif') . '" alt=""></p>'
    );
    return apply_filters('fansub_default_script_object', $args);
}

function fansub_enqueue_jquery_ui_style() {
    $version = FANSUB_JQUERY_LATEST_VERSION;
    $version = apply_filters('fansub_jquery_ui_version', $version);
    $theme = apply_filters('fansub_jquery_ui_theme', 'smoothness');
    wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $version . '/themes/' . $theme . '/jquery-ui.css');
}

function fansub_enqueue_jquery_ui_datepicker() {
    wp_enqueue_script('jquery-ui-datepicker');
    fansub_enqueue_jquery_ui_style();
}

function fansub_get_recaptcha_language() {
    $lang = apply_filters('fansub_recaptcha_language', fansub_get_language());
    return $lang;
}

function fansub_enqueue_recaptcha() {
    $lang = fansub_get_recaptcha_language();
    $url = 'https://www.google.com/recaptcha/api.js';
    $url = add_query_arg(array('hl' => $lang), $url);
    $multiple = apply_filters('fansub_multiple_recaptcha', false);
    if($multiple) {
        $url = add_query_arg(array('onload' => 'CaptchaCallback', 'render' => 'explicit'), $url);
    }
    wp_enqueue_script('recaptcha', $url, array(), false, true);
}

function fansub_recaptcha_response($secret_key) {
    $result = false;
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_POST['g-recaptcha-response']);
    $response = json_decode($response, true);
    if(true === $response['success']) {
        $result = true;
    }
    return $result;
}

function fansub_admin_enqueue_scripts() {
    global $pagenow;
    $current_page = fansub_get_current_admin_page();
    $use = apply_filters('fansub_use_jquery_ui', false);
    if($use || ('themes.php' == $pagenow && 'fansub_theme_setting' == $current_page)) {
        wp_enqueue_script('jquery-ui-core');
    }
    $use = apply_filters('fansub_use_jquery_ui_sortable', false);
    if($use) {
        wp_enqueue_script('jquery-ui-sortable');
    }
    $use = apply_filters('fansub_use_color_picker', false);
    if($use) {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
    }
    $use = apply_filters('fansub_wp_enqueue_media', false);
    if($use) {
        wp_enqueue_media();
    }
    fansub_register_core_style_and_script();
    wp_register_style('fansub-admin-style', FANSUB_URL . '/css/fansub-admin'. FANSUB_CSS_SUFFIX, array('fansub-style'), FANSUB_VERSION);
    wp_register_script('fansub-admin', FANSUB_URL . '/js/fansub-admin' . FANSUB_JS_SUFFIX, array('jquery', 'fansub'), FANSUB_VERSION, true);
    wp_localize_script('fansub', 'fansub', fansub_default_script_localize_object());
    $use = apply_filters('fansub_use_admin_style_and_script', false);
    if($use || 'post-new.php' == $pagenow || 'post.php' == $pagenow) {
        wp_enqueue_style('fansub-admin-style');
        wp_enqueue_script('fansub-admin');
    } elseif('wpsupercache' == $current_page) {
        wp_enqueue_style('fansub-admin-style');
    }
}

function fansub_get_admin_email() {
    return get_bloginfo('admin_email');
}

function fansub_google_plus_client_script() {
    wp_enqueue_script('google-client', 'https://plus.google.com/js/client:platform.js', array(), false, true);
}

function fansub_facebook_javascript_sdk($args = array()) {
    $language = isset($args['language']) ? $args['language'] : 'vi_VN';
    $language = apply_filters('fansub_facebook_javascript_sdk_language', $language);
    $app_id = isset($args['app_id']) ? $args['app_id'] : '';
    $app_id = apply_filters('fansub_facebook_javascript_sdk_app_id', $app_id);
    if(empty($app_id)) {
        return;
    }
    $version = isset($args['version']) ? $args['version'] : FANSUB_FACEBOOK_GRAPH_API_VERSION;
    $version = apply_filters('fansub_facebook_javascript_sdk_version', $version);
    $use = fansub_use_facebook_javascript_sdk();
    if(!(bool)$use) {
        return;
    }
    ?>
    <div id="fb-root"></div>
    <script>
        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/<?php echo $language; ?>/sdk.js#xfbml=1&version=v<?php echo $version; ?>&appId=<?php echo $app_id; ?>";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
    <?php
}

function fansub_use_full_mce_toolbar() {
    $use = false;
    global $pagenow;
    if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
        $use = true;
    }
    return apply_filters('fansub_use_full_mce_toolbar', $use);
}

function fansub_use_facebook_javascript_sdk() {
    $result = apply_filters('fansub_use_facebook_javascript_sdk', false);
    return $result;
}

function fansub_facebook_page_plugin($args = array()) {
    $href = fansub_get_value_by_key($args, 'href', fansub_get_value_by_key($args, 'url'));
    if(empty($href)) {
        $page_id = isset($args['page_id']) ? $args['page_id'] : 'fansubnet';
        $href = 'https://www.facebook.com/' . $page_id;
    }
    if(empty($href)) {
        return;
    }
    $page_name = isset($args['page_name']) ? $args['page_name'] : '';
    $width = isset($args['width']) ? $args['width'] : 340;
    $height = isset($args['height']) ? $args['height'] : 500;
    $hide_cover = (bool)(isset($args['hide_cover']) ? $args['hide_cover'] : false);
    $hide_cover = fansub_bool_to_string($hide_cover);
    $show_facepile = (bool)(isset($args['show_facepile']) ? $args['show_facepile'] : true);
    $show_facepile = fansub_bool_to_string($show_facepile);
    $show_posts = (bool)(isset($args['show_posts']) ? $args['show_posts'] : false);
    $show_posts = fansub_bool_to_string($show_posts);
    $hide_cta = (bool)(isset($args['hide_cta']) ? $args['hide_cta'] : false);
    $hide_cta = fansub_bool_to_string($hide_cta);
    $small_header = (bool)(isset($args['small_header']) ? $args['small_header'] : false);
    $small_header = fansub_bool_to_string($small_header);
    $adapt_container_width = (bool)(isset($args['adapt_container_width']) ? $args['adapt_container_width'] : true);
    $adapt_container_width = fansub_bool_to_string($adapt_container_width);
    ?>
    <div class="fb-page" data-href="<?php echo $href; ?>" data-width="<?php echo $width; ?>" data-height="<?php echo $height; ?>" data-hide-cta="<?php echo $hide_cta; ?>" data-small-header="<?php echo $small_header; ?>" data-adapt-container-width="<?php echo $adapt_container_width; ?>" data-hide-cover="<?php echo $hide_cover; ?>" data-show-facepile="<?php echo $show_facepile; ?>" data-show-posts="<?php echo $show_posts; ?>">
        <div class="fb-xfbml-parse-ignore">
            <?php if(!empty($page_name)) : ?>
                <blockquote cite="<?php echo $href; ?>">
                    <a href="<?php echo $href; ?>"><?php echo $page_name; ?></a>
                </blockquote>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function fansub_update_permalink_struct($struct) {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure($struct);
    update_option('permalink_structure', $struct);
    flush_rewrite_rules();
}

function fansub_flush_rewrite_rules_after_site_url_changed() {
    $old_url = get_option('fansub_site_url');
    $defined_url = (defined('WP_SITEURL')) ? WP_SITEURL : get_option('siteurl');
    if(empty($old_url) || $old_url != $defined_url) {
        update_option('fansub_site_url', $defined_url);
        flush_rewrite_rules();
    }
}

function fansub_the_footer_logo() {
    $footer_logo = fansub_get_footer_logo_url();
    if(!empty($footer_logo)) {
        $a = new FANSUB_HTML('a');
        $a->set_attribute('href', home_url('/'));
        $img = new FANSUB_HTML('img');
        $img->set_attribute('src', $footer_logo);
        $a->set_text($img->build());
        $a->output();
    }
}

function fansub_remove_array_item_by_value($value, $array) {
    if(($key = array_search($value, $array)) !== false) {
        unset($array[$key]);
    }
    return $array;
}

function fansub_find_valid_value_in_array($arr, $key) {
    $result = '';
    if(is_array($arr)) {
        if(isset($arr[$key])) {
            $result = $arr[$key];
        } else {
            $index = absint(count($arr)/2);
            if(isset($arr[$index])) {
                $result = $arr[$index];
            } else {
                $result = current($arr);
            }
        }
    }
    return $result;
}

function fansub_pretty_permalinks_enabled() {
    $permalink_structure = get_option('permalink_structure');
    return (empty($permalink_structure)) ? false : true;
}

function fansub_exclude_special_taxonomies(&$taxonomies) {
    unset($taxonomies['nav_menu']);
    unset($taxonomies['link_category']);
    unset($taxonomies['post_format']);
}

function fansub_exclude_special_post_types(&$post_types) {
    unset($post_types['attachment']);
    unset($post_types['page']);
}

function fansub_get_last_part_in_url($url) {
    return substr(parse_url($url, PHP_URL_PATH), 1);
}

function fansub_substr($str, $len, $more = '...', $charset = 'UTF-8') {
    $more = esc_html($more);
    $str = html_entity_decode($str, ENT_QUOTES, $charset);
    if(mb_strlen($str, $charset) > $len) {
        $arr = explode(' ', $str);
        $str = mb_substr($str, 0, $len, $charset);
        $arr_words = explode(' ', $str);
        $index = count($arr_words) - 1;
        $last = $arr[$index];
        unset($arr);
        if(strcasecmp($arr_words[$index], $last)) {
            unset($arr_words[$index]);
        }
        return implode(' ', $arr_words) . $more;
    }
    return $str;
}

function fansub_icon_circle_ajax($post_id, $meta_key) {
    $div = new FANSUB_HTML('div');
    $div->set_attribute('style', 'text-align: center');
    $div->set_class('fansub-switcher-ajax');
    $span = new FANSUB_HTML('span');
    $circle_class = 'icon-circle';
    $result = get_post_meta($post_id, $meta_key, true);
    if(1 == $result) {
        $circle_class .= ' icon-circle-success';
    }
    $span->set_attribute('data-id', $post_id);
    $span->set_attribute('data-value', $result);
    $span->set_attribute('data-key', $meta_key);
    $span->set_class($circle_class);
    $div->set_text($span->build());
    $div->output();
}

function fansub_get_posts_per_page() {
    return get_option('posts_per_page');
}

function fansub_delete_transient_with_condition($transient_name, $condition = '', $blog_id = '') {
    global $wpdb;
    if(!empty($blog_id)) {
        $wpdb->set_blog_id($blog_id);
    }
    $last_char = fansub_get_last_char($transient_name);
    if('_' == $last_char) {
        $transient_name = fansub_remove_last_char($transient_name, $last_char);
    }
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name like %s" . $condition, '_transient_' . $transient_name . '_%'));
    $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name like %s" . $condition, '_transient_timeout_' . $transient_name . '_%'));
}

function fansub_delete_transient($transient_name, $blog_id = '') {
    fansub_delete_transient_with_condition($transient_name, $blog_id);
}

function fansub_delete_transient_license_valid($blog_id = '') {
    $transient_name = 'fansub_check_license';
    fansub_delete_transient($transient_name, $blog_id);
}

function fansub_get_wp_version() {
    global $wp_version;
    return $wp_version;
}

function fansub_get_ip_address() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
}

function fansub_get_upload_folder_details() {
    $upload = wp_upload_dir();
    $dir = isset($upload['basedir']) ? $upload['basedir'] : '';
    $url = isset($upload['baseurl']) ? $upload['baseurl'] : '';
    if(empty($dir)) {
        $dir = WP_CONTENT_DIR . '/uploads';
    }
    if(empty($url)) {
        $url = content_url('uploads');
    }
    return array('path' => $dir, 'url' => $url);
}

function fansub_is_phone_number($number) {
    $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
    $result = (preg_match($regex, $number)) ? true : false;
    if($result) {
        $len = strlen($number);
        if($len < 7 || $len > 20) {
            $result = false;
        }
    }
    return $result;
}

function fansub_image_base64($file) {
    $image_data = @file_get_contents($file);
    return 'data:image/png;base64,' . base64_encode($image_data);
}

function fansub_upload($args = array()) {
    $name = isset($args['name']) ? $args['name'] : '';
    $path = isset($args['path']) ? $args['path'] : '';
    $size = isset($args['size']) ? $args['size'] : 0;
    $max_size = isset($args['max_size']) ? $args['max_size'] : -1;
    $is_image = isset($args['is_image']) ? $args['is_image'] : false;
    $extensions = isset($args['extensions']) ? $args['extensions'] : array();
    $tmp_name = isset($args['tmp_name']) ? $args['tmp_name'] : '';
    $duplicate_exists = isset($args['duplicate_exists']) ? $args['duplicate_exists'] : true;
    $result = array(
        'success' => false
    );
    $result['image_base64'] = fansub_image_base64($tmp_name);
    $name = strtolower($name);
    $file_path = $path . '/' . basename($name);
    $file_type = pathinfo($file_path, PATHINFO_EXTENSION);
    if($is_image && !empty($tmp_name)) {
        $check = getimagesize($tmp_name);
        if($check === false) {
            $result['message'][] = 'Tập tin ' . $name . ' không phải là hình ảnh.';
            return $result;
        }
    }
    if(file_exists($file_path)) {
        if($duplicate_exists) {
            $path_info = pathinfo($file_path);
            $name = $path_info['filename'] . '-' . fansub_random_string() . '.' . $file_type;
            $name = strtolower($name);
            $file_path = $path . '/' . basename($name);
        } else {
            $result['message'][] = 'Tập tin ' . $name . ' đã tồn tại.';
            return $result;
        }
    }
    if($max_size > 0 && $size > $max_size) {
        $result['message'][] = 'Dung lượng tập tin không được quá ' . $max_size . 'KB.';
        return $result;
    }
    if(count($extensions) > 0 && !in_array($file_type, $extensions)) {
        $result['message'][] = 'Bạn không được phép upload tập tin với định dạng ' . $file_type . '.';
        return $result;
    }
    $file_path = strtolower($file_path);
    if(move_uploaded_file($tmp_name, $file_path)) {
        $result['success'] = true;
    } else {
        $result['message'][] = 'Đã có lỗi xảy ra, tập tin của bạn chưa được upload.';
    }
    $result['name'] = $name;
    $result['path'] = $file_path;
    return $result;
}

function fansub_execute_upload($args = array()) {
    $files = isset($args['files']) ? $args['files'] : array();
    unset($args['files']);
    $upload_path = isset($args['upload_path']) ? $args['upload_path'] : '';
    unset($args['upload_path']);
    $upload_url = isset($args['upload_url']) ? $args['upload_url'] : '';
    unset($args['upload_url']);
    if(empty($upload_path)) {
        $upload_dir = fansub_get_upload_folder_details();
        $target_dir = untrailingslashit($upload_dir['path']) . '/fansub';
        $upload_url = untrailingslashit($upload_dir['url']) . '/fansub';
        if(!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        $upload_path = $target_dir;
    }
    $file_names = isset($files['name']) ? $files['name'] : array();
    $file_count = count($file_names);
    $list_files = array();
    for($i = 0; $i < $file_count; $i++) {
        $name = isset($files['name'][$i]) ? $files['name'][$i] : '';
        $type = isset($files['type'][$i]) ? $files['type'][$i] : '';
        $tmp_name = isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '';
        $error = isset($files['error'][$i]) ? $files['error'][$i] : '';
        $size = isset($files['size'][$i]) ? $files['size'][$i] : '';
        $file_item = array(
            'name' => $name,
            'type' => $type,
            'tmp_name' => $tmp_name,
            'error' => $error,
            'size' => $size
        );
        $list_files[] = $file_item;
    }
    $list_results = array();
    foreach($list_files as $key => $file) {
        $file['path'] = $upload_path;
        $file = wp_parse_args($args, $file);
        $result = fansub_upload($file);
        if($result['success']) {
            $file_name = $file['name'];
            $file_path = untrailingslashit($upload_path) . '/' . $file_name;
            $file_url = untrailingslashit($upload_url) . '/' . basename($result['name']);
            $attachment = array(
                'guid' => $file_url
            );
            fansub_insert_attachment($attachment, $file_path);
            $result['url'] = $file_url;
        }
        $list_results[] = $result;
    }
    return $list_results;
}

function fansub_insert_attachment($attachment, $file_path, $parent_post_id = 0) {
    if(!file_exists($file_path)) {
        return 0;
    }
    $file_type = wp_check_filetype(basename($file_path), null);
    $attachment['post_mime_type'] = $file_type['type'];
    if(!isset($attachment['guid'])) {
        return 0;
    }
    $attachment['post_status'] = isset($attachment['post_status']) ? $attachment['post_status'] : 'inherit';
    if(!isset($attachment['post_title'])) {
        $attachment['post_title'] = preg_replace('/\.[^.]+$/', '', basename($file_path));
    }
    $attach_id = wp_insert_attachment($attachment, $file_path, $parent_post_id);
    if($attach_id > 0) {
        fansub_update_attachment_meta($attach_id, $file_path);
        if($parent_post_id > 0) {
            fansub_set_thumbnail($parent_post_id, $attach_id);
        }
    }
    return $attach_id;
}

function fansub_update_attachment_meta($attach_id, $file_path) {
    if(!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
}

function fansub_set_thumbnail($post_id, $attach_id) {
    return set_post_thumbnail($post_id, $attach_id);
}