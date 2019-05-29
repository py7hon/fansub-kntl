<?php

if(!function_exists('add_filter')) exit;
class FANSUB_Remove_Term_Base {
	private $query_var;
	private $query_var_redirect;
	private $base;
	private $taxonomy;

	public function set_taxonomy($taxonomy) {
		$this->taxonomy = $taxonomy;
	}

	public function set_base($base) {
		$this->base = $base;
		$this->set_query_var($this->taxonomy);
	}

	public function set_query_var($query_var) {
		$this->query_var = $query_var;
		$this->set_query_var_redirect($this->query_var . '_redirect');
	}

	public function set_query_var_redirect($query_var_redirect) {
		$this->query_var_redirect = $query_var_redirect;
	}

	public function __construct($taxonomy) {
		$this->set_taxonomy($taxonomy);
		$this->base = $this->get_taxonomy_base($taxonomy);
		$this->query_var = $this->get_taxonomy_query_var($taxonomy);
		$this->query_var_redirect = $this->query_var . '_redirect';
	}

	public function get_taxonomy_query_var($taxonomy) {
		$result = '';
		if(taxonomy_exists($taxonomy)) {
			$taxonomy = get_taxonomy($taxonomy);
			$result = $taxonomy->query_var;
		}
		return $result;
	}

	public function get_taxonomy_base($taxonomy = null) {
		if(empty($taxonomy)) {
			$taxonomy = $this->taxonomy;
		}
		$base_slug = '';
		if(taxonomy_exists($taxonomy)) {
			$taxonomy = get_taxonomy($taxonomy);
			$base_slug = fansub_get_value_by_key($taxonomy->rewrite, 'slug');
		}
		return $base_slug;
	}

	public function add_permastructs() {
		$this->extra_permastructs($this->taxonomy, '%' . $this->taxonomy . '%');
	}

	public function add_query_vars($query_vars) {
		return $this->add_query_var($query_vars, $this->query_var_redirect);
	}

	public function control_request($query_vars) {
		$this->control_request_helper($this->taxonomy, $query_vars, $this->query_var_redirect);
		return $query_vars;
	}

	public function term_link($termlink, $term, $taxonomy) {
		if($taxonomy == $this->taxonomy) {
			if(!empty($this->base)) {
				$termlink = str_replace('/' . $this->base . '/', '/' , $termlink);
			}
			$termlink = apply_filters('fansub_rewrite_term_link', $termlink, $term);
		}
		return $termlink;
	}

	public function term_rewrite_rules($rules) {
		$rules = $this->add_rewrite_rule($this->taxonomy, $this->get_taxonomy_base(), $this->query_var, $this->query_var_redirect, $rules);
		return $rules;
	}

	public function flush_rewrite_rule() {
		flush_rewrite_rules();
	}

	public function init() {
		if(!fansub_pretty_permalinks_enabled()) {
			return;
		}
		if('category' == $this->taxonomy) {
			$this->remove_category_base();
		} elseif('post_tag' == $this->taxonomy) {
			$this->remove_post_tag_base_slug();
		} else {
			$base = apply_filters('fansub_remove_term_base_taxonomy_base', $this->base, $this->taxonomy);
			$this->set_base($base);
			if(empty($this->base) || empty($this->query_var)) {
				return;
			}
			add_action('init', array($this, 'add_permastructs'));
			add_filter('query_vars', array($this, 'add_query_vars'));
			add_filter('request', array($this, 'control_request'));
			add_filter($this->taxonomy . '_rewrite_rules', array($this, 'term_rewrite_rules'));
			add_filter('term_link', array($this, 'term_link'), 10, 3);
			add_action('created_' . $this->taxonomy, array($this, 'flush_rewrite_rule'));
			add_action('edited_' . $this->taxonomy, array($this, 'flush_rewrite_rule'));
			add_action('delete_' . $this->taxonomy, array($this, 'flush_rewrite_rule'));
		}
	}

	public function get_taxonomy_base_slug($key, $default) {
		$saved = get_option($key);
		$tax_base = (empty($saved)) ? $default : $saved;
		return $tax_base;
	}

	public function extra_permastructs($taxonomy_name, $tag_struct) {
		global $wp_rewrite;
		$is_old_wp_version = version_compare($GLOBALS['wp_version'], '3.4', '<');
		if($is_old_wp_version) {
			$wp_rewrite->extra_permastructs[$taxonomy_name][0] = $tag_struct;
		} else {
			$wp_rewrite->extra_permastructs[$taxonomy_name]['struct'] = $tag_struct;
		}
	}

