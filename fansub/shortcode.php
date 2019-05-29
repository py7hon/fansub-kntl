<?php
$use_shortcode = apply_filters('fansub_add_tiny_mce_shortcode_button', false);
if($use_shortcode && is_admin()) {
	new FANSUB_TinyMCE_Shortcode();
}