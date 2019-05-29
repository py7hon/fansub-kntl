<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'themes.php';

$option = new FANSUB_Option(__('Add to head', 'fansub'), 'fansub_theme_add_to_head');
$option->set_parent_slug($parent_slug);
$option->add_field(array('id' => 'code', 'title' => __('Code', 'fansub'), 'class' => 'widefat', 'row' => 30, 'field_callback' => 'fansub_field_textarea'));

$option->init();
fansub_option_add_object_to_list($option);