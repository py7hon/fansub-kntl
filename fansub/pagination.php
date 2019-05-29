<?php

if(!function_exists('add_filter')) exit;

function hocwp_pagination_defaults() {
    $defaults = array(
        'label' => __('Trang', 'fansub'),
        'last' => __('Trang cuối', 'fansub'),
        'first' => __('Trang đầu', 'fansub'),
        'show_first_item' => false,
        'next' => '&raquo;',
        'prev' => '&laquo;',
        'style' => 'default',
        'border_radius' => 'none',
        'range' => 3,
        'anchor' => 1,
        'gap' => 3,
        'hellip' => true,
        'show_max_page' => true,
        'min_page' => 5,
        'current_item_link' => false
    );
    return apply_filters('fansub_pagination_defaults', $defaults);
}

function fansub_get_request() {
    $request = remove_query_arg('paged');
    $home_root = parse_url(home_url());
    $home_root =(isset($home_root['path'])) ? $home_root['path'] : '';
    $home_root = preg_quote($home_root, '|');
    $request = preg_replace('|^'. $home_root . '|i', '', $request);
    $request = preg_replace('|^/+|', '', $request);
    return $request;
}

function fansub_get_pagenum_link($args = array()) {
    $pagenum = isset($args['pagenum']) ? $args['pagenum'] : 1;
    $escape = isset($args['escape']) ? $args['escape'] : true;
    $request = isset($args['request']) ? $args['request'] : fansub_get_request();
    if (!is_admin()) {
        return get_pagenum_link($pagenum, $escape);
    } else {
        global $wp_rewrite;
        $pagenum = (int)$pagenum;
        if(!$wp_rewrite->using_permalinks()) {
            $base = trailingslashit(get_bloginfo('url'));
            if($pagenum > 1) {
                $result = add_query_arg('paged', $pagenum, $base . $request);
            } else {
                $result = $base . $request;
            }
        } else {
            $qs_regex = '|\?.*?$|';
            preg_match($qs_regex, $request, $qs_match);
            if(!empty($qs_match[0])) {
                $query_string = $qs_match[0];
                $request = preg_replace($qs_regex, '', $request);
            } else {
                $query_string = '';
            }
            $request = preg_replace("|$wp_rewrite->pagination_base/\d+/?$|", '', $request);
            $request = preg_replace('|^' . preg_quote($wp_rewrite->index, '|') . '|i', '', $request);
            $request = ltrim($request, '/');
            $base = trailingslashit(get_bloginfo('url'));
            if($wp_rewrite->using_index_permalinks() &&($pagenum > 1 || '' != $request)) {
                $base .= $wp_rewrite->index . '/';
            }
            if($pagenum > 1) {
                $request =((!empty($request)) ? trailingslashit($request) : $request) . user_trailingslashit($wp_rewrite->pagination_base . "/" . $pagenum, 'paged');
            }
            $result = $base . $request . $query_string;
        }
        $result = apply_filters('get_pagenum_link', $result);
        if($escape) {
            return esc_url($result);
        }
        return esc_url_raw($result);
    }
}

function fansub_get_query($args = array()) {
    global $wp_query;
    $query = isset($args['query']) ? $args['query'] : null;
    if(!fansub_object_valid($query)) {
        $query = $wp_query;
    }
    return $query;
}

function fansub_get_total_page($args = array()) {
    $query = fansub_get_query($args);
    $posts_per_page = isset($query->query_vars['posts_per_page']) ? $query->query_vars['posts_per_page'] : get_option('posts_per_page');
    if(1 > $posts_per_page) {
        return 0;
    }
    $total_page = intval(ceil($query->found_posts / $posts_per_page));
    return $total_page;
}

function fansub_has_paged($args = array()) {
    $total = fansub_get_value_by_key($args, 'total_page', fansub_get_total_page($args));
    if($total > 1) {
        return true;
    }
    return false;
}

