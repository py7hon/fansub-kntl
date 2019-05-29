<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'themes.php';

$option_theme_license = new FANSUB_Option(__('Theme license', 'fansub'), 'fansub_theme_license');
$option_theme_license->set_parent_slug($parent_slug);
$option_theme_license->add_field(array('id' => 'customer_email', 'title' => __('Customer email', 'fansub')));
$option_theme_license->add_field(array('id' => 'license_code', 'title' => __('License code', 'fansub')));
$option_theme_license->add_help_tab(array(
	'id' => 'overview',
	'title' => __('Overview', 'fansub'),
	'content' => '<p>' . sprintf(__('Thank you for using WordPress theme by %s.', 'fansub'), FANSUB_NAME) . '</p>' .
	             '<p>' . __('With each theme, you will receive a license code to activate it. Please enter your theme license information into the form below, if you do not have one, please contact the author to get new code.', 'fansub') . '</p>'
));
$option_theme_license->set_help_sidebar(
	'<p><strong>' . __('For more information:', 'fansub') . '</strong></p>' .
	'<p><a href="https://fb.gg/composer.json" target="_blank">' . __('IQBAL RIFAI', 'fansub') . '</a></p>' .
	'<p><a href="https://nhent.ai" target="_blank">' . __('nHentai', 'fansub') . '</a></p>'
);
$option_theme_license->init();
fansub_option_add_object_to_list($option_theme_license);

function fansub_theme_license_option_saved($option) {
	if(is_a($option, 'FANSUB_Option')) {
		fansub_delete_transient_license_valid();
	}
}
add_action($option_theme_license->get_menu_slug() . '_option_saved', 'fansub_theme_license_option_saved');