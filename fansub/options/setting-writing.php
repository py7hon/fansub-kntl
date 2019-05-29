<?php
if(!function_exists('add_filter')) exit;
$writing_option = new FANSUB_Option('', 'writing');
$writing_option->set_page('options-writing.php');
$writing_option->add_field(array('id' => 'default_post_thumbnail', 'title' => __('Default post thumbnail', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
$writing_option->init();
fansub_option_add_object_to_list($writing_option);