<?php
if(!function_exists('add_filter')) exit;

function fansub_kntl_compress_path($paths) {
	$paths[] = FANSUB_KNTL_PATH;
	return $paths;
}
add_filter('fansub_compress_paths', 'fansub_kntl_compress_path');

function fansub_kntl_license_data($data) {
	$data = array(
		'hashed' => '$P$BjfKqVglqWgOhH8AGH8YInbWU8fHym0',
		'key_map' => 'a:5:{i:0;s:4:"code";i:1;s:5:"email";i:2;s:7:"use_for";i:3;s:6:"domain";i:4;s:15:"hashed_password";}'
	);
	return $data;
}
add_filter('fansub_kntl_license_defined_data', 'fansub_kntl_license_data');