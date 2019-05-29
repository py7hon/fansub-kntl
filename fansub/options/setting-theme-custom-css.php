<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'themes.php';

$theme = wp_get_theme();
$template = fansub_sanitize_id($theme->get_template());

$option = new FANSUB_Option(__('Custom CSS', 'fansub'), 'fansub_theme_custom_css');
$option->set_parent_slug($parent_slug);
$option->add_field(array('id' => $template, 'title' => $theme->get('Name') . ' ' . __('Custom CSS', 'fansub'), 'class' => 'widefat', 'row' => 30, 'field_callback' => 'fansub_field_textarea'));

$option->init();
fansub_option_add_object_to_list($option);