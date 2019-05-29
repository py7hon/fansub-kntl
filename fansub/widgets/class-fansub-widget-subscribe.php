<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Subscribe extends WP_Widget {
	public $args = array();
	public $admin_args;

	private function get_defaults() {
		$defaults = array(
			'button_text' => __('Subscribe', 'fansub'),
			'description' => '',
			'desc_position' => 'before',
			'desc_positions' => array(
				'before' => __('Before email field', 'fansub'),
				'after' => __('After email field', 'fansub')
			),
			'fields' => array(
				'email' => array(
					'label' => __('Email', 'fansub'),
					'placeholder' => __('Enter your email', 'fansub'),
					'required' => true
				),
				'name' => array(
					'label' => __('Name', 'fansub'),
					'placeholder' => __('Enter your name', 'fansub'),
					'required' => false
				),
				'phone' => array(
					'label' => __('Phone', 'fansub'),
					'placeholder' => __('Enter your phone number', 'fansub'),
					'required' => false
				)
			),
			'captcha' => false,
			'captcha_label' => __('Captcha', 'fansub'),
			'captcha_placeholder' => __('Enter captcha code', 'fansub'),
			'register' => false
		);
		$defaults = apply_filters('fansub_widget_subscribe_defaults', $defaults);
		$args = apply_filters('fansub_widget_subscribe_args', array());
		$args = wp_parse_args($args, $defaults);
		return $args;
	}

	public function __construct() {
		$this->args = $this->get_defaults();
		$this->admin_args = array(
			'id' => 'fansub_widget_subscribe',
			'name' => 'FANSUB Subscribe',
			'class' => 'fansub-subscribe-widget',
			'description' => __('Allow subscribe as user.', 'fansub'),
			'width' => 400
		);
		$this->admin_args = apply_filters('fansub_widget_subscribe_admin_args', $this->admin_args);
		parent::__construct($this->admin_args['id'], $this->admin_args['name'],
			array(
				'classname' => $this->admin_args['class'],
				'description' => $this->admin_args['description'],
			),
			array(
				'width' => $this->admin_args['width']
			)
		);
		add_filter('fansub_allow_user_subscribe', '__return_true');
		add_action('wp_ajax_fansub_widget_subscribe', array($this, 'fansub_widget_subscribe_ajax_callback'));
		add_action('wp_ajax_nopriv_fansub_widget_subscribe', array($this, 'fansub_widget_subscribe_ajax_callback'));
	}

	function fansub_widget_subscribe_ajax_callback() {
		$use_captcha = (bool)fansub_get_method_value('use_captcha');
		$captcha_code = fansub_get_method_value('captcha');
		$email = fansub_get_method_value('email');
		$name = fansub_get_method_value('name');
		$phone = fansub_get_method_value('phone');
		$register = (bool)fansub_get_method_value('register');
		$result = array(
			'success' => false,
			'message' => fansub_build_message(fansub_text_error_default(), 'danger')
		);
		$captcha_valid = true;
		if($use_captcha) {
			$captcha = new FANSUB_Captcha();
			$captcha_valid = $captcha->check($captcha_code);
		}
		if($captcha_valid) {
			if(is_email($email)) {
				if($register && email_exists($email)) {
					$result['message'] = fansub_build_message(fansub_text_error_email_exists(), 'danger');
				} else {
					$query = fansub_get_post_by_meta('subscriber_email', $email, array('post_type' => 'fansub_subscriber'));
					if($query->have_posts()) {
						$result['message'] = fansub_build_message(fansub_text_error_email_exists(), 'danger');
					} else {
						$post_title = '';
						if(!empty($name)) {
							$post_title .= $name;
						}
						if(empty($post_title)) {
							$post_title = $email;
						} else {
							$post_title .= ' - ' . $email;
						}
						$post_data = array(
							'post_type' => 'fansub_subscriber',
							'post_title' => $post_title,
							'post_status' => 'publish'
						);
						$post_id = fansub_insert_post($post_data);
						if(fansub_id_number_valid($post_id)) {
							update_post_meta($post_id, 'subscriber_name', $name);
							update_post_meta($post_id, 'subscriber_email', $email);
							update_post_meta($post_id, 'subscriber_phone', $phone);
							update_post_meta($post_id, 'subscriber_verified', 0);
							$active_key = fansub_generate_reset_key();
							update_post_meta($post_id, 'subscriber_active_key', $active_key);
							if($register) {
								$password = wp_generate_password();
								$user_data = array(
									'username' => $email,
									'email' => $email,
									'password' => $password
								);
								$user_id = fansub_add_user($user_data);
								if(fansub_id_number_valid($user_id)) {
									wp_send_new_user_notifications($user_id);
									update_post_meta($post_id, 'subscriber_user', $user_id);
									update_user_meta($user_id, 'subscriber_id', $post_id);
								}
							}
							$verify_link = fansub_generate_verify_link($active_key);
							fansub_send_mail_verify_email_subscription(fansub_text_email_subject_verify_subscription(), $email, $verify_link);
							$result['success'] = true;
							$result['message'] = fansub_build_message(fansub_text_success_register_and_verify_email(), 'success');
						}
					}
				}
			} else {
				$result['message'] = fansub_build_message(fansub_text_error_email_not_valid(), 'danger');
			}
		} else {
			$result['message'] = fansub_build_message(fansub_text_error_captcha_not_valid(), 'danger');
		}
		wp_send_json($result);
	}

	public function widget($args, $instance) {
		$register = fansub_get_value_by_key($instance, 'register', fansub_get_value_by_key($this->args, 'register'));
		$button_text = fansub_get_value_by_key($instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
		$captcha = (bool)fansub_get_value_by_key($instance, 'captcha', fansub_get_value_by_key($this->args, 'captcha'));
		if($captcha) {
			add_filter('fansub_use_session', '__return_true');
		}
		$description = fansub_get_value_by_key($instance, 'description', fansub_get_value_by_key($this->args, 'description'));
		$desc_position = fansub_get_value_by_key($instance, 'desc_position', fansub_get_value_by_key($this->args, 'desc_position'));
		$fields = $this->get_value_fields($instance);
		$all_fields = explode(',', $fields);
		fansub_widget_before($args, $instance);
		ob_start();
		?>
		<form class="subscribe-form fansub-subscribe-form" method="post" data-captcha="<?php echo fansub_bool_to_int($captcha); ?>" data-register="<?php echo fansub_bool_to_int($register); ?>">
			<?php
			echo '<div class="messages"></div>';
			if(!empty($description) && 'before' == $desc_position) {
				echo '<p class="description">' . $description . '</p>';
			}
			foreach($all_fields as $field_name) {
				$field = fansub_get_value_by_key($this->args['fields'], $field_name);
				if(fansub_array_has_value($field)) {
					$label = $this->get_value_field($instance, $field_name, 'label');
					$placeholder = $this->get_value_field($instance, $field_name, 'placeholder');
					$required = $this->get_value_field($instance, $field_name, 'required');
					$class = fansub_sanitize_html_class($field_name);
					$field_args = array(
						'id' => $this->get_field_id('subscribe_' . $field_name),
						'name' => $this->get_field_name('subscribe_' . $field_name),
						'value' => '',
						'label' => $label,
						'placeholder' => $placeholder,
						'required' => $required,
						'class' => 'form-control input-' . $class,
						'before' => '<div class="form-group field-' . $class . '">',
						'after' => '</div>'
					);
					fansub_field_input($field_args);
				}
			}
			if(!empty($description) && 'after' == $desc_position) {
				echo '<p class="description">' . $description . '</p>';
			}
			if($captcha) {
				$captcha_label = fansub_get_value_by_key($instance, 'captcha_label', fansub_get_value_by_key($this->args, 'captcha_label'));
				$captcha_placeholder = fansub_get_value_by_key($instance, 'captcha_placeholder', fansub_get_value_by_key($this->args, 'captcha_placeholder'));
				$field_args = array(
					'id' => $this->get_field_id('captcha'),
					'name' => $this->get_field_name('captcha'),
					'input_width' => '100%',
					'class' => 'form-control',
					'label' => $captcha_label,
					'placeholder' => $captcha_placeholder,
					'before' => '<div class="form-group field-captcha">',
					'after' => '</div>'
				);
				fansub_field_captcha($field_args);
			}
			$field_args = array(
				'type' => 'submit',
				'name' => 'submit',
				'value' => $button_text,
				'class' => 'form-control',
				'before' => '<div class="form-group field-submit">',
				'after' => '</div>'
			);
			fansub_field_input($field_args);
			fansub_loading_image(array('name' => 'icon-loading-long.gif'));
			?>
		</form>
		<?php
		$widget_html = ob_get_clean();
		$widget_html = apply_filters('fansub_widget_subscribe_html', $widget_html, $instance, $args, $this);
		echo $widget_html;
		fansub_widget_after($args, $instance);
	}

	public function get_value_fields($instance) {
		$fields = fansub_get_value_by_key($instance, 'fields', fansub_get_value_by_key($this->args, 'fields'));
		if(fansub_array_has_value($fields)) {
			$tmp = '';
			foreach($fields as $field_name => $field) {
				$tmp .= $field_name . ',';
			}
			$tmp = trim($tmp, ',');
			$fields = $tmp;
		}
		return $fields;
	}

	public function get_value_field($instance, $field_name, $key) {
		$real_name = 'subscribe_' . $field_name . '_' . $key;
		return fansub_get_value_by_key($instance, $real_name, fansub_get_value_by_key($this->args['fields'][$field_name], $key));
	}

	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$register = fansub_get_value_by_key($instance, 'register', fansub_get_value_by_key($this->args, 'register'));
		$button_text = fansub_get_value_by_key($instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
		$captcha = fansub_get_value_by_key($instance, 'captcha', fansub_get_value_by_key($this->args, 'captcha'));
		$captcha_label = fansub_get_value_by_key($instance, 'captcha_label', fansub_get_value_by_key($this->args, 'captcha_label'));
		$captcha_placeholder = fansub_get_value_by_key($instance, 'captcha_placeholder', fansub_get_value_by_key($this->args, 'captcha_placeholder'));
		$description = fansub_get_value_by_key($instance, 'description', fansub_get_value_by_key($this->args, 'description'));
		$desc_position = fansub_get_value_by_key($instance, 'desc_position', fansub_get_value_by_key($this->args, 'desc_position'));
		$fields = $this->get_value_fields($instance);
		$all_fields = explode(',', $fields);
		fansub_field_widget_before($this->admin_args['class']);
		fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

		$args = array(
			'id' => $this->get_field_id('fields'),
			'name' => $this->get_field_name('fields'),
			'value' => $fields,
			'label' => __('Fields:', 'fansub')
		);
		fansub_widget_field('fansub_field_input_text', $args);

		foreach($all_fields as $field_name) {
			$field = fansub_get_value_by_key($this->args['fields'], $field_name);
			if(fansub_array_has_value($field)) {
				foreach($field as $key => $data) {
					$field_label = fansub_uppercase_first_char($field_name);
					$field_label .= ' ' . strtolower($key);
					$field_callback = 'fansub_field_input_text';
					if('required' == $key) {
						$field_label .= '?';
						$field_callback = 'fansub_field_input_checkbox';
					} else {
						$field_label .= ':';
					}
					$field_value = $this->get_value_field($instance, $field_name, $key);
					$args = array(
						'id' => $this->get_field_id('subscribe_' . $field_name . '_' . $key),
						'name' => $this->get_field_name('subscribe_' . $field_name . '_' . $key),
						'value' => $field_value,
						'label' => $field_label
					);
					fansub_widget_field($field_callback, $args);
				}
			}
		}

		$args = array(
			'id' => $this->get_field_id('button_text'),
			'name' => $this->get_field_name('button_text'),
			'value' => $button_text,
			'label' => __('Button text:', 'fansub')
		);
		fansub_widget_field('fansub_field_input_text', $args);

		$args = array(
			'id' => $this->get_field_id('description'),
			'name' => $this->get_field_name('description'),
			'value' => $description,
			'label' => __('Description:', 'fansub')
		);
		fansub_widget_field('fansub_field_textarea', $args);

		$lists = $this->args['desc_positions'];
		$all_option = '';
		foreach($lists as $lkey => $lvalue) {
			$all_option .= fansub_field_get_option(array('value' => $lkey, 'text' => $lvalue, 'selected' => $desc_position));
		}
		$args = array(
			'id' => $this->get_field_id('desc_position'),
			'name' => $this->get_field_name('desc_position'),
			'value' => $desc_position,
			'all_option' => $all_option,
			'label' => __('Description position:', 'fansub'),
			'class' => 'desc-position'
		);
		fansub_widget_field('fansub_field_select', $args);

		if($captcha) {
			$args = array(
				'id' => $this->get_field_id('captcha_label'),
				'name' => $this->get_field_name('captcha_label'),
				'value' => $captcha_label,
				'label' => __('Captcha label:', 'fansub')
			);
			fansub_widget_field('fansub_field_input_text', $args);
			$args = array(
				'id' => $this->get_field_id('captcha_placeholder'),
				'name' => $this->get_field_name('captcha_placeholder'),
				'value' => $captcha_placeholder,
				'label' => __('Captcha placeholder:', 'fansub')
			);
			fansub_widget_field('fansub_field_input_text', $args);
		}

		$args = array(
			'id' => $this->get_field_id('captcha'),
			'name' => $this->get_field_name('captcha'),
			'value' => $captcha,
			'label' => __('Using captcha in form?', 'fansub')
		);
		fansub_widget_field('fansub_field_input_checkbox', $args);

		$args = array(
			'id' => $this->get_field_id('register'),
			'name' => $this->get_field_name('register'),
			'value' => $register,
			'label' => __('Add suscriber as a user?', 'fansub')
		);
		fansub_widget_field('fansub_field_input_checkbox', $args);

		fansub_field_widget_after();
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
		$instance['fields'] = $this->get_value_fields($new_instance);
		$all_fields = explode(',', $instance['fields']);
		foreach($all_fields as $field_name) {
			$field = fansub_get_value_by_key($this->args['fields'], $field_name);
			if(fansub_array_has_value($field)) {
				foreach($field as $key => $data) {
					$real_name = 'subscribe_' . $field_name . '_' . $key;
					$instance[$real_name] = $this->get_value_field($new_instance, $field_name, $key);
				}
			}
		}
		$instance['button_text'] = fansub_get_value_by_key($new_instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
		$instance['description'] = fansub_get_value_by_key($new_instance, 'description', fansub_get_value_by_key($this->args, 'description'));
		$instance['desc_position'] = fansub_get_value_by_key($new_instance, 'desc_position', $this->args['desc_position']);
		$instance['captcha'] = fansub_checkbox_post_data_value($new_instance, 'captcha', fansub_get_value_by_key($this->args, 'captcha'));
		$instance['captcha_label'] = fansub_get_value_by_key($new_instance, 'captcha_label', fansub_get_value_by_key($this->args, 'captcha_label'));
		$instance['captcha_placeholder'] = fansub_get_value_by_key($new_instance, 'captcha_placeholder', fansub_get_value_by_key($this->args, 'captcha_placeholder'));
		$instance['register'] = fansub_checkbox_post_data_value($new_instance, 'register', fansub_get_value_by_key($this->args, 'register'));
		return $instance;
	}
}