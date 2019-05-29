<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'themes.php';

$option_theme_setting = new FANSUB_Option(__('Theme settings', 'fansub'), 'fansub_theme_setting');
$option_theme_setting->set_parent_slug($parent_slug);
$option_theme_setting->set_use_style_and_script(true);
$option_theme_setting->set_use_media_upload(true);
$option_theme_setting->add_field(array('id' => 'language', 'title' => __('Language', 'fansub'), 'field_callback' => 'fansub_field_select_language'));
$option_theme_setting->add_field(array('id' => 'favicon', 'title' => __('Favicon', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
$option_theme_setting->add_field(array('id' => 'logo', 'title' => __('Logo', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
$option_theme_setting->init();
fansub_option_add_object_to_list($option_theme_setting);