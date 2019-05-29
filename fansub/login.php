<?php
if(!function_exists('add_filter')) exit;

function fansub_login_body_class($classes, $action) {
    $classes[] = 'hocwp';
    if(!empty($action)) {
        $classes[] = 'action-' . $action;
    }
    return $classes;
}
add_filter('login_body_class', 'fansub_login_body_class', 10, 2);

function fansub_login_redirect_if_logged_in() {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if(empty($action) && is_user_logged_in()) {
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('login_init', 'fansub_login_redirect_if_logged_in');

function fansub_get_login_logo_url() {
    $user_login = fansub_option_get_object_from_list('user_login');
    $url = '';
    if(fansub_object_valid($user_login)) {
        $option = $user_login->get();
        $logo = fansub_get_value_by_key($option, 'logo');
        $logo = fansub_sanitize_media_value($logo);
        $url = $logo['url'];
    }
    if(empty($url)) {
        $theme_setting = fansub_option_get_object_from_list('theme_setting');
        if(fansub_object_valid($theme_setting)) {
            $option = $theme_setting->get();
            $logo = fansub_get_value_by_key($option, 'logo');
            $logo = fansub_sanitize_media_value($logo);
            $url = $logo['url'];
        }
    }
    return $url;
}

function fansub_use_captcha_for_login_page() {
    $options = get_option('fansub_user_login');
    $use_captcha = fansub_get_value_by_key($options, 'use_captcha');
    $use_captcha = apply_filters('fansub_use_captcha_for_login_page', $use_captcha);
    return (bool)$use_captcha;
}

function fansub_login_captcha_field() {
    echo fansub_login_get_captcha_field();
}

function fansub_login_get_captcha_field() {
    ob_start();
    $args = array(
        'before' => '<p>',
        'after' => '</p>'
    );
    fansub_field_captcha($args);
    return ob_get_clean();
}

function fansub_login_form_top() {
    ob_start();
    do_action('fansub_login_form_before');
    return ob_get_clean();
}

function fansub_login_form_middle() {
    ob_start();
    do_action('login_form');
    return ob_get_clean();
}

function fansub_login_form_bottom() {
    ob_start();
    do_action('fansub_login_form_after');
    return ob_get_clean();
}

function fansub_verify_login_captcha($user, $password) {
    if(isset($_POST['captcha'])) {
        $captcha_code = $_POST['captcha'];
        $captcha = new FANSUB_Captcha();
        if($captcha->check($captcha_code)) {
            return $user;
        }
        return new WP_Error(__('Captcha Invalid', 'fansub'), '<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('Please enter a valid captcha.', 'fansub'));
    }
    return new WP_Error(__('Captcha Invalid', 'fansub'), '<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('You are a robot, if not please check JavaScript enabled on your browser.', 'fansub'));
}

function fansub_verify_registration_captcha($errors, $sanitized_user_login, $user_email) {
    if(isset($_POST['captcha'])) {
        $captcha_code = $_POST['captcha'];
        $captcha = new FANSUB_Captcha();
        if(!$captcha->check($captcha_code)) {
            $errors->add(__('Captcha Invalid', 'fansub'), '<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('Please enter a valid captcha.', 'fansub'));
        }
    } else {
        $errors->add(__('Captcha Invalid', 'fansub'), '<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('You are a robot, if not please check JavaScript enabled on your browser.', 'fansub'));
    }
    return $errors;
}

function fansub_verify_lostpassword_captcha() {
    if(isset($_POST['captcha'])) {
        $captcha_code = $_POST['captcha'];
        $captcha = new FANSUB_Captcha();
        if(!$captcha->check($captcha_code)) {
            wp_die('<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('Please enter a valid captcha.', 'fansub'), __('Captcha Invalid', 'fansub'));
        }
    } else {
        wp_die('<strong>' . __('ERROR:', 'fansub') . '</strong> ' . __('You are a robot, if not please check JavaScript enabled on your browser.', 'fansub'), __('Captcha Invalid', 'fansub'));
    }
}

if(fansub_use_captcha_for_login_page()) {
    add_action('login_form', 'fansub_login_captcha_field');
    add_action('lostpassword_form', 'fansub_login_captcha_field');
    add_action('register_form', 'fansub_login_captcha_field');
    add_filter('wp_authenticate_user', 'fansub_verify_login_captcha', 10, 2);
    add_filter('registration_errors', 'fansub_verify_registration_captcha', 10, 3);
    add_action('lostpassword_post', 'fansub_verify_lostpassword_captcha');
}

add_filter('login_form_top', 'fansub_login_form_top');
add_filter('login_form_middle', 'fansub_login_form_middle');
add_filter('login_form_bottom', 'fansub_login_form_bottom');

function fansub_get_account_url($type = 'login', $action = '') {
    $url = '';
    $page_account = fansub_get_pages_by_template('page-templates/account.php', array('output' => 'object'));
    switch($type) {
        case 'signup':
        case 'register':
            $page = fansub_get_pages_by_template('page-templates/register.php', array('output' => 'object'));
            if(is_a($page, 'WP_Post')) {
                $url = get_permalink($page);
            } else {
                if(is_a($page_account, 'WP_Post')) {
                    $url = get_permalink($page_account);
                    $url = trailingslashit($url);
                    $url = add_query_arg(array('action' => 'register'), $url);
                }
            }
            break;
        case 'lostpassword':
            if(is_a($page_account, 'WP_Post')) {
                $url = get_permalink($page_account);
                $url = trailingslashit($url);
                $url = add_query_arg(array('action' => 'lostpassword'), $url);
            }
            break;
        default:
            if(empty($type) || 'account' === $type) {
                if(is_a($page_account, 'WP_Post')) {
                    $url = get_permalink($page_account);
                }
            } else {
                $page = fansub_get_pages_by_template('page-templates/login.php', array('output' => 'object'));
                if(is_a($page, 'WP_Post')) {
                    $url = get_permalink($page);
                } else {
                    if(is_a($page_account, 'WP_Post')) {
                        $url = get_permalink($page_account);
                        $url = trailingslashit($url);
                        if(empty($action)) {
                            $action = 'login';
                        }
                        $url = add_query_arg(array('action' => $action), $url);
                    }
                }
            }
    }
    return $url;
}

function fansub_user_force_login($user_id) {
    wp_set_auth_cookie($user_id, true);
}

function fansub_user_login($username, $password, $remember = true) {
    $credentials = array();
    $credentials['user_login'] = $username;
    $credentials['user_password'] = $password;
    $credentials['remember'] = $remember;
    $user = wp_signon($credentials, false);
    if(fansub_allow_user_login_with_email() && !is_a($user, 'WP_User')) {
        if(is_email($username) && email_exists($username)) {
            $new_user = get_user_by('email', $username);
            if(fansub_check_user_password($password, $new_user)) {
                $user = $new_user;
                fansub_user_force_login($new_user->ID);
            }
        }
    }
    return $user;
}

function fansub_account_form_default_args() {
    $lang = fansub_get_language();
    $defaults = array(
        'placeholder_username' => __('Username or email', 'fansub'),
        'placeholder_password' => __('Password', 'fansub'),
        'slogan' => 'One free account gets you into everything %s.',
        'title_lostpassword_link' => __('Password Lost and Found', 'fansub'),
        'text_lostpassword_link' => __('Lost your password?', 'fansub'),
        'text_register_link' => __('Register', 'fansub'),
        'label_email' => __('Email', 'fansub'),
        'label_confirm_password' => __('Confirm your password', 'fansub'),
        'label_phone' => __('Phone', 'fansub')
    );
    if('vi' == $lang) {
        $defaults['label_username'] = 'Tài khoản';
        $defaults['placeholder_username'] = 'Tên tài khoản hoặc email';
        $defaults['label_password'] = 'Mật khẩu';
        $defaults['placeholder_password'] = 'Mật khẩu';
        $defaults['label_remember'] = 'Ghi nhớ đăng nhập?';
        $defaults['label_log_in'] = 'Đăng nhập';
        $defaults['slogan'] = 'Một tài khoản dùng cho tất cả dịch vụ của %s.';
        $defaults['title_lostpassword_link'] = 'Nếu bạn đã quên mật khẩu thì vào đây để lấy lại';
        $defaults['text_lostpassword_link'] = 'Quên mật khẩu?';
        $defaults['text_register_link'] = 'Đăng ký';
        $defaults['label_confirm_password'] = 'Xác nhận mật khẩu';
        $defaults['label_phone'] = 'Điện thoại';
    }
    return apply_filters('fansub_account_form_default_args', $defaults);
}

function fansub_execute_register() {
    $http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
    $user_login = '';
    $user_email = '';
    $pwd = '';
    $pwd_again = '';
    $phone = '';
    $captcha = '';
    $error = false;
    $message = 'Đã có lỗi xảy ra, xin vui lòng thử lại.';
    $inserted = false;
    $user_id = 0;
    $registration_redirect = fansub_get_value_by_key($_REQUEST, 'redirect_to');
    $redirect_to = apply_filters('registration_redirect', $registration_redirect);
    if(is_user_logged_in()) {
        if(empty($redirect_to)) {
            $redirect_to = home_url('/');
        }
        wp_redirect($redirect_to);
        exit;
    }
    $transient = '';
    if($http_post) {
        $action = fansub_get_method_value('action');
        if('register' === $action) {
            $user_login = fansub_get_method_value('user_login');
            $user_email = fansub_get_method_value('user_email');
            $pwd = fansub_get_method_value('pwd');
            $pwd_again = fansub_get_method_value('pwd_again');
            $phone = fansub_get_method_value('phone');
            $captcha = fansub_get_method_value('captcha');
            $user_login = sanitize_user($user_login, true);
            $user_email = sanitize_email($user_email);
            $transient_name = 'fansub_register_user_' . md5($user_email);
            if(false === ($transient = get_transient($transient_name))) {
                if(empty($user_login) || empty($user_email) || empty($pwd) || empty($pwd_again) || empty($phone) || empty($captcha)) {
                    $error = true;
                    $message = 'Xin vui lòng nhập đầy đủ thông tin đăng ký.';
                } elseif(!is_email($user_email)) {
                    $error = true;
                    $message = 'Địa chỉ email không đúng.';
                } elseif($pwd !== $pwd_again) {
                    $error = true;
                    $message = 'Mật khẩu không khớp.';
                } elseif(username_exists($user_login)) {
                    $error = true;
                    $message = 'Tài khoản đã tồn tại.';
                } elseif(email_exists($user_email)) {
                    $error = true;
                    $message = 'Địa chỉ email đã tồn tại.';
                } else {
                    $capt = new FANSUB_Captcha();
                    if(!$capt->check($captcha)) {
                        $error = true;
                        $message = 'Mã bảo mật không đúng.';
                    }
                }
                if(!$error) {
                    $user_data = array(
                        'username' => $user_login,
                        'password' => $pwd,
                        'email' => $user_email
                    );
                    $user = fansub_add_user($user_data);
                    if(fansub_id_number_valid($user)) {
                        update_user_meta($user, 'phone', $phone);
                        $inserted = true;
                        fansub_user_force_login($user);
                        $message = 'Tài khoản của bạn đã được tạo thành công.';
                        $user_id = $user;
                        set_transient($transient_name, $user_id);
                    }
                }
                if($inserted && !empty($redirect_to)) {
                    wp_redirect($redirect_to);
                    exit;
                }
            } else {
                if(fansub_id_number_valid($transient)) {
                    $inserted = true;
                    $message = 'Tài khoản của bạn đã được tạo thành công.';
                }
            }
        }
    }
    $result = array(
        'user_login' => $user_login,
        'user_email' => $user_email,
        'pwd' => $pwd,
        'pwd_again' => $pwd_again,
        'phone' => $phone,
        'captcha' => $captcha,
        'error' => $error,
        'message' => $message,
        'inserted' => $inserted,
        'redirect_to' => $redirect_to,
        'user_id' => $user_id,
        'transient' => $transient
    );
    return $result;
}

function fansub_register_form($args = array()) {
    $defaults = fansub_account_form_default_args();
    $args = wp_parse_args($args, $defaults);
    $data = fansub_execute_register();
    $user_login = $data['user_login'];
    $user_email = $data['user_email'];
    $pwd = $data['pwd'];
    $pwd_again = $data['pwd_again'];
    $phone = $data['phone'];
    $error = $data['error'];
    $message = $data['message'];
    $inserted = $data['inserted'];
    $redirect_to = $data['redirect_to'];
    $logo = fansub_get_value_by_key($args, 'logo', fansub_get_login_logo_url());
    ?>
    <div class="fansub-login-box module">
        <div class="module-header text-center">
            <?php
            if(!empty($logo)) {
                $a = new FANSUB_HTML('a');
                $a->set_href(home_url('/'));
                $a->set_class('logo');
                $img = new FANSUB_HTML('img');
                $img->set_image_alt('');
                $img->set_image_src($logo);
                $a->set_text($img->build());
                $a->output();
            }
            $slogan = new FANSUB_HTML('p');
            $slogan->set_class('slogan');
            $slogan->set_text(sprintf($args['slogan'], fansub_get_root_domain_name(home_url('/'))));
            $slogan->output();
            if(isset($_REQUEST['error']) || $error) {
                $message = fansub_build_message($message, 'danger');
                echo $message;
            } elseif($inserted || fansub_id_number_valid($data['transient'])) {
                $message = fansub_build_message($message, 'success');
                echo $message;
                fansub_auto_reload_script();
            }
            ?>
        </div>
        <div class="module-body">
            <h4 class="form-title">Đăng ký tài khoản</h4>
            <form name="registerform register-form signup-form" id="registerform" action="" method="post" novalidate="novalidate">
                <p>
                    <label for="user_login"><?php echo $args['label_username']; ?><br />
                        <input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr(wp_unslash($user_login)); ?>" size="20" /></label>
                </p>
                <p>
                    <label for="user_email"><?php echo $args['label_email']; ?><br />
                        <input type="email" name="user_email" id="user_email" class="input" value="<?php echo esc_attr( wp_unslash( $user_email ) ); ?>" size="25" /></label>
                </p>
                <p>
                    <label for="user_pass"><?php echo $args['label_password']; ?><br />
                        <input type="password" name="pwd" id="user_pass" class="input" value="<?php echo $pwd; ?>" size="20" /></label>
                </p>
                <p>
                    <label for="user_pass_again"><?php echo $args['label_confirm_password']; ?><br />
                        <input type="password" name="pwd_again" id="user_pass_again" class="input" value="<?php echo $pwd_again; ?>" size="20" /></label>
                </p>
                <p>
                    <label for="phone"><?php echo $args['label_phone']; ?><br />
                        <input type="text" name="phone" id="phone" class="input" value="<?php echo $phone; ?>" size="20" /></label>
                </p>
                <?php do_action('register_form'); ?>
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
                <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php echo $args['text_register_link']; ?>" /></p>
            </form>
        </div>
        <div class="module-footer">
            <div class="text-center">
                <p class="form-nav">
                    <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo $args['label_log_in']; ?></a>
                    <span class="sep">|</span>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" title="<?php echo $args['title_lostpassword_link']; ?>"><?php echo $args['text_lostpassword_link']; ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php
}

function fansub_login_form($args = array()) {
    $defaults = fansub_account_form_default_args();
    $args = wp_parse_args($args, $defaults);
    $placeholder = (bool)fansub_get_value_by_key($args, 'placeholder', false);
    $args['echo'] = false;
    $form = wp_login_form($args);
    if($placeholder) {
        $form = str_replace('name="log"', 'name="log" placeholder="' . $args['placeholder_username'] . '"', $form);
        $form = str_replace('name="pwd"', 'name="pwd" placeholder="' . $args['placeholder_password'] . '"', $form);
    }
    $logo = fansub_get_value_by_key($args, 'logo', fansub_get_login_logo_url());
    $hide_form = (bool)fansub_get_value_by_key($args, 'hide_form');
    ?>
    <div class="fansub-login-box module">
        <div class="module-header text-center">
            <?php
            if(!empty($logo)) {
                $a = new FANSUB_HTML('a');
                $a->set_href(home_url('/'));
                $a->set_class('logo');
                $img = new FANSUB_HTML('img');
                $img->set_image_alt('');
                $img->set_image_src($logo);
                $a->set_text($img->build());
                $a->output();
            }
            $slogan = new FANSUB_HTML('p');
            $slogan->set_class('slogan');
            $slogan->set_text(sprintf($args['slogan'], fansub_get_root_domain_name(home_url('/'))));
            $slogan->output();
            if(isset($_REQUEST['error'])) {
                echo '<p class="alert alert-danger">Đã có lỗi xảy ra, xin vui lòng thử lại.</p>';
            }
            ?>
        </div>
        <div class="module-body">
            <h4 class="form-title">Đăng nhập</h4>
            <?php
            if($hide_form) {
                $login_form_top = apply_filters('login_form_top', '', $args);
                $login_form_middle = apply_filters('login_form_middle', '', $args);
                $login_form_bottom = apply_filters('login_form_bottom', '', $args);
                $form = $login_form_top . $login_form_middle . $login_form_bottom;
                $form = fansub_wrap_tag($form, 'form', 'login-form fansub-login-form');
                echo $form;
            } else {
                echo $form;
            }
            ?>
        </div>
        <div class="module-footer">
            <div class="text-center">
                <p class="form-nav">
                    <?php
                    if(!isset($_GET['checkemail']) || !in_array($_GET['checkemail'], array('confirm', 'newpass'))) {
                        if(fansub_users_can_register()) {
                            $registration_url = sprintf('<a href="%s">%s</a>', esc_url(wp_registration_url()), $args['text_register_link']);
                            echo apply_filters('register', $registration_url) . '<span class="sep">|</span>';
                        }
                    }
                    ?>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" title="<?php echo $args['title_lostpassword_link']; ?>"><?php echo $args['text_lostpassword_link']; ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php
}