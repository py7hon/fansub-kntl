<?php
function hocwp_coupon_store_base() {
	$option = get_option('hocwp_permalink');
	$base = hocwp_get_value_by_key($option, 'coupon_store_base', 'store');
	$base = apply_filters('hocwp_coupon_store_base', $base);
	if(empty($base)) {
		$base = 'store';
	}
	return $base;
}

function hocwp_coupon_category_base() {
	$option = get_option('hocwp_permalink');
	$base = hocwp_get_value_by_key($option, 'coupon_category_base', 'coupon-cat');
	$base = apply_filters('hocwp_coupon_category_base', $base);
	if(empty($base)) {
		$base = 'coupon-cat';
	}
	return $base;
}

function hocwp_coupon_tag_base() {
	$option = get_option('hocwp_permalink');
	$base = hocwp_get_value_by_key($option, 'coupon_tag_base', 'coupon-tag');
	$base = apply_filters('hocwp_coupon_tag_base', $base);
	if(empty($base)) {
		$base = 'coupon-tag';
	}
	return $base;
}

function hocwp_coupon_type_base() {
	$option = get_option('hocwp_permalink');
	$base = hocwp_get_value_by_key($option, 'coupon_type_base', 'coupon-type');
	$base = apply_filters('hocwp_coupon_type_base', $base);
	if(empty($base)) {
		$base = 'coupon-type';
	}
	return $base;
}

function hocwp_coupon_install_post_type_and_taxonomy() {
	$args = array(
		'name' => __('Coupons', 'hocwp'),
		'singular_name' => __('Coupon', 'hocwp'),
		'supports' => array('editor', 'comments', 'thumbnail'),
		'slug' => 'coupon',
		'taxonomies' => array('store', 'coupon_cat', 'coupon_tag', 'coupon_type'),
		'show_in_admin_bar' => true
	);
	hocwp_register_post_type($args);

	$args = array(
		'name' => __('Events', 'hocwp'),
		'singular_name' => __('Event', 'hocwp'),
		'supports' => array('editor', 'comments', 'thumbnail'),
		'slug' => 'event',
		'show_in_admin_bar' => true
	);
	hocwp_register_post_type($args);

	$args = array(
		'name' => __('Stores', 'hocwp'),
		'singular_name' => __('Store', 'hocwp'),
		'taxonomy' => 'store',
		'slug' => hocwp_coupon_store_base(),
		'post_types' => array('coupon')
	);
	hocwp_register_taxonomy($args);

	$args = array(
		'name' => __('Coupon Categories', 'hocwp'),
		'singular_name' => __('Coupon Category', 'hocwp'),
		'menu_name' => __('Categories', 'hocwp'),
		'slug' => hocwp_coupon_category_base(),
		'taxonomy' => 'coupon_cat',
		'post_types' => array('coupon')
	);
	hocwp_register_taxonomy($args);

	$args = array(
		'name' => __('Coupon Tags', 'hocwp'),
		'singular_name' => __('Coupon Tag', 'hocwp'),
		'menu_name' => __('Tags', 'hocwp'),
		'slug' => hocwp_coupon_tag_base(),
		'taxonomy' => 'coupon_tag',
		'hierarchical' => false,
		'post_types' => array('coupon')
	);
	hocwp_register_taxonomy($args);

	$args = array(
		'name' => __('Coupon Types', 'hocwp'),
		'singular_name' => __('Coupon Type', 'hocwp'),
		'menu_name' => __('Types', 'hocwp'),
		'slug' => hocwp_coupon_type_base(),
		'taxonomy' => 'coupon_type',
		'post_types' => array('coupon')
	);
	hocwp_register_taxonomy($args);
}

function hocwp_get_coupon_url($post_id = null) {
	if(!hocwp_id_number_valid($post_id)) {
		$out = get_query_var('out');
		if(!empty($out)) {
			if(hocwp_id_number_valid($out)) {
				$post_id = $out;
			} else {
				$post = hocwp_get_post_by_slug($out);
				if(is_a($post, 'WP_Post')) {
					$post_id = $post->ID;
				}
			}
		} else {
			$post = hocwp_get_post_by_slug($post_id);
			if(is_a($post, 'WP_Post')) {
				$post_id = $post->ID;
			} else {
				$post_id = get_the_ID();
			}
		}
	}
	$url = fansub_get_coupon_meta('url', $post_id);
	if(empty($url)) {
		$store = fansub_get_coupon_store($post_id);
		if(is_a($store, 'WP_Term')) {
			$url = fansub_get_store_url($store->term_id);
		}
	}
	return $url;
}