function fansub_build_pagination($args = array()) {
    $defaults = fansub_pagination_defaults();
    $label = trim(fansub_get_value_by_key($args, 'label', fansub_get_value_by_key($defaults, 'label')));
    $label = apply_filters('fansub_replace_text_placeholder', $label);
    $previous = fansub_get_value_by_key($args, 'prev', fansub_get_value_by_key($defaults, 'prev'));
    $next = fansub_get_value_by_key($args, 'next', fansub_get_value_by_key($defaults, 'next'));
    $first = fansub_get_value_by_key($args, 'first', fansub_get_value_by_key($defaults, 'first'));
    $last = fansub_get_value_by_key($args, 'last', fansub_get_value_by_key($defaults, 'last'));
    $show_first_item = fansub_get_value_by_key($args, 'show_first_item', fansub_get_value_by_key($defaults, 'show_first_item'));
    $request = isset($args['request']) ? $args['request'] : '';
    if(empty($request)) {
        $request = fansub_get_request();
    }
    $current_page = fansub_get_value_by_key($args, 'current_page');
    if(!isset($args['current_page'])) {
        $query = fansub_get_query($args);
        $current_page = isset($query->query_vars['paged']) ? $query->query_vars['paged'] : '0';
    }
    $total_page = fansub_get_value_by_key($args, 'total_page', fansub_get_total_page($args));
    if(1 > $current_page || $current_page > $total_page) {
        $current_page = fansub_get_paged();
    }
    $args['current_page'] = $current_page;
    if(1 >= $total_page) {
        return '';
    }
    $args['total_page'] = $total_page;
    $result = '';
    if(!empty($label)) {
        $label = str_replace('%TOTAL_PAGES%', $total_page, $label);
        $result .= '<span class="item label-item">' . $label . '</span>';
    }
    if($current_page > 1 || $show_first_item) {
        $link_href = fansub_get_pagenum_link(array('pagenum' => 1, 'request' => $request));
        if(!empty($first)) {
            $result .= '<a class="item link-item first-item" href="' . $link_href . '" data-paged="' . 1 . '">' . $first . '</a>';
        }
        $link_href = fansub_get_pagenum_link(array('pagenum' => ($current_page - 1), 'request' => $request));
        $result .= '<a class="item link-item previous-item" href="' . $link_href . '" data-paged="' . ($current_page - 1) . '">' . $previous . '</a>';
    }
    $result .= fansub_loop_pagination_item($args);
    if($current_page < $total_page) {
        $link_href = fansub_get_pagenum_link(array('pagenum' => ($current_page + 1), 'request' => $request));
        $result .= '<a href="' . $link_href . '" class="item next-item link-item" data-paged="' . ($current_page + 1) . '">' . $next . '</a>';
        $link_href = fansub_get_pagenum_link(array('pagenum' => $total_page, 'request' => $request));
        if(!empty($last)) {
            $result .= '<a href="' . $link_href . '" class="item last-item link-item" data-paged="' . $total_page . '">' . $last . '</a>';
        }
    }
    return $result;
}

function fansub_pagination_before($args = array()) {
    $default_style = 'default';
    $default_border_radius = 'default';
    $style = $default_style;
    $border_radius = isset($args['border_radius']) ? $args['border_radius'] : $default_border_radius;

    $style .= '-style';
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'pagination loop-paginations fansub-pagination clearfix');
    $class .= ' ' . $style;
    switch($border_radius) {
        case 'circle':
            $class .= ' border-radius-circle';
            break;
        case 'default':
            break;
        case 'none':
            $class .= ' no-border-radius';
            break;
    }
    $class = trim($class);
    if(fansub_has_paged($args)) {
        fansub_add_string_with_space_before($class, 'has-paged');
    } else {
        fansub_add_string_with_space_before($class, 'no-paged');
    }
    $query = fansub_get_value_by_key($args, 'query', $GLOBALS['wp_query']);
    $ajax = fansub_get_value_by_key($args, 'ajax');
    $query_vars = array();
    if((bool)$ajax) {
        $query_vars = fansub_get_value_by_key($args, 'query_vars', $query->query_vars);
        fansub_add_string_with_space_before($class, 'ajax');
    }
    echo '<nav class="' . $class . '" data-query-vars="' . esc_attr(json_encode($query_vars)) . '">';
}

function fansub_pagination_after() {
    echo '</nav>';
}

function fansub_show_pagination($args = array()) {
    fansub_pagination_before($args);
    echo fansub_build_pagination($args);
    fansub_pagination_after();
}

