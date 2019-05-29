<?php
if(!function_exists('add_filter')) exit;
$icon_url = get_template_directory_uri() . '/fansub/images/icon-loading-circle-dark-full.gif';
$icon_url = apply_filters('fansub_ajax_loading_full_screen_icon', $icon_url);
$color = apply_filters('fansub_ajax_loading_full_screen_color', '#000');
$opacity = apply_filters('fansub_ajax_loading_full_screen_opacity', 0.75);
$main_color = $color;
$color = fansub_color_hex_to_rgb($color, $opacity);
$border_radius = apply_filters('fansub_ajax_loading_full_screen_icon_border_radius', 10);
$padding = apply_filters('fansub_ajax_loading_full_screen_icon_padding', 10);
$icon_color = apply_filters('fansub_ajax_loading_full_screen_icon_color', $main_color);
if($opacity < 1) {
    $opacity += 0.1;
}
$icon_opacity = apply_filters('fansub_ajax_loading_full_screen_icon_opacity', $opacity);
$icon_color = fansub_color_hex_to_rgb($icon_color, $icon_opacity);
?>
<div class="ajax-loading full-page" style="background-color: <?php echo $color; ?>">
    <img alt="" src="<?php echo $icon_url; ?>" style="background-color: <?php echo $icon_color; ?>; padding: <?php echo $padding . 'px'; ?>; border-radius: <?php echo $border_radius . 'px'; ?>">
</div>