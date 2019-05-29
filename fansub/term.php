<?php
if(!function_exists('add_filter')) exit;

function fansub_get_term_link($term) {
    return '<a href="' . esc_url(get_term_link($term)) . '" rel="category ' . fansub_sanitize_html_class($term->taxonomy) . ' tag">' . $term->name.'</a>';
}

function fansub_the_terms($args = array()) {
    $terms = fansub_get_value_by_key($args, 'terms');
    $before = fansub_get_value_by_key($args, 'before');
    $sep = fansub_get_value_by_key($args, 'separator', ', ');
    $after = fansub_get_value_by_key($args, 'after');
    if(fansub_array_has_value($terms)) {
        echo $before;
        $html = '';
        foreach($terms as $term) {
            $html .= fansub_get_term_link($term) . $sep;
        }
        $html = trim($html, $sep);
        echo $html;
        echo $after;
    } else {
        $post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
        $taxonomy = fansub_get_value_by_key($args, 'taxonomy');
        the_terms($post_id, $taxonomy, $before, $sep, $after);
    }
}

function fansub_get_hierarchical_terms($taxonomies, $args = array()) {
    if(!fansub_array_has_value($taxonomies)) {
        $taxonomies = array('category');
    }
    $args['hierarchical'] = true;
    return fansub_get_terms($taxonomies, $args);
}

function fansub_get_taxonomies($args = array()) {
    return get_taxonomies($args, 'objects');
}

function fansub_get_hierarchical_taxonomies($args = array()) {
    $args['hierarchical'] = true;
    return fansub_get_taxonomies($args);
}

