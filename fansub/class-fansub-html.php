<?php
if(!function_exists('add_filter')) exit;
if(defined('FANSUB_HTML_VERSION')) {
    return;
}
define('FANSUB_HTML_VERSION', '1.0.0');
class FANSUB_HTML {
    private $self_closers = array();
    public $name = null;
    public $attributes = array();
    public $break_line = true;
    public $close = true;
    public $only_text = false;
    public $wrap_tag = '';

    public function set_close($close) {
        $this->close = $close;
    }

    public function get_close() {
        return $this->close;
    }

    public function __construct($name) {
        $this->set_name($name);
        if('img' == $name) {
            $this->set_image_alt("");
        }
    }

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = strtolower($name);
    }

    public function use_only_text() {
        $this->only_text = true;
    }

    public function get_self_closers() {
        $self_closers = array('input', 'img', 'hr', 'br', 'meta', 'link');
        $this->set_self_closers($self_closers);
        return $this->self_closers;
    }

    private function set_self_closers($self_closers) {
        $this->self_closers = $self_closers;
    }

    public function get_attribute($attribute_name) {
        if($this->is_attribute_exists($attribute_name)) {
            return $this->attributes[$attribute_name];
        }
        return null;
    }

    public function set_attribute($attribute_name, $value) {
        $this->attributes[$attribute_name] = $value;
    }

    public function set_image_src($src) {
        $this->set_attribute('src', $src);
    }

    public function set_image_alt($alt) {
        $this->set_attribute('alt', $alt);
    }

    public function set_class($class) {
        $this->set_attribute('class', $class);
    }

    public function set_id($id) {
        $id = fansub_sanitize_id($id);
        $this->set_attribute('id', $id);
    }

    public function add_class($class) {
        $old_class = $this->get_attribute('class');
        fansub_add_string_with_space_before($old_class, $class);
        $this->set_class($old_class);
    }

    public function set_href($href) {
        $this->set_attribute('href', $href);
    }

    public function set_html($value) {
        $this->set_attribute('text', $value);
    }

    public function set_text($value) {
        if(is_a($value, 'FANSUB_HTML')) {
            $value = $value->build();
        }
        if('input' == $this->get_name()) {
            $this->set_attribute('value', $value);
        } else {
            $this->set_html($value);
        }
    }

    public function set_attribute_array($attributes) {
        if(is_array($attributes)) {
            $this->attributes = wp_parse_args($attributes, $this->attributes);
        }
    }

    public function remove_attribute($attribute_name) {
        if($this->is_attribute_exists($attribute_name)) {
            unset($this->attributes[$attribute_name]);
        }
    }

    public function text_exsits() {
        $text = $this->get_attribute('text');
        if(!empty($text)) {
            return true;
        }
        return false;
    }

    public function remove_all_attribute() {
        $this->attributes = array();
    }

    private function make_outlink_nofollow() {
        if('a' == $this->get_name()) {
            $href = $this->get_attribute('href');
            if(!empty($href)) {
                if(!fansub_is_site_domain($href)) {
                    $this->set_attribute('rel', 'external nofollow');
                    $this->set_attribute('target', '_blank');
                }
            }
        }
    }

    private function check_html() {
        $this->make_outlink_nofollow();
    }

    public function build() {
        $wrap_tag = $this->get_wrap_tag();
        if($this->only_text) {
            return $this->get_attribute('text');
        }
        $this->check_html();
        $html_name = $this->get_name();
        $result = '<' . $html_name;
        if(!empty($wrap_tag)) {
            $result = '<' . $wrap_tag . '>' . $result;
        }
        foreach($this->attributes as $key => $value) {
            if($key != 'text') {
                $result .= sprintf(' %1$s="%2$s"', $key, trim(esc_attr($value)));
            }
        }
        $result .= '>';
        if(!in_array($html_name, $this->get_self_closers())) {
            $text = $this->get_attribute('text');
            $result .= $text;
        }
        if($this->get_close() && !in_array($html_name, $this->get_self_closers())) {
            $result .= sprintf('</%s>', $html_name);
        }
        if(!empty($wrap_tag)) {
            $result .= '</' . $wrap_tag . '>';
        }
        return $result;
    }

    public function set_break_line($break_line) {
        $this->break_line = $break_line;
    }

    public function get_break_line() {
        return $this->break_line;
    }

    public function output() {
        $html = $this->build();
        if($this->get_break_line()) {
            $html .= PHP_EOL;
        }
        echo $html;
    }

    public function is_attribute_exists($attribute_name) {
        return array_key_exists($attribute_name, $this->attributes);
    }

    public function set_wrap_tag($tag) {
        $this->wrap_tag = $tag;
    }

    public function get_wrap_tag() {
        return $this->wrap_tag;
    }
}