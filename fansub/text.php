<?php
if(!function_exists('add_filter')) exit;

function fansub_text($vi, $en) {
	$lang = fansub_get_language();
	if(function_exists('qtranxf_getLanguage')) {
		$lang = qtranxf_getLanguage();
	}
	if('vi' == $lang) {
		echo $vi;
	} else {
		echo $en;
	}
}

function fansub_get_text($lang, $args = array()) {
	$text = apply_filters('fansub_get_text', fansub_get_value_by_key($args, $lang), $lang, $args);
	return $text;
}

function fansub_text_error_default($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Đã có lỗi xảy ra, xin vui lòng thử lại!', 'fansub'),
		'en' => __('There was an error occurred, please try again!', 'fansub')
	));
	return apply_filters('fansub_text_error_default', $text, $lang);
}

function fansub_text_error_email_exists($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Địa chỉ email đã tồn tại!', 'fansub'),
		'en' => __('Email address already exists!', 'fansub')
	));
	return apply_filters('fansub_text_error_email_exists', $text, $lang);
}

function fansub_text_error_email_not_valid($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Địa chỉ email không đúng!', 'fansub'),
		'en' => __('The email address is not correct!', 'fansub')
	));
	return apply_filters('fansub_text_error_email_not_valid', $text, $lang);
}

function fansub_text_error_captcha_not_valid($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Mã bảo mật không đúng!', 'fansub'),
		'en' => __('The captcha code is not correct!', 'fansub')
	));
	return apply_filters('fansub_text_error_captcha_not_valid', $text, $lang);
}

function fansub_text_success_register_and_verify_email($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Bạn đã đăng ký thành công, xin vui lòng kiểm tra email để kích hoạt.', 'fansub'),
		'en' => __('You have successfully registered, please check your email for activation.', 'fansub')
	));
	return apply_filters('fansub_text_success_register_and_verify_email', $text, $lang);
}

function fansub_text_email_subject_verify_subscription($lang = 'vi') {
	$text = fansub_get_text($lang, array(
		'vi' => __('Kích hoạt địa chỉ email của bạn tại: %s', 'fansub'),
		'en' => __('Activate your Email Subscription to: %s', 'fansub')
	));
	$text = sprintf($text, get_bloginfo('name'));
	return apply_filters('fansub_text_email_subject_verify_subscription', $text, $lang);
}