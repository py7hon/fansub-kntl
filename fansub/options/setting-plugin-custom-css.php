<?php
if(!function_exists('add_filter')) exit;

global $fansub_pos_tabs;
$parent_slug = 'fansub_plugin_option';

$option = new FANSUB_Option(__('Custom CSS', 'fansub'), 'fansub_plugin_custom_css');
$option->set_parent_slug($parent_slug);
$option->add_field(array('id' => 'code', 'title' => __('Custom Style Sheet', 'fansub'), 'class' => 'widefat', 'row' => 30, 'field_callback' => 'fansub_field_textarea'));
$option->add_option_tab($fansub_pos_tabs);
$option->set_page_header_callback('fansub_plugin_option_page_header');
$option->set_page_footer_callback('fansub_plugin_option_page_footer');
$option->set_page_sidebar_callback('fansub_plugin_option_page_sidebar');
$option->init();
fansub_option_add_object_to_list($option);