<?php
if (!function_exists('add_filter')) exit;

if (!fansub_kntl_license_valid()) {
    return;
}

global $pagenow;

if ('post.php' == $pagenow || 'post-new.php' == $pagenow) {
    global $post_ID;
    $post_type = fansub_get_current_post_type();
    $post = null;
    if ('post-new.php' == $pagenow) {
        if (!function_exists('get_default_post_to_edit')) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }
        $post = get_default_post_to_edit($post_type);
    }
    $post_types = array('batch', 'episode', 'video');
    fansub_meta_box_post_attribute($post_types);

    $meta = new FANSUB_Meta('post');
    $meta->add_post_type('video');
    $meta->set_id('fansub_video_information');
    $meta->set_title('Video Information');
    $meta->set_use_media_upload(true);
    $meta->add_field(array('id' => 'video_url', 'label' => 'Video URL:'));
    //$meta->init();

    $meta = new FANSUB_Meta('post');
    $meta->add_post_type('episode');
    $meta->add_post_type('batch');
    $meta->set_id('post_information');
    $meta->set_title('Extra Information');
    $meta->set_use_media_upload(true);
    $meta->add_field(array('id' => 'custom_qualities', 'label' => 'Custom qualities:'));
    $meta->add_field(array('id' => 'custom_servers', 'label' => 'Custom servers:'));

    $value = '';
    if ('post.php' == $pagenow) {
        $post_id = fansub_get_method_value('post', 'get');
        if (fansub_id_number_valid($post_id)) {
            $shortlink = get_post_meta($post_id, 'shortlink', true);
            if (empty($shortlink)) {
                $shortlink = md5(wp_get_shortlink($post_id));
                update_post_meta($post_id, 'shortlink', $shortlink);
            }
            if (!empty($shortlink)) {
                $shortlink = home_url('/go/' . $shortlink);
                $value = $shortlink;
            }
        }
    }

    $args = array(
        'id' => 'go_shortlink',
        'readonly' => true,
        'value' => $value,
        'attributes' => array(
            'readonly' => 'readonly',
            'autocomplete' => 'off'
        ),
        'label' => 'Shortlink:'
    );
    $meta->add_field($args);

    $meta->init();

    add_action('fansub_post_meta_box_field', 'fansub_kntl_meta_box_field');
    add_action('save_post', 'fansub_kntl_save_post_meta');

    $post_id = null;

    if ('post.php' == $pagenow) {
        $post_id = fansub_get_method_value('post', 'get');
    }

    $qs = fansub_ph_get_qualities_and_servers($post_id);

    $qualities = $qs['qualities'];
    $servers = $qs['servers'];


    foreach ($qualities as $quality) {
        $meta_id = 'quality_' . $quality;
        $meta_id = fansub_sanitize_id($meta_id);
        $post_meta = new FANSUB_Meta('post');
        $post_meta->set_id($meta_id);
        $field_name = $meta_id . '_file_name';
        $post_meta->add_field(array('name' => $field_name, 'label' => 'File Name [' . $quality . ']'));
        foreach ($servers as $server) {
            $field_name = $meta_id . '_' . $server;
            $field_name = fansub_sanitize_id($field_name);
            $post_meta->add_field(array('name' => $field_name, 'label' => fansub_uppercase_first_char($server)));
        }
        $post_meta->set_title($quality);
        $post_meta->set_post_types(array('episode', 'batch'));
        $post_meta->init();
    }
}