function fansub_get_store_url($id) {
	return get_term_meta($id, 'site', true);
}

function fansub_get_top_store_by_coupon_count($args = array()) {
	return fansub_term_get_by_count('store', $args);
}

function fansub_get_top_category_by_coupon_count($args = array()) {
	return fansub_term_get_by_count('coupon_cat', $args);
}

function fansub_get_coupon_categories($args = array()) {
	return fansub_get_terms('coupon_cat', $args);
}

function fansub_get_coupon_stores($args = array()) {
	return fansub_get_terms('store', $args);
}

function fansub_get_coupon_hint($post_id = null) {
	$code = fansub_get_coupon_code($post_id);
	$len = strlen($code);
	if($len > 3) {
		$len = intval($len/2);
	}
	if($len < 3) {
		$len = 3;
	}
	if($len > 10) {
		$len = 10;
	}
	$len = -$len;
	$code = substr($code, $len);
	return $code;
}

function fansub_get_coupon_code($post_id = null) {
	if(!fansub_id_number_valid($post_id)) {
		$post_id = get_the_ID();
	}
	$code = fansub_get_coupon_meta('coupon_code', $post_id);
	if(empty($code)) {
		$code = fansub_get_coupon_meta('code', $post_id);
	}
	if(empty($code)) {
		$code = fansub_get_coupon_meta('wpcf-coupon-code', $post_id);
	}
	return $code;
}

function fansub_get_coupon_meta($meta_key, $post_id = null) {
	return fansub_get_post_meta($meta_key, $post_id);
}

function fansub_get_coupon_percent_label($post_id = null) {
	return fansub_get_coupon_meta('percent_label', $post_id);
}

function fansub_get_coupon_text_label($post_id = null) {
	return fansub_get_coupon_meta('text_label', $post_id);
}

function fansub_get_coupon_expired_date($post_id = null) {
	return fansub_get_coupon_meta('expired_date', $post_id);
}

function fansub_get_coupon_type_term($post_id = null) {
	if(!fansub_id_number_valid($post_id)) {
		$post_id = get_the_ID();
	}
	$result = array(
		'code'
	);
	$terms = wp_get_post_terms($post_id, 'coupon_type');
	$term = current($terms);
	return $term;
}

function fansub_get_coupon_type_object($type = 'code') {
	$term = new WP_Error();
	switch($type) {
		case 'deal':
			$term = fansub_get_term_by_slug('deal', 'coupon_type');
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('sales', 'coupon_type');
			}
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('promotion', 'coupon_type');
			}
			break;
		default:
			$term = fansub_get_term_by_slug('promo-codes', 'coupon_type');
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('promo-code', 'coupon_type');
			}
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('code', 'coupon_type');
			}
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('coupon-code', 'coupon_type');
			}
			if(!is_a($term, 'WP_Term')) {
				$term = fansub_get_term_by_slug('coupon-code', 'coupon_type');
			}
	}
	return $term;
}

function fansub_coupon_get_store_by_category($category) {
	$args = array(
		'post_type' => 'coupon',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => $category->taxonomy,
				'field' => 'id',
				'terms' => array($category->term_id)
			)
		)
	);
	$query = fansub_query($args);
	$result = array();
	if($query->have_posts()) {
		while($query->have_posts()) {
			$query->the_post();
			$terms = wp_get_object_terms(get_the_ID(), 'store');
			if(fansub_array_has_value($terms)) {
				$result = array_merge($result, $terms);
			}
		}
		wp_reset_postdata();
	}
	$result = array_unique($result, SORT_REGULAR);
	return $result;
}

function fansub_get_event_coupons($event_id, $args = array()) {
	$args['meta_key'] = 'event';
	$args['meta_value_num'] = $event_id;
	$args['post_type'] = 'coupon';
	return fansub_query($args);
}

