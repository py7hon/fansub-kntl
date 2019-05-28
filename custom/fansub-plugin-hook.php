<?php
if (!function_exists('add_filter')) {
    exit;
}

if (!fansub_kntl_license_valid()) {
    return;
}

function fansub_kntl_post_type_and_taxonomy()
{
    $data = fansub_kntl_get_option();
    $post_type = fansub_get_value_by_key($data, 'post_type_name');
    if (!empty($post_type) && 'post' != $post_type && 'page' != $post_type) {
        $name = fansub_get_value_by_key($data, 'post_type_label_name');
        if (!empty($name)) {
            $singular_name = fansub_get_value_by_key($data, 'post_type_label_singular_name');
            if (empty($singular_name)) {
                $singular_name = $name;
            }
            $args = array(
                'name' => $name,
                'singular_name' => $singular_name,
                'post_type' => $post_type,
                'slug' => $post_type,
                'hierarchical' => true,
                'show_in_admin_bar' => true,
                'supports' => array('editor', 'thumbnail', 'comments'),
                'public' => true,
                'has_archive' => true
            );
            fansub_register_post_type($args);
        }
    }

    $args = array(
        'name' => 'Batches',
        'singular_name' => 'Batch',
        'slug' => 'batch',
        'hierarchical' => true,
        'show_in_admin_bar' => true,
        'public' => true,
        'has_archive' => true
    );
    fansub_register_post_type($args);

    $args = array(
        'name' => 'Episodes',
        'singular_name' => 'Episode',
        'slug' => 'episode',
        'hierarchical' => true,
        'show_in_admin_bar' => true,
        'public' => true,
        'has_archive' => true
    );
    fansub_register_post_type($args);

    $args = array(
        'name' => 'Videos',
        'singular_name' => 'Video',
        'slug' => 'video',
        'hierarchical' => true,
        'show_in_admin_bar' => true,
        'public' => true,
        'supports' => array('thumbnail', 'editor'),
        'has_archive' => true
    );
    fansub_register_post_type($args);
}

add_action('fansub_post_type_and_taxonomy', 'fansub_kntl_post_type_and_taxonomy', 0);

function fansub_kntl_redirect_after_comment($location)
{
    global $wpdb;
    if (is_page()) {
        $single_page = fansub_option_get_value('fansub_kntl', 'single_page');
        if (get_the_ID() == $single_page) {
            $location = $_SERVER["HTTP_REFERER"] . "#comment-" . $wpdb->insert_id;
        }
    }

    return $location;
}

add_filter('comment_post_redirect', 'fansub_kntl_redirect_after_comment', 99);

function fansub_kntl_custom_permalink($permalink, $post, $leavename)
{
    $post_type = fansub_kntl_get_post_type();
    if ($post_type == $post->post_type) {
        $page_id = fansub_option_get_value('fansub_kntl', 'single_page');
        if (fansub_id_number_valid($page_id)) {
            remove_filter('post_link', 'fansub_kntl_custom_permalink', 99);
            $new_link = fansub_kntl_build_single_url($page_id, $post->ID);
            add_filter('post_link', 'fansub_kntl_custom_permalink', 99, 3);
            $permalink = $new_link;
        }
    } else {
        if ('batch' == $post->post_type || 'episode' == $post->post_type || 'video' == $post->post_type) {
            $page_id = fansub_option_get_value('fansub_kntl', 'single_page');
            if (fansub_id_number_valid($page_id)) {
                remove_filter('post_link', 'fansub_kntl_custom_permalink', 99);
                $post_id = get_post_meta($post->ID, 'animation', true);
                if (fansub_array_has_value($post_id)) {
                    $post_id = array_shift($post_id);
                }
                $new_link = fansub_kntl_build_single_url($page_id, $post_id);
                add_filter('post_link', 'fansub_kntl_custom_permalink', 99, 3);
                $permalink = $new_link;
            }
        }
    }

    return $permalink;
}

add_filter('post_link', 'fansub_kntl_custom_permalink', 99, 3);

