<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'tools.php';

$defaults = fansub_maintenance_mode_default_settings();

$option = new FANSUB_Option(__('Maintenance', 'fansub'), 'fansub_maintenance');
$option->set_parent_slug($parent_slug);
$option->set_use_media_upload(true);
$option->set_use_style_and_script(true);
$option->add_field(array('id' => 'enabled', 'title' => __('Enable', 'fansub'), 'label' => __('Put your WordPress site in maintenance mode.', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox'));

$option->add_section(array('id' => 'front_end', 'title' => __('Front-end', 'fansub'), 'description' => __('All settings to display on front-end.', 'fansub')));
$option->add_field(array('id' => 'background', 'title' => __('Background', 'fansub'), 'field_callback' => 'fansub_field_media_upload', 'section' => 'front_end'));
$option->add_field(array('id' => 'heading', 'title' => __('Heading', 'fansub'), 'default' => fansub_get_value_by_key($defaults, 'heading'), 'section' => 'front_end'));
$option->add_field(array('id' => 'text', 'title' => __('Text', 'fansub'), 'default' => fansub_get_value_by_key($defaults, 'text'), 'field_callback' => 'fansub_field_rich_editor', 'section' => 'front_end'));
$option->init();
fansub_option_add_object_to_list($option);