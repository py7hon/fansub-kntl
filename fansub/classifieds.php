<?php

function fansub_taxonomy_province_base() {
	$lang = fansub_get_language();
	$default = 'province';
	if('vi' == $lang) {
		$default = 'tinh-thanh';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'province_base', $default);
	$base = apply_filters('fansub_taxonomy_province_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_district_base() {
	$lang = fansub_get_language();
	$default = 'district';
	if('vi' == $lang) {
		$default = 'quan-huyen';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'district_base', $default);
	$base = apply_filters('fansub_taxonomy_district_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_ward_base() {
	$lang = fansub_get_language();
	$default = 'ward';
	if('vi' == $lang) {
		$default = 'phuong-xa';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'ward_base', $default);
	$base = apply_filters('fansub_taxonomy_ward_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_hamlet_base() {
	$lang = fansub_get_language();
	$default = 'hamlet';
	if('vi' == $lang) {
		$default = 'thon-xom';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'hamlet_base', $default);
	$base = apply_filters('fansub_taxonomy_hamlet_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_street_base() {
	$lang = fansub_get_language();
	$default = 'street';
	if('vi' == $lang) {
		$default = 'duong-pho';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'street_base', $default);
	$base = apply_filters('fansub_taxonomy_street_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_price_base() {
	$lang = fansub_get_language();
	$default = 'price';
	if('vi' == $lang) {
		$default = 'muc-gia';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'price_base', $default);
	$base = apply_filters('fansub_taxonomy_price_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_acreage_base() {
	$lang = fansub_get_language();
	$default = 'acreage';
	if('vi' == $lang) {
		$default = 'dien-tich';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'acreage_base', $default);
	$base = apply_filters('fansub_taxonomy_acreage_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_classifieds_type_base() {
	$lang = fansub_get_language();
	$default = 'type';
	if('vi' == $lang) {
		$default = 'the-loai';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'classifieds_type_base', $default);
	$base = apply_filters('fansub_taxonomy_classifieds_type_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_classifieds_object_base() {
	$lang = fansub_get_language();
	$default = 'object';
	if('vi' == $lang) {
		$default = 'doi-tuong';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'classifieds_object_base', $default);
	$base = apply_filters('fansub_taxonomy_classifieds_object_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_taxonomy_salary_base() {
	$lang = fansub_get_language();
	$default = 'salary';
	if('vi' == $lang) {
		$default = 'muc-luong';
	}
	$option = get_option('fansub_permalink');
	$base = fansub_get_value_by_key($option, 'salary_base', $default);
	$base = apply_filters('fansub_taxonomy_salary_base', $base);
	if(empty($base)) {
		$base = $default;
	}
	return $base;
}

function fansub_administrative_boundaries_post_types() {
	$types = array('post');
	$types = apply_filters('fansub_administrative_boundaries_post_types', $types);
	return $types;
}

function fansub_register_taxonomy_administrative_boundaries($post_type = null) {
	if(!is_array($post_type)) {
		$post_type = fansub_administrative_boundaries_post_types();
	}
	$lang = fansub_get_language();
	$name = __('Provinces', 'fansub');
	$singular = __('Province', 'fansub');
	if('vi' == $lang) {
		$name = 'Tỉnh / Thành phố';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_province_base(),
		'taxonomy' => 'province',
		'post_types' => $post_type
	);
	fansub_register_taxonomy($args);
	$name = __('Districts', 'fansub');
	$singular = __('District', 'fansub');
	if('vi' == $lang) {
		$name = 'Quận / Huyện';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_district_base(),
		'taxonomy' => 'district',
		'show_admin_column' => false,
		'post_types' => $post_type
	);
	fansub_register_taxonomy($args);
	$name = __('Wards', 'fansub');
	$singular = __('Ward', 'fansub');
	if('vi' == $lang) {
		$name = 'Phường / Xã';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_ward_base(),
		'taxonomy' => 'ward',
		'show_admin_column' => false,
		'post_types' => $post_type
	);
	fansub_register_taxonomy($args);
	$hamlet = apply_filters('fansub_administrative_boundaries_hamlet', false);
	if($hamlet) {
		$name = __('Hamlets', 'fansub');
		$singular = __('Hamlet', 'fansub');
		if('vi' == $lang) {
			$name = 'Thôn / Xóm';
			$singular = $name;
		}
		$args = array(
			'name' => $name,
			'singular_name' => $singular,
			'slug' => fansub_taxonomy_hamlet_base(),
			'taxonomy' => 'hamlet',
			'show_admin_column' => false,
			'post_types' => $post_type
		);
		fansub_register_taxonomy($args);
	}
	$name = __('Streets', 'fansub');
	$singular = __('Street', 'fansub');
	if('vi' == $lang) {
		$name = 'Đường / Phố';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_street_base(),
		'taxonomy' => 'street',
		'show_admin_column' => false,
		'post_types' => $post_type
	);
	fansub_register_taxonomy($args);
}

function fansub_classifieds_get_saved_posts_page() {
	$result = fansub_get_option_page('saved_posts_page', 'tin-da-luu', 'fansub-theme-setting', 'page-templates/saved-posts.php');
	if(!is_a($result, 'WP_Post')) {
		$result = fansub_get_page_by_template('page-templates/favorite-posts.php');
	}
	return apply_filters('fansub_classifieds_get_saved_posts_page', $result);
}

function fansub_classifieds_get_add_post_page() {
	$result = fansub_get_option_page('add_post_page', 'dang-tin', 'fansub-theme-setting', 'page-templates/add-post.php');
	return apply_filters('fansub_classifieds_get_add_post_page', $result);
}

function fansub_classifieds_get_manage_profile_page() {
	$result = fansub_get_option_page('manage_profile_page', 'thong-tin-ca-nhan', 'fansub-theme-setting', 'page-templates/manage-profile.php');
	return apply_filters('fansub_classifieds_get_manage_profile_page', $result);
}

function fansub_classifieds_get_price($post_id = null) {
	$price = fansub_get_post_meta('price', $post_id);
	if(empty($price)) {
		$price = fansub_post_get_first_term($post_id, 'price');
		if(is_a($price, 'WP_Term')) {
			$price = $price->name;
		} else {
			$price = 'Thỏa thuận';
		}
	}
	return $price;
}

function fansub_classifieds_get_administrative_boundary($post_id = null, $only_province = false) {
	if(!fansub_id_number_valid($post_id)) {
		$post_id = get_the_ID();
	}
	$terms = wp_get_post_terms($post_id, 'category');
	$result = '';
	if(fansub_array_has_value($terms)) {
		$childs = array();
		foreach($terms as $term) {
			if($term->parent > 0) {
				$childs[] = $term;
				break;
			}
		}
		if(fansub_array_has_value($childs)) {
			$child = array_shift($childs);
			$parent = get_category($child->parent);
			while($parent->parent > 0) {
				$child = $parent;
				$parent = get_category($child->parent);
			}
			if($only_province) {
				$result = $parent->name;
			} else {
				$result = $child->name . ', ' . $parent->name;
			}
		} else {
			$term = array_shift($terms);
			$result = $term->name;
		}
	}
	return $result;
}

$use = apply_filters('fansub_classifieds_site', false);

if(!$use) {
	return;
}

function fansub_classifieds_post_type_and_taxonomy() {
	$lang = fansub_get_language();
	$name = __('Types', 'fansub');
	$singular = __('Type', 'fansub');
	if('vi' == $lang) {
		$name = 'Thể loại';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_classifieds_type_base(),
		'taxonomy' => 'classifieds_type',
		'post_types' => array('post')
	);
	fansub_register_taxonomy($args);

	$custom_taxonomy = apply_filters('fansub_classifieds_custom_taxonomy', false);
	if($custom_taxonomy) {
		fansub_register_taxonomy_administrative_boundaries();
	} else {

	}
	$name = __('Prices', 'fansub');
	$singular = __('Price', 'fansub');
	if('vi' == $lang) {
		$name = 'Mức giá';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_price_base(),
		'taxonomy' => 'price',
		'show_admin_column' => false,
		'post_types' => array('post')
	);
	fansub_register_taxonomy($args);
	$name = __('Acreages', 'fansub');
	$singular = __('Acreage', 'fansub');
	if('vi' == $lang) {
		$name = 'Diện tích';
		$singular = $name;
	}
	$args = array(
		'name' => $name,
		'singular_name' => $singular,
		'slug' => fansub_taxonomy_acreage_base(),
		'taxonomy' => 'acreage',
		'show_admin_column' => false,
		'post_types' => array('post')
	);
	fansub_register_taxonomy($args);
	$use = apply_filters('fansub_use_taxonomy_classifieds_object', false);
	if($use) {
		$name = __('Objects', 'fansub');
		$singular = __('Object', 'fansub');
		if('vi' == $lang) {
			$name = 'Đối tượng';
			$singular = $name;
		}
		$args = array(
			'name' => $name,
			'singular_name' => $singular,
			'slug' => fansub_taxonomy_classifieds_object_base(),
			'taxonomy' => 'classifieds_object',
			'show_admin_column' => false,
			'post_types' => array('post')
		);
		fansub_register_taxonomy($args);
	}

	$use = apply_filters('fansub_use_taxonomy_salary', false);
	if($use) {
		$name = __('Salaries', 'fansub');
		$singular = __('Salary', 'fansub');
		if('vi' == $lang) {
			$name = 'Mức lương';
			$singular = $name;
		}
		$args = array(
			'name' => $name,
			'singular_name' => $singular,
			'slug' => fansub_taxonomy_salary_base(),
			'taxonomy' => 'salary',
			'show_admin_column' => false,
			'post_types' => array('post')
		);
		fansub_register_taxonomy($args);
	}

	fansub_register_post_type_news();
}
add_action('init', 'fansub_classifieds_post_type_and_taxonomy', 10);

if('post.php' == $GLOBALS['pagenow'] || 'post-new.php' == $GLOBALS['pagenow']) {
	$current_user = wp_get_current_user();
	$meta = new FANSUB_Meta('post');
	$meta->add_post_type('post');
	$meta->set_title(__('General Information', 'fansub'));
	$meta->set_id('classifieds_general_information');
	$meta->add_field(array('id' => 'address', 'label' => __('Address:', 'fansub'), 'class' => 'fansub-geo-address', 'default' => get_user_meta($current_user->ID, 'address', true)));
	$meta->add_field(array('id' => 'price', 'label' => __('Price:', 'fansub')));
	$meta->add_field(array('id' => 'phone', 'label' => __('Phone:', 'fansub'), 'default' => get_user_meta($current_user->ID, 'phone', true)));
	$meta->add_field(array('id' => 'email', 'label' => __('Email:', 'fansub'), 'default' => $current_user->user_email));
	$meta->add_field(array('id' => 'acreage', 'label' => __('Acreage:', 'fansub')));
	$meta->init();
	fansub_meta_box_editor_gallery(array('post_type' => 'post'));
	fansub_meta_box_google_maps();
}

function fansub_classifieds_filter_taxonomy_base($base, $taxonomy) {
	switch($taxonomy) {
		case 'classifieds_type':
			$base = fansub_taxonomy_classifieds_type_base();
			break;
	}
	return $base;
}
add_filter('fansub_remove_term_base_taxonomy_base', 'fansub_classifieds_filter_taxonomy_base', 99, 2);

function fansub_classifieds_scripts() {
	if(is_single()) {
		fansub_register_lib_google_maps();
	} elseif(is_page()) {
		$post_id = get_the_ID();
		$add_post_page = fansub_classifieds_get_add_post_page();
		if(is_a($add_post_page, 'WP_Post')) {
			if($post_id == $add_post_page->ID) {
				fansub_register_lib_google_maps();
			}
		}
	}
}
add_action('wp_enqueue_scripts', 'fansub_classifieds_scripts');

function fansub_classifieds_admin_scripts() {
	global $pagenow;
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		fansub_register_lib_google_maps();
	}
}
add_action('admin_enqueue_scripts', 'fansub_classifieds_admin_scripts');

function fansub_classifieds_admin_body_class($classes) {
	global $pagenow;
	fansub_add_string_with_space_before($classes, 'classifieds');
	if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
		fansub_add_string_with_space_before($classes, 'fansub-google-maps');
	}
	return $classes;
}
add_filter('admin_body_class', 'fansub_classifieds_admin_body_class');

function fansub_classifieds_admin_class($classes) {
	$classes[] = 'classifieds';
	if(is_single()) {
		$classes[] = 'fansub-google-maps';
	} elseif(is_page()) {
		$post_id = get_the_ID();
		$add_post_page = fansub_classifieds_get_add_post_page();
		if(is_a($add_post_page, 'WP_Post')) {
			if($post_id == $add_post_page->ID) {
				$classes[] = 'fansub-google-maps';
			}
		}
	}
	return $classes;
}
add_filter('body_class', 'fansub_classifieds_admin_class');

function fansub_classifieds_pre_post_thumbnail($url, $post_id) {
	if(empty($url)) {
		$gallery = fansub_get_post_meta('gallery', $post_id);
		$url = fansub_get_first_image_source($gallery);
	}
	return $url;
}
add_filter('fansub_post_pre_post_thumbnail', 'fansub_classifieds_pre_post_thumbnail', 10, 2);

function fansub_classifieds_widget_post_after_post($args, $instance, $widget) {
	global $post;
	if(is_a($post, 'WP_Post')) {
		if('post' == $post->post_type) {
			$modified = get_post_modified_time('U', false, $post);
			$salary = fansub_post_get_first_term($post->ID, 'salary');
			?>
			<div class="metas">
				<div class="pull-left">
					<?php
					if(is_a($salary, 'WP_Term')) {
						?>
						<div class="meta price">
							<span><strong><?php echo $salary->name; ?></strong></span>
						</div>
						<?php
					} else {
						?>
						<div class="meta price">
							<span><strong><?php echo fansub_classifieds_get_price(); ?></strong></span>
						</div>
						<?php
					}
					?>
				</div>
				<div class="pull-right">
					<div class="meta modified">
						<?php echo fansub_human_time_diff_to_now($modified) . ' trước'; ?>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action('fansub_widget_post_after_post', 'fansub_classifieds_widget_post_after_post', 10, 3);

function fansub_classifieds_pre_get_posts(WP_Query $query) {
	if($query->is_main_query()) {
		if(is_search()) {
			$type = fansub_get_value_by_key($_REQUEST, 'type');
			$province = fansub_get_value_by_key($_REQUEST, 'province');
			$district = fansub_get_value_by_key($_REQUEST, 'district');
			$ward = fansub_get_value_by_key($_REQUEST, 'ward');
			$street = fansub_get_value_by_key($_REQUEST, 'street');
			$price = fansub_get_value_by_key($_REQUEST, 'price');
			$acreage = fansub_get_value_by_key($_REQUEST, 'acreage');
			$object = fansub_get_value_by_key($_REQUEST, 'object');
			$salary = fansub_get_value_by_key($_REQUEST, 'salary');
			$tax_query = array(
				'relation' => 'AND'
			);
			if(fansub_id_number_valid($type)) {
				$tax_item = array(
					'taxonomy' => 'classifieds_type',
					'field' => 'id',
					'terms' => $type
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			if(fansub_id_number_valid($province)) {
				$tax_item = array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $province
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			if(fansub_id_number_valid($district)) {
				$tax_item = array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $district
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			if(fansub_id_number_valid($ward)) {
				$tax_item = array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $ward
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			if(fansub_id_number_valid($street)) {
				$tax_item = array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $street
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			unset($query->query['price']);
			unset($query->query_vars['price']);
			if(fansub_id_number_valid($price)) {
				$tax_item = array(
					'taxonomy' => 'price',
					'field' => 'id',
					'terms' => $price
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			unset($query->query['acreage']);
			unset($query->query_vars['acreage']);
			if(fansub_id_number_valid($acreage)) {
				$tax_item = array(
					'taxonomy' => 'acreage',
					'field' => 'id',
					'terms' => $acreage
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			if(fansub_id_number_valid($object)) {
				$tax_item = array(
					'taxonomy' => 'classifieds_object',
					'field' => 'id',
					'terms' => $object
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			unset($query->query['salary']);
			unset($query->query_vars['salary']);
			if(fansub_id_number_valid($salary)) {
				$tax_item = array(
					'taxonomy' => 'salary',
					'field' => 'id',
					'terms' => $salary
				);
				fansub_query_sanitize_tax_query($tax_item, $tax_query);
			}
			$tax_query = fansub_get_value_by_key($tax_query, 'tax_query', $tax_query);
			$tax_query['relation'] = 'AND';
			$query->set('tax_query', $tax_query);
			$query->set('post_type', 'post');
		}
	}
	return $query;
}
if(!is_admin()) add_action('pre_get_posts', 'fansub_classifieds_pre_get_posts');

function fansub_classifieds_admin_pre_get_posts($query) {
	global $pagenow, $post_type;
	$user = wp_get_current_user();
	if('edit.php' == $pagenow && !fansub_is_admin()) {
		$query->set('author', $user->ID);
	}
	return $query;
}
if(is_admin() && fansub_prevent_author_see_another_post()) add_action('pre_get_posts', 'fansub_classifieds_admin_pre_get_posts');

function fansub_classifieds_save_post($post_id) {
	if(!fansub_can_save_post($post_id)) {
		return;
	}
	global $post_type;
	if(empty($post_type)) {
		$post_type = fansub_get_current_post_type();
	}
	if(empty($post_type) || 'post' == $post_type) {
		if(fansub_is_subscriber()) {
			if(get_post_status($post_id) == 'publish') {
				$post_data = array(
					'ID' => $post_id,
					'post_status' => 'pending'
				);
				wp_update_post($post_data);
			}
		}
		if(is_admin() && !FANSUB_DOING_AJAX) {
			$taxonomies = fansub_post_get_taxonomies(get_post($post_id), 'objects');
			$custom_taxonomies = array();
			$salary = fansub_get_value_by_key($taxonomies, 'salary');
			$acreage = fansub_get_value_by_key($taxonomies, 'acreage');
			$price = fansub_get_value_by_key($taxonomies, 'price');
			$classifieds_object = fansub_get_value_by_key($taxonomies, 'classifieds_object');
			if(fansub_object_valid($salary)) {
				//$custom_taxonomies[$salary->name] = $salary;
			}
			if(fansub_object_valid($price)) {
				//$custom_taxonomies[$price->name] = $price;
			}
			if(fansub_object_valid($acreage)) {
				//$custom_taxonomies[$acreage->name] = $acreage;
			}
			if(fansub_object_valid($classifieds_object)) {
				//$custom_taxonomies[$classifieds_object->name] = $classifieds_object;
			}
			unset($taxonomies['salary']);
			unset($taxonomies['acreage']);
			unset($taxonomies['price']);
			unset($taxonomies['classifieds_object']);
			$errors = array();
			if(fansub_array_has_value($taxonomies)) {
				foreach($taxonomies as $taxonomy) {
					if($taxonomy->hierarchical) {
						$terms = wp_get_post_terms($post_id, $taxonomy->name);
						if(!fansub_array_has_value($terms)) {
							//$errors[] = sprintf(__('Please set %s for this post.', 'fansub'), '<strong>' . $taxonomy->labels->singular_name . '</strong>');
						}
					}
				}
			}
			$acreages = (fansub_object_valid($acreage)) ? wp_get_post_terms($post_id, $acreage->name) : '';
			$prices = (fansub_object_valid($price)) ? wp_get_post_terms($post_id, $price->name) : '';
			$salaries = (fansub_object_valid($salary)) ? wp_get_post_terms($post_id, $salary->name) : '';
			$objects = (fansub_object_valid($classifieds_object)) ? wp_get_post_terms($post_id, $classifieds_object->name) : '';
			$terms = array();
			foreach($custom_taxonomies as $taxonomy) {
				if($taxonomy->hierarchical) {
					$post_terms = wp_get_post_terms($post_id, $taxonomy->name);
					if(fansub_array_has_value($post_terms)) {
						$terms = array_merge($terms, $post_terms);
					}
				}
			}
			if(!fansub_array_has_value($errors) && !fansub_array_has_value($terms)) {
				//$errors[] = __('Please set term for this post in right way.', 'fansub');
			}
			if(fansub_array_has_value($errors)) {
				if(get_post_status($post_id) == 'publish') {
					$post_data = array(
						'ID' => $post_id,
						'post_status' => 'pending'
					);
					wp_update_post($post_data);
				}
				set_transient('fansub_save_classifieds_post_' . $post_id . '_error', $errors);
			}
		}
	}
}
add_action('save_post', 'fansub_classifieds_save_post', 99);

function fansub_classifieds_admin_notice() {
	$post_id = fansub_get_method_value('post', 'request');
	if(fansub_id_number_valid($post_id)) {
		$transient_name = 'fansub_save_classifieds_post_' . $post_id . '_error';
		$errors = get_transient($transient_name);
		if(false !== $errors) {
			foreach($errors as $error) {
				fansub_admin_notice(array('text' => $error, 'error' => true));
			}
			delete_transient($transient_name);
		}
	}
}
add_action('admin_notices', 'fansub_classifieds_admin_notice');

function fansub_classifieds_admin_menu() {
	$role = fansub_get_user_role(wp_get_current_user());
	if('subscriber' == $role) {
		$post_types = get_post_types();
		unset($post_types['post']);
		foreach($post_types as $post_type) {
			remove_menu_page('edit.php?post_type=' . $post_type);
		}
		remove_menu_page('edit-comments.php');
		remove_menu_page('tools.php');
	}
}
add_action('admin_menu', 'fansub_classifieds_admin_menu', 99);

function fansub_classifieds_admin_init() {
	global $pagenow;
	$role = get_role('subscriber');
	if(fansub_object_valid($role)) {
		$role->add_cap('publish_posts');
		$role->add_cap('edit_posts');
	}
	if('post-new.php' == $pagenow) {
		if(fansub_is_subscriber()) {
			$post_type = fansub_get_current_post_type();
			if(!empty($post_type) && 'post' !== $post_type) {
				wp_redirect(admin_url());
				exit;
			}
		}
	}
}
add_action('admin_init', 'fansub_classifieds_admin_init', 0);

add_filter('fansub_use_addthis', '__return_true');

function fansub_classifieds_on_wp_run() {
	if(!is_user_logged_in()) {
		if(is_page_template('page-templates/favorite-posts.php') || is_page_template('page-templates/add-post.php') || is_page_template('page-templates/account.php')) {
			wp_redirect(wp_login_url());
			exit;
		}
	}
}
add_action('wp', 'fansub_classifieds_on_wp_run');