function fansub_kntl_post_type_link($url, $post)
{
    $post_type = fansub_kntl_get_post_type();
    if ($post_type == $post->post_type || 'batch' == $post->post_type || 'episode' == $post->post_type || 'video' == $post->post_type) {
        $page_id = fansub_option_get_value('fansub_kntl', 'single_page');
        if (fansub_id_number_valid($page_id)) {
            remove_filter('post_type_link', 'fansub_kntl_post_type_link', 10);
            $post_id = $post->ID;
            if ($post_type != $post->post_type) {
                $post_id = get_post_meta($post->ID, 'animation', true);
            }
            if (fansub_array_has_value($post_id)) {
                $post_id = array_shift($post_id);
            }
            $new_link = fansub_kntl_build_single_url($page_id, $post_id);
            add_filter('post_type_link', 'fansub_kntl_post_type_link', 10, 2);
            $url = $new_link;
        }
    }

    return $url;
}

add_filter('post_type_link', 'fansub_kntl_post_type_link', 10, 2);

function fansub_kntl_use_chosen_select()
{
    return true;
}

add_filter('fansub_use_chosen_select', 'fansub_kntl_use_chosen_select');

function fansub_kntl_check_before_post_published($post_id)
{
    global $pagenow;
    if (!fansub_can_save_post($post_id) || !is_numeric($post_id)) {
        return;
    }
    $post_new = false;
    if ('post-new.php' == $pagenow) {
        $post_new = true;
    }
    $post_type = fansub_get_current_post_type();
    $prevent_publish = false;
    switch ($post_type) {
        case 'episode':
            $animation = get_post_meta($post_id, 'animation', true);
            $animation = absint($animation);
            if ($animation < 1 && !$post_new) {
                $prevent_publish = true;
                set_transient('fansub_kntl_episode_missing_animation', 1);
            }
            break;
        case 'batch':
            $episode = get_post_meta($post_id, 'episode', true);
            $episode = absint($episode);
            $animation = get_post_meta($post_id, 'animation', true);
            $animation = absint($animation);
            if ($episode < 1 && !$post_new && $animation < 1) {
                $prevent_publish = true;
                set_transient('fansub_kntl_batch_missing_episode', 1);
            }
            break;
    }
    if ($prevent_publish && !$post_new) {
        remove_action('save_post', 'fansub_kntl_check_before_post_published');
        if ('trash' != get_post_status($post_id)) {
            wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        }
    } else {
        delete_transient('fansub_kntl_query_new_release');
    }
}

add_action('save_post', 'fansub_kntl_check_before_post_published');

function fansub_kntl_redirect_post_location($location, $post_id)
{
    if (isset($_POST['publish'])) {
        $status = get_post_status($post_id);
        if ($status == 'draft') {
            $location = add_query_arg('message', 10, $location);
        }
    }

    return $location;
}

add_filter('redirect_post_location', 'fansub_kntl_redirect_post_location', 10, 2);

function fansub_kntl_admin_notices()
{
    global $pagenow;
    if ('post-new.php' == $pagenow) {
        return;
    }
    if (false !== get_transient('fansub_kntl_batch_missing_episode')) {
        $args = array(
            'title' => 'Error',
            'text' => 'Please select episode for batch.',
            'error' => true
        );
        fansub_admin_notice($args);
        delete_transient('fansub_kntl_batch_missing_episode');
    } elseif (false !== get_transient('fansub_kntl_episode_missing_animation')) {
        $args = array(
            'title' => 'Error',
            'text' => 'Please select ' . fansub_kntl_get_post_type() . ' for episode.',
            'error' => true
        );
        $post_type = fansub_get_current_post_type();
        if ('post.php' == $pagenow && 'episode' == $post_type) {
            fansub_admin_notice($args);
        }
        delete_transient('fansub_kntl_episode_missing_animation');
    }
}

add_action('admin_notices', 'fansub_kntl_admin_notices');

function fansub_kntl_query_vars($vars)
{
    $vars[] = 'animation';

    return $vars;
}

add_filter('query_vars', 'fansub_kntl_query_vars');

function fansub_kntl_rewrite_rules($rules)
{
    $new_rule = array('shows/([^/]+)/?$' => 'index.php?pagename=shows&animation=$matches[1]', 'top');
    $rules = $new_rule + $rules;

    return $rules;
}

add_filter('rewrite_rules_array', 'fansub_kntl_rewrite_rules');

