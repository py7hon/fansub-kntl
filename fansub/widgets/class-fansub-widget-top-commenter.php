<?php
if(!function_exists('add_filter')) exit;
class FANSUB_Widget_Top_Commenter extends WP_Widget {
    public $args = array();
    public $admin_args;

    private function get_defaults() {
        $defaults = array(
            'number' => 5,
            'time' => 'week',
            'times' => array(
                'today' => __('Today', 'fansub'),
                'week' => __('This week', 'fansub'),
                'month' => __('This month', 'fansub'),
                'year' => __('This year', 'fansub'),
                'all' => __('All time', 'fansub')
            ),
            'show_count' => true,
            'link_author_name' => true,
            'none_text' => __('There is no commenter in this list.', 'fansub')
        );
        $defaults = apply_filters('fansub_widget_top_commenter_defaults', $defaults);
        $args = apply_filters('fansub_widget_top_commenter_args', array());
        $args = wp_parse_args($args, $defaults);
        return $args;
    }

    public function __construct() {
        $this->args = $this->get_defaults();
        $this->admin_args = array(
            'id' => 'fansub_widget_top_commenter',
            'name' => 'FANSUB Top Commenter',
            'class' => 'fansub-top-commenter-widget',
            'description' => __('Get top commenters on your site.', 'fansub'),
            'width' => 400
        );
        $this->admin_args = apply_filters('fansub_widget_top_commenter_admin_args', $this->admin_args);
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
        $number = fansub_get_value_by_key($instance, 'number', fansub_get_value_by_key($this->args, 'number'));
        $time = fansub_get_value_by_key($instance, 'time', fansub_get_value_by_key($this->args, 'time'));
        $exclude_users = fansub_get_value_by_key($instance, 'exclude_users');
        $exclude_users = fansub_json_string_to_array($exclude_users);
        $show_count = fansub_get_value_by_key($instance, 'show_count', fansub_get_value_by_key($this->args, 'show_count'));
        $link_author_name = fansub_get_value_by_key($instance, 'link_author_name', fansub_get_value_by_key($this->args, 'link_author_name'));
        $none_text = fansub_get_value_by_key($instance, 'none_text', fansub_get_value_by_key($this->args, 'none_text'));

        fansub_widget_before($args, $instance);
        $condition = '';
        if(fansub_array_has_value($exclude_users)) {
            $not_in = array();
            foreach($exclude_users as $data) {
                $uid = fansub_get_value_by_key($data, 'value');
                if(fansub_id_number_valid($uid)) {
                    $not_in[] = $uid;
                }
            }
            if(fansub_array_has_value($not_in)) {
                $condition = 'AND user_id NOT IN (' . implode(', ', $not_in) . ')';
            }
        }
        $commenters = fansub_get_top_commenters($number, $time, $condition);
        ob_start();
        if(!fansub_array_has_value($commenters)) {
            echo wpautop($none_text);
        } else {
            ?>
            <ol class="list-commenters">
                <?php
                foreach($commenters as $commenter) {
                    $url = $commenter->comment_author_url;
                    $author = $commenter->comment_author;
                    $count = absint($commenter->comments_count);
                    $email = $commenter->comment_author_email;
                    $user_id = 0;
                    if(!empty($commenter->user_id)) {
                        $user_id = $commenter->user_id;
                    }
                    if((bool)$show_count) {
                        $author .= " ($count)";
                    }
                    if(empty($url) || 'http://' == $url || !(bool)$link_author_name) {
                        $url = $author;
                    } else {
                        $url = "<a href='$url' rel='external nofollow' class='url'>$author</a>";
                    }
                    ?>
                    <li class="commenter"><?php echo $url; ?></li>
                    <?php
                }
                ?>
            </ol>
            <?php
        }
        $widget_html = ob_get_clean();
        $widget_html = apply_filters('fansub_widget_top_commenter_html', $widget_html, $instance, $args, $this);
        echo $widget_html;
        fansub_widget_after($args, $instance);
    }

