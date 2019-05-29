<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'users.php';

$option_user_login = new FANSUB_Option(__('Login settings', 'fansub'), 'fansub_user_login');
$option_user_login->set_parent_slug($parent_slug);
$option_user_login->set_use_style_and_script(true);
$option_user_login->set_use_media_upload(true);
$option_user_login->add_field(array('id' => 'logo', 'title' => __('Logo', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
$option_user_login->add_field(array('id' => 'users_can_register', 'title' => __('Membership', 'fansub'), 'label' => __('Anyone can register', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'value' => fansub_users_can_register()));
$option_user_login->add_field(array('id' => 'use_captcha', 'title' => __('Captcha', 'fansub'), 'label' => __('Protect your site against bots by using captcha', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox'));
$option_user_login->init();
fansub_option_add_object_to_list($option_user_login);

function fansub_users_can_register() {
	$result = (bool)get_option('users_can_register');
	return $result;
}

function fansub_option_user_login_update($input) {
	$users_can_register = isset($input['users_can_register']) ? 1 : 0;
	if((bool)$users_can_register) {
		update_option('users_can_register', 1);
	} else {
		update_option('users_can_register', 0);
	}
}
add_action('fansub_sanitize_' . $option_user_login->get_option_name_no_prefix() . '_option', 'fansub_option_user_login_update');