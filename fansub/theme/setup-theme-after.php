<?php
if(!function_exists('add_filter')) exit;
function fansub_theme_check_load_facebook_javascript_sdk() {
    $data = apply_filters('fansub_load_facebook_javascript_sdk_on_page_sidebar', array());
    foreach($data as $value) {
        $conditional_functions = isset($value['condition']) ? $value['condition'] : '';
        $conditional_functions = fansub_sanitize_array($conditional_functions);
        $condition_result = false;
        foreach($conditional_functions as $function) {
            if(!fansub_callback_exists($function)) {
                continue;
            }
            if(call_user_func($function)) {
                $condition_result = true;
                break;
            }
        }
        $sidebar = isset($value['sidebar']) ? $value['sidebar'] : '';
        $sidebars = fansub_sanitize_array($sidebar);
        foreach($sidebars as $sidebar) {
            if(is_active_sidebar($sidebar) && $condition_result && fansub_sidebar_has_widget($sidebar, 'fansub_widget_facebook_box')) {
                return true;
            }
        }
    }
    $comment_system = fansub_theme_get_option('comment_system', 'discussion');
    if('facebook' == $comment_system || 'default_and_facebook' == $comment_system) {
        if(is_singular()) {
            $post_id = get_the_ID();
            if(comments_open($post_id) || get_comments_number($post_id)) {
                return true;
            }
        }
    }
    return false;
}
add_filter('fansub_use_facebook_javascript_sdk', 'fansub_theme_check_load_facebook_javascript_sdk');

function fansub_setup_theme_add_facebook_javascript_sdk() {
    if(fansub_use_facebook_javascript_sdk()) {
        $args = array();
        $app_id = fansub_get_wpseo_social_value('fbadminapp');
        if(!empty($app_id)) {
            $args['app_id'] = $app_id;
        }
        fansub_facebook_javascript_sdk($args);
    }
}
add_action('fansub_close_body', 'fansub_setup_theme_add_facebook_javascript_sdk');

function fansub_more_mce_buttons_toolbar_1($buttons) {
    if(!fansub_use_full_mce_toolbar()) {
        return $buttons;
    }
    $tmp = $buttons;
    unset($buttons);
    $buttons[] = 'fontselect';
    $buttons[] = 'fontsizeselect';
    $last = array_pop($tmp);
    $buttons = array_merge($buttons, $tmp);
    $buttons[] = 'styleselect';
    $buttons[] = $last;
    return $buttons;
}
add_filter('mce_buttons', 'fansub_more_mce_buttons_toolbar_1');

function fansub_more_mce_buttons_toolbar_2($buttons) {
    if(!fansub_use_full_mce_toolbar()) {
        return $buttons;
    }
    $buttons[] = 'subscript';
    $buttons[] = 'superscript';
    $buttons[] = 'hr';
    $buttons[] = 'cut';
    $buttons[] = 'copy';
    $buttons[] = 'paste';
    $buttons[] = 'backcolor';
    $buttons[] = 'newdocument';
    return $buttons;
}
add_filter('mce_buttons_2', 'fansub_more_mce_buttons_toolbar_2');

function fansub_load_addthis_script() {
    $use = apply_filters('fansub_use_addthis', false);
    if($use) {
        fansub_addthis_script();
    }
}
add_action('wp_footer', 'fansub_load_addthis_script');

unset($GLOBALS['wpdb']->dbpassword);
unset($GLOBALS['wpdb']->dbname);

function fansub_theme_custom_check_license() {
    $option = get_option('fansub_cancel_license');
    $theme_key = md5(get_option('template'));
    $cancel = absint(isset($option['theme'][$theme_key]) ? $option['theme'][$theme_key] : '');
    if(1 == $cancel || !has_action('fansub_check_license', 'fansub_setup_theme_check_license')) {
        fansub_theme_invalid_license_redirect();
    }
}
add_action('fansub_check_license', 'fansub_theme_custom_check_license');

function fansub_theme_post_submitbox_misc_actions() {
    global $post;
    if(!fansub_object_valid($post)) {
        return;
    }
    $post_type = $post->post_type;
    $post_types = fansub_post_type_no_featured_field();
    if(!in_array($post_type, $post_types)) {
        $key = 'featured';
        $value = get_post_meta($post->ID, $key, true);
        $args = array(
            'id' => 'fansub_featured_post',
            'name' => $key,
            'value' => $value,
            'label' => __('Featured?', 'hocwp')
        );
        fansub_field_publish_box('fansub_field_input_checkbox', $args);
    }
    do_action('fansub_publish_box_field');
}
add_action('post_submitbox_misc_actions', 'fansub_theme_post_submitbox_misc_actions');

