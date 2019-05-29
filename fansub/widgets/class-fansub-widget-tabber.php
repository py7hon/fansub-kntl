<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Tabber extends WP_Widget {
	public $args = array();
	public $admin_args;

	private function get_defaults() {
		$defaults = array();
		$defaults = apply_filters('fansub_widget_tabber_defaults', $defaults);
		$args = apply_filters('fansub_widget_tabber_args', array());
		$args = wp_parse_args($args, $defaults);
		return $args;
	}

	public function __construct() {
		$this->args = $this->get_defaults();
		$this->admin_args = array(
			'id' => 'fansub_widget_tabber',
			'name' => 'FANSUB Tabber',
			'class' => 'fansub-tabber-widget',
			'description' => __('Display widgets as tabber on sidebar.', 'fansub'),
			'width' => 400
		);
		$this->admin_args = apply_filters('fansub_widget_tabber_admin_args', $this->admin_args);
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

	public function dynamic_sidebar_params($params) {
		$widget_id = $params[0]['widget_id'];
		$widget_class = fansub_build_widget_class($widget_id);
		$params[0]['before_widget'] = '<div id="' . $widget_id . '" class="tab-item tab-pane ' . $widget_class . '">';
		$params[0]['after_widget'] = '</div>';
		$params[0]['before_title'] = '<a href="#" class="tab-title" data-toggle="tab">';
		$params[0]['after_title'] = '</a>';
		return $params;
	}

	public function widget($args, $instance) {
		add_filter('dynamic_sidebar_params', array($this, 'dynamic_sidebar_params'));
		$sidebar = fansub_get_value_by_key($instance, 'sidebar');
		fansub_widget_before($args, $instance, false);
		if(empty($sidebar)) {
			echo '<p>'.__('Please select the sidebar containing the widget tabs first.', 'fansub').'</p>';
		} elseif($args['id'] != $sidebar) { ?>
			<div class="fansub-tab-content">
				<ul class="nav nav-tabs list-tab fansub-tabs"></ul>
				<div class="tab-content fansub-tab-container">
					<?php
					if(is_active_sidebar($sidebar)) {
						dynamic_sidebar($sidebar);
					} else {
						$sidebar_tmp = fansub_get_sidebar_by('id', $sidebar);
						$sidebar_name = '';
						if($sidebar_tmp) {
							$sidebar_name = $sidebar_tmp['name'];
						}
						?>
						<p><?php printf(__('Please drag the widgets to display into the sidebar %s.', 'fansub'), $sidebar_name); ?></p>
						<?php
					}
					?>
				</div>
			</div>
		<?php }
		fansub_widget_after($args, $instance);
		remove_filter('dynamic_sidebar_params', array($this, 'dynamic_sidebar_params'));
	}

	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$sidebar = fansub_get_value_by_key($instance, 'sidebar');
		fansub_field_widget_before($this->admin_args['class']);
		fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

		$args = array(
			'id' => $this->get_field_id('sidebar'),
			'name' => $this->get_field_name('sidebar'),
			'value' => $sidebar,
			'label' => __('Sidebar:', 'fansub')
		);
		fansub_widget_field('fansub_field_select_sidebar', $args);

		fansub_field_widget_after();
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
		$instance['sidebar'] = fansub_get_value_by_key($new_instance, 'sidebar');
		return $instance;
	}
}