	public function add_query_var($query_vars, $query_var) {
		$query_vars[] = $query_var;
		return $query_vars;
	}

	public function control_request_helper($taxonomy_name, $query_vars, $query_var) {
		if(isset($query_vars[$query_var])) {
			$term_name = user_trailingslashit($query_vars[$query_var], $taxonomy_name);
			$term_permalink = home_url($term_name);
			wp_redirect($term_permalink, 301);
			exit;
		}
	}

	public function add_rewrite_rule($taxonomy_name, $tax_base, $wp_query_var, $query_var, $rules) {
		$rules = array();
		$terms = fansub_get_terms($taxonomy_name, array('hide_empty' => false));
		foreach($terms as $term) {
			$rules['(' . $term->slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?' . $wp_query_var . '=$matches[1]&feed=$matches[2]';
			$rules['(' . $term->slug . ')/page/?([0-9]{1,})/?$'] = 'index.php?' . $wp_query_var . '=$matches[1]&paged=$matches[2]';
			$rules['(' . $term->slug . ')/?$'] = 'index.php?' . $wp_query_var . '=$matches[1]';
		}
		$tax_base = trim($tax_base, '/');
		$rules[$tax_base . '/(.*)$'] = 'index.php?' . $query_var . '=$matches[1]';
		return $rules;
	}

	// Remove category (/category/ into /) base slug from url

	public function remove_category_base() {
		add_action('init', array($this, 'category_extra_permastructs'));
		add_filter('query_vars', array($this, 'category_query_vars'));
		add_filter('request', array($this, 'category_request'));
		add_filter('category_rewrite_rules', array($this, 'category_rewrite_rules'));
		add_filter('category_link', array($this, 'category_link'));
		add_action('created_category', array($this, 'flush_rewrite_rule'));
		add_action('edited_category', array($this, 'flush_rewrite_rule'));
		add_action('delete_category', array($this, 'flush_rewrite_rule'));
	}

	public function get_category_base() {
		return $this->get_taxonomy_base_slug('category_base', 'category');
	}

	public function category_extra_permastructs() {
		$this->extra_permastructs('category', '%category%');
	}

	public function category_query_vars($public_query_vars) {
		return $this->add_query_var($public_query_vars, 'category_redirect');
	}

	public function category_request($query_vars) {
		$this->control_request_helper('category', $query_vars, 'category_redirect');
		return $query_vars;
	}

	public function category_rewrite_rules($rules) {
		$rules = $this->add_rewrite_rule('category', $this->get_category_base(), 'category_name', 'category_redirect', $rules);
		return $rules;
	}

	public function category_link($termlink) {
		$category_base = $this->get_category_base();
		return str_replace('/' . $category_base . '/', '/' , $termlink);
	}

	// Remove post_tag (/tag/ into /) base slug from url

	public function remove_post_tag_base_slug() {
		add_action('init', array($this, 'tag_extra_permastructs'));
		add_filter('query_vars', array($this, 'tag_query_vars'));
		add_filter('request', array($this, 'tag_request'));
		add_filter('tag_rewrite_rules', array($this, 'tag_rewrite_rules'));
		add_filter('tag_link', array($this, 'tag_link'));
		add_action('created_post_tag', array($this, 'flush_rewrite_rule'));
		add_action('edited_post_tag', array($this, 'flush_rewrite_rule'));
		add_action('delete_post_tag', array($this, 'flush_rewrite_rule'));
	}

	public function get_tag_base() {
		return $this->get_taxonomy_base_slug('tag_base', 'tag');
	}

	public function tag_extra_permastructs() {
		$this->extra_permastructs('post_tag', '%post_tag%');
	}

	public function tag_query_vars($public_query_vars) {
		return $this->add_query_var($public_query_vars, 'tag_redirect');
	}

	public function tag_request($query_vars) {
		$this->control_request_helper('post_tag', $query_vars, 'tag_redirect');
		return $query_vars;
	}

	public function tag_rewrite_rules($rules) {
		$rules = $this->add_rewrite_rule('post_tag', $this->get_tag_base(), 'tag', 'tag_redirect', $rules);
		return $rules;
	}

	public function tag_link($termlink) {
		$tag_base = $this->get_tag_base();
		return str_replace('/' . $tag_base . '/', '/' , $termlink);
	}
}