function fansub_theme_use_admin_style_and_script($use) {
    global $pagenow;
    if('edit.php' == $pagenow) {
        $use = true;
    }
    return $use;
}
add_filter('fansub_use_admin_style_and_script', 'fansub_theme_use_admin_style_and_script');

function fansub_theme_post_column_head_featured($columns) {
    global $post_type;
    $exclude_types = fansub_post_type_no_featured_field();
    if(!in_array($post_type, $exclude_types)) {
        $columns['featured'] = __('Featured', 'hocwp');
    }
    return $columns;
}
add_filter('manage_posts_columns', 'fansub_theme_post_column_head_featured');

function fansub_theme_post_column_content_featured($column, $post_id) {
    if('featured' == $column) {
        fansub_icon_circle_ajax($post_id, 'featured');
    }
}
add_action('manage_posts_custom_column', 'fansub_theme_post_column_content_featured', 10, 2);

function fansub_theme_switcher_ajax_ajax_callback() {
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
    $post_id = absint($post_id);
    $result = array(
        'success' => false
    );
    if($post_id > 0) {
        $value = isset($_POST['value']) ? $_POST['value'] : 0;
        if(0 == $value) {
            $value = 1;
        } else {
            $value = 0;
        }
        $key = isset($_POST['key']) ? $_POST['key'] : '';
        if(!empty($key)) {
            update_post_meta($post_id, $key, $value);
            $result['success'] = true;
        }
    }
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_switcher_ajax', 'fansub_theme_switcher_ajax_ajax_callback');

function fansub_theme_save_post_featured_meta($post_id) {
    if(!fansub_can_save_post($post_id)) {
        return $post_id;
    }
    $value = isset($_POST['featured']) ? 1 : 0;
    update_post_meta($post_id, 'featured', $value);
    return $post_id;
}
add_action('save_post', 'fansub_theme_save_post_featured_meta');

function fansub_theme_last_widget_fixed() {
    $fixed = apply_filters('fansub_theme_last_widget_fixed', true);
    if($fixed) {
        get_template_part('hocwp/theme/fixed-widget');
    }
}
add_action('fansub_close_body', 'fansub_theme_last_widget_fixed');

function fansub_bold_first_paragraph($content) {
    $bold = apply_filters('fansub_bold_post_content_first_paragraph', false);
    if($bold) {
        return preg_replace('/<p([^>]+)?>/', '<p$1 class="first-paragraph">', $content, 1);
    }
    return $content;
}
add_filter('the_content', 'fansub_bold_first_paragraph');

function fansub_theme_add_full_screen_loading() {
    get_template_part('/hocwp/theme/ajax-loading', 'full-screen');
}
add_action('fansub_close_body', 'fansub_theme_add_full_screen_loading');

function fansub_setup_theme_after_go_to_top_button() {
    $button = (bool)fansub_option_get_value('reading', 'go_to_top');
    $button = apply_filters('fansub_theme_go_to_top_button', $button);
    if($button) {
        $icon = fansub_option_get_value('reading', 'scroll_top_icon');
        $icon = fansub_sanitize_media_value($icon);
        $icon = $icon['url'];
        $class = 'fansub-go-top';
        if(empty($icon)) {
            $icon = '<i class="fa fa-chevron-up"></i>';
            fansub_add_string_with_space_before($class, 'icon-default');
        }
        $icon = apply_filters('fansub_theme_go_to_top_button_icon', $icon);
        if(fansub_url_valid($icon)) {
            $icon = '<img src="' . $icon . '">';
            fansub_add_string_with_space_before($class, 'icon-image');
        }
        $a = new FANSUB_HTML('a');
        $a->set_attribute('id', 'fansub_go_top');
        $a->set_text($icon);
        $a->set_attribute('href', '#');
        $a->set_attribute('class', $class);
        $a->output();
    }
}
add_action('fansub_before_wp_footer', 'fansub_setup_theme_after_go_to_top_button');

function fansub_setup_theme_add_favicon() {
    $favicon = fansub_theme_get_option('favicon');
    $favicon = fansub_sanitize_media_value($favicon);
    if(!empty($favicon['url'])) {
        echo '<link type="image/x-icon" href="' . $favicon['url'] . '" rel="shortcut icon">';
    }
}
add_action('wp_head', 'fansub_setup_theme_add_favicon');

if('vi' == fansub_get_language() && !is_admin()) {
    include FANSUB_PATH . '/theme/theme-translation.php';
}

function fansub_setup_theme_custom_css() {
    $option = get_option('fansub_theme_custom_css');
    $theme = wp_get_theme();
    $template = fansub_sanitize_id($theme->get_template());
    $css = fansub_get_value_by_key($option, $template);
    if(!empty($css)) {
        $css = fansub_minify_css($css);
        $style = new FANSUB_HTML('style');
        $style->set_attribute('type', 'text/css');
        $style->set_text($css);
        $style->output();
    }
}
add_action('wp_head', 'fansub_setup_theme_custom_css', 99);

function fansub_setup_theme_custom_head_data() {
    $option = get_option('fansub_theme_add_to_head');
    $code = fansub_get_value_by_key($option, 'code');
    if(!empty($code)) {
        echo $code;
    }
}
add_action('wp_head', 'fansub_setup_theme_custom_head_data', 99);

function fansub_setup_theme_the_excerpt($excerpt) {
    $excerpt = str_replace('<p>', '<p class="post-excerpt">', $excerpt);
    return $excerpt;
}
add_filter('the_excerpt', 'fansub_setup_theme_the_excerpt');

function fansub_setup_theme_comment_form() {

}
add_action('comment_form', 'fansub_setup_theme_comment_form');

function fansub_setup_theme_comment_form_submit_field($submit_field, $args) {
    if(fansub_use_comment_form_captcha() && !fansub_use_comment_form_captcha_custom_position()) {
        $disable_captcha_user = fansub_user_not_use_comment_form_captcha();
        if(!$disable_captcha_user || ($disable_captcha_user && !is_user_logged_in())) {
            $submit_field = str_replace('form-submit', 'form-submit captcha-beside', $submit_field);
            ob_start();
            $args = array(
                'before' => '<p class="captcha-group">',
                'after' => '</p>',
                'input_width' => 165
            );
            if('vi' == fansub_get_language()) {
                $args['placeholder'] = __('Nhập mã bảo mật', 'hocwp');
            }
            fansub_field_captcha($args);
            $captcha_field = ob_get_clean();
            $submit_field .= $captcha_field;
        }
    }
    return $submit_field;
}
add_filter('comment_form_submit_field', 'fansub_setup_theme_comment_form_submit_field', 10, 2);

function fansub_setup_theme_preprocess_comment($commentdata) {
    $disable_captcha_user = fansub_user_not_use_comment_form_captcha();
    if(fansub_use_comment_form_captcha() && (!$disable_captcha_user || ($disable_captcha_user && !is_user_logged_in()))) {
        $lang = fansub_get_language();
        if(isset($_POST['captcha'])) {
            $captcha = $_POST['captcha'];
            if(empty($captcha)) {
                if('vi' == $lang) {
                    wp_die(__('Để xác nhận bạn không phải là máy tính, xin vui lòng nhập mã bảo mật!', 'hocwp'), __('Chưa nhập mã bảo mật', 'hocwp'));
                } else {
                    wp_die(__('To confirm you are not a computer, please enter the security code!', 'hocwp'), __('Empty captcha code error', 'hocwp'));
                }
                exit;
            } else {
                $hw_captcha = new FANSUB_Captcha();
                if(!$hw_captcha->check($captcha)) {
                    if('vi' == $lang) {
                        wp_die(__('Mã bảo mật bạn nhập không chính xác, xin vui lòng thử lại!', 'hocwp'), __('Sai mã bảo mật', 'hocwp'));
                    } else {
                        wp_die(__('The security code you entered is incorrect, please try again!', 'hocwp'), __('Invalid captcha code', 'hocwp'));
                    }
                    exit;
                }
            }
        } else {
            $commentdata = null;
            if('vi' == $lang) {
                wp_die(__('Hệ thống đã phát hiện bạn không phải là người!', 'hocwp'), __('Lỗi gửi bình luận', 'hocwp'));
            } else {
                wp_die(__('Our systems have detected that you are not a human!', 'hocwp'), __('Post comment error', 'hocwp'));
            }
            exit;
        }
    }
    return $commentdata;
}
add_filter('preprocess_comment', 'fansub_setup_theme_preprocess_comment', 1);

function fansub_setup_theme_enable_session($use) {
    if(!is_admin()) {
        $disable_captcha_user = fansub_user_not_use_comment_form_captcha();
        if(fansub_use_comment_form_captcha() && (!$disable_captcha_user || ($disable_captcha_user && !is_user_logged_in()))) {
            $use = true;
        }
    }
    return $use;
}
add_filter('fansub_use_session', 'fansub_setup_theme_enable_session');

$maintenance_mode = fansub_in_maintenance_mode();

function fansub_setup_theme_in_maintenance_mode_notice() {
    fansub_in_maintenance_mode_notice();
}

function fansub_setup_theme_maintenance_head() {
    $args = fansub_maintenance_mode_settings();
    $background = fansub_get_value_by_key($args, 'background');
    $background = fansub_sanitize_media_value($background);
    $background = $background['url'];
    $css = '';
    if(!empty($background)) {
        $css .= fansub_build_css_rule(array('.fansub-maintenance'), array('background-image' => 'url("' . $background . '")'));
    }
    if(!empty($css)) {
        $css = fansub_minify_css($css);
        echo '<style type="text/css">' . $css . '</style>';
    }
}

function fansub_setup_theme_maintenance() {
    $options = fansub_maintenance_mode_settings();
    $heading = fansub_get_value_by_key($options, 'heading');
    $text = fansub_get_value_by_key($options, 'text');
    echo '<h2 class="heading">' . $heading . '</h2>';
    echo wpautop($text);
}

function fansub_setup_theme_maintenance_scripts() {
    wp_enqueue_style('fansub-maintenance-style', FANSUB_URL . '/css/fansub-maintenance.css', array());
}

function fansub_setup_theme_maintenance_body_class($classes) {
    $classes[] = 'fansub-maintenance';
    return $classes;
}

function fansub_setup_theme_navigation_markup_template($template) {
    $template = '<nav class="navigation %1$s">
		<h2 class="screen-reader-text">%2$s</h2>
		<div class="nav-links">%3$s</div>
	</nav>';
    return $template;
}
add_filter('navigation_markup_template', 'fansub_setup_theme_navigation_markup_template');

function fansub_setup_theme_get_search_form($form) {
    $format = current_theme_supports('html5', 'search-form') ? 'html5' : 'xhtml';
    $format = apply_filters('search_form_format', $format);
    if('html5' == $format) {
        $form = '<form method="get" class="search-form" action="' . esc_url(home_url('/')) . '">
				<label>
					<span class="screen-reader-text">' . _x('Search for:', 'label') . '</span>
					<input type="search" class="search-field" placeholder="' . esc_attr_x('Search &hellip;', 'placeholder') . '" value="' . get_search_query() . '" name="s" title="' . esc_attr_x('Search for:', 'label') . '" />
				</label>
				<input type="submit" class="search-submit" value="'. esc_attr_x('Search', 'submit button') .'" />
			</form>';
    } else {
        $form = '<form method="get" id="searchform" class="searchform" action="' . esc_url(home_url('/')) . '">
				<div>
					<label class="screen-reader-text" for="s">' . _x('Search for:', 'label') . '</label>
					<input type="text" value="' . get_search_query() . '" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="'. esc_attr_x('Search', 'submit button') .'" />
				</div>
			</form>';
    }
    return $form;
}
add_filter('get_search_form', 'fansub_setup_theme_get_search_form');

function fansub_setup_theme_wpseo_breadcrumb_separator($separator) {
    if(!fansub_string_contain($separator, '</')) {
        $separator = '<span class="sep separator">' . $separator . '</span>';
    }
    return $separator;
}
add_filter('wpseo_breadcrumb_separator', 'fansub_setup_theme_wpseo_breadcrumb_separator');

function fansub_setup_theme_wpseo_breadcrumb_links($crumbs) {
    $options = get_option('fansub_reading');
    $disable_post_title = fansub_get_value_by_key($options, 'disable_post_title_breadcrumb');
    $disable_post_title = apply_filters('fansub_disable_post_title_breadcrumb', $disable_post_title);
    if((bool)$disable_post_title) {
        if(fansub_array_has_value($crumbs)) {
            array_pop($crumbs);
        }
    }
    return $crumbs;
}
add_filter('wpseo_breadcrumb_links', 'fansub_setup_theme_wpseo_breadcrumb_links');

function fansub_setup_theme_wpseo_breadcrumb_single_link($output, $crumbs) {
    $options = get_option('fansub_reading');
    $link_last_item = fansub_get_value_by_key($options, 'link_last_item_breadcrumb');
    $link_last_item = apply_filters('fansub_link_last_item_breadcrumb', $link_last_item);
    if((bool)$link_last_item) {
        if(fansub_array_has_value($crumbs)) {
            if(strpos($output, '<span class="breadcrumb_last"') !== false || strpos($output, '<strong class="breadcrumb_last"') !== false) {
                $output = '<a class="breadcrumb_last" property="v:title" rel="v:url" href="'. $crumbs['url']. '">';
                $output .= $crumbs['text'];
                $output .= '</a></span>';
            }
        }
    }
    return $output;
}
add_filter('wpseo_breadcrumb_single_link', 'fansub_setup_theme_wpseo_breadcrumb_single_link' , 10, 2);

function fansub_setup_theme_get_comment_author($author, $comment_id, $comment) {
    if(!is_admin()) {
        if(!is_email($author)) {
            $author = fansub_uppercase_first_char_words($author);
        }
    }
    return $author;
}
add_filter('get_comment_author', 'fansub_setup_theme_get_comment_author', 10, 3);

if($maintenance_mode && !fansub_maintenance_mode_exclude_condition()) {
    add_action('admin_notices', 'fansub_setup_theme_in_maintenance_mode_notice');
    add_action('init', 'fansub_maintenance_mode');
    add_action('fansub_maintenance_head', 'fansub_setup_theme_maintenance_head');
    add_action('fansub_maintenance', 'fansub_setup_theme_maintenance');
    add_action('wp_enqueue_scripts', 'fansub_setup_theme_maintenance_scripts');
    add_filter('body_class', 'fansub_setup_theme_maintenance_body_class');
}

function fansub_setup_theme_allow_shortcode_in_comment() {
    $options = get_option('fansub_discussion');
    $allow_shortcode = fansub_get_value_by_key($options, 'allow_shortcode');
    if((bool)$allow_shortcode) {
        add_filter('comment_text', 'do_shortcode');
    }
}
add_action('fansub_front_end_init', 'fansub_setup_theme_allow_shortcode_in_comment');

function fansub_setup_theme_custom_head() {
    $options = get_option('fansub_theme_custom');
    $background_image = fansub_get_value_by_key($options, 'background_image');
    $background_image = fansub_get_media_option_url($background_image);
    if(fansub_url_valid($background_image)) {
        $style = new FANSUB_HTML('style');
        $style->set_attribute('type', 'text/css');
        $elements = array('body.hocwp');
        $properties = array(
            'background-image' => 'url("' . $background_image . '")',
            'background-repeat' => 'no-repeat',
            'background-color' => 'rgba(0,0,0,0)'
        );
        $background_repeat = fansub_get_value_by_key($options, 'background_repeat');
        if((bool)$background_repeat) {
            $properties['background-repeat'] = 'repeat';
        }
        $background_color = fansub_get_value_by_key($options, 'background_color');
        if(fansub_color_valid($background_color)) {
            $properties['background-color'] = $background_color;
        }
        $background_size = fansub_get_value_by_key($options, 'background_size');
        if(!empty($background_size)) {
            $properties['background-size'] = $background_size;
        }
        $background_position = fansub_get_value_by_key($options, 'background_position');
        if(!empty($background_position)) {
            $properties['background-position'] = $background_position;
        }
        $background_attachment = fansub_get_value_by_key($options, 'background_attachment');
        if(!empty($background_attachment)) {
            $properties['background-attachment'] = $background_attachment;
        }
        $css = fansub_build_css_rule($elements, $properties);
        $css = fansub_minify_css($css);
        $style->set_text($css);
        if(!empty($css)) {
            $style->output();
        }
    }
}
add_action('wp_head', 'fansub_setup_theme_custom_head');

function fansub_setup_theme_custom_footer() {
    if(!wp_is_mobile()) {
        $options = get_option('fansub_theme_custom');
        $background_music = fansub_get_value_by_key($options, 'background_music');
        if(!empty($background_music)) {
            $play_on = fansub_get_value_by_key($options, 'play_on');
            if(empty($play_on)) {
                $defaults = fansub_option_defaults();
                $play_on = fansub_get_value_by_key($defaults, array('theme_custom', 'background_music', 'play_on'));
            }
            $play = false;
            if('home' == $play_on && is_home()) {
                $play = true;
            } elseif('single' == $play_on && is_single()) {
                $play = true;
            } elseif('page' == $play_on && is_page()) {
                $play = true;
            } elseif('archive' == $play_on && is_archive()) {
                $play = true;
            } elseif('search' == $play_on && is_search()) {
                $play = true;
            } elseif('all' == $play_on) {
                $play = true;
            }
            $play = apply_filters('fansub_play_background_music', $play);
            if((bool)$play) {
                $div = new FANSUB_HTML('div');
                $div->set_class('fansub-background-music fansub-hidden');
                if(fansub_url_valid($background_music)) {

                }
                $div->set_text($background_music);
                $div->output();
            }
        }
    }
}
add_action('wp_footer', 'fansub_setup_theme_custom_footer');