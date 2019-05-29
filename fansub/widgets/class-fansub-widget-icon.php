<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Icon extends WP_Widget {
    public $args = array();
    public $admin_args;

    private function get_defaults() {
        $defaults = array(
            'title_link' => 0
        );
        $defaults = apply_filters('fansub_widget_icon_defaults', $defaults);
        $args = apply_filters('fansub_widget_icon_args', array());
        $args = wp_parse_args($args, $defaults);
        return $args;
    }

    public function __construct() {
        $this->args = $this->get_defaults();
        $this->admin_args = array(
            'id' => 'fansub_widget_icon',
            'name' => 'FANSUB Icon',
            'class' => 'fansub-icon-widget',
            'description' => __('Display widget with icon.', 'fansub'),
            'width' => 400
        );
        $this->admin_args = apply_filters('fansub_widget_icon_admin_args', $this->admin_args);
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
        $sidebar = fansub_get_value_by_key($args, 'id', 'default');
        $title = fansub_widget_title($args, $instance, false);
        $title_link = fansub_get_value_by_key($instance, 'title_link', fansub_get_value_by_key($this->args, 'title_link'));
        $icon = fansub_get_value_by_key($instance, 'icon');
        $icon = fansub_sanitize_media_value($icon);
        $icon_url = $icon['url'];
        $icon_hover = fansub_get_value_by_key($instance, 'icon_hover');
        $icon_hover = fansub_sanitize_media_value($icon_hover);
        $icon_hover_url = $icon_hover['url'];
        $link = fansub_get_value_by_key($instance, 'link');
        $text = fansub_get_value_by_key($instance, 'text');
        fansub_widget_before($args, $instance, false);
        $widget_html = '';
        if(!empty($icon_url)) {
            $widget_html .= '<a href="' . $link . '"><img class="icon" src="' . $icon_url . '" alt="" data-hover="' . $icon_hover_url . '"></a>';
        }
        if((bool)$title_link) {
            $title = '<a href="' . $link . '">' . $title . '</a>';
            $title = apply_filters('fansub_widget_icon_title_html', $title, $instance, $args, $sidebar);
        }
        $widget_html .= $title;
        $widget_html .= '<div class="text">' . fansub_get_rich_text($text) . '</div>';
        $widget_html = apply_filters($this->option_name . '_html', $widget_html, $instance, $widget_args = $args, $widget_number = $this->number, $sidebar_id = $sidebar);
        $widget_html = apply_filters($this->option_name . '_' . $sidebar . '_html', $widget_html, $instance, $widget_args = $args, $widget_number = $this->number);
        $widget_html = apply_filters('fansub_widget_icon_html', $widget_html, $args, $instance, $this);
        echo $widget_html;
        fansub_widget_after($args, $instance);
    }

    public function form($instance) {
        $title = fansub_get_value_by_key($instance, 'title');
        $icon = fansub_get_value_by_key($instance, 'icon');
        $icon = fansub_sanitize_media_value($icon);
        $icon_hover = fansub_get_value_by_key($instance, 'icon_hover');
        $icon_hover = fansub_sanitize_media_value($icon_hover);
        $link = fansub_get_value_by_key($instance, 'link');
        $text = fansub_get_value_by_key($instance, 'text');
        $title_link = fansub_get_value_by_key($instance, 'title_link', fansub_get_value_by_key($this->args, 'title_link'));
        fansub_field_widget_before($this->admin_args['class']);
        fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

        $args = array(
            'id' => $this->get_field_id('icon'),
            'name' => $this->get_field_name('icon'),
            'value' => $icon['url'],
            'label' => __('Icon:', 'fansub')
        );
        fansub_widget_field('fansub_field_media_upload', $args);

        $args = array(
            'id' => $this->get_field_id('icon_hover'),
            'name' => $this->get_field_name('icon_hover'),
            'value' => $icon_hover['url'],
            'label' => __('Icon hover:', 'fansub')
        );
        fansub_widget_field('fansub_field_media_upload', $args);

        $args = array(
            'id' => $this->get_field_id('link'),
            'name' => $this->get_field_name('link'),
            'value' => $link,
            'label' => __('Link:', 'fansub')
        );
        fansub_widget_field('fansub_field_input_text', $args);

        $args = array(
            'id' => $this->get_field_id('text'),
            'name' => $this->get_field_name('text'),
            'value' => $text,
            'label' => __('Text:', 'fansub')
        );
        fansub_widget_field('fansub_field_textarea', $args);

        $args = array(
            'id' => $this->get_field_id('title_link'),
            'name' => $this->get_field_name('title_link'),
            'value' => $title_link,
            'label' => __('Display title as link?', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        fansub_field_widget_after();
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
        $instance['icon'] = fansub_get_value_by_key($new_instance, 'icon');
        $instance['icon_hover'] = fansub_get_value_by_key($new_instance, 'icon_hover');
        $instance['link'] = esc_url(fansub_get_value_by_key($new_instance, 'link'));
        $instance['title_link'] = fansub_checkbox_post_data_value($new_instance, 'title_link');
        $instance['text'] = fansub_get_value_by_key($new_instance, 'text');
        return $instance;
    }
}