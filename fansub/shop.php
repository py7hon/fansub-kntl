<?php
if(!function_exists('add_filter')) exit;
function fansub_register_post_type_product() {
    $args = array(
        'name' => __('Products', 'fansub'),
        'singular_name' => __('Product', 'fansub'),
        'slug' => 'product',
        'menu_icon' => 'dashicons-products'
    );
    fansub_register_post_type_normal($args);
}

function fansub_register_taxonomy_product_cat() {
    $args = array(
        'name' => __('Product Categories', 'fansub'),
        'singular_name' => __('Product Category', 'fansub'),
        'menu_name' => __('Categories', 'fansub'),
        'slug' => 'product_cat',
        'post_types' => 'product'
    );
    fansub_register_taxonomy($args);
}

function fansub_register_taxonomy_product_tag() {
    $args = array(
        'name' => __('Product Tags', 'fansub'),
        'singular_name' => __('Product Tag', 'fansub'),
        'menu_name' => __('Tags', 'fansub'),
        'slug' => 'product_tag',
        'post_types' => 'product'
    );
    fansub_register_taxonomy($args);
}

function fansub_shop_install_post_type_and_taxonomy() {
    if(fansub_wc_installed()) {
        return;
    }
    fansub_register_post_type_product();
    fansub_register_taxonomy_product_cat();
    fansub_register_taxonomy_product_tag();
}

function fansub_query_best_selling_product($args = array()) {
    $args['meta_key'] = 'total_sales';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'DESC';
    return fansub_query_product($args);
}

function fansub_get_product_cat_base() {
    $base = get_option('woocommerce_permalinks');
    $base = fansub_get_value_by_key($base, 'category_base');
    if(empty($base)) {
        $base = 'product-category';
    }
    return $base;
}

function fansub_get_product_tag_base() {
    $base = get_option('woocommerce_permalinks');
    $base = fansub_get_value_by_key($base, 'tag_base');
    if(empty($base)) {
        $base = 'product-tag';
    }
    return $base;
}

function fansub_get_product_base() {
    $page = fansub_wc_get_shop_page();
    $base = 'product';
    if(is_a($page, 'WP_Post')) {
        $base = $page->post_name;
    }
    return $base;
}

$fansub_shop_site = apply_filters('fansub_shop_site', false);

if(!(bool)$fansub_shop_site) {
    return;
}

function fansub_shop_after_setup_theme() {
    if(fansub_wc_installed()) {
        add_theme_support('woocommerce');
    }
}
add_action('after_setup_theme', 'fansub_shop_after_setup_theme');

function fansub_shop_pre_get_posts($query) {
    if($query->is_main_query()) {
        if(is_search()) {
            $query->set('post_type', 'product');
        }
    }
    return $query;
}
if(!is_admin()) add_action('pre_get_posts', 'fansub_shop_pre_get_posts');