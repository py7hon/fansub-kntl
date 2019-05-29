<?php
if(!function_exists('add_filter')) exit;

function fansub_qtranslate_x_installed() {
	return defined('QTX_VERSION');
}

function fansub_qtranslate_x_admin_sections($sections) {
	$sections['fansub_string_translation'] = __('String Translation', 'fansub');
	return $sections;
}

function fansub_qtranslate_x_admin_section_field() {
	qtranxf_admin_section_start('fansub_string_translation');
	echo '<br>';
	$table = new FANSUB_Table_String_Translation(fansub_get_all_mo_posts());
	$table->prepare_items();
	$table->search_box(__('Search translations', 'fansub'), 'translations');
	$table->display();
	fansub_field_input_hidden(array('id' => 'fansub_action', 'value' => 'string_translation'));
	qtranxf_admin_section_end('fansub_string_translation');
}

if(fansub_qtranslate_x_installed()) {
	add_filter('qtranslate_admin_sections', 'fansub_qtranslate_x_admin_sections');
	add_action('qtranslate_configuration', 'fansub_qtranslate_x_admin_section_field');
}

function fansub_get_all_mo_posts($args = array()) {
	$defaults = array(
		'post_type' => 'fansub_mo',
		'posts_per_page' => -1
	);
	$args = wp_parse_args($args, $defaults);
	$query = fansub_query($args);
	return $query->posts;
}

function fansub_get_qtranslate_x_config() {
	return $GLOBALS['q_config'];
}

function fansub_get_qtranslate_x_enabled_languages() {
	return qtranxf_getSortedLanguages();
}

function fansub_get_registered_string_language() {
	$strings = get_option('fansub_string_translations');
	if(!is_array($strings)) {
		$strings = array();
	}
	$strings = apply_filters('fansub_registered_string_language', $strings);
	return $strings;
}

function fansub_get_active_registered_string_language() {
	global $fansub_active_registered_string_translations;
	if(!is_array(($fansub_active_registered_string_translations))) {
		$fansub_active_registered_string_translations = array();
	}
	return apply_filters('fansub_active_registered_string_language', $fansub_active_registered_string_translations);
}

function fansub_register_string_language($args = array()) {
	if(!did_action('init')) {
		_doing_it_wrong(__FUNCTION__, __('Please call this function in <strong>fansub_register_string_translation</strong> hook.', 'fansub'), FANSUB_VERSION);
		return;
	}
	$name = fansub_get_value_by_key($args, 'name');
	$string = fansub_get_value_by_key($args, 'string');
	$context = fansub_get_value_by_key($args, 'context', 'HocWP');
	$multiline = fansub_get_value_by_key($args, 'multiline');
	$key = md5($string);
	$active_strings = fansub_get_active_registered_string_language();
	$active_strings[$key]['name'] = $name;
	$active_strings[$key]['string'] = $string;
	$active_strings[$key]['context'] = $context;
	$active_strings[$key]['multiline'] = $multiline;
	$GLOBALS['fansub_active_registered_string_translations'] = $active_strings;
	$transient_name = 'fansub_string_translation_registered_' . serialize($args);
	if(false === get_transient($transient_name)) {
		$strings = fansub_get_registered_string_language();
		$strings[$key]['name'] = $name;
		$strings[$key]['string'] = $string;
		$strings[$key]['context'] = $context;
		$strings[$key]['multiline'] = $multiline;
		update_option('fansub_string_translations', $strings);
		$mo = new FANSUB_MO();
		$post_id = $mo->export_to_db($string);
		if(fansub_id_number_valid($post_id)) {
			set_transient($transient_name, $post_id, WEEK_IN_SECONDS);
		}
	}
}

function fansub_translate_x_string_transaltion_update() {
	if(isset($_REQUEST['fansub_action'])) {
		$search = fansub_get_method_value('s', 'request');
		$strings = fansub_get_method_value('strings');
		if(fansub_array_has_value($strings)) {
			$mo = new FANSUB_MO();
			$saved_strings = fansub_get_registered_string_language();
			foreach($strings as $encrypted_string) {
				unset($saved_strings[$encrypted_string]);
				$mo->delete_from_db($encrypted_string, true);
			}
			update_option('fansub_string_translations', $saved_strings);
			fansub_delete_transient('fansub_string_translation_registered');
		}
		$args = array_intersect_key($_REQUEST, array_flip(array('s', 'paged', 'group')));
		if(!empty($search)) {
			$args['s'] = $search;
		}
		if(!empty($args['s'])) {
			$args['s'] = urlencode($args['s']);
		}
		$translations = fansub_get_method_value('translation');
		if(fansub_array_has_value($translations)) {
			foreach($translations as $key => $value) {
				if(!empty($value)) {
					$mo = fansub_get_post_by_column('post_title', 'fansub_mo_' . $key, OBJECT, array('post_type' => 'fansub_mo'));
					if(is_a($mo, 'WP_Post')) {
						$obj = new FANSUB_MO($mo->ID);
						$obj->export_to_db($mo->post_excerpt, $value);
					}
				}
			}
		}
		$url = add_query_arg($args, wp_get_referer());
		wp_safe_redirect($url);
		exit;
	}
}
add_action('admin_init', 'fansub_translate_x_string_transaltion_update');

function fansub_language_register_hook() {
	do_action('fansub_register_string_translation');
}
add_action('wp_loaded', 'fansub_language_register_hook');

function fansub_language_admin_enqueue_scripts($hook) {
	if('settings_page_qtranslate-x' == $hook) {
		add_filter('fansub_use_admin_style_and_script', '__return_true');
	}
}
add_action('admin_enqueue_scripts', 'fansub_language_admin_enqueue_scripts');