function fansub_kntl_template_redirect()
{
    if (is_page()) {
        $permalink_struct = get_option('permalink_structure');
        if (empty($permalink_struct)) {
            return;
        }
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        $show_page = get_post($single_page);
        $post_id = get_query_var('page_id');
        $pagename = get_query_var('pagename');
        if ((fansub_id_number_valid($post_id) && $post_id == $single_page) || ($pagename == $show_page->post_name)) {
            $query_var = get_query_var('animation');
            if (!fansub_id_number_valid($query_var)) {
                return;
            }
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $page = get_post($post_id);
                    if (!is_a($page, 'WP_Post')) {
                        $page = get_page_by_path($pagename);
                    }
                    if ('page' == $page->post_type) {
                        $url = trailingslashit(get_permalink($page));
                        $url .= trailingslashit(trim(sanitize_title_for_query($animation->post_name)));
                        wp_redirect($url);
                        exit;
                    }
                }
            }
        }
    }
}

add_action('template_redirect', 'fansub_kntl_template_redirect');

function fansub_kntl_the_title($title, $post_id)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if ($post_id == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $page = get_post($post_id);
                    if ('page' == $page->post_type) {
                        $title = $animation->post_title;
                        if ('private' == $animation->post_status) {
                            $title = 'Private: ' . $title;
                        } elseif (post_password_required($animation)) {
                            $title = 'Protected: ' . $title;
                        }
                    }
                }
            }
        } else {
            $page = get_post($post_id);
            if (is_a($page, 'WP_Post')) {
                if (has_shortcode($page->post_content, 'fansub_release')) {
                    $title = '';
                }
            }
        }
    }

    return $title;
}

add_filter('the_title', 'fansub_kntl_the_title', 10, 2);

function fansub_kntl_yoast_seo_title($title)
{
    if (is_page()) {
        $post_id = get_the_ID();
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if ($post_id == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $page = get_post($post_id);
                    if ('page' == $page->post_type) {
                        $title = $animation->post_title;
                    }
                }
            }
        } else {
            $page = get_post($post_id);
            if (is_a($page, 'WP_Post')) {
                if (has_shortcode($page->post_content, 'fansub_release')) {
                    $title = '';
                }
            }
        }
    }

    return $title;
}

add_filter('wpseo_title', 'fansub_kntl_yoast_seo_title');

function fansub_kntl_custom_body_class($classes)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $classes[] = 'single-animation';
        }
    }
}

add_filter('body_class', 'fansub_kntl_custom_body_class');

function fansub_kntl_change_wp_title($title, $sep, $seplocation)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $title = $animation->post_title;
                    if (!empty($sep)) {
                        if ('right' == $seplocation) {
                            $title .= ' ' . $sep . ' ' . get_bloginfo('name');
                        } else {
                            $title = get_bloginfo('name') . ' ' . $sep . ' ' . $title;
                        }
                    }
                }
            }
        }
    }

    return $title;
}

add_filter('wp_title', 'fansub_kntl_change_wp_title', 99, 3);

function fansub_kntl_option_saved()
{
    delete_transient('fansub_kntl_query_new_release');
}

add_action('fansub_option_saved', 'fansub_kntl_option_saved');

function fansub_kntl_wpseo_opengraph_url($content)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $content = fansub_kntl_build_single_url(get_the_ID(), $animation->ID);
                }
            }
        }
    }

    return $content;
}

add_filter('wpseo_opengraph_url', 'fansub_kntl_wpseo_opengraph_url', 99);
add_filter('wpseo_canonical', 'fansub_kntl_wpseo_opengraph_url', 99);

function fansub_kntl_wpseo_opengraph_desc($content)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $content = $animation->post_content;
                    $content = wp_strip_all_tags($content);
                    $content = fansub_substr($content, 165);
                }
            }
        }
    }

    return $content;
}

add_filter('wpseo_opengraph_desc', 'fansub_kntl_wpseo_opengraph_desc', 99);

function fansub_kntl_wpseo_opengraph_title($content)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $content = $animation->post_title . ' - ' . get_bloginfo('name');
                }
            }
        }
    }

    return $content;
}

add_filter('wpseo_opengraph_title', 'fansub_kntl_wpseo_opengraph_title', 99);
add_filter('wpseo_twitter_title', 'fansub_kntl_wpseo_opengraph_title', 99);

function fansub_kntl_wpseo_opengraph_image($content)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $content = fansub_get_post_thumbnail_url($animation->ID);
                }
            }
        }
    }

    return $content;
}

add_filter('wpseo_opengraph_image', 'fansub_kntl_wpseo_opengraph_image', 99);

