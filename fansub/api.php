<?php
if(!function_exists('add_filter')) exit;

$plugins = get_option('active_plugins');

if(!is_array($plugins) || !in_array('rest-api/plugin.php', $plugins)) {
	return;
}

function fansub_api_allow_meta_query($valid_vars) {
	$valid_vars = array_merge($valid_vars, array('meta_key', 'meta_value', 'meta_query'));
	return $valid_vars;
}
add_filter('rest_query_vars', 'fansub_api_allow_meta_query');

function fansub_api_get_by_meta($meta_query, $rest_base, $version = 'v2', $server = HOCWP_API_SERVER) {
	$fields = array(
		'filter[meta_query]' => $meta_query
	);
	$meta_query = http_build_query($fields);
	$url = trailingslashit($server) . 'wp-json/wp/' . $version . '/' . $rest_base . '?' . $meta_query;
	return json_decode(@file_get_contents($url));
}