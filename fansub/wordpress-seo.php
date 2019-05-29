<?php
if(!function_exists('add_filter')) exit;
function fansub_get_wpseo_social() {
    return get_option('wpseo_social');
}

function fansub_get_wpseo_social_value($key) {
    $social = fansub_get_wpseo_social();
    return fansub_get_value_by_key($social, $key);
}

function fansub_get_wpseo_social_facebook_admin() {
    return fansub_get_wpseo_social_value('fb_admins');
}

function fansub_get_wpseo_social_facebook_app_id() {
    return fansub_get_wpseo_social_value('fbadminapp');
}

function fansub_wpseo_installed() {
    return defined('WPSEO_FILE');
}

function fansub_update_wpseo_social($key, $value) {
    $social = fansub_get_wpseo_social();
    $social[$key] = $value;
    update_option('wpseo_social', $social);
}

function fansub_wpseo_get_internallinks() {
    return get_option('wpseo_internallinks');
}

function fansub_wpseo_breadcrumb_enabled() {
    $option = fansub_wpseo_get_internallinks();
    $value = fansub_get_value_by_key($option, 'breadcrumbs-enable');
    return (bool)$value;
}

function fansub_wpseo_get_post_title($post_id) {
    $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
    if(empty($title)) {
        $title = get_the_title($post_id);
    }
    return $title;
}

function fansub_wpseo_internallinks() {
    return get_option('wpseo_internallinks');
}

function fansub_update_wpseo_internallinks($wpseo_internallinks) {
    update_option('wpseo_internallinks', $wpseo_internallinks);
}

function fansub_update_wpseo_internallink($key, $value) {
    $wpseo_internallinks = fansub_wpseo_internallinks();
    $wpseo_internallinks[$key] = $value;
    fansub_update_wpseo_internallinks($wpseo_internallinks);
}

function fansub_wpseo_internallink_value($key) {
    $wpseo_internallinks = fansub_wpseo_internallinks();
    return fansub_get_value_by_key($wpseo_internallinks, $key);
}