    public function form($instance) {
        $title = fansub_get_value_by_key($instance, 'title');
        $number = fansub_get_value_by_key($instance, 'number', fansub_get_value_by_key($this->args, 'number'));
        $time = fansub_get_value_by_key($instance, 'time', fansub_get_value_by_key($this->args, 'time'));
        $exclude_users = fansub_get_value_by_key($instance, 'exclude_users');
        $users = fansub_json_string_to_array($exclude_users);
        $show_count = fansub_get_value_by_key($instance, 'show_count', fansub_get_value_by_key($this->args, 'show_count'));
        $link_author_name = fansub_get_value_by_key($instance, 'link_author_name', fansub_get_value_by_key($this->args, 'link_author_name'));
        $none_text = fansub_get_value_by_key($instance, 'none_text', fansub_get_value_by_key($this->args, 'none_text'));

        fansub_field_widget_before($this->admin_args['class']);
        fansub_widget_field_title($this->get_field_id('title'), $this->get_field_name('title'), $title);

        $args = array(
            'id' => $this->get_field_id('number'),
            'name' => $this->get_field_name('number'),
            'value' => $number,
            'label' => __('Number:', 'fansub')
        );
        fansub_widget_field('fansub_field_input_number', $args);

        $lists = $this->args['times'];
        $all_option = '';
        foreach($lists as $key => $lvalue) {
            $all_option .= fansub_field_get_option(array('value' => $key, 'text' => $lvalue, 'selected' => $time));
        }
        $args = array(
            'id' => $this->get_field_id('time'),
            'name' => $this->get_field_name('time'),
            'all_option' => $all_option,
            'value' => $time,
            'label' => __('Time:', 'fansub'),
            'multiple' => true
        );
        fansub_widget_field('fansub_field_select', $args);

        $lists = get_users();
        $all_option = '';
        foreach($lists as $lvalue) {
            $selected = '';
            foreach($users as $data) {
                $user_name = fansub_get_value_by_key($data, 'value');
                if($lvalue->ID == $user_name) {
                    $selected = $user_name;
                }
            }
            $all_option .= fansub_field_get_option(array('value' => $lvalue->ID, 'text' => $lvalue->display_name, 'selected' => $selected));
        }
        $args = array(
            'id' => $this->get_field_id('exclude_users'),
            'name' => $this->get_field_name('exclude_users'),
            'all_option' => $all_option,
            'value' => $exclude_users,
            'label' => __('Exclude users:', 'fansub'),
            'placeholder' => __('Choose user', 'fansub'),
            'multiple' => true
        );
        fansub_widget_field('fansub_field_select_chosen', $args);

        $args = array(
            'id' => $this->get_field_id('show_count'),
            'name' => $this->get_field_name('show_count'),
            'value' => $show_count,
            'label' => __('Show count', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        $args = array(
            'id' => $this->get_field_id('link_author_name'),
            'name' => $this->get_field_name('link_author_name'),
            'value' => $link_author_name,
            'label' => __('Link author name', 'fansub')
        );
        fansub_widget_field('fansub_field_input_checkbox', $args);

        $args = array(
            'id' => $this->get_field_id('none_text'),
            'name' => $this->get_field_name('none_text'),
            'value' => $none_text,
            'label' => __('No commenter text:', 'fansub')
        );
        fansub_widget_field('fansub_field_input', $args);

        fansub_field_widget_after();
    }

    public function update($new_instance, $old_instance) {
        fansub_delete_transient('fansub_top_commenters');
        $instance = $old_instance;
        $instance['title'] = strip_tags(fansub_get_value_by_key($new_instance, 'title'));
        $instance['number'] = fansub_get_value_by_key($new_instance, 'number', fansub_get_value_by_key($this->args, 'number'));
        $instance['time'] = fansub_get_value_by_key($new_instance, 'time', fansub_get_value_by_key($this->args, 'time'));
        $instance['exclude_users'] = fansub_get_value_by_key($new_instance, 'exclude_users');
        $instance['show_count'] = fansub_checkbox_post_data_value($new_instance, 'show_count');
        $instance['link_author_name'] = fansub_checkbox_post_data_value($new_instance, 'link_author_name');
        $instance['none_text'] = fansub_get_value_by_key($new_instance, 'none_text', fansub_get_value_by_key($this->args, 'none_text'));
        return $instance;
    }
}