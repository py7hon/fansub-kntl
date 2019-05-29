<?php
if(!function_exists('add_filter')) exit;

function fansub_debug_log_ajax_callback() {
    $object = fansub_get_method_value('object');
    $object = fansub_json_string_to_array($object);
    fansub_debug_log($object);
    exit;
}
add_action('wp_ajax_fansub_debug_log', 'fansub_debug_log_ajax_callback');
add_action('wp_ajax_nopriv_fansub_debug_log', 'fansub_debug_log_ajax_callback');

function fansub_comment_likes_ajax_callback() {
    $result = array();
    $likes = isset($_POST['likes']) ? absint($_POST['likes']) : 0;
    $comment_id = isset($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;
    $likes++;
    update_comment_meta($comment_id, 'likes', $likes);
    $result['likes'] = fansub_number_format($likes);
    $_SESSION['comment_' . $comment_id . '_likes'] = 1;
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_comment_likes', 'fansub_comment_likes_ajax_callback');
add_action('wp_ajax_nopriv_fansub_comment_likes', 'fansub_comment_likes_ajax_callback');

function fansub_comment_report_ajax_callback() {
    $result = array();
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_comment_report', 'fansub_comment_report_ajax_callback');
add_action('wp_ajax_nopriv_fansub_comment_report', 'fansub_comment_report_ajax_callback');

function fansub_fetch_plugin_license_ajax_callback() {
    $result = array(
        'customer_email' => '',
        'license_code' => ''
    );
    $use_for = isset($_POST['use_for']) ? $_POST['use_for'] : '';
    if(!empty($use_for)) {
        $use_for_key = md5($use_for);
        $option = get_option('fansub_plugin_licenses');
        $customer_email = fansub_get_value_by_key($option, array($use_for_key, 'customer_email'));
        if(is_array($customer_email) || !is_email($customer_email)) {
            $customer_email = '';
        }
        $license_code = fansub_get_value_by_key($option, array($use_for_key, 'license_code'));
        if(is_array($license_code) || strlen($license_code) < 5) {
            $license_code = '';
        }
        $result['customer_email'] = $customer_email;
        $result['license_code'] = $license_code;
        update_option('test', $result);
    }
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_fetch_plugin_license', 'fansub_fetch_plugin_license_ajax_callback');

function fansub_change_captcha_image_ajax_callback() {
    $result = array(
        'success' => false
    );
    $captcha = new FANSUB_Captcha();
    $url = $captcha->generate_image();
    if(!empty($url)) {
        $result['success'] = true;
        $result['captcha_image_url'] = $url;
    } else {
        $result['message'] = __('Sorry, cannot generate captcha image, please try again or contact administrator!', 'fansub');
    }
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_change_captcha_image', 'fansub_change_captcha_image_ajax_callback');
add_action('wp_ajax_nopriv_fansub_change_captcha_image', 'fansub_change_captcha_image_ajax_callback');

function fansub_vote_post_ajax_callback() {
    $result = array(
        'success' => false
    );
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
    $post_id = absint($post_id);
    if($post_id > 0) {
        $type = isset($_POST['type']) ? $_POST['type'] : fansub_get_method_value('vote_type');
        $session_name = 'fansub_vote_' . $type . '_post_' . $post_id;
        if(!isset($_SESSION[$session_name]) || 1 != $_SESSION[$session_name]) {
            $value = isset($_POST['value']) ? $_POST['value'] : '';
            $value = absint($value);
            $value++;
            if('up' == $type || 'like' == $type) {
                update_post_meta($post_id, 'likes', $value);
            } elseif('down' == $type || 'dislike' == $type) {
                update_post_meta($post_id, 'dislikes', $value);
            }
            $result['value'] = $value;
            $result['type'] = $type;
            $result['post_id'] = $post_id;
            $result['value_html'] = number_format($value);
            $_SESSION[$session_name] = 1;
            $result['success'] = true;
        }
    }
    echo json_encode($result);
    die();
}
add_action('wp_ajax_fansub_vote_post', 'fansub_vote_post_ajax_callback');
add_action('wp_ajax_nopriv_fansub_vote_post', 'fansub_vote_post_ajax_callback');

function fansub_favorite_post_ajax_callback() {
    $result = array(
        'html_data' => '',
        'success' => false,
        'remove' => false
    );
    $post_id = fansub_get_method_value('post_id');
    if(fansub_id_number_valid($post_id) && is_user_logged_in()) {
        $user = wp_get_current_user();
        $favorites = get_user_meta($user->ID, 'favorite_posts', true);
        if(!is_array($favorites)) {
            $favorites = array();
        }
        if(!in_array($post_id, $favorites)) {
            $favorites[] = $post_id;
        } else {
            unset($favorites[array_search($post_id, $favorites)]);
            $result['remove'] = true;
        }
        $updated = update_user_meta($user->ID, 'favorite_posts', $favorites);
        if($updated) {
            $result['success'] = true;
            if($result['remove']) {
                $result['html_data'] = '<i class="fa fa-heart-o"></i> Lưu tin';
            } else {
                $result['html_data'] = '<i class="fa fa-heart"></i> Bỏ lưu';
            }
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_favorite_post', 'fansub_favorite_post_ajax_callback');
add_action('wp_ajax_nopriv_fansub_favorite_post', 'fansub_favorite_post_ajax_callback');

function fansub_sanitize_media_value_ajax_callback() {
    $id = isset($_POST['id']) ? $_POST['id'] : 0;
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $result = array('id' => $id, 'url' => $url);
    $result = fansub_sanitize_media_value($result);
    echo json_encode($result);
    exit;
}
add_action('wp_ajax_fansub_sanitize_media_value', 'fansub_sanitize_media_value_ajax_callback');
add_action('wp_ajax_nopriv_fansub_sanitize_media_value', 'fansub_sanitize_media_value_ajax_callback');

function fansub_fetch_administrative_boundaries_ajax_callback() {
    $result = array();
    $default = fansub_get_method_value('default');
    $default = str_replace('\\', '', $default);
    //$type = fansub_get_method_value('type');
    if(empty($default)) {

    }
    $html_data = $default;
    $parent = fansub_get_method_value('parent');
    if(fansub_id_number_valid($parent)) {
        $taxonomy = fansub_get_method_value('taxonomy');
        if(!empty($taxonomy)) {
            $terms = fansub_get_terms($taxonomy, array('parent' => $parent, 'orderby' => 'NAME'));
            if(fansub_array_has_value($terms)) {
                foreach($terms as $term) {
                    $option = fansub_field_get_option(array('value' => $term->term_id, 'text' => $term->name));
                    $html_data .= $option;
                }
            }
        }
    }
    $result['html_data'] = $html_data;
    wp_send_json($result);
}
add_action('wp_ajax_fansub_fetch_administrative_boundaries', 'fansub_fetch_administrative_boundaries_ajax_callback');
add_action('wp_ajax_nopriv_fansub_fetch_administrative_boundaries', 'fansub_fetch_administrative_boundaries_ajax_callback');

function fansub_get_term_ajax_callback() {
    $term_id = fansub_get_method_value('term_id');
    $result = array(
        'term' => new WP_Error()
    );
    if(fansub_id_number_valid($term_id)) {
        $taxonomy = fansub_get_method_value('taxonomy');
        if(!empty($taxonomy)) {
            $result['term'] = get_term($term_id, $taxonomy);
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_get_term', 'fansub_get_term_ajax_callback');
add_action('wp_ajax_nopriv_fansub_get_term', 'fansub_get_term_ajax_callback');

function fansub_get_term_administrative_boundaries_address_ajax_callback() {
    $result = array(
        'address' => ''
    );
    $term_id = fansub_get_method_value('term_id');
    if(fansub_id_number_valid($term_id)) {
        $taxonomy = fansub_get_method_value('taxonomy');
        if(!empty($taxonomy)) {
            $term = get_term($term_id, $taxonomy);
            $address = $term->name;
            while($term->parent > 0) {
                $address .= ', ';
                $term = get_term($term->parent, $taxonomy);
                $address .= $term->name;
            }
            $address = rtrim($address, ', ');
            $result['address'] = $address;
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_get_term_administrative_boundaries_address', 'fansub_get_term_administrative_boundaries_address_ajax_callback');
add_action('wp_ajax_nopriv_fansub_get_term_administrative_boundaries_address', 'fansub_get_term_administrative_boundaries_address_ajax_callback');

function fansub_dashboard_widget_ajax_callback() {
    $result = array(
        'html_data' => ''
    );
    $widget = fansub_get_method_value('widget');
    if(!empty($widget)) {
        $widgets = explode('_', $widget);
        array_shift($widgets);
        $widget = implode('_', $widgets);
        $callback = 'fansub_theme_dashboard_widget_' . $widget;
        if(fansub_callback_exists($callback)) {
            ob_start();
            call_user_func($callback);
            $result['html_data'] = ob_get_clean();
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_dashboard_widget', 'fansub_dashboard_widget_ajax_callback');

function fansub_social_login_facebook_ajax_callback() {
    $result = array(
        'redirect_to' => '',
        'logged_in' => false
    );
    $data = fansub_get_method_value('data');
    $data = fansub_json_string_to_array($data);
    $connect = (bool)fansub_get_method_value('connect');
    if(fansub_array_has_value($data)) {
        $verified = (bool)fansub_get_value_by_key($data, 'verified');
        $allow_not_verified = apply_filters('fansub_allow_social_user_signup_not_verified', true);
        if($verified || $allow_not_verified) {
            $id = fansub_get_value_by_key($data, 'id');
            $requested_redirect_to = fansub_get_method_value('redirect_to');
            $redirect_to = home_url('/');
            $transient_name = 'fansub_social_login_facebook_' . md5($id);
            $user_id = get_transient($transient_name);
            $user = get_user_by('ID', $user_id);
            if($connect && is_user_logged_in()) {
                $user = wp_get_current_user();
                $user_id = $user->ID;
            }
            $find_users = get_users(array('meta_key' => 'facebook', 'meta_value' => $id));
            if(fansub_array_has_value($find_users)) {
                $user = $find_users[0];
                $user_id = $user->ID;
            }
            if(false === $user_id || !fansub_id_number_valid($user_id) || !is_a($user, 'WP_User') || $connect) {
                $avatar = fansub_get_value_by_key($data, array('picture', 'data', 'url'));
                if($connect) {
                    update_user_meta($user_id, 'facebook', $id);
                    update_user_meta($user_id, 'facebook_data', $data);
                    update_user_meta($user_id, 'avatar', $avatar);
                    $result['redirect_to'] = get_edit_profile_url($user_id);
                    $result['logged_in'] = true;
                } else {
                    $email = fansub_get_value_by_key($data, 'email');
                    if(is_email($email)) {
                        $name = fansub_get_value_by_key($data, 'name');
                        $first_name = fansub_get_value_by_key($data, 'first_name');
                        $last_name = fansub_get_value_by_key($data, 'last_name');

                        $password = wp_generate_password();
                        $user_id = null;
                        if(username_exists($email)) {
                            $user = get_user_by('login', $email);
                            $user_id = $user->ID;
                        } elseif(email_exists($email)) {
                            $user = get_user_by('email', $email);
                            $user_id = $user->ID;
                        }
                        $old_user = true;
                        if(!fansub_id_number_valid($user_id)) {
                            $user_data = array(
                                'username' => $email,
                                'email' => $email,
                                'password' => $password
                            );
                            $user_id = fansub_add_user($user_data);
                            if(fansub_id_number_valid($user_id)) {
                                $old_user = false;
                            }
                        }
                        if(fansub_id_number_valid($user_id)) {
                            $user = get_user_by('id', $user_id);
                            $redirect_to = apply_filters('login_redirect', $redirect_to, $requested_redirect_to, $user);
                            if(!$old_user) {
                                update_user_meta($user_id, 'facebook', $id);
                                $user_data = array(
                                    'ID' => $user_id,
                                    'display_name' => $name,
                                    'first_name' => $first_name,
                                    'last_name' => $last_name
                                );
                                wp_update_user($user_data);
                                update_user_meta($user_id, 'avatar', $avatar);
                                update_user_meta($user_id, 'facebook_data', $data);
                            }
                            fansub_user_force_login($user_id);
                            $result['redirect_to'] = $redirect_to;
                            $result['logged_in'] = true;
                            set_transient($transient_name, $user_id, DAY_IN_SECONDS);
                        }
                    }
                }
            } else {
                update_user_meta($user_id, 'facebook_data', $data);
                $user = get_user_by('id', $user_id);
                $redirect_to = apply_filters('login_redirect', $redirect_to, $requested_redirect_to, $user);
                fansub_user_force_login($user_id);
                $result['redirect_to'] = $redirect_to;
                $result['logged_in'] = true;
            }
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_social_login_facebook', 'fansub_social_login_facebook_ajax_callback');
add_action('wp_ajax_nopriv_fansub_social_login_facebook', 'fansub_social_login_facebook_ajax_callback');

function fansub_social_login_google_ajax_callback() {
    $result = array(
        'redirect_to' => '',
        'logged_in' => false
    );
    $data = fansub_get_method_value('data');
    $data = fansub_json_string_to_array($data);
    $connect = fansub_get_method_value('connect');
    if(fansub_array_has_value($data)) {
        $verified = (bool)fansub_get_value_by_key($data, 'verified');
        $allow_not_verified = apply_filters('fansub_allow_social_user_signup_not_verified', true);
        if($verified || $allow_not_verified) {
            $id = fansub_get_value_by_key($data, 'id');
            $requested_redirect_to = fansub_get_method_value('redirect_to');
            $redirect_to = home_url('/');
            $transient_name = 'fansub_social_login_google_' . md5($id);
            $user_id = get_transient($transient_name);
            $user = get_user_by('id', $user_id);
            if($connect && is_user_logged_in()) {
                $user = wp_get_current_user();
                $user_id = $user->ID;
            }
            $find_users = get_users(array('meta_key' => 'google', 'meta_value' => $id));
            if(fansub_array_has_value($find_users)) {
                $user = $find_users[0];
                $user_id = $user->ID;
            }
            if(false === $user_id || !fansub_id_number_valid($user_id) || !is_a($user, 'WP_User') || $connect) {
                $avatar = fansub_get_value_by_key($data, array('image', 'url'));
                if($connect) {
                    update_user_meta($user_id, 'google', $id);
                    update_user_meta($user_id, 'avatar', $avatar);
                    update_user_meta($user_id, 'google_data', $data);
                    $result['redirect_to'] = get_edit_profile_url($user_id);
                    $result['logged_in'] = true;
                } else {
                    $email = fansub_get_value_by_key($data, array('emails', 0, 'value'));
                    if(is_email($email)) {
                        $name = fansub_get_value_by_key($data, 'displayName');
                        $first_name = fansub_get_value_by_key($data, array('name', 'givenName'));
                        $last_name = fansub_get_value_by_key($data, array('name', 'familyName'));
                        $password = wp_generate_password();
                        $user_id = null;
                        if(username_exists($email)) {
                            $user = get_user_by('login', $email);
                            $user_id = $user->ID;
                        } elseif(email_exists($email)) {
                            $user = get_user_by('email', $email);
                            $user_id = $user->ID;
                        }
                        $old_user = true;
                        if(!fansub_id_number_valid($user_id)) {
                            $user_data = array(
                                'username' => $email,
                                'email' => $email,
                                'password' => $password
                            );
                            $user_id = fansub_add_user($user_data);
                            if(fansub_id_number_valid($user_id)) {
                                $old_user = false;
                            }
                        }
                        if(fansub_id_number_valid($user_id)) {
                            $user = get_user_by('id', $user_id);
                            $redirect_to = apply_filters('login_redirect', $redirect_to, $requested_redirect_to, $user);
                            if(!$old_user) {
                                update_user_meta($user_id, 'google', $id);
                                $user_data = array(
                                    'ID' => $user_id,
                                    'display_name' => $name,
                                    'first_name' => $first_name,
                                    'last_name' => $last_name
                                );
                                wp_update_user($user_data);
                                update_user_meta($user_id, 'avatar', $avatar);
                                update_user_meta($user_id, 'google_data', $data);
                            }
                            fansub_user_force_login($user_id);
                            $result['redirect_to'] = $redirect_to;
                            $result['logged_in'] = true;
                            set_transient($transient_name, $user_id, DAY_IN_SECONDS);
                        }
                    }
                }
            } else {
                update_user_meta($user_id, 'google_data', $data);
                $user = get_user_by('id', $user_id);
                $redirect_to = apply_filters('login_redirect', $redirect_to, $requested_redirect_to, $user);
                fansub_user_force_login($user_id);
                $result['redirect_to'] = $redirect_to;
                $result['logged_in'] = true;
            }
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_social_login_google', 'fansub_social_login_google_ajax_callback');
add_action('wp_ajax_nopriv_fansub_social_login_google', 'fansub_social_login_google_ajax_callback');

function fansub_disconnect_social_account_ajax_callback() {
    $social = fansub_get_method_value('social');
    $user_id = fansub_get_method_value('user_id');
    if(fansub_id_number_valid($user_id)) {
        switch($social) {
            case 'facebook':
                delete_user_meta($user_id, 'facebook');
                delete_user_meta($user_id, 'facebook_data');
                break;
            case 'google':
                delete_user_meta($user_id, 'google');
                delete_user_meta($user_id, 'google_data');
                break;
        }
    }
    exit;
}
add_action('wp_ajax_fansub_disconnect_social_account', 'fansub_disconnect_social_account_ajax_callback');

function fansub_compress_style_and_script_ajax_callback() {
    $result = array();
    $type = fansub_get_method_value('type');
    $type = fansub_json_string_to_array($type);
    $force_compress = fansub_get_method_value('force_compress');
    $args = array(
        'type' => $type,
        'force_compress' => $force_compress
    );
    fansub_compress_style_and_script($args);
    wp_send_json($result);
}
add_action('wp_ajax_fansub_compress_style_and_script', 'fansub_compress_style_and_script_ajax_callback');