function fansub_loop_pagination_item($args = array()) {
    $defaults = fansub_pagination_defaults();

    // The number of page links to show before and after the current page.
    $range = fansub_get_value_by_key($args, 'range', fansub_get_value_by_key($defaults, 'range'));
    // The number of page links to show at beginning and end of pagination.
    $anchor = fansub_get_value_by_key($args, 'anchor', fansub_get_value_by_key($defaults, 'anchor'));
    // The minimum number of page links before ellipsis shows.
    $gap = fansub_get_value_by_key($args, 'gap', fansub_get_value_by_key($defaults, 'gap'));

    $hellip = fansub_get_value_by_key($args, 'hellip', fansub_get_value_by_key($defaults, 'hellip'));
    $show_max_page = fansub_get_value_by_key($args, 'show_max_page', fansub_get_value_by_key($defaults, 'show_max_page'));
    $min_page = fansub_get_value_by_key($args, 'min_page', fansub_get_value_by_key($defaults, 'min_page'));
    $current_page = isset($args['current_page']) ? $args['current_page'] : 1;
    $total_page = isset($args['total_page']) ? $args['total_page'] : 1;
    $current_item_link = fansub_get_value_by_key($args, 'current_item_link', fansub_get_value_by_key($defaults, 'current_item_link'));

    $request = isset($args['request']) ? $args['request'] : fansub_get_request();

    $hidden_button = '<span class="item hidden-item">&hellip;</span>';
    if(!(bool)$hellip) {
        $hidden_button = '';
    }
    $result = '';

    $hidden_before = false;
    $hidden_after = false;
    $before_current = $current_page - $range;
    $after_current = $current_page + $range;
    for($i = 1; $i <= $total_page; $i++) {
        if(!(bool)$show_max_page) {
            if($i == $total_page && $current_page < ($total_page - 2)) {
                continue;
            }
        }
        if($current_page == $i) {
            if($current_item_link) {
                $link_href = fansub_get_pagenum_link(array('pagenum' => $i, 'request' => $request));
                $result .= '<a class="item link-item current-item" href="' . $link_href . '" data-paged="' . $i . '">' . $i . '</a>';
            } else {
                $ajax = (bool)fansub_get_value_by_key($args, 'ajax');
                $current_item = new FANSUB_HTML('span');
                if($ajax) {
                    $current_item = new FANSUB_HTML('a');
                    $current_item->add_class('link-item');
                    $current_item->set_attribute('data-paged', $i);
                }
                $current_item->add_class('item current-item');
                $current_item->set_text($i);
                $result .= $current_item->build();
            }
        } else {
            $count_hidden_button_before = $before_current - ($anchor + 1);
            $count_hidden_button_after = $total_page - ($after_current + 1);
            $show_hidden_button_before = ($i < $before_current && !$hidden_before && $count_hidden_button_before >= $gap) ? true : false;
            $show_hidden_button_after = ($i > $after_current && !$hidden_after && $count_hidden_button_after >= $gap) ? true : false;
            if(1 == $i || $total_page == $i || ($i <= $after_current && $i >= $before_current) || ($i <= $min_page && $current_page < 2)) {
                $link_href = fansub_get_pagenum_link(array('pagenum' => $i, 'request' => $request));
                $result .= '<a class="item link-item" href="' . $link_href . '" data-paged="' . $i . '">' . $i . '</a>';
            } else {
                if($show_hidden_button_before && ($current_page > 1)) {
                    $result .= $hidden_button;
                    $hidden_before = true;
                    $i = $before_current - 1;
                } elseif($i < $before_current) {
                    $link_href = fansub_get_pagenum_link(array('pagenum' => $i, 'request' => $request));
                    $result .= '<a class="item link-item" href="' . $link_href . '" data-paged="' . $i . '">' . $i . '</a>';
                } elseif($show_hidden_button_after) {
                    $result .= $hidden_button;
                    $hidden_after = true;
                    $i = $total_page - 1;
                } else {
                    $link_href = fansub_get_pagenum_link(array('pagenum' => $i, 'request' => $request));
                    $result .= '<a class="item link-item" href="' . $link_href . '" data-paged="' . $i . '">' . $i . '</a>';
                }
            }
        }
    }
    return $result;
}

function fansub_get_paged() {
    return absint(get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
}

function fansub_pagination($args = array()) {
    fansub_show_pagination($args);
}

function fansub_term_pagination($args = array()) {
    $query_vars = fansub_get_value_by_key($args, 'query_vars', array());
    $posts_per_page = fansub_get_value_by_key($query_vars, 'number', fansub_get_posts_per_page());
    $offset = absint(fansub_get_value_by_key($query_vars, 'offset'));
    $paged = fansub_get_paged();
    $taxonomy = fansub_get_value_by_key($args, 'taxonomy', 'category');
    if($paged > 1) {
        $offset = ($paged - 1) * $posts_per_page;
    }
    $term_args = array(
        'number' => 0,
        'hide_empty' => false
    );
    $terms = fansub_get_terms($taxonomy, $term_args);
    $total_page = 1;
    if(0 < $posts_per_page) {
        $total_page = ceil(count($terms) / $posts_per_page);
    }
    $args['current_page'] = $paged;
    $args['total_page'] = $total_page;
    $class = fansub_get_value_by_key($args, 'class');
    fansub_add_string_with_space_before($class, 'term-pagination');
    $args['class'] = $class;
    $result = fansub_build_pagination($args);
    fansub_pagination_before($args);
    echo $result;
    fansub_pagination_after();
}

function fansub_get_last_paged($query = null) {
    if(!is_a($query, 'WP_Query')) {
        $query = $GLOBALS['wp_query'];
    }
    $total_page = fansub_get_total_page(array('query' => $query));
    return $total_page;
}

function fansub_is_last_paged($query = null, $paged = '') {
    if(empty($paged)) {
        $paged = fansub_get_paged();
    }
    $total_page = fansub_get_last_paged($query);
    if($paged == $total_page) {
        return true;
    }
    return false;
}