function fansub_get_expired_coupons($args = array()) {
	$timestamp = current_time('timestamp', 0);
	$args['meta_key'] = 'expired_date';
	$args['meta_value'] = $timestamp;
	$args['meta_compare'] = '<';
	$args['meta_type'] = 'numeric';
	$meta_item = array(
		'key' => 'expired_date',
		'value' => $timestamp,
		'compare' => '<'
	);
	if(isset($args['meta_query'])) {
		foreach($args['meta_query'] as $i => $meta) {
			if(fansub_array_has_value($meta)) {
				foreach($meta as $j => $child_meta) {
					if('key' == $j && 'expired_date' == $child_meta) {
						unset($args['meta_query'][$i]);
					}
				}
			}
		}
	}
	$args = fansub_query_sanitize_meta_query($meta_item, $args);
	$meta_item = array(
		'key' => 'expired_date',
		'compare' => 'EXISTS'
	);
	$args = fansub_query_sanitize_meta_query($meta_item, $args);
	$args['meta_query']['relation'] = 'AND';
	$args['expired_coupon'] = true;
	return fansub_query($args);
}

function fansub_get_coupon_type($post_id = null) {
	$term = fansub_get_coupon_type_term($post_id);
	$result = array();
	if(is_a($term, 'WP_Term')) {
		$type = 'code';
		$text = $term->name;
		switch($term->slug) {
			case 'deal':
			case 'online-deal':
			case 'sale':
			case 'sales':
				$type = 'deal';
				$text = 'Deal';
				break;
			case 'in-store-coupons':
			case 'in-store-coupon':
			case 'in-store':
			case 'print':
			case 'printable':
				$type = 'printable';
				$text = 'Printable';
				break;
			default:
				$type = 'code';
				$text = 'Coupon';
		}
		$result[$type] = $text;
	}
	return $result;
}

function fansub_coupon_label_html($percent, $text, $type) {
	?>
	<div class="coupon-label-context text-center">
		<p class="percent"><?php echo $percent; ?></p>
		<p class="text"><?php echo $text; ?></p>
	</div>
	<div class="coupon-type text-center">
		<span><?php echo $type; ?></span>
	</div>
	<?php
}

function fansub_coupon_filter_bar_html($args = array()) {
	$term = fansub_get_value_by_key($args, 'term');
	$posts_per_page = fansub_get_value_by_key($args, 'posts_per_page', fansub_get_posts_per_page());
	$code_count = absint(fansub_get_value_by_key($args, 'code_count'));
	$deal_count = absint(fansub_get_value_by_key($args, 'deal_count'));
	?>
	<ul data-store="<?php echo $term->term_id; ?>" data-paged="<?php echo fansub_get_paged(); ?>" data-posts-per-page="<?php echo $posts_per_page; ?>" class="filter">
		<li>
			<a href="#" data-filter="all" class="active">All (<?php echo $term->count; ?>)</a>
		</li>
		<li>
			<a href="#" data-filter="coupon-code">Coupon Codes (<?php echo $code_count; ?>)</a>
		</li>
		<li>
			<a href="#" data-filter="promotion">Deals (<?php echo $deal_count; ?>)</a>
		</li>
	</ul>
	<?php
}

function fansub_coupon_button_html($args = array()) {
	$post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
	$type = fansub_get_value_by_key($args, 'type');
	$code_hint = fansub_get_value_by_key($args, 'code_hint');
	$type_text = fansub_get_value_by_key($args, 'type_text');
	$out_url = fansub_get_value_by_key($args, 'out_url', fansub_get_coupon_out_url($post_id));
	?>
	<a href="#coupon_box_<?php echo $post_id; ?>" data-post-id="<?php echo $post_id; ?>" class="code type-<?php echo $type; ?>" data-out-url="<?php echo $out_url; ?>" data-toggle="modal">
		<span class="cc"><?php echo $code_hint; ?></span>
		<span class="cc-label"><?php printf(__('Get %s', 'fansub'), $type_text); ?></span>
	</a>
	<?php
}

function fansub_coupon_button_code_html($args = array()) {
	$post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
	$code = fansub_get_value_by_key($args, 'code');
	if(empty($code) && fansub_id_number_valid($post_id)) {
		$code = fansub_get_coupon_code($post_id);
	}
	if(empty($code)) {
		return;
	}
	$out_url = fansub_get_value_by_key($args, 'out_url', fansub_get_coupon_out_url($post_id));
	$button_class = fansub_get_value_by_key($args, 'button_class');
	fansub_add_string_with_space_before($button_class, 'copy-button');
	$input_class = fansub_get_value_by_key($args, 'input_class');
	fansub_add_string_with_space_before($input_class, 'text');
	?>
	<div class="code clearfix">
		<input class="<?php echo $input_class; ?>" type="text" value="<?php echo $code; ?>" readonly>
		<a class="<?php echo $button_class; ?>" data-clipboard-text="<?php echo $code; ?>" data-out-url="<?php echo $out_url; ?>">Copy</a>
	</div>
	<?php
}

