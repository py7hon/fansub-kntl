<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_FeedBurner extends WP_Widget {
	public $args = array();
	public $admin_args;

	private function get_defaults() {
		$defaults = array(
			'button_text' => __('Subscribe', 'fansub'),
			'placeholder' => __('Enter your email', 'fansub'),
			'description' => '',
			'desc_position' => 'before',
			'desc_positions' => array(
				'before' => __('Before email field', 'fansub'),
				'after' => __('After email field', 'fansub')
			)
		);
		$defaults = apply_filters('fansub_widget_feedburner_defaults', $defaults);
		$args = apply_filters('fansub_widget_feedburner_args', array());
		$args = wp_parse_args($args, $defaults);
		return $args;
	}

	public function __construct() {
		$this->args = $this->get_defaults();
		$this->admin_args = array(
			'id' => 'fansub_widget_feedburner',
			'name' => 'FANSUB FeedBurner',
			'class' => 'fansub-feedburner-widget',
			'description' => __('Display FeedBurner subscription box on sidebar.', 'fansub'),
			'width' => 400
		);
		$this->admin_args = apply_filters('fansub_widget_feedburner_admin_args', $this->admin_args);
		parent::__construct($this->admin_args['id'], $this->admin_args['name'],
			array(
				'classname' => $this->admin_args['class'],
				'description' => $this->admin_args['description'],
			),
			array(
				'width' => $this->admin_args['width']
			)
		);
	}

	public function widget($args, $instance) {
		$feedburner_name = fansub_get_value_by_key($instance, 'feedburner_name');
		if(!empty($feedburner_name)) {
			$button_text = fansub_get_value_by_key($instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
			$placeholder = fansub_get_value_by_key($instance, 'placeholder', fansub_get_value_by_key($this->args, 'placeholder'));
			$description = fansub_get_value_by_key($instance, 'description', fansub_get_value_by_key($this->args, 'description'));
			$desc_position = fansub_get_value_by_key($instance, 'desc_position', fansub_get_value_by_key($this->args, 'desc_position'));
			fansub_widget_before($args, $instance);
			ob_start();
			if(!empty($description) && 'before' == $desc_position) {
				echo '<p class="description">' . $description . '</p>';
			}
			$fb_args = array(
				'button_text' => $button_text,
				'name' => $feedburner_name,
				'placeholder' => $placeholder
			);
			fansub_feedburner_form($fb_args);
			if(!empty($description) && 'after' == $desc_position) {
				echo '<p class="description">' . $description . '</p>';
			}
			$widget_html = ob_get_clean();
			$widget_html = apply_filters('fansub_widget_feedburner_html', $widget_html, $instance, $args, $this);
			echo $widget_html;
			fansub_widget_after($args, $instance);
		}
	}

	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$feedburner_name = fansub_get_value_by_key($instance, 'feedburner_name');
		$button_text = fansub_get_value_by_key($instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
		$placeholder = fansub_get_value_by_key($instance, 'placeholder', fansub_get_value_by_key($this->args, 'placeholder'));
		$description = fansub_get_value_by_key($instance, 'description', fansub_get_value_by_key($this->args, 'description'));
		$desc_position = fansub_get_value_by_key($instance, 'desc_position', fansub_get_value_by_key($this->args, 'desc_position'));
		fansub_field_widget_before($this->admin_args['class']);
		fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

		$args = array(
			'id' => $this->get_field_id('feedburner_name'),
			'name' => $this->get_field_name('feedburner_name'),
			'value' => $feedburner_name,
			'label' => __('Name:', 'fansub')
		);
		fansub_widget_field('fansub_field_input_text', $args);

		$args = array(
			'id' => $this->get_field_id('button_text'),
			'name' => $this->get_field_name('button_text'),
			'value' => $button_text,
			'label' => __('Button text:', 'fansub')
		);
		fansub_widget_field('fansub_field_input_text', $args);

		$args = array(
			'id' => $this->get_field_id('placeholder'),
			'name' => $this->get_field_name('placeholder'),
			'value' => $placeholder,
			'label' => __('Placeholder:', 'fansub')
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

		fansub_field_widget_after();
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
		$instance['feedburner_name'] = fansub_get_value_by_key($new_instance, 'feedburner_name');
		$instance['button_text'] = fansub_get_value_by_key($new_instance, 'button_text', fansub_get_value_by_key($this->args, 'button_text'));
		$instance['placeholder'] = fansub_get_value_by_key($new_instance, 'placeholder', fansub_get_value_by_key($this->args, 'placeholder'));
		$instance['description'] = fansub_get_value_by_key($new_instance, 'description', fansub_get_value_by_key($this->args, 'description'));
		$instance['desc_position'] = fansub_get_value_by_key($new_instance, 'desc_position', $this->args['desc_position']);
		return $instance;
	}
}