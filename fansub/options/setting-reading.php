<?php
if(!function_exists('add_filter')) exit;
$reading_option = new FANSUB_Option('', 'reading');
$reading_option->set_page('options-reading.php');
$reading_option->add_field(array('id' => 'post_statistics', 'title' => __('Post Statistics', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Track post views on your site.', 'fansub')));
$reading_option->add_section(array('id' => 'breadcrumb', 'title' => __('Breadcrumb', 'fansub'), 'description' => __('Custom breadcrumb on your site.', 'fansub')));
$reading_option->add_field(array('id' => 'breadcrumb_label', 'title' => __('Breadcrumb Label', 'fansub'), 'value' => fansub_wpseo_internallink_value('breadcrumbs-prefix'), 'section' => 'breadcrumb'));
$reading_option->add_field(array('id' => 'disable_post_title_breadcrumb', 'title' => __('Disable Post Title', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Prevent post title to be shown on last item.', 'fansub'), 'section' => 'breadcrumb'));
$reading_option->add_field(array('id' => 'link_last_item_breadcrumb', 'title' => __('Link Last Item', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Add link to last item instead of text.', 'fansub'), 'section' => 'breadcrumb'));
$reading_option->add_section(array('id' => 'scroll_top_section', 'title' => __('Scroll To Top', 'fansub'), 'description' => __('This option can help you to display scroll to top button on your site.', 'fansub')));
$reading_option->add_field(array('id' => 'go_to_top', 'title' => __('Scroll Top Button', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'label' => __('Display scroll top to top button on bottom right of site.', 'fansub'), 'section' => 'scroll_top_section'));
$reading_option->add_field(array('id' => 'scroll_top_icon', 'title' => __('Button Icon', 'fansub'), 'field_callback' => 'fansub_field_media_upload', 'section' => 'scroll_top_section'));
$reading_option->init();
fansub_option_add_object_to_list($reading_option);

function fansub_option_reading_update($input) {
    $breadcrumb_label = fansub_get_value_by_key($input, 'breadcrumb_label');
    if(!empty($breadcrumb_label)) {
        $breadcrumb_label = fansub_remove_last_char($breadcrumb_label, ':');
        $breadcrumb_label .= ':';
        $wpseo_internallinks = get_option('wpseo_internallinks');
        $wpseo_internallinks['breadcrumbs-prefix'] = $breadcrumb_label;
        update_option('wpseo_internallinks', $wpseo_internallinks);
    }
}
add_action('fansub_sanitize_' . $reading_option->get_option_name_no_prefix() . '_option', 'fansub_option_reading_update');