<?php
if(!function_exists('add_filter')) exit;
if(!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class FANSUB_Table_String_Translation extends WP_List_Table {
	protected $languages, $strings, $active_strings, $groups, $selected_group;

	function __construct() {
		parent::__construct(array(
			'plural' => 'String Translation',
			'ajax' => false
		));
		$this->languages = fansub_get_qtranslate_x_enabled_languages();
		$this->strings = fansub_get_registered_string_language();
		$this->active_strings = fansub_get_active_registered_string_language();
		if(!is_array($this->active_strings)) {
			$this->active_strings = array();
		}
		$this->groups = array_unique(wp_list_pluck($this->strings, 'context'));
		$this->selected_group = empty($_GET['group']) || !in_array($_GET['group'], $this->groups) ? -1 : $_GET['group'];
		$this->save_translations();
	}

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox">',
			'string' => __('String', 'fansub'),
			'name' => __('Name', 'fansub'),
			'context' => __('Group', 'fansub'),
			'translations' => __('Translation', 'fansub'),
		);
		return $columns;
	}

	function get_sortable_columns() {
		return array(
			'string' => array('string', false),
			'name' => array('name', false),
			'context' => array('context', false),
		);
	}

	protected function usort_reorder($a, $b) {
		$result = strcmp($a[$_GET['orderby']], $b[$_GET['orderby']]);
		return (empty($_GET['order']) || 'asc' === $_GET['order']) ? $result : -$result;
	}

	function prepare_items() {
		$data = $this->strings;
		$active_data = $this->active_strings;
		$s = empty($_GET['s']) ? '' : wp_unslash($_GET['s']);
		foreach($data as $key => $row) {
			if((-1 !== $this->selected_group && $row['context'] !== $this->selected_group) || (!empty($s) && stripos($row['name'], $s) === false && stripos($row['string'], $s) === false)) {
				unset($data[$key]);
			}
		}
		$count = 0;
		foreach($data as $key => $row) {
			$data[$key]['row'] = $count;
			$mo = new FANSUB_MO();
			$translation = $mo->import_from_db($row['string']);
			$data[$key]['translation'] = $translation;
			if(!isset($active_data[$key])) {
				$data[$key]['bulk_action'] = true;
			}
			$count++;
		}
		$per_page = $this->get_items_per_page('fansub_string_translation_posts_per_page');
		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
		if(!empty($_GET['orderby'])) {
			usort($data, array(&$this, 'usort_reorder'));
		}
		$total_items = count($data);
		$this->items = array_slice($data, ($this->get_pagenum() - 1) * $per_page, $per_page);
		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}

	function get_bulk_actions() {
		return array('delete' => __('Delete', 'fansub'));
	}

	public function current_action() {
		return empty($_POST['submit']) ? parent::current_action() : false;
	}

	function extra_tablenav($which) {
		if('top' !== $which) {
			return;
		}
		echo '<div class="alignleft actions">';
		printf('<label class="screen-reader-text" for="select-group" >%s</label>', __( 'Filter by group', 'fansub'));
		echo '<select id="select-group" name="group">' . "\n";
		printf(
			'<option value="-1"%s>%s</option>' . "\n",
			-1 === $this->group_selected ? ' selected="selected"' : '',
			__('View all groups', 'fansub')
		);
		foreach($this->groups as $group) {
			printf(
				'<option value="%s"%s>%s</option>' . "\n",
				esc_attr(urlencode($group)),
				$this->selected_group === $group ? ' selected="selected"' : '',
				esc_html($group)
			);
		}
		echo '</select>'."\n";
		submit_button(__('Filter'), 'button', 'filter_action', false, array('id' => 'post-query-submit'));
		echo '</div>';
	}

	public function display_tablenav($which) {
		?>
		<div class="tablenav <?php echo esc_attr($which); ?>">
			<?php if($this->has_items()) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions($which); ?>
				</div>
			<?php endif;
			$this->extra_tablenav($which);
			$this->pagination($which);
			?>
			<br class="clear">
		</div>
		<?php
	}

	public function save_translations() {
	}

	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	function column_cb($item) {
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$s">%2$s</label><input id="cb-select-%1$s" type="checkbox" name="strings[]" value="%3$s" %4$s>',
			esc_attr(fansub_get_value_by_key($item, 'row')),
			sprintf(__('Select %s'), format_to_edit($item['string'])),
			md5($item['string']),
			empty($item['bulk_action']) ? 'disabled' : ''
		);
	}

	function column_string($item) {
		return format_to_edit($item['string']);
	}

	function column_translations($item) {
		$out = '';
		$string = fansub_get_value_by_key($item, 'string');
		if(!empty($string)) {
			$input_type = $item['multiline'] ?
				'<textarea name="translation[%1$s]" id="%1$s-%2$s" class="widefat">%3$s</textarea>' :
				'<input type="text" name="translation[%1$s]" id="%1$s-%2$s" class="widefat" value="%3$s" />';
			$out .= sprintf(
				$input_type,
				esc_attr(md5($string)),
				esc_attr($item['row']),
				format_to_edit($item['translation'])
			);
		}
		return $out;
	}
}