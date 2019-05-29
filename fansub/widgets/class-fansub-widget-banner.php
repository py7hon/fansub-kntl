<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Banner extends WP_Widget {
    public $args = array();
    public $admin_args;

    private function get_defaults() {
        $defaults = array();
        $defaults = apply_filters('fansub_widget_banner_defaults', $defaults);
        $args = apply_filters('fansub_widget_banner_args', array());
        $args = wp_parse_args($args, $defaults);
        return $args;
    }

    public function __construct() {
        $this->args = $this->get_defaults();
        $this->admin_args = array(
            'id' => 'fansub_widget_banner',
            'name' => 'FANSUB Banner',
            'class' => 'fansub-banner-widget',
            'description' => __('Display banner on sidebar.', 'fansub'),
            'width' => 400
        );
        $this->admin_args = apply_filters('fansub_widget_banner_admin_args', $this->admin_args);
        parent::__construct($this->admin_args['id'], $this->admin_args['name'],
            array(
                'classname' => $this->admin_args['class'],
                'description' => $this->admin_args['description'],
            ),
            array(
                'width' => $this->admin_args['width']
            )
        );
    }

    public function widget($args, $instance) {
        $title_text = isset($instance['title']) ? $instance['title'] : '';
        $first_char = fansub_get_first_char($title_text);
        if('!' === $first_char) {
            $title_text = ltrim($title_text, '!');
        }
        $banner_image = isset($instance['banner_image']) ? $instance['banner_image'] : '';
        $banner_url = isset($instance['banner_url']) ? $instance['banner_url'] : '';
        $banner_image = fansub_sanitize_media_value($banner_image);
        $banner_image = $banner_image['url'];
        if(!empty($banner_image)) {
            fansub_widget_before($args, $instance);
            $img = new FANSUB_HTML('img');
            $img->set_image_src($banner_image);
            $img->set_image_alt($title_text);
            $img->set_class('fansub-banner-image');
            $html = $img->build();
            if(!empty($banner_url)) {
                $a = new FANSUB_HTML('a');
                $a->set_class('fansub-banner-link');
                $a->set_attribute('title', $title_text);
                $a->set_href($banner_url);
                $a->set_text($html);
                $html = $a->build();
            }
            $widget_html = apply_filters('fansub_widget_banner_html', $html, $instance, $args, $this);
            echo $widget_html;
            fansub_widget_after($args, $instance);
        }
    }

    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $banner_image = isset($instance['banner_image']) ? $instance['banner_image'] : '';
        $banner_url = isset($instance['banner_url']) ? $instance['banner_url'] : '';
        fansub_field_widget_before($this->admin_args['class']);
        fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

        $args = array(
            'id' => $this->get_field_id('banner_image'),
            'name' => $this->get_field_name('banner_image'),
            'value' => $banner_image,
            'label' => __('Image url:', 'fansub')
        );
        fansub_widget_field('fansub_field_media_upload', $args);

        $args = array(
            'id' => $this->get_field_id('banner_url'),
            'name' => $this->get_field_name('banner_url'),
            'value' => $banner_url,
            'label' => __('Image link:', 'fansub')
        );
        fansub_widget_field('fansub_field_input_text', $args);

        fansub_field_widget_after();
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
        $instance['banner_image'] = fansub_get_value_by_key($new_instance, 'banner_image');
        $instance['banner_url'] = fansub_get_value_by_key($new_instance, 'banner_url');
        return $instance;
    }
}