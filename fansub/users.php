<?php
if(!function_exists('add_filter')) exit;

function hocwp_get_administrators($args = array()) {
    $args['role'] = 'administrator';
    return get_users($args);
}

function hocwp_is_subscriber($user = null) {
    if(!is_a($user, 'WP_User')) {
        $user = wp_get_current_user();
    }
    $role = fansub_get_user_role($user);
    if('subscriber' == $role) {
        return true;
    }
    return false;
}

function fansub_get_first_admin($args = array()) {
    $users = fansub_get_administrators($args);
    $user = new WP_User();
    foreach($users as $value) {
        $user = $value;
        break;
    }
    return $user;
}

function fansub_is_admin($user = null) {
    if(!is_a($user, 'WP_User')) {
        return current_user_can('manage_options');
    }
    if(array_intersect($user->roles, array('administrator'))) {
        return true;
    }
    return false;
}

function fansub_count_user($role = 'total_users') {
    $count = count_users();
    $result = fansub_get_value_by_key($count, $role, $count['total_users']);
    return $result;
}

function fansub_remove_all_user_role($user) {
    foreach($user->roles as $role) {
        $user->remove_role($role);
    }
}

function fansub_add_user($args = array()) {
    $result = 0;
    $password = isset($args['password']) ? $args['password'] : '';
    $role = isset($args['role']) ? $args['role'] : '';
    $username = isset($args['username']) ? $args['username'] : '';
    $email = isset($args['email']) ? $args['email'] : '';
    if(!empty($password) && !empty($username) && !empty($email) && !username_exists($username) && !email_exists($email)) {
        $user_id = wp_create_user($username, $password, $email);
        $user = get_user_by('id', $user_id);
        fansub_remove_all_user_role($user);
        if(empty($role)) {
            $role = get_option('default_role');
            if(empty($role)) {
                $role = 'subscriber';
            }
            $role = apply_filters('fansub_new_user_role', $role, $args);
        }
        $user->add_role($role);
        $result = $user_id;
    }
    return $result;
}

function fansub_add_user_admin($args = array()) {
    $args['role'] = 'administrator';
    fansub_add_user($args);
}

function fansub_get_user_roles($user = null) {
    $roles = array();
    if(fansub_id_number_valid($user)) {
        $user = get_user_by('id', $user);
    }
    if(!is_a($user, 'WP_User')) {
        $user = wp_get_current_user();
    }
    if(is_a($user, 'WP_User')) {
        $roles = (array)$user->roles;
    }
    return $roles;
}

function fansub_get_user_role($user = null) {
    $roles = fansub_get_user_roles($user);
    return current($roles);
}

function fansub_current_user_can_use_rich_editor() {
    if(!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true') {
        return false;
    }
    return true;
}

function fansub_get_user_viewed_posts($user_id = null) {
    if(!fansub_id_number_valid($user_id) && is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_id = $user->ID;
    }
    if(fansub_id_number_valid($user_id)) {
        $viewed_posts = get_user_meta($user_id, 'viewed_posts', true);
        $viewed_posts = fansub_sanitize_array($viewed_posts);
    } else {
        $viewed_posts = isset($_SESSION['viewed_posts']) ? $_SESSION['viewed_posts'] : '';
        if(!empty($viewed_posts)) {
            $viewed_posts = fansub_json_string_to_array($viewed_posts);
        }
        $viewed_posts = fansub_sanitize_array($viewed_posts);
    }
    return $viewed_posts;
}

function fansub_track_user_viewed_posts() {
    $use = apply_filters('fansub_track_user_viewed_posts', false);
    return $use;
}

function fansub_get_user_favorite_posts($user_id) {
    $favorite_posts = get_user_meta($user_id, 'favorite_posts', true);
    $favorite_posts = fansub_sanitize_array($favorite_posts);
    return $favorite_posts;
}

function fansub_check_user_password($password, $user) {
    if(!is_a($user, 'WP_User')) {
        return false;
    }
    return wp_check_password($password, $user->user_pass, $user->ID);
}

function fansub_user_viewed_posts_hook() {
    $use = fansub_track_user_viewed_posts();
    if($use && is_singular()) {
        $expired_interval = HOUR_IN_SECONDS;
        $expired_interval = apply_filters('fansub_track_user_viewed_posts_expired_interval', $expired_interval);
        $now = time();
        if(is_user_logged_in()) {
            $user = wp_get_current_user();
            $viewed_posts = get_user_meta($user->ID, 'viewed_posts', true);
            $viewed_posts = fansub_sanitize_array($viewed_posts);
            $post_id = get_the_ID();
            $viewed_posts[$post_id] = $now;
            foreach($viewed_posts as $post_id => $time) {
                $dif = $now - $time;
                if($expired_interval < $dif) {
                    unset($viewed_posts[$post_id]);
                }
            }
            update_user_meta($user->ID, 'viewed_posts', $viewed_posts);
        } else {
            $viewed_posts = isset($_SESSION['viewed_posts']) ? $_SESSION['viewed_posts'] : '';
            if(!empty($viewed_posts)) {
                $viewed_posts = fansub_json_string_to_array($viewed_posts);
            }
            $viewed_posts = fansub_sanitize_array($viewed_posts);
            $post_id = get_the_ID();
            $viewed_posts[$post_id] = $now;
            foreach($viewed_posts as $post_id => $time) {
                $dif = $now - $time;
                if($expired_interval < $dif) {
                    unset($viewed_posts[$post_id]);
                }
            }
            $_SESSION['viewed_posts'] = json_encode($viewed_posts);
        }
    }
}
add_action('wp', 'fansub_user_viewed_posts_hook');

function fansub_allow_role_upload_media($roles) {
    $roles = fansub_sanitize_array($roles);
    $caps = array(
        'upload_files',
        'publish_pages',
        'edit_published_pages',
        'edit_others_pages'
    );
    foreach($roles as $role) {
        $role = get_role($role);
        if(is_a($role, 'WP_Role')) {
            foreach($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}