function fansub_kntl_amt_metadata_head($metadata_arr)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    if (fansub_array_has_value($metadata_arr)) {
                        $tmp_metas = array();
                        $has_og_image = false;
                        $has_og_description = false;
                        foreach ($metadata_arr as $metadata) {
                            $property = fansub_get_tag_attr('meta', 'property', $metadata);
                            if (empty($property)) {
                                $property = fansub_get_tag_attr('meta', 'name', $metadata);
                            }
                            switch ($property) {
                                case 'og:url':
                                    $url = fansub_kntl_build_single_url(get_the_ID(), $animation->ID);
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', $url);
                                    break;
                                case 'og:updated_time':
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', get_post_modified_time('c', true, $animation));
                                    break;
                                case 'article:published_time':
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', get_post_time('c', true, $animation));
                                    break;
                                case 'article:modified_time':
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', get_post_modified_time('c', true, $animation));
                                    break;
                                case 'og:image':
                                    $has_og_image = true;
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', fansub_get_post_thumbnail_url($animation->ID));
                                    break;
                                case 'og:description':
                                    $has_og_description = true;
                                    $content = $animation->post_content;
                                    $content = wp_strip_all_tags($content);
                                    $content = fansub_substr($content, 165);
                                    $metadata = fansub_change_tag_attribute($metadata, 'content', $content);
                                    break;
                            }
                            $tmp_metas[] = $metadata;
                        }
                        if (!$has_og_image) {
                            $tmp_metas[] = '<meta property="og:image" content="' . fansub_get_post_thumbnail_url($animation->ID) . '" />';
                        }
                        if (!$has_og_description) {
                            $content = $animation->post_content;
                            $content = wp_strip_all_tags($content);
                            $content = fansub_substr($content, 165);
                            $tmp_metas[] = '<meta property="og:description" content="' . $content . '" />';
                        }
                        $metadata_arr = $tmp_metas;
                    }
                }
            }
        }
    }

    return $metadata_arr;
}

add_filter('amt_metadata_head', 'fansub_kntl_amt_metadata_head', 99);

function fansub_kntl_single_shortlink($shortlink, $id, $context, $allow_slugs)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');

        if (get_the_ID() == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    remove_filter('pre_get_shortlink', 'fansub_kntl_single_shortlink', 99);
                    $shortlink = wp_get_shortlink($id);
                    add_filter('pre_get_shortlink', 'fansub_kntl_single_shortlink', 99, 4);
                    $shortlink = add_query_arg(array('animation' => $animation->ID), $shortlink);
                }
            }
        }
    }

    return $shortlink;
}

add_filter('pre_get_shortlink', 'fansub_kntl_single_shortlink', 99, 4);

remove_action('wp_head', 'rel_canonical');
add_action('wp_head', 'fansub_rel_canonical');

function fansub_kntl_change_head_rel_canonical($link, $id)
{
    if (is_page()) {
        $option_data = fansub_kntl_get_option();
        $single_page = fansub_get_value_by_key($option_data, 'single_page');
        if ($id == $single_page) {
            $animation = fansub_kntl_get_current_animation_single();
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    $link = fansub_kntl_build_single_url($id, $animation->ID);
                }
            }
        }
    }

    return $link;
}

add_filter('fansub_head_rel_canonical', 'fansub_kntl_change_head_rel_canonical', 99, 2);

function fansub_kntl_get_edit_post_link($link, $post_id, $context)
{
    if (is_page()) {
        $page = get_post($post_id);
        if ('shows' == $page->post_name) {
            $qa = fansub_kntl_get_current_animation_single();
            $animate = get_post($qa);
            if (is_a($animate, 'WP_Post')) {
                $link = admin_url('post.php?post=' . $animate->ID . '&action=edit');
            }
        }
    }

    return $link;
}

add_filter('get_edit_post_link', 'fansub_kntl_get_edit_post_link', 99, 3);

function fansub_kntl_custom_scripts()
{
    wp_register_script('scrollstop', FANSUB_KNTL_URL . '/lib/lazyload/jquery.scrollstop.min.js', array('jquery'),
        false, true);
    wp_enqueue_script('lazyload', FANSUB_KNTL_URL . '/lib/lazyload/jquery.lazyload.min.js', array(
        'jquery',
        'scrollstop'
    ),
        false, true);
    wp_enqueue_style('fancybox-style', FANSUB_KNTL_URL . '/lib/fancybox/jquery.fancybox.css');
    wp_enqueue_script('fancybox', FANSUB_KNTL_URL . '/lib/fancybox/jquery.fancybox.pack.js', array('jquery'), false, true);

    wp_enqueue_style('bxslider-style', FANSUB_KNTL_URL . '/lib/bxslider/jquery.bxslider.css');
    wp_enqueue_script('bxslider', FANSUB_KNTL_URL . '/lib/bxslider/jquery.bxslider.min.js', array('jquery'), false, true);

    global $wp_scripts;
    $ui = $wp_scripts->query('jquery-ui-core');
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-style', $url);
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_style('dashicons');
}