function fansub_term_meta_thumbnail_field($taxonomies = array()) {
    global $pagenow;
    if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
        if(!fansub_array_has_value($taxonomies)) {
            $taxonomies = array('category');
        }
        $meta = new FANSUB_Meta('term');
        $meta->set_taxonomies($taxonomies);
        $meta->set_use_media_upload(true);
        $meta->add_field(array('id' => 'thumbnail', 'label' => __('Thumbnail', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
        $meta->init();
    }
}

function fansub_term_meta_different_name_field($taxonomies = array()) {
    global $pagenow;
    if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
        if(!fansub_array_has_value($taxonomies)) {
            $taxonomies = get_taxonomies(array('public' => true));
        }
        $taxonomies = apply_filters('fansub_term_different_name_field_taxonomies', $taxonomies);
        fansub_exclude_special_taxonomies($taxonomies);
        if(!fansub_array_has_value($taxonomies)) {
            $taxonomies = array('category');
        }
        $meta = new FANSUB_Meta('term');
        $meta->set_taxonomies($taxonomies);
        $meta->add_field(array('id' => 'different_name', 'label' => __('Different Name', 'fansub')));
        $meta->init();
    }
}

function fansub_get_term_meta($key, $term_id) {
    return get_term_meta($term_id, $key, true);
}

function fansub_term_name($term) {
    echo fansub_term_get_name($term);
}

function fansub_term_get_name($term) {
    $name = '';
    if(is_a($term, 'WP_Term')) {
        $name = $term->name;
        $different_name = fansub_get_term_meta('different_name', $term->term_id);
        if(!empty($different_name)) {
            $name = strip_tags($different_name);
        }
        $name = apply_filters('fansub_term_name', $name, $term);
    }
    return $name;
}

function fansub_term_link_html($term) {
    return fansub_get_term_link($term);
}

function fansub_term_link_li_html($term) {
    $link = fansub_term_link_html($term);
    $link = fansub_wrap_tag($link, 'li');
    return $link . PHP_EOL;
}

function fansub_term_get_thumbnail_url($args = array()) {
    $term_id = fansub_get_value_by_key($args, 'term_id');
    if(!fansub_id_number_valid($term_id)) {
        $term = fansub_get_value_by_key($args, 'term');
        if(is_a($term, 'WP_Term')) {
            $term_id = $term->term_id;
        }
    }
    if(!fansub_id_number_valid($term_id)) {
        $term_id = 0;
    }
    $value = get_term_meta($term_id, 'thumbnail', true);
    $use_default_term_thumbnail = apply_filters('fansub_use_default_term_thumbnail', fansub_get_value_by_key($args, 'use_default_thumbnail', true));
    $value = fansub_sanitize_media_value($value);
    $value = $value['url'];
    $icon = false;
    if(empty($value)) {
        $icon_url = fansub_get_term_icon($term_id);
        $value = $icon_url;
        if(!empty($value)) {
            $icon = true;
        }
    }
    if(!$icon) {
        if(empty($value) && (bool)$use_default_term_thumbnail) {
            $value = fansub_get_image_url('no-thumbnail.png');
        }
        $bfi_thumb = fansub_get_value_by_key($args, 'bfi_thumb', true);
        if((bool)$bfi_thumb) {
            $size = fansub_sanitize_size($args);
            $params = array();
            $width = $size[0];
            if(fansub_id_number_valid($width)) {
                $params['width'] = $width;
            }
            $height = $size[1];
            if(fansub_id_number_valid($height)) {
                $params['height'] = $height;
            }
            $crop = fansub_get_value_by_key($args, 'crop', true);
            $params['crop'] = $crop;
            $value = bfi_thumb($value, $params);
        }
    }
    return apply_filters('fansub_term_thumbnail', $value, $term_id);
}

function fansub_term_get_thumbnail_html($args = array()) {
    $thumb_url = fansub_term_get_thumbnail_url($args);
    $result = '';
    $term = fansub_get_value_by_key($args, 'term');
    if(!empty($thumb_url)) {
        $taxonomy = fansub_get_value_by_key($args, 'taxonomy');
        if(!is_a($term, 'WP_Term')) {
            $term_id = fansub_get_value_by_key($args, 'term_id');
            if(fansub_id_number_valid($term_id) && !empty($taxonomy)) {
                $term = get_term($term_id, $taxonomy);
            }
        }
        if(is_a($term, 'WP_Term')) {
            $size = fansub_sanitize_size($args);
            $link = fansub_get_value_by_key($args, 'link', true);
            $show_name = fansub_get_value_by_key($args, 'show_name');
            $img = new FANSUB_HTML('img');
            $img->set_image_src($thumb_url);
            $img->set_attribute('width', $size[0]);
            $img->set_attribute('height', $size[1]);
            $class = 'img-responsive wp-term-image';
            $slug = $term->taxonomy;
            fansub_add_string_with_space_before($class, fansub_sanitize_html_class($slug) . '-thumb');
            $img->set_class($class);
            $link_text = $img->build();
            if((bool)$show_name) {
                $link_text .= '<span class="term-name">' . $term->name . '</span>';
            }
            $a = new FANSUB_HTML('a');
            $a->set_text($link_text);
            $a->set_attribute('title', $term->name);
            $a->set_href(get_term_link($term));
            if(!(bool)$link) {
                $result = $img->build();
            } else {
                $result = $a->build();
            }
        }
    }
    return apply_filters('fansub_term_thumbnail_html', $result, $term);
}

function fansub_term_the_thumbnail($args = array()) {
    echo fansub_term_get_thumbnail_html($args);
}

function fansub_term_get_current() {
    return get_queried_object();
}

function fansub_term_get_current_id() {
    return get_queried_object_id();
}

function fansub_term_get_top_most_parent_ids($term) {
    $term_ids = array();
    if(is_a($term, 'WP_Term')) {
        $term_ids = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');
    }
    return $term_ids;
}

function fansub_term_get_top_most_parent($term) {
    $term_ids = fansub_term_get_top_most_parent_ids($term);
    $term_id = array_shift($term_ids);
    $parent = '';
    if(fansub_id_number_valid($term_id)) {
        $parent = get_term($term_id, $term->taxonomy);
    }
    return $parent;
}

function fansub_term_get_by_count($taxonomy = 'category', $args = array()) {
    $result = array();
    $args['orderby'] = 'count';
    $args['order'] = 'DESC';
    $terms = fansub_get_terms($taxonomy, $args);
    if(fansub_array_has_value($terms)) {
        $result = $terms;
    }
    return $result;
}

function fansub_get_term_by_slug($slug, $taxonomy = 'category') {
    return get_term_by('slug', $slug, $taxonomy);
}

function fansub_insert_term($term, $taxonomy, $args = array()) {
    $override = fansub_get_value_by_key($args, 'override', false);
    if(!$override) {
        $exists = get_term_by('name', $term, $taxonomy);
        if(is_a($exists, 'WP_Term')) {
            return;
        }
    }
    wp_insert_term($term, $taxonomy, $args);
}

function fansub_get_term_icon($term_id) {
    $icon = fansub_get_term_meta('icon_html', $term_id);
    if(empty($icon)) {
        $icon = fansub_get_term_meta('icon', $term_id);
        $icon = fansub_sanitize_media_value($icon);
        $icon = $icon['url'];
    }
    return $icon;
}

function fansub_get_child_terms($parent_id, $taxonomy, $args = array()) {
    $args['child_of'] = $parent_id;
    $terms = fansub_get_terms($taxonomy, $args);
    return $terms;
}