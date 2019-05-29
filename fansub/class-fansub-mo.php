<?php
if(!function_exists('add_filter')) exit;
class FANSUB_MO extends MO {
	protected $post_id;

	public function __construct($post_id = null) {
		if(!post_type_exists('fansub_mo')) {
			$this->register_post_type();
		}
		$this->post_id = $post_id;
	}

	public function register_post_type() {
		$args = array(
			'name' => __('String Translation', 'fansub'),
			'slug' => 'fansub_mo'
		);
		fansub_register_post_type_private($args);
	}

	public function get_object($string, $encrypted = false) {
		$post = get_post($this->get_id($string, $encrypted));
		if(is_a($post, 'WP_Post') && 'fansub_mo' == $post->post_type) {
			return $post;
		}
		return null;
	}

	public function export_to_db($string, $translation = '') {
		$encrypted_string = md5($string);
		$query = fansub_get_post_by_meta('encrypted_string', $encrypted_string);
		$post_id = $this->post_id;
		$post_title = $this->build_post_title($string);
		$postarr = array(
			'post_content' => $translation,
			'post_type' => 'fansub_mo',
			'post_title' => $post_title,
			'post_status' => 'private',
			'post_excerpt' => $string
		);
		if(!$query->have_posts()) {
			if(fansub_id_number_valid($post_id)) {
				$postarr['ID'] = $post_id;
			} else {
				$mo = $this->get_object($string);
				if(is_a($mo, 'WP_Post')) {
					$postarr['ID'] = $mo->ID;
				}
			}
		} else {
			if(fansub_id_number_valid($this->post_id)) {
				$postarr['ID'] = $this->post_id;
			}
		}
		$post_id = fansub_insert_post($postarr);
		if(fansub_id_number_valid($post_id)) {
			update_post_meta($post_id, 'encrypted_string', $encrypted_string);
		}
		return $post_id;
	}

	public function import_from_db($string) {
		$translation = '';
		if(!empty($string)) {
			$post = $this->get_object($string);
			if(is_a($post, 'WP_Post')) {
				$translation = $post->post_content;
			}
		}
		return $translation;
	}

	public function delete_from_db($string, $encrypted = false) {
		$post = $this->get_object($string, $encrypted);
		if(is_a($post, 'WP_Post')) {
			wp_delete_post($post->ID, true);
		}
	}

	private function build_post_title($string, $encrypted = false) {
		if(!$encrypted) {
			$string = md5($string);
		}
		return 'fansub_mo_' . $string;
	}

	public function get_id($string, $encrypted = false) {
		$string = $this->build_post_title($string, $encrypted);
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", $string, 'fansub_mo'));
	}
}