function fansub_coupon_vote_comment_html($args = array()) {
	$result = fansub_get_value_by_key($args, 'result');
	$post_id = fansub_get_value_by_key($args, 'post_id', get_the_ID());
	if(empty($result)) {
		$likes = fansub_get_post_meta('likes', $post_id);
		$dislikes = fansub_get_post_meta('dislikes', $post_id);
		$result = fansub_percentage($likes, $dislikes);
		$result = apply_filters('fansub_coupon_rating_percentage', $result, $likes, $dislikes);
		$result .= '%';
	}
	?>
	<p class="vote-result" data-post-id="<?php the_ID(); ?>">
		<i class="fa fa-thumbs-o-up"></i>
		<span><?php printf(__('%s Success', 'fansub'), $result); ?></span>
	</p>
	<?php
	if(comments_open($post_id) || get_comments_number($post_id)) {
		?>
		<p class="add-comment">
			<a href="#add_comment_<?php the_ID(); ?>">
				<i class="fa fa-comments-o"></i> <?php _e('Add a Comment', 'fansub'); ?>
			</a>
		</p>
		<?php
	}
}

function fansub_get_coupon_store($post_id = null) {
	if(!fansub_id_number_valid($post_id)) {
		$post_id = get_the_ID();
	}
	$term = new WP_Error();
	if(has_term('', 'store', $post_id)) {
		$terms = wp_get_post_terms($post_id, 'store');
		$term = current($terms);
	}
	return $term;
}

function fansub_get_store_out_link($term) {
	if(fansub_id_number_valid($term)) {
		$term = get_term($term, 'store');
	}
	$url = '';
	if(is_a($term, 'WP_Term')) {
		$url = home_url('go-store/' . $term->slug);
	}
	return $url;
}

function fansub_get_coupon_out_url($post_id) {
	if(is_a($post_id, 'WP_Post')) {
		$post_id = $post_id->ID;
	}
	$url = home_url('out/' . $post_id);
	return $url;
}

function fansub_get_store_by_slug($slug) {
	return fansub_get_term_by_slug($slug, 'store');
}

$fansub_coupon_site = apply_filters('fansub_coupon_site', false);

if(!(bool)$fansub_coupon_site) {
	return;
}

global $pagenow;

if('edit-tags.php' == $pagenow || 'term.php' == $pagenow) {
	fansub_term_meta_different_name_field(array('store', 'coupon_cat'));
	fansub_term_meta_thumbnail_field(array('store'));
	$meta = new FANSUB_Meta('term');
	$meta->set_taxonomies(array('store'));
	$meta->add_field(array('id' => 'site', 'label' => __('Store URL', 'fansub')));
	$meta->init();
}

if('post-new.php' == $pagenow || 'post.php' == $pagenow) {
	fansub_meta_box_post_attribute(array('coupon'));
	$meta = new FANSUB_Meta('post');
	$meta->set_post_types(array('coupon'));
	$meta->set_id('fansub_coupon_information');
	$meta->set_title(__('Coupon Information', 'fansub'));
	$meta->add_field(array('id' => 'percent_label', 'label' => __('Percent Label:', 'fansub')));
	$meta->add_field(array('id' => 'text_label', 'label' => __('Text Label:', 'fansub')));
	$meta->add_field(array('id' => 'coupon_code', 'label' => __('Code:', 'fansub')));
	$meta->add_field(array('id' => 'expired_date', 'label' => __('Expires:', 'fansub'), 'field_callback' => 'fansub_field_datetime_picker', 'data_type' => 'timestamp', 'min_date' => 0, 'date_format' => 'm/d/Y'));
	$meta->add_field(array('id' => 'url', 'label' => __('URL:', 'fansub')));
	$meta->init();
}

function fansub_coupon_on_save_post($post_id) {
	if(!fansub_can_save_post($post_id)) {
		return;
	}
	$current_post = get_post($post_id);
	if(!has_term('', 'coupon_type', $post_id) && !empty($current_post->post_title)) {
		wp_set_object_terms($post_id, 'Promo Codes', 'coupon_type');
	}
	if('coupon' == $current_post->post_type) {
		$event = fansub_get_method_value('event');
		update_post_meta($post_id, 'event', $event);
	}
}
add_action('save_post', 'fansub_coupon_on_save_post');

