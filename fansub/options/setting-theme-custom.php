<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'themes.php';
$defaults = fansub_option_defaults();

$option = new fansub_Option(__('Theme Custom', 'fansub'), 'fansub_theme_custom');
$options = $option->get();
$option->set_parent_slug($parent_slug);
$option->add_section(array('id' => 'music', 'title' => __('Music', 'fansub'), 'description' => __('Play music on your site as background music.', 'fansub')));
$option->add_field(array('id' => 'background_music', 'title' => __('Embed Code', 'fansub'), 'class' => 'widefat', 'row' => 3, 'field_callback' => 'fansub_field_textarea', 'section' => 'music'));
$lists = fansub_get_value_by_key($defaults, array('theme_custom', 'background_music', 'play_ons'));
$play_on = fansub_get_value_by_key($defaults, array('theme_custom', 'background_music', 'play_on'));
$all_option = '';
$value = fansub_get_value_by_key($options, 'play_on');
if(empty($value)) {
    $value = $play_on;
}
foreach($lists as $key => $item) {
    $tmp_option = fansub_field_get_option(array('value' => $key, 'text' => $item, 'selected' => $value));
    $all_option .= $tmp_option;
}
$option->add_field(array('id' => 'play_on', 'title' => __('Play On', 'fansub'), 'field_callback' => 'fansub_field_select', 'section' => 'music', 'all_option' => $all_option, 'default' => $play_on));
$option->add_section(array('id' => 'background', 'title' => __('Background', 'fansub'), 'description' => __('Custom background of your site.', 'fansub')));
$option->add_field(array('id' => 'background_image', 'title' => __('Image', 'fansub'), 'field_callback' => 'fansub_field_media_upload', 'section' => 'background'));
$option->add_field(array('id' => 'background_size', 'title' => __('Size', 'fansub'), 'section' => 'background'));
$option->add_field(array('id' => 'background_repeat', 'title' => __('Repeat', 'fansub'), 'label' => __('Check here if you want background to be repeated.', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'section' => 'background'));
$option->add_field(array('id' => 'background_position', 'title' => __('Position', 'fansub'), 'section' => 'background'));
$option->add_field(array('id' => 'background_color', 'title' => __('Color', 'fansub'), 'field_callback' => 'fansub_field_color_picker', 'section' => 'background'));
$option->add_field(array('id' => 'background_attachment', 'title' => __('Attachment', 'fansub'), 'section' => 'background'));
$option->set_use_color_picker(true);
$option->set_use_media_upload(true);
$option->set_use_style_and_script(true);
$option->init();
fansub_option_add_object_to_list($option);