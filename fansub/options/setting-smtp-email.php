<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'options-general.php';

$option_smtp = new FANSUB_Option(__('SMTP Email', 'fansub'), 'fansub_option_smtp_email');
$option_smtp->set_parent_slug($parent_slug);
$option_smtp->add_section(array('id' => 'smtp_option', 'title' => __('SMTP Options', 'fansub'), 'description' => __('These options only apply if you have chosen to send mail by SMTP above.', 'fansub')));
$option_smtp->add_section(array('id' => 'testing', 'title' => __('Configuration Testing', 'fansub'), 'description' => __('If you do not feel very confident with the above configuration, you can send a test mail to know the results.', 'fansub')));
$option_smtp->add_field(array('id' => 'mail_from', 'title' => __('From Email', 'fansub'), 'description' => __('You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', 'fansub')));
$option_smtp->add_field(array('id' => 'mail_from_name', 'title' => __('From Name', 'fansub'), 'description' => __('You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', 'fansub')));
$field_options = array(
	array(
		'id' => 'mailer_smtp',
		'label' => __('Send all WordPress emails via SMTP.', 'fansub'),
		'option_value' => 'smtp'
	),
	array(
		'id' => 'mailer_mail',
		'label' => __('Use the PHP mail() function to send emails.', 'fansub'),
		'option_value' => 'mail'
	)
);
$option_smtp->add_field(array('id' => 'mailer', 'title' => __('Mailer', 'fansub'), 'field_callback' => 'fansub_field_input_radio', 'options' => $field_options));
$field_options = array(
	array(
		'id' => 'mail_set_return_path',
		'label' => __('Set the return-path to match the From Email.', 'fansub')
	)
);
$option_smtp->add_field(array('id' => 'mail_set_return_path', 'title' => __('Return Path', 'fansub'), 'field_callback' => 'fansub_field_input_checkbox', 'options' => $field_options));
$option_smtp->add_field(array('id' => 'smtp_host', 'title' => __('SMTP Host', 'fansub'), 'default' => 'localhost', 'section' => 'smtp_option'));
$option_smtp->add_field(array('id' => 'smtp_port', 'title' => __('SMTP Port', 'fansub'), 'default' => 25, 'section' => 'smtp_option'));
$field_options = array(
	array(
		'id' => 'smtp_ssl_none',
		'label' => __('No encryption.', 'fansub'),
		'option_value' => 'none'
	),
	array(
		'id' => 'smtp_ssl_ssl',
		'label' => __('Use SSL encryption.', 'fansub'),
		'option_value' => 'ssl'
	),
	array(
		'id' => 'smtp_ssl_tls',
		'label' => __('Use TLS encryption. This is not the same as STARTTLS. For most servers SSL is the recommended option.', 'fansub'),
		'option_value' => 'tls'
	)
);
$option_smtp->add_field(array('id' => 'smtp_ssl', 'title' => __('Encryption', 'fansub'), 'field_callback' => 'fansub_field_input_radio', 'options' => $field_options, 'section' => 'smtp_option'));
$field_options = array(
	array(
		'id' => 'smtp_auth_true',
		'label' => __('Yes: Use SMTP authentication.', 'fansub'),
		'option_value' => 'true'
	),
	array(
		'id' => 'smtp_auth_false',
		'label' => __('No: Do not use SMTP authentication.', 'fansub'),
		'option_value' => 'false'
	)
);
$option_smtp->add_field(array('id' => 'smtp_auth', 'title' => __('Authentication', 'fansub'), 'field_callback' => 'fansub_field_input_radio', 'options' => $field_options, 'section' => 'smtp_option'));
$option_smtp->add_field(array('id' => 'smtp_user', 'title' => __('Username', 'fansub'), 'section' => 'smtp_option'));
$option_smtp->add_field(array('id' => 'smtp_pass', 'title' => __('Password', 'fansub'), 'section' => 'smtp_option', 'type' => 'password'));
$option_smtp->add_field(array('id' => 'to_email', 'title' => __('To', 'fansub'), 'section' => 'testing', 'description' => __('Type an email address here and then click Send Test to generate a test email.', 'fansub'), 'type' => 'email'));
$option_smtp->init();
fansub_option_add_object_to_list($option_smtp);

function fansub_sanitize_option_smtp_mail($input) {
	if(isset($input['to_email'])) {
		if(is_email($input['to_email'])) {
			set_transient('fansub_test_smtp_email', $input['to_email']);
		}
	}
	unset($input['to_email']);
	return $input;
}
add_filter('fansub_sanitize_option_' . $option_smtp->get_option_name_no_prefix(), 'fansub_sanitize_option_smtp_mail');

function fansub_option_smtp_mail_update($input) {
	return $input;
}
add_action('fansub_sanitize_' . $option_smtp->get_option_name_no_prefix() . '_option', 'fansub_option_smtp_mail_update');

function fansub_option_smtp_email_testing() {
	if(false !== ($email = get_transient('fansub_test_smtp_email'))) {
		if(is_email($email)) {
			unset($_GET['settings-updated']);
			$test_message = fansub_mail_test_smtp_setting($email);
			set_transient('fansub_test_smtp_email_message', $test_message);
			delete_transient('fansub_test_smtp_email');
			add_action('admin_notices', 'fansub_option_smtp_email_testing_message');
			unset($phpmailer);
		}
	}
}
add_action('admin_init', 'fansub_option_smtp_email_testing');

function fansub_option_smtp_email_testing_message() {
	if(false !== ($message = get_transient('fansub_test_smtp_email_message'))) {
		fansub_admin_notice(array('text' => $message));
		delete_transient('fansub_test_smtp_email_message');
	}
}