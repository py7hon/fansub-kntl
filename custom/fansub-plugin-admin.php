<?php
if (!function_exists('add_filter')) exit;

if (!fansub_kntl_license_valid()) {
    return;
}

function fansub_ph_admin_option()
{
    global $fansub_pos_tabs;

    $defaults = fansub_kntl_get_option_defaults();

    $option = new FANSUB_Option('HorribleSubs', 'fansub_kntl');
    $option->set_parent_slug('fansub_plugin_option');
    $option->set_use_style_and_script(true);
    $option->set_use_media_upload(true);

    $options = $option->get();

    $option->add_field(array('id' => 'test_upload', 'title' => 'Test upload', 'field_callback' => 'fansub_field_media_upload'));
    $option->add_field(array('id' => 'single_page', 'title' => 'Single Page', 'default' => $defaults['single_page'], 'field_callback' => 'fansub_field_select_page'));
    $option->add_field(array('id' => 'posts_per_page', 'title' => 'Post Number', 'default' => $defaults['posts_per_page'], 'field_callback' => 'fansub_field_input_number'));
    $option->add_field(array('id' => 'date_format', 'title' => 'Date Format', 'default' => $defaults['date_format']));
    $option->add_field(array('id' => 'loading_text', 'title' => 'Loading Text', 'default' => $defaults['loading_text']));
    $option->add_field(array('id' => 'search_none_text', 'title' => 'No Result Text', 'default' => $defaults['search_none_text']));
    $option->add_field(array('id' => 'show_more_text', 'title' => 'Show More Text', 'default' => $defaults['show_more_text']));
    $option->add_field(array('id' => 'reached_end_text', 'title' => 'Reached End Text', 'default' => $defaults['reached_end_text']));
    $option->add_field(array('id' => 'clear_text', 'title' => 'Clear Text', 'default' => $defaults['clear_text']));
    $option->add_field(array('id' => 'refresh_text', 'title' => 'Refresh Text', 'default' => $defaults['refresh_text']));
    $option->add_field(array('id' => 'qualities', 'title' => 'Qualities', 'default' => $defaults['qualities'], 'description' => 'Separate qualities with commas.'));
    $option->add_field(array('id' => 'servers', 'title' => 'Servers', 'default' => $defaults['servers'], 'description' => 'Separate servers with commas.'));

    $option->add_section(array('id' => 'post_type', 'title' => 'Post Type', 'description' => 'Post type informations, if all fields are empty, the default post type will be used.'));
    $option->add_field(array('id' => 'post_type_name', 'title' => 'Name', 'default' => $defaults['post_type_name'], 'section' => 'post_type'));
    $option->add_field(array('id' => 'post_type_label_name', 'title' => 'Label Name', 'default' => $defaults['post_type_label_name'], 'section' => 'post_type'));
    $option->add_field(array('id' => 'post_type_label_singular_name', 'title' => 'Label Singular Name', 'default' => $defaults['post_type_label_singular_name'], 'section' => 'post_type'));

    $option->add_section(array('id' => 'release_box', 'title' => 'Release Box', 'description' => 'All settings for release box.'));
    $option->add_field(array('id' => 'release_box_title', 'title' => 'Title', 'default' => $defaults['release_box_title'], 'section' => 'release_box'));
    $option->add_field(array('id' => 'release_box_search_placeholder', 'title' => 'Search Placeholder', 'default' => $defaults['release_box_search_placeholder'], 'section' => 'release_box'));

    $option->add_section(array('id' => 'single_box', 'title' => 'Single Box', 'description' => 'All settings for post box on single page.'));
    $option->add_field(array('id' => 'single_batch_title', 'title' => 'Batch Title', 'default' => $defaults['single_batch_title'], 'section' => 'single_box'));
    $option->add_field(array('id' => 'single_batch_tip', 'title' => 'Batch Tips', 'default' => $defaults['single_batch_tip'], 'section' => 'single_box'));
    $option->add_field(array('id' => 'single_batch_none', 'title' => 'No Batch Text', 'default' => $defaults['single_batch_none'], 'section' => 'single_box'));
    $option->add_field(array('id' => 'single_search_placeholder', 'title' => 'Search Placeholder', 'default' => $defaults['single_search_placeholder'], 'section' => 'single_box'));
    $option->add_field(array('id' => 'single_episode_none', 'title' => 'No Episode Text', 'default' => $defaults['single_episode_none'], 'section' => 'single_box'));

    $option->add_section(array('id' => 'facebook', 'title' => 'Facebook', 'description' => 'Provide your information to connect with Facebook.'));

    $post_type = isset($options['fb_post_type']) ? $options['fb_post_type'] : '';
    $post_type = fansub_json_string_to_array($post_type);
    if (!fansub_array_has_value($post_type)) {
        $post_type = array();
    }
    $lists = get_post_types(array('_builtin' => false, 'public' => true), 'objects');

    if (!array_key_exists('post', $lists)) {
        $lists[] = get_post_type_object('post');
    }
    //$lists[] = get_post_type_object('episode');
    //$lists[] = get_post_type_object('batch');
    $all_option = '';
    foreach ($lists as $lvalue) {
        $selected = '';
        foreach ($post_type as $ptvalue) {
            $ptype = isset($ptvalue['value']) ? $ptvalue['value'] : '';
            if ($lvalue->name == $ptype) {
                $selected = $lvalue->name;
                break;
            }
        }
        $args = array(
            'value' => $lvalue->name,
            'text' => $lvalue->labels->singular_name,
            'selected' => $selected
        );
        $all_option .= fansub_field_get_option($args);
    }
    $args = array(
        'id' => 'fb_post_type',
        'name' => 'fb_post_type',
        'all_option' => $all_option,
        'value' => $post_type,
        'title' => 'Post type',
        'placeholder' => 'Choose post types',
        'multiple' => true,
        'field_callback' => 'fansub_field_select_chosen',
        'section' => 'facebook'
    );
    $option->add_field($args);

    $option->add_field(array('id' => 'fb_app_id', 'title' => 'App ID', 'section' => 'facebook'));
    $option->add_field(array('id' => 'fb_app_secret', 'title' => 'App Secret', 'section' => 'facebook'));
    $option->add_field(array('id' => 'fb_redirect_uri', 'title' => 'Redirect Uri', 'section' => 'facebook'));
    $option->add_field(array('id' => 'fb_access_token', 'title' => 'Access Token', 'section' => 'facebook'));

    $option->add_option_tab($fansub_pos_tabs);
    $option->set_page_header_callback('fansub_plugin_option_page_header');
    $option->set_page_footer_callback('fansub_plugin_option_page_footer');
    $option->set_page_sidebar_callback('fansub_plugin_option_page_sidebar');
    $option->init();

    fansub_option_add_object_to_list($option);
}

add_action('init', 'fansub_ph_admin_option');