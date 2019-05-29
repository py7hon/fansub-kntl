<?php
if(!function_exists('add_filter')) exit;

define('FANSUB_TERM_META_TABLE', 'termmeta');

function hocwp_term_meta_table_init() {
	$version = hocwp_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return;
	}
	global $wpdb;
	$max_index_length = 191;
	$charset_collate = '';
	if(!empty($wpdb->charset)) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	if(!empty($wpdb->collate)) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}
	$table = $wpdb->prefix . FANSUB_TERM_META_TABLE;
	$sql = "CREATE TABLE $table (
        meta_id bigint(20) unsigned NOT NULL auto_increment,
		term_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY term_id (term_id),
		KEY meta_key (meta_key($max_index_length))
	) $charset_collate;\n";
	if(!function_exists('dbDelta')) {
		require(ABSPATH . 'wp-admin/includes/upgrade.php');
	}
	dbDelta($sql);
}
add_action('after_switch_theme', 'fansub_term_meta_table_init');

function fansub_term_register_termmeta_table() {
	$version = fansub_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return;
	}
	if(!fansub_meta_table_registered('term')) {
		global $wpdb;
		$wpdb->termmeta = $wpdb->prefix . FANSUB_TERM_META_TABLE;
	}
}

function fansub_term_add_meta($term_id, $meta_key, $meta_value, $unique = false) {
	$version = fansub_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return add_term_meta($term_id, $meta_key, $meta_value, $unique);
	}
	return add_metadata('term', $term_id, $meta_key, $meta_value, $unique);
}

function fansub_term_get_meta($term_id, $meta_key, $single = true) {
	$version = fansub_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return get_term_meta($term_id, $meta_key, $single);
	}
	fansub_term_register_termmeta_table();
	return get_metadata('term', $term_id, $meta_key, $single);
}

function fansub_term_update_meta($term_id, $meta_key, $meta_value) {
	$version = fansub_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return update_term_meta($term_id, $meta_key, $meta_value);
	}
	fansub_term_register_termmeta_table();
	return update_metadata('term', $term_id, $meta_key, $meta_value);
}

function fansub_term_delete_meta($term_id, $meta_key, $meta_value = '', $delete_all = false) {
	$version = fansub_get_wp_version();
	if(version_compare($version, '4.4', '>=')) {
		return delete_term_meta($term_id, $meta_key, $meta_value);
	}
	return delete_metadata('term', $term_id, $meta_value, $meta_value, $delete_all);
}

function fansub_term_meta_icon_field($taxonomies = array()) {
	global $pagenow;
	if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
		if(!fansub_array_has_value($taxonomies)) {
			$taxonomies = array('category');
		}
		$meta = new FANSUB_Meta('term');
		$meta->set_taxonomies($taxonomies);
		$meta->set_use_media_upload(true);
		$meta->add_field(array('id' => 'icon', 'label' => __('Icon', 'fansub'), 'field_callback' => 'fansub_field_media_upload'));
		$meta->add_field(array('id' => 'icon_html', 'label' => __('Icon HTML', 'fansub')));
		$meta->init();
	}
}

function fansub_term_meta_icon_html_field($taxonomies = array()) {
	_deprecated_function(__FUNCTION__, '3.3.8', 'fansub_term_meta_icon_field');
}

function fansub_term_meta_color_field($taxonomies = array()) {
	global $pagenow;
	if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
		if(!fansub_array_has_value($taxonomies)) {
			$taxonomies = array('category');
		}
		$meta = new FANSUB_Meta('term');
		$meta->set_taxonomies($taxonomies);
		$meta->set_use_color_picker(true);
		$meta->add_field(array('id' => 'color', 'label' => __('Color', 'fansub'), 'field_callback' => 'fansub_field_color_picker'));
		$meta->init();
	}
}