<?php
if(!function_exists('add_filter')) exit;
function fansub_theme_switcher_enabled() {
    return apply_filters('fansub_theme_switcher_enabled', defined('FANSUB_THEME_SWITCHER_VERSION'));
}

function fansub_theme_switcher_default_mobile_theme_name() {
    $name = fansub_option_get_value('theme_switcher', 'mobile_theme');
    return $name;
}

function fansub_theme_switcher_get_mobile_theme_name() {
    $name = fansub_theme_switcher_default_mobile_theme_name();
    return $name;
}

function fansub_theme_switcher_control($name) {
    $mobile_theme = fansub_theme_switcher_get_mobile_theme_name();
    if(!empty($mobile_theme)) {
        $name = $mobile_theme;
    }
    return $name;
}