function fansub_coupon_update_post_class($classes) {
	global $post;
	if('coupon' == $post->post_type) {
		$post_id = $post->ID;
		$type = fansub_get_coupon_type($post_id);
		$type = array_search(current($type), $type);
		if(!empty($type)) {
			$classes[] = 'coupon-type-' . $type;
			if('code' == $type) {
				$code = fansub_get_coupon_code($post_id);
				if(empty($code)) {
					$classes[] = 'coupon-no-code';
				}
			}
		}
	}
	return $classes;
}
add_filter('post_class', 'fansub_coupon_update_post_class');

function fansub_coupon_on_init_hook() {
	add_rewrite_endpoint('go-store', EP_ALL);
	add_rewrite_endpoint('out', EP_ALL);
}
add_action('init', 'fansub_coupon_on_init_hook');

function fansub_coupon_on_wp_hook() {
	$store = get_query_var('go-store');
	if(!empty($store)) {
		$term = fansub_get_store_by_slug($store);
		if(is_a($term, 'WP_Term')) {
			$url = fansub_get_store_url($term->term_id);
			if(!empty($url)) {
				wp_redirect($url);
				exit;
			} else {
				wp_redirect(home_url('/'));
				exit;
			}
		}
	}
	$out = get_query_var('out');
	if(!empty($out)) {
		$url = fansub_get_coupon_url($out);
		if(!empty($url)) {
			wp_redirect($url);
			exit;
		} else {
			wp_redirect(home_url('/'));
			exit;
		}
	}
}
add_action('wp', 'fansub_coupon_on_wp_hook');

function fansub_coupon_pre_get_posts($query) {
	if($query->is_main_query()) {
		if(is_tax('store')) {
			$posts_per_page = apply_filters('fansub_archive_coupon_posts_per_page', 15);
			$query->set('posts_per_page', $posts_per_page);
		} elseif(is_search()) {
			$query->set('post_type', 'coupon');
		}
		if(is_post_type_archive('coupon') || is_search() || is_tax('store') || is_tax('coupon_cat') || is_tax('coupon_tag')) {
			$exclude_expired = apply_filters('fansub_exclude_expired_coupon', false);
			if($exclude_expired) {
				$query_vars = $query->query_vars;
				$expired_coupon = (bool)fansub_get_value_by_key($query_vars, 'expired_coupon');
				if(!$expired_coupon) {
					$meta_query = fansub_get_value_by_key($query_vars, 'meta_query');
					if(fansub_array_has_value($meta_query)) {
						foreach($meta_query as $meta) {
							if(fansub_array_has_value($meta)) {
								foreach($meta as $child_meta) {
									if(fansub_array_has_value($child_meta)) {
										$key = fansub_get_value_by_key($child_meta, 'key');
										$value = fansub_get_value_by_key($child_meta, 'value');
										$compare = fansub_get_value_by_key($child_meta, 'compare');
										if('expired_date' == $key && is_numeric($value) && '<' == $compare) {
											$expired_coupon = true;
											break;
										}
									}
								}
							}
						}
					}
				}
				if(!$expired_coupon) {
					$current_date_time = fansub_get_current_date('m/d/Y');
					$timestamp = current_time('timestamp', 0);
					$meta_item = array(
						'relation' => 'OR',
						array(
							'key' => 'expired_date',
							'value' => $timestamp,
							'type' => 'numeric',
							'compare' => '>='
						),
						array(
							'key' => 'expired_date',
							'compare' => 'NOT EXISTS'
						)
					);
					$args = array(
						$meta_item
					);
					$query->set('meta_query', $args);
				}
			}
		}
	}
	return $query;
}
if(!is_admin()) add_action('pre_get_posts', 'fansub_coupon_pre_get_posts');

