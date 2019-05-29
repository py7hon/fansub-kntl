<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'options-general.php';

$option = new FANSUB_Option(__('Optimize', 'fansub'), 'fansub_optimize');
$option->set_parent_slug($parent_slug);
$option->add_field(array('id' => 'use_jquery_cdn', 'title' => __('jQuery CDN', 'fansub'), 'label' => __('Load jQuery from Google CDN server.', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'default' => 1));

$option->init();
fansub_option_add_object_to_list($option);