<?php
if(!function_exists('add_filter')) exit;

global $pagenow;

$parent_slug = 'tools.php';

$option = new FANSUB_Option(__('Developers', 'fansub'), 'fansub_developers');
$option->set_parent_slug($parent_slug);
$option->disable_sidebar();

$option->add_field(array('id' => 'compress_css', 'title' => __('Compress CSS', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Compress all style in current theme or plugins?', 'fansub'), 'default' => 1));
$option->add_field(array('id' => 'compress_js', 'title' => __('Compress Javascript', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Compress all javascript in current theme or plugins?', 'fansub'), 'default' => 1));
$option->add_field(array('id' => 're_compress', 'title' => __('Recompress', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Check here if you want to recompress all minified files?', 'fansub')));
$option->add_field(array('id' => 'force_compress', 'title' => __('Force Compress', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Disable compress cache each 15 minutes?', 'fansub')));
$option->add_field(array('id' => 'compress_css_js', 'field_callback' => 'fansub_field_button', 'value' => __('Compress CSS and Javascript', 'fansub')));

if(FANSUB_DEVELOPING && fansub_is_localhost()) {
	$option->init();
}

fansub_option_add_object_to_list($option);