function fansub_coupon_filter_ajax_callback() {
	$result = array(
		'have_posts' => false
	);
	$term = fansub_get_method_value('term');
	$filter = fansub_get_method_value('filter');
	if(fansub_id_number_valid($term)) {
		$posts_per_page = fansub_get_method_value('posts_per_page');
		$paged = fansub_get_method_value('paged');
		$args = array(
			'post_type' => 'coupon',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'store',
					'field' => 'id',
					'terms' => array($term)
				)
			)
		);
		$type_object = new WP_Error();
		switch($filter) {
			case 'coupon-code';
				$type_object = fansub_get_coupon_type_object();
				break;
			case 'promotion':
				$type_object = fansub_get_coupon_type_object('deal');
				break;
		}
		if(is_a($type_object, 'WP_Term')) {
			$tax_item = array(
				'taxonomy' => 'coupon_type',
				'field' => 'id',
				'terms' => array($type_object->term_id)
			);
			$args = fansub_query_sanitize_tax_query($tax_item, $args);
		}
		$query = fansub_query($args);
		$result['have_posts'] = $query->have_posts();
		if($query->have_posts()) {
			$html_data = '';
			while($query->have_posts()) {
				$query->the_post();
				ob_start();
				fansub_theme_get_loop('archive-coupon');
				$html_data .= ob_get_clean();
			}
			wp_reset_postdata();
			$result['html_data'] = $html_data;
		}
	}
	echo json_encode($result);
	exit;
}
add_action('wp_ajax_fansub_coupon_filter', 'fansub_coupon_filter_ajax_callback');
add_action('wp_ajax_nopriv_fansub_coupon_filter', 'fansub_coupon_filter_ajax_callback');

function fansub_coupon_attribute_meta_box_field($meta) {
	if(!is_object($meta)) {
		return;
	}
	global $post;
	$meta_id = $post->post_type . '_attributes';
	$meta_id = fansub_sanitize_id($meta_id);
	if('coupon' == $post->post_type && $meta->get_id() == $meta_id) {
		$query = fansub_query(array('post_type' => 'event', 'posts_per_page' => -1));
		$all_option = '<option value=""></option>';
		$selected = get_post_meta($post->ID, 'event', true);
		foreach($query->posts as $qpost) {
			$all_option .= fansub_field_get_option(array('value' => $qpost->ID, 'text' => $qpost->post_title, 'selected' => $selected));
		}
		$args = array(
			'id' => 'event_chosen',
			'name' => 'event',
			'all_option' => $all_option,
			'value' => $selected,
			'class' => 'widefat',
			'label' => fansub_uppercase_first_char_only('Event') . ':',
			'placeholder' => __('Choose parent post', 'fansub')
		);
		fansub_field_select_chosen($args);
	}
}
add_action('fansub_post_meta_box_field', 'fansub_coupon_attribute_meta_box_field');

if('post.php' == $pagenow || 'post-new.php' == $pagenow) {
	add_filter('fansub_use_chosen_select', '__return_true');
}

if('options-permalink.php' == $pagenow || true) {
	$data = get_option('fansub_permalink');
	$option = new FANSUB_Option('', 'permalink');
	$option->set_parent_slug('options-permalink.php');
	$option->set_update_option(true);
	$option->add_field(array('value' => fansub_get_value_by_key($data, 'coupon_store_base'), 'id' => 'coupon_store_base', 'title' => __('Coupon store base', 'fansub'), 'section' => 'optional', 'placeholder' => fansub_coupon_store_base()));
	$option->add_field(array('value' => fansub_get_value_by_key($data, 'coupon_category_base'), 'id' => 'coupon_category_base', 'title' => __('Coupon category base', 'fansub'), 'section' => 'optional', 'placeholder' => fansub_coupon_category_base()));
	$option->add_field(array('value' => fansub_get_value_by_key($data, 'coupon_tag_base'), 'id' => 'coupon_tag_base', 'title' => __('Coupon tag base', 'fansub'), 'section' => 'optional', 'placeholder' => fansub_coupon_tag_base()));
	$option->add_field(array('value' => fansub_get_value_by_key($data, 'coupon_type_base'), 'id' => 'coupon_type_base', 'title' => __('Coupon type base', 'fansub'), 'section' => 'optional', 'placeholder' => fansub_coupon_type_base()));
	$option->init();
}

function fansub_coupon_filter_taxonomy_base($base, $taxonomy) {
	switch($taxonomy) {
		case 'store':
			$base = 'store';
			break;
		case 'coupon_cat':
			$base = 'coupon_cat';
			break;
	}
	return $base;
}
add_filter('fansub_remove_term_base_taxonomy_base', 'fansub_coupon_filter_taxonomy_base', 10, 2);