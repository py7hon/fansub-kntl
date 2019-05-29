<?php
if(!function_exists('add_filter')) exit;
function fansub_smtp_mail_defaults() {
    $defaults = array (
        'mail_from' => '',
        'mail_from_name' => '',
        'mailer' => 'smtp',
        'mail_set_return_path' => 'false',
        'smtp_host' => 'localhost',
        'smtp_port' => '25',
        'smtp_ssl' => 'none',
        'smtp_auth' => false,
        'smtp_user' => '',
        'smtp_pass' => ''
    );
    return $defaults;
}

function fansub_get_smtp_mail_data() {
    $defaults = fansub_smtp_mail_defaults();
    $option = fansub_get_option('option_smtp_email');
    $result = wp_parse_args($option, $defaults);
    return apply_filters('fansub_smtp_mail_args', $result);
}

function fansub_get_mail_from_name() {
    $data = fansub_get_smtp_mail_data();
    $name = get_bloginfo('name');
    if(isset($data['mail_from_name']) && !empty($data['mail_from_name'])) {
        $name = $data['mail_from_name'];
    }
    return $name;
}

function fansub_mail_from_name($name) {
    $name = fansub_get_mail_from_name();
    return $name;
}
add_filter('wp_mail_from_name', 'fansub_mail_from_name');

function fansub_get_mail_from() {
    $data = fansub_get_smtp_mail_data();
    $email = get_bloginfo('admin_email');
    if(isset($data['mail_from']) && !empty($data['mail_from'])) {
        $email = $data['mail_from'];
    }
    return $email;
}

function fansub_mail_from($email) {
    $email = fansub_get_mail_from();
    return $email;
}
add_filter('wp_mail_from', 'fansub_mail_from');

function fansub_get_mailer() {
    $data = fansub_get_smtp_mail_data();
    $mailer = fansub_get_value_by_key($data, 'mailer');
    return apply_filters('fansub_mailer', $mailer);
}

function fansub_phpmailer_init_change_info($phpmailer) {
    $data = fansub_get_smtp_mail_data();
    if(empty($data['mailer'])) {
        return;
    }
    if('smtp' == $data['mailer'] && empty($data['smtp_host'])) {
        return;
    }
    $phpmailer->Mailer = $data['mailer'];
    if((bool)$data['mail_set_return_path']) {
        $phpmailer->Sender = $phpmailer->From;
    }
    $phpmailer->SMTPSecure = ($data['smtp_ssl'] == 'none') ? '' : $data['smtp_ssl'];
    if('smtp' == $data['mailer']) {
        $phpmailer->Host = $data['smtp_host'];
        $phpmailer->Port = $data['smtp_port'];
        if(fansub_string_to_bool($data['smtp_auth'])) {
            $phpmailer->SMTPAuth = TRUE;
            $phpmailer->Username = $data['smtp_user'];
            $phpmailer->Password = $data['smtp_pass'];
        }
    }
    $phpmailer = apply_filters('fansub_phpmailer', $phpmailer);
}
add_action('phpmailer_init', 'fansub_phpmailer_init_change_info');

function fansub_mail_test_smtp_setting($to_email) {
    global $phpmailer;
    if(!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
        require(ABSPATH . WPINC . '/class-phpmailer.php');
        require(ABSPATH . WPINC . '/class-smtp.php');
        $phpmailer = new PHPMailer(true);
    }
    $subject = __('SMTP Email', 'hocwp') . ': ' . sprintf(__('Test mail to %s', 'hocwp'), $to_email);
    $message = __('Thank you for using HocWP, your SMTP mail settings work successfully.', 'hocwp');
    $phpmailer->SMTPDebug = true;
    ob_start();
    $result = wp_mail($to_email, $subject, $message);
    $smtp_debug = ob_get_clean();
    $test_message = '<p><strong>' . __('Test Message Sent', 'hocwp') . '</strong></p>';
    ob_start();
    var_dump($result);
    $result = ob_get_clean();
    $test_message .= '<p>' . sprintf(__('The result was: %s', 'hocwp'), $result) . '</p>';
    $test_message .= '<p>' . __('The full debugging output is shown below:', 'hocwp') . '</p>';
    ob_start();
    var_dump($phpmailer);
    $mailer_debug = ob_get_clean();
    $test_message .= '<pre>' . $mailer_debug . '</pre>';
    $test_message .= '<p>' . __('The SMTP debugging output is shown below:', 'hocwp') . '</p>';
    $test_message .= '<pre>' . $smtp_debug . '</pre>';
    return $test_message;
}

function fansub_set_html_mail_content_type() {
    return 'text/html';
}

function fansub_send_html_mail($to, $subject, $message, $headers = '', $attachments = '') {
    $result = false;
    $mailer = fansub_get_mailer();
    if('smtp' == $mailer) {
        add_filter('wp_mail_content_type', 'fansub_set_html_mail_content_type');
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        remove_filter('wp_mail_content_type', 'fansub_set_html_mail_content_type');
    } else {
        $result = fansub_send_mail($to, $subject, $message);
    }
    return $result;
}

function fansub_build_html_mail_headers(&$headers = '') {
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    return $headers;
}

function fansub_build_mail_headers(&$headers = '') {
    $from_name = fansub_get_mail_from_name();
    $from = fansub_get_mail_from();
    $headers .= "From: " . $from_name . " < " . $from . " >\r\n";
    fansub_build_html_mail_headers($headers);
    $headers = apply_filters('fansub_mail_headers', $headers);
    return $headers;
}

function fansub_send_mail($to, $subject, $message) {
    $mailer = fansub_get_mailer();
    if('smtp' == $mailer) {
        $result = fansub_send_html_mail($to, $subject, $message);
    } else {
        $headers = fansub_build_mail_headers();
        $result = mail($to, $subject, $message, $headers);
    }
    return $result;
}

function fansub_send_mail_invalid_license($project_name, $type = 'Theme') {
    $transient_name = 'fansub_mail_invalid_license_' . $type . '_' . $project_name;
    $transient_name = md5($transient_name);
    if(false === get_transient($transient_name)) {
        $subject = get_bloginfo('name');
        $subject .= ' vi phạm bản quyền';
        $message = wpautop('Địa chỉ website: ' . get_bloginfo('url'));
        $message .= wpautop('Admin email: ' . fansub_get_admin_email());
        $message .= wpautop('Thể loại: ' . $type);
        $message .= wpautop('Tên dự án: ' . $project_name);
        fansub_send_html_mail(FANSUB_EMAIL, $subject, $message);
        set_transient($transient_name, 1, DAY_IN_SECONDS);
    }
}

function fansub_send_mail_verify_email_subscription($subject, $to_email, $verify_link) {
    $message = '<p>You received this message because someone requested an email subscription for ' . $to_email . ' to %SITE_NAME%.  If you did not make this request, please ignore the rest of this message.</p>';
    $message = fansub_replace_text_placeholder($message);
    $message .= '<br>';
    $message .= '<p>Please click here to verify your email: ' . $verify_link . '</p>';
    fansub_send_html_mail($to_email, $subject, $message);
}