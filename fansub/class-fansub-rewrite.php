<?php

if(!function_exists('add_filter')) exit;
class FANSUB_Rewrite {
	private $taxonomies = array();
	private $tax_objects = array();

	public function set_taxonomy_objects($tax_objects) {
		$this->tax_objects = $tax_objects;
	}

	public function add_taxonomy_object($tax_object) {
		if(fansub_object_valid($tax_object)) {
			if(!isset($this->tax_objects[$tax_object->name])) {
				$this->tax_objects[$tax_object->name] = $tax_object;
			}
		}
	}

	public function get_taxonomy_objects() {
		return $this->tax_objects;
	}

	public function set_taxonomies($taxonomies) {
		$this->taxonomies = $taxonomies;
		foreach($taxonomies as $taxonomy) {
			$this->add_taxonomy_object(get_taxonomy($taxonomy));
		}
	}

	public function add_taxonomy($taxonomy) {
		if(!array_search($taxonomy, $this->get_taxonomies())) {
			$this->taxonomies[] = $taxonomy;
			$this->add_taxonomy_object(get_taxonomy($taxonomy));
		}
	}

	public function get_taxonomies() {
		return $this->taxonomies;
	}

	public function __construct() {

	}

	public function remove_taxonomy_base() {
		$taxonomies = $this->get_taxonomies();
		foreach($taxonomies as $taxonomy) {
			$remove_base = new FANSUB_Remove_Term_Base($taxonomy);
			switch($taxonomy) {
				case 'category':
					$remove_base->set_base('category');
					break;
				case 'post_tag':
					$remove_base->set_base('tag');
					break;
			}
			$remove_base->init();
		}
	}
}