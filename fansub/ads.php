<?php
if(!function_exists('add_filter')) exit;

function fansub_get_ads_positions() {
    global $fansub_ads_positions;
    $fansub_ads_positions = fansub_sanitize_array($fansub_ads_positions);
    $defaults = array(
        'leaderboard' => array(
            'id' => 'leaderboard',
            'name' => __('Leaderboard', 'fansub'),
            'description' => __('Display beside logo in header area.', 'fansub')
        )
    );
    $fansub_ads_positions = wp_parse_args($fansub_ads_positions, $defaults);
    return apply_filters('fansub_ads_positions', $fansub_ads_positions);
}

function fansub_add_ads_position($args = array()) {
    $positions = fansub_get_ads_positions();
    $id = fansub_get_value_by_key($args, 'id');
    $positions[$id] = $args;
    $GLOBALS['fansub_ads_positions'] = $positions;
}

function fansub_show_ads($args = array()) {
    if(!is_array($args)) {
        $args = array(
            'position' => $args
        );
    }
    $position = fansub_get_value_by_key($args, 'position');
    if(!empty($position)) {
        $random = (bool)fansub_get_value_by_key($args, 'random');
        $current_datetime = date(fansub_get_date_format());
        $current_datetime = strtotime($current_datetime);
        $query_args = array(
            'post_type' => 'fansub_ads',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'expire',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'expire',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'expire',
                        'value' => 0,
                        'type' => 'numeric'
                    ),
                    array(
                        'key' => 'expire',
                        'value' => $current_datetime,
                        'type' => 'numeric',
                        'compare' => '>='
                    )
                ),
                array(
                    'key' => 'active',
                    'value' => 1,
                    'type' => 'numeric'
                )
            )
        );
        if($random) {
            $query_args['orderby'] = 'rand';
        }
        $ads = fansub_get_post_by_meta('position', $position, $query_args);
        if($ads->have_posts()) {
            $posts = $ads->posts;
            $ads = array_shift($posts);
            $ads = fansub_get_post_meta('code', $ads->ID);
            if(!empty($ads)) {
                $class = fansub_get_value_by_key($args, 'class');
                fansub_add_string_with_space_before($class, 'fansub-ads text-center ads position-' . $position);
                $div = new FANSUB_HTML('div');
                $div->set_class($class);
                $div->set_text($ads);
                $div->output();
            }
        }
    }
}