add_action('wp_enqueue_scripts', 'fansub_kntl_custom_scripts');

function fansub_kntl_custom_redirect_single_animation()
{
    if (is_preview()) {
        return;
    }
    $post_type = fansub_kntl_get_post_type();
    if (is_singular($post_type) || is_singular('batch') || is_singular('episode') || is_singular('video')) {
        global $post;
        $single_page = fansub_option_get_value('fansub_kntl', 'single_page');
        if (fansub_id_number_valid($single_page)) {
            $post_id = $post->ID;
            if ($post_type != $post->post_type) {
                $post_id = get_post_meta($post_id, 'animation');
                if (fansub_array_has_value($post_id)) {
                    $post_id = array_shift($post_id);
                }
            }
            $permalink = fansub_kntl_build_single_url($single_page, $post_id);
            wp_redirect($permalink);
            exit;
        }
    }
}

add_action('wp', 'fansub_kntl_custom_redirect_single_animation');

function fansub_kntl_custom_admin_scripts()
{
    wp_enqueue_media();
    wp_enqueue_script('zeroclipboard', FANSUB_KNTL_URL . '/lib/zeroclipboard/ZeroClipboard.min.js', array('jquery'), false, true);
}

add_action('admin_enqueue_scripts', 'fansub_kntl_custom_admin_scripts');

function fansub_kntl_custom_posts_columns($columns)
{
    global $post_type;
    switch ($post_type) {
        case 'batch':
            $columns['animation'] = __('Animation', 'fansub-kntl');
            $columns['episode'] = __('Episode', 'fansub-kntl');
            break;
        case 'episode':
            $columns['animation'] = __('Animation', 'fansub-kntl');
            break;
    }

    return $columns;
}

add_filter('manage_posts_columns', 'fansub_kntl_custom_posts_columns', 99);

function fansub_kntl_custom_posts_custom_column($column, $post_id)
{
    switch ($column) {
        case 'animation':
            $animation = get_post_meta($post_id, 'animation', true);
            if (fansub_id_number_valid($animation)) {
                $animation = get_post($animation);
                if (is_a($animation, 'WP_Post')) {
                    echo $animation->post_title;
                }
            }
            break;
        case 'episode':
            $episode = get_post_meta($post_id, 'episode', true);
            if (fansub_id_number_valid($episode)) {
                $episode = get_post($episode);
                if (is_a($episode, 'WP_Post')) {
                    echo $episode->post_title;
                }
            }
            break;
    }
}

add_action('manage_batch_posts_custom_column', 'fansub_kntl_custom_posts_custom_column', 10, 2);
add_action('manage_episode_posts_custom_column', 'fansub_kntl_custom_posts_custom_column', 10, 2);

function fansub_ph_add_query_vars($vars)
{
    $vars[] = 'go';

    return $vars;
}

add_filter('query_vars', 'fansub_ph_add_query_vars');

function fansub_ph_init()
{
    add_rewrite_endpoint('go', EP_ALL);
    //add_rewrite_rule('^go/([^/]*)/?)', 'index.php?go=$matches[1]', 'top');
}

add_action('init', 'fansub_ph_init', 0);

function fansub_ph_custom_template_redirect()
{
    $go = get_query_var('go');
    if (!empty($go)) {
        $go = base64_decode($go);
        $parts = explode('|', $go);
        if (is_array($parts) && count($parts) == 2) {
            $url = $parts[1];
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                wp_redirect($url);
                exit;
            }
        }
    } else {
        return;
    }
    if (empty($go)) {
        $go = fansub_get_method_value('link', 'get');
        $parts = explode('p=', $go);
        if (is_array($parts) && count($parts) == 2) {
            $post_id = $parts[1];
            if (fansub_id_number_valid($post_id)) {
                $post = get_post($post_id);
                if (is_a($post, 'WP_Post')) {
                    $click_count = get_post_meta($post_id, 'click_count', true);
                    $click_count = absint($click_count);
                    $click_count++;
                    update_post_meta($post_id, 'click_count', $click_count);
                }
            }
        }
    }
    if (fansub_id_number_valid($go)) {
        $post = get_post($go);
        if (is_a($post, 'WP_Post')) {
            $click_count = get_post_meta($go, 'click_count', true);
            $click_count = absint($click_count);
            $click_count++;
            update_post_meta($go, 'click_count', $click_count);
            wp_redirect(wp_get_shortlink($post));
            exit;
        }
    } elseif (filter_var($go, FILTER_VALIDATE_URL)) {
        wp_redirect($go);
        exit;
    } elseif (!empty($go)) {
        $query = fansub_get_post_by_meta('shortlink', $go);
        if ($query->have_posts()) {
            $post = array_shift($query->posts);
            $post_id = $post->ID;
            $click_count = get_post_meta($post_id, 'click_count', true);
            $click_count = absint($click_count);
            $click_count++;
            update_post_meta($post_id, 'click_count', $click_count);
            wp_redirect(wp_get_shortlink($post));
            exit;
        }
    }
}