function fansub_kntl_meta_box_field($meta)
{
    if (!is_object($meta)) {
        return;
    }
    global $post;
    $meta_id = $post->post_type . '_attributes';
    $meta_id = fansub_sanitize_id($meta_id);
    $animation_type = fansub_kntl_get_post_type();
    if ($meta->get_id() == $meta_id) {
        $post_type = $post->post_type;
        if ('episode' == $post_type || 'video' == $post_type) {
            $query = fansub_query(array('post_type' => fansub_kntl_get_post_type(), 'posts_per_page' => -1));
            $all_option = '<option value=""></option>';
            $selected = get_post_meta($post->ID, $animation_type, true);
            foreach ($query->posts as $qpost) {
                $all_option .= fansub_field_get_option(array('value' => $qpost->ID, 'text' => $qpost->post_title, 'selected' => $selected));
            }
            $args = array(
                'id' => $animation_type,
                'name' => $animation_type,
                'all_option' => $all_option,
                'value' => $selected,
                'class' => 'widefat',
                'label' => fansub_uppercase_first_char_only(fansub_kntl_get_post_type_label_singular_name()) . ':',
                'placeholder' => __('Choose parent post', 'fansub')
            );
            fansub_field_select_chosen($args);

            if ('video' == $post_type) {
                $only_member = get_post_meta($post->ID, 'only_member', true);
                $args = array(
                    'id' => 'only_member',
                    'name' => 'only_member',
                    'value' => $only_member,
                    'label' => __('Disable for guest?', 'fansub')
                );
                fansub_field_input_checkbox($args);
            }
        } elseif ('batch' == $post_type) {
            $query = fansub_query(array('post_type' => fansub_kntl_get_post_type(), 'posts_per_page' => -1));
            $all_option = '<option value=""></option>';
            $selected = get_post_meta($post->ID, $animation_type, true);
            foreach ($query->posts as $qpost) {
                $all_option .= fansub_field_get_option(array('value' => $qpost->ID, 'text' => $qpost->post_title, 'selected' => $selected));
            }
            $args = array(
                'id' => $animation_type,
                'name' => $animation_type,
                'all_option' => $all_option,
                'value' => $selected,
                'class' => 'widefat',
                'label' => fansub_uppercase_first_char_only(fansub_kntl_get_post_type_label_singular_name()) . ':',
                'placeholder' => __('Choose parent post', 'fansub')
            );
            fansub_field_select_chosen($args);
            $query = fansub_query(array('post_type' => 'episode', 'posts_per_page' => -1));
            $all_option = '<option value=""></option>';
            $selected = get_post_meta($post->ID, 'episode', true);
            foreach ($query->posts as $qpost) {
                $all_option .= fansub_field_get_option(array('value' => $qpost->ID, 'text' => $qpost->post_title, 'selected' => $selected));
            }
            $args = array(
                'id' => 'episode',
                'name' => 'episode',
                'all_option' => $all_option,
                'value' => $selected,
                'class' => 'widefat',
                'label' => 'Episode:',
                'placeholder' => __('Choose parent post', 'fansub')
            );
            fansub_field_select_chosen($args);
            $value = get_post_meta($post->ID, 'suffix', true);
            $args = array(
                'id' => 'suffix',
                'name' => 'suffix',
                'value' => $value,
                'label' => 'Suffix:'
            );
            fansub_field_input($args);
        }
        $post_types = fansub_ph_get_auto_facebook_post_types();
        if (in_array($post->post_type, $post_types)) {
            $value = get_post_meta($post->ID, 'auto_facebook', true);
            $args = array(
                'id' => 'auto_facebook',
                'name' => 'auto_facebook',
                'value' => $value,
                'label' => 'Auto publish to Facebook?',
                'default' => 1
            );
            fansub_field_input_checkbox($args);
        }
    }
}

function fansub_kntl_save_post_meta($post_id)
{
    if (!fansub_can_save_post($post_id)) {
        return;
    }
    $animation_type = fansub_kntl_get_post_type();
    $post = get_post($post_id);
    $post_title = $post->post_title;
    $title_parts = fansub_kntl_convert_post_title_to_parts($post_title);
    array_shift($title_parts);
    $title_parts = array_map('trim', $title_parts);
    $post_type = fansub_get_current_post_type();
    $suffix = isset($_POST['suffix']) ? $_POST['suffix'] : '';
    update_post_meta($post_id, 'suffix', $suffix);
    if ('episode' == $post_type || 'video' == $post_type) {
        $animation = isset($_POST[$animation_type]) ? $_POST[$animation_type] : '';
        update_post_meta($post_id, $animation_type, $animation);
        if (is_numeric($animation) && $animation > 0) {
            $episodes = get_post_meta($animation, 'episodes', true);
            $episodes = fansub_sanitize_array($episodes);
            if (!in_array($post_id, $episodes)) {
                $episodes[] = $post_id;
            }
            update_post_meta($animation, 'episodes', $episodes);
        }
        $title_count = array_pop($title_parts);
        update_post_meta($post_id, 'episode', $title_count);

        if ('video' == $post_type) {
            $only_member = fansub_checkbox_post_data_value($_POST, 'only_member');
            update_post_meta($post_id, 'only_member', $only_member);
        }
    } elseif ('batch' == $post_type) {
        $episode = isset($_POST['episode']) ? $_POST['episode'] : '';
        update_post_meta($post_id, 'episode', $episode);
        if (fansub_id_number_valid($episode)) {
            $batches = get_post_meta($episode, 'batches', true);
            $batches = fansub_sanitize_array($batches);
            if (!in_array($post_id, $batches)) {
                $batches[] = $post_id;
            }
            update_post_meta($episode, 'batches', $batches);
        }
        $animation = isset($_POST[$animation_type]) ? $_POST[$animation_type] : '';
        update_post_meta($post_id, $animation_type, $animation);
        if (fansub_id_number_valid($animation)) {
            $batches = get_post_meta($animation, 'batches', true);
            $batches = fansub_sanitize_array($batches);
            if (!in_array($post_id, $batches)) {
                $batches[] = $post_id;
            }
            update_post_meta($animation, 'batches', $batches);
        }
        $title_count = array_pop($title_parts);
        $title_count = array_pop($title_parts);
        update_post_meta($post_id, 'batch', $title_count);
    }
    $qs = fansub_ph_get_qualities_and_servers($post_id);

    $qualities = $qs['qualities'];
    $servers = $qs['servers'];


    foreach ($qualities as $quality) {
        $meta_id = 'quality_' . $quality;
        $meta_id = fansub_sanitize_id($meta_id);
        $field_name = $meta_id . '_file_name';
        if (isset($_POST[$field_name])) {
            update_post_meta($post_id, $field_name, $_POST[$field_name]);
        }
        foreach ($servers as $server) {
            $field_name = $meta_id . '_' . $server;
            $field_name = fansub_sanitize_id($field_name);
            if (isset($_POST[$field_name])) {
                update_post_meta($post_id, $field_name . '_encrypted', $_POST[$field_name]);
            }
        }
    }
}