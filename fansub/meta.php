<?php
if(!function_exists('add_filter')) exit;
function fansub_meta_table_registered($type) {
	return _get_meta_table($type);
}

function fansub_meta_box_post_attribute($post_types) {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		$post_type = fansub_get_current_post_type();
		if(is_array($post_type)) {
			$post_type = current($post_type);
		}
		if(empty($post_type)) {
			$post_type = 'post';
		}
		$post_type =  fansub_uppercase_first_char_only($post_type);
		$meta_id = $post_type . '_attributes';
		$meta_id = fansub_sanitize_id($meta_id);
		$meta = new FANSUB_Meta('post');
		$meta->set_post_types($post_types);
		$meta->set_id($meta_id);
		$meta->set_title($post_type . ' Attributes');
		$meta->set_context('side');
		$meta->set_priority('core');
		$meta->init();
	}
}

function fansub_meta_box_side_image($args = array()) {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		$id = fansub_get_value_by_key($args, 'id', 'secondary_image_box');
		$title = fansub_get_value_by_key($args, 'title', __('Secondary Image', 'fansub'));
		$post_types = fansub_get_value_by_key($args, 'post_type');
		if('all' == $post_types) {
			$post_types = array();
			$types = get_post_types(array('public' => true), 'objects');
			fansub_exclude_special_post_types($types);
			foreach($types as $key => $object_type) {
				$post_types[] = $key;
			}
		}
		$post_types = fansub_sanitize_array($post_types);
		$field_id = fansub_get_value_by_key($args, 'field_id', 'secondary_image');
		if(!fansub_array_has_value($post_types)) {
			return;
		}
		$meta = new FANSUB_Meta('post');
		$meta->set_post_types($post_types);
		$meta->set_id($id);
		$meta->set_title($title);
		$meta->set_context('side');
		$meta->set_priority('low');
		$field_args = array('id' => $field_id, 'field_callback' => 'fansub_field_media_upload_simple');
		$field_name = fansub_get_value_by_key($args, 'field_name', $field_id);
		$field_args['name'] = $field_name;
		$meta->add_field($field_args);
		$meta->init();
	}
}

function fansub_meta_box_page_additional_information() {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		$meta = new FANSUB_Meta('post');
		$meta->set_title(__('Additional Information', 'fansub'));
		$meta->set_id('page_additional_information');
		$meta->set_post_types(array('page'));
		$meta->add_field(array('id' => 'different_title', 'label' => __('Different title:', 'fansub')));
		$meta->add_field(array('id' => 'sidebar', 'label' => __('Sidebar', 'fansub'), 'field_callback' => 'fansub_field_select_sidebar'));
		$meta->init();
	}
}

function fansub_meta_box_google_maps($args = array()) {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		$post_id = fansub_get_value_by_key($_REQUEST, 'post');
		$id = fansub_get_value_by_key($args, 'id', 'google_maps_box');
		$title = fansub_get_value_by_key($args, 'title', __('Maps', 'fansub'));
		$post_types = fansub_get_value_by_key($args, 'post_types', array('post'));
		$meta = new FANSUB_Meta('post');
		$meta->set_title($title);
		$meta->set_id($id);
		$meta->set_post_types($post_types);
		$map_args = array('id' => 'maps_content', 'label' => '', 'field_callback' => 'fansub_field_google_maps', 'names' => array('google_maps'));
		if(fansub_id_number_valid($post_id)) {
			$google_maps = fansub_get_post_meta('google_maps', $post_id);
			$google_maps = fansub_json_string_to_array($google_maps);
			$map_args['lat'] = fansub_get_value_by_key($google_maps, 'lat');
			$map_args['long'] = fansub_get_value_by_key($google_maps, 'lng');
		}
		$meta->add_field($map_args);
		//$meta->add_field(array('id' => 'google_maps', 'label' => '', 'field_callback' => 'fansub_field_input_hidden'));
		$meta->init();
	}
}

function fansub_meta_box_editor($args = array()) {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		$post_type = fansub_get_value_by_key($args, 'post_type');
		if(!is_array($post_type)) {
			$post_type = array($post_type);
		}
		$box_title = fansub_get_value_by_key($args, 'title', __('Additional Information', 'fansub'));
		$current_type = fansub_get_current_post_type();
		if(is_array($current_type)) {
			$current_type = current($current_type);
		}
		$box_id = fansub_get_value_by_key($args, 'id');
		if(empty($box_id)) {
			$box_id = fansub_sanitize_id($box_title);
			if(empty($box_id)) {
				return;
			}
		}
		if(!empty($current_type)) {
			$box_id = $current_type . '_' . $box_id;
		}
		$field_args = fansub_get_value_by_key($args, 'field_args', array());
		$field_args = fansub_sanitize_array($field_args);
		$field_args['field_callback'] = 'fansub_field_editor';
		$field_args['label'] = '';
		$field_id = fansub_get_value_by_key($args, 'field_id', fansub_get_value_by_key($field_args, 'field_id'));
		$field_name = fansub_get_value_by_key($args, 'field_name', fansub_get_value_by_key($field_args, 'field_name'));
		fansub_transmit_id_and_name($field_id, $field_name);
		if(empty($field_id)) {
			return;
		}
		$field_args['id'] = $field_id;
		$field_args['name'] = $field_name;
		$meta = new FANSUB_Meta('post');
		$meta->set_title($box_title);
		$meta->set_id($box_id);
		$meta->set_post_types($post_type);
		$meta->add_field($field_args);
		$meta->init();
	}
}

function fansub_meta_box_editor_gallery($args = array()) {
	$defaults = array(
		'title' => __('Gallery', 'fansub'),
		'field_id' => 'image_gallery',
		'field_name' => 'gallery',
		'field_args' => array(
			'teeny' => true,
			'toolbar' => false
		)
	);
	$args = wp_parse_args($args, $defaults);
	fansub_meta_box_editor($args);
}