add_action('wp', 'fansub_ph_custom_template_redirect');

function fansub_ph_on_admin_init()
{
    global $pagenow;
    if ('admin.php' == $pagenow) {
        $page = fansub_get_method_value('page', 'get');
        if ('fansub_kntl' == $page) {
            $options = get_option('fansub_kntl');
            $fb_app_id = fansub_get_value_by_key($options, 'fb_app_id');
            if (!empty($fb_app_id)) {
                $fb_code = fansub_get_value_by_key($options, 'fb_code');
                if (empty($fb_code)) {
                    $fb_app_secret = fansub_get_value_by_key($options, 'fb_app_secret');
                    $fb_redirect_uri = fansub_get_value_by_key($options, 'fb_redirect_uri');
                    if (empty($fb_redirect_uri)) {
                        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
                        $protocol .= '://';
                        $fb_redirect_uri = $protocol;
                        if (isset($_SERVER['HTTP_HOST'])) {
                            $fb_redirect_uri .= $_SERVER['HTTP_HOST'];
                            if (isset($_SERVER['REQUEST_URI'])) {
                                $fb_redirect_uri .= $_SERVER['REQUEST_URI'];
                            }
                        }
                    }
                    $url = 'https://www.facebook.com/dialog/oauth';
                    $params = array(
                        'client_id' => $fb_app_id,
                        'redirect_uri' => $fb_redirect_uri,
                        'scope' => 'manage_pages,publish_actions'
                    );
                    $url = add_query_arg($params, $url);
                }
            }
        }
    }
}

add_action('admin_init', 'fansub_ph_on_admin_init');

function fansub_ph_auto_post_facebook($post_id)
{
    if (!fansub_can_save_post($post_id)) {
        return;
    }
    $post = get_post($post_id);
    $new_status = $post->post_status;
    $post_type = fansub_ph_get_auto_facebook_post_types();
    if (in_array($post->post_type, $post_type) && 'publish' == $new_status && isset($_POST['auto_facebook']) && 1 == $_POST['auto_facebook']) {
        $options = get_option('fansub_kntl');
        $fb_app_id = fansub_get_value_by_key($options, 'fb_app_id');
        if (!empty($fb_app_id)) {
            $fb_app_secret = fansub_get_value_by_key($options, 'fb_app_secret');
            if (!empty($fb_app_secret)) {
                $fb_access_token = fansub_get_value_by_key($options, 'fb_access_token');
                if (!empty($fb_access_token)) {
                    $auto_facebook = get_post_meta($post_id, 'auto_facebook', true);
                    if (1 != $auto_facebook) {
                        require_once(FANSUB_KNTL_PATH . '/lib/Facebook/autoload.php');
                        $fb = new Facebook\Facebook([
                            'app_id' => $fb_app_id,
                            'app_secret' => $fb_app_secret,
                            'default_graph_version' => 'v2.9',
                        ]);

                        $linkData = [
                            'link' => esc_url(get_permalink($post)),
                            'message' => $post->post_title,
                            'privacy' => json_encode(array('value' => 'EVERYONE'))
                        ];
                        $response = null;
                        try {
                            $response = $fb->post('/me/feed', $linkData, $fb_access_token);
                        } catch (Facebook\Exceptions\FacebookResponseException $e) {

                        } catch (Facebook\Exceptions\FacebookSDKException $e) {

                        }
                        if ($response) {
                            update_post_meta($post_id, 'auto_facebook', 1);
                        }
                    }
                }
            }
        }
    }
}

add_action('save_post', 'fansub_ph_auto_post_facebook', 99);

