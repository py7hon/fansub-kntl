<?php
if(!function_exists('add_filter')) exit;
function fansub_lib_load_chosen() {
    wp_enqueue_script('chosen', FANSUB_URL . '/lib/chosen/chosen.jquery.min.js', array('jquery'), false, true);
    wp_enqueue_style('chosen-style', FANSUB_URL . '/lib/chosen/chosen.min.css');
}

function fansub_lib_admin_style_and_script() {
    global $pagenow;
    $use_chosen_select = apply_filters('fansub_use_chosen_select', false);
    if('widgets.php' == $pagenow || $use_chosen_select) {
        fansub_lib_load_chosen();
    }
}
add_action('admin_enqueue_scripts', 'fansub